<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ExcelReaderService;
use App\Services\ClassificationService;
use App\Services\ExcelWriterService;
use App\Rules\ValidRules;
use App\Rules\ValidRulesUploadXLSX;
use App\Services\DirectoryService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ClassificationController extends Controller
{
    public function showUploadForm(DirectoryService $directory_service)
    {
        $directory_service->deleteDirectoryContents(storage_path('app\\temp\\'));
        $directory_service->deleteDirectoryContents(storage_path('app\\tmp\\'));

        // Carrega os critérios de desempate do arquivo de configuração
        // Load the criteria of tierbreakers of the file configuration

        $tieBreakers_Resgistered = config('cotas.tie_breakers');
        $tieBreakers = [];

        foreach ($tieBreakers_Resgistered as $ordem => $attributes) {
            $tieBreakers[$attributes['key_tie_breakers']] = $attributes['title_tie_breakers'];
        }

        return view('classification.upload', compact('tieBreakers'));
    }

    public function handleUpload(Request $request, ExcelReaderService $reader)
    { //, ValidRulesUploadXLSX $validRulesUploadXLSX
        // $validRulesUploadXLSX->uploadCandidatesValidate($request);

        $request->validate([
            'candidates_file' => ['required', 'file', 'mimes:xlsx'],
            'tie_breakers_order' => ['required', 'array'],
            'tie_breakers_order.*' => ['required', 'string', new ValidRules],
        ]);



        $file = $request->file('candidates_file');

        // Gera um nome único e move para storage/app/temp
        $storedPath = $file->storeAs('temp', uniqid('candidates_') . '.xlsx');

        // Extrai os polos únicos
        $polos = $reader->getUniquePolos(storage_path('app/' . $storedPath));

        // Armazena na sessão
        $request->session()->put('candidates_file_path', $storedPath);
        $request->session()->put('tie_breakers_order', $request->input('tie_breakers_order'));

        return view('classification.configure', [
            'polos' => $polos,
            'storedPath' => $storedPath,
            'originalFileName' => $file->getClientOriginalName(),
        ]);
    }

    public function processClassification(Request $request, ExcelReaderService $reader, ClassificationService $classifier)
    {
        //    dd($request['percentages']);

        // $request->validate([
        //     'vagas' => ['required', 'array'],
        //     'vagas.*' => ['required', 'integer', 'min:0'],
        //     'percentages' => ['required', 'array'],
        //     // 'percentages.*' => ['required', 'numeric', 'min:0'],
        // ]);

        // Recupera os dados da sessão
        $filePath = storage_path('app\\' . $request->session()->get('candidates_file_path'));
        $tieBreakersOrder = $request->session()->get('tie_breakers_order');

        // dd($request);

        if (!$filePath) {
            return redirect()->route('classification.showUploadForm')->withErrors('Sessão expirada. Por favor, envie o arquivo novamente.');
        }

        // Lê todos os candidatos do arquivo
        $allCandidates = $reader->getAllCandidates($filePath);

        // A lógica de classificação é delegada ao Service
        $results = $classifier->classify(
            $allCandidates, //coleta todos os candidatos no formato 

            $request->input('vagas'), //coleta o valor total de vagas por polo no formato

            $request->input('percentages'), //coleta as porcentagens das cota do polo no formato

            $tieBreakersOrder
        );

        $tieBreakers_Resgistered = config('cotas.tie_breakers');
        $tieBreakers = [];

        foreach ($tieBreakersOrder as $chave => $attributesOrder) {
            foreach ($tieBreakers_Resgistered as $attributes) {
                if ($attributes['key_tie_breakers'] === $attributesOrder) {
                    $tieBreakers[$attributes['key_tie_breakers']] = $attributes['title_tie_breakers'];
                }
            }
            // $tieBreakers[$attributes['key_tie_breakers']] = $attributes['title_tie_breakers'];
        }

        //dd($tieBreakers);

        $results_por_cota = [];
        $mensagem_tela = config('cotas.mensagem_titulo_publicacao');

        foreach ($results as $poloChave => $valor) {
            foreach ($valor as $chaveClassificados => $valor_classi) {
                foreach ($valor_classi as $have => $valor_classi) {
                    $resultado = (string) $valor_classi['resultado'];

                    if (!isset($results_por_cota[$poloChave][$chaveClassificados][$resultado])) {
                        $results_por_cota[$poloChave][$chaveClassificados][$resultado] = [];
                    }
                    if (str_starts_with($resultado, $mensagem_tela['APROVADO'])) {
                        $valor_classi['resultado'] = $mensagem_tela['APROVADO'];
                    }else{
                          $valor_classi['resultado'] = $mensagem_tela['RESERVA'];
                    }

                    $results_por_cota[$poloChave][$chaveClassificados][$resultado][] = $valor_classi;
                }
            }
        }
        // dd($results_por_cota);

        $uuid = Str::uuid();
        Storage::put("tmp/{$uuid}.json", json_encode([
            // 'results' => $results,
            'results_por_cota' => $results_por_cota
        ]));

        $results = null; //cleanning var
        // dd($results_por_cota);
        return view('classification.results', compact('results_por_cota', 'uuid', 'tieBreakers'));
    }

    public function downloadResults(Request $request, ExcelWriterService $writer)
    {
        // $results = $request->session()->get('classification_results');

        $uuid = $request->input('uuid');
        $json = Storage::get("tmp/{$uuid}.json");
        $data = json_decode($json, true);

        // $results = $data['results'];
        $resultsPorCota = $data['results_por_cota'];

        // O ExcelWriterService gera o arquivo na memória
        $fileStream = $writer->generate($resultsPorCota);

        return response()->stream(
            function () use ($fileStream) {
                $fileStream->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="resultado_classificacao.xlsx"',
            ]
        );
    }
}
