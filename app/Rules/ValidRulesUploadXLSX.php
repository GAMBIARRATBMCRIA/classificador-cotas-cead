<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;


class ValidRulesUploadXLSX
{
    public function uploadCandidatesValidate(Request $request)
    {
        // Validação básica do arquivo (se é .xlsx mesmo)
        $request->validate([
            'candidates_file' => ['required', 'file', 'mimes:xlsx']
        ]);

        // Lê o arquivo Excel
        $spreadsheet = IOFactory::load($request->file('candidates_file')->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // A=coluna 1, B=coluna 2...

        // Esperado: Nome, CPF, Nota
        $expectedHeaders = ['Nome1', 'CPF', 'Nota'];
        $headerRow = array_values($rows[1]); // Primeira linha da planilha

        
        // Valida se as colunas estão corretas

        if ($headerRow !== $expectedHeaders) {
            // $fail('As colunas da planilha não estão no formato esperado: ' . implode(', ', $expectedHeaders));
            return back()->withErrors([
                'candidates_file' => 'As colunas da planilha não estão no formato esperado: ' . implode(', ', $expectedHeaders)
            ]);
        }

        // Remove cabeçalho para validar dados
        unset($rows[1]);

        // Transforma para um array "limpo"
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'nome' => $row['A'],
                'cpf' => $row['B'],
                'nota' => $row['C'],
            ];
        }

        // Validação detalhada
        $validator = Validator::make(
            ['candidatos' => $data],
            [
                'candidatos' => ['required', 'array', 'min:1'],
                'candidatos.*.nome' => ['required', 'string', 'min:3'],
                'candidatos.*.cpf' => ['required', 'regex:/^\d{11}$/'], //CPF com 
                'candidatos.*.nota' => ['required', 'numeric', 'between:0,100'],
            ]
        );

        if ($validator->fails()) {
            // $fail("Erro ao validar o arquivo!");
            $errors = new MessageBag([
                'nome' => ['O nome é obrigatório.'],
                'idade' => ['A idade mínima é 18 anos.'],
            ]);

            throw ValidationException::withMessages($errors->toArray());
            // return back()->withErrors($validator)->withInput();
        }

        // Se chegou aqui, está validado e pronto para mandar para o Service
        // Exemplo:



        // return app(CandidateService::class)->process($data);
    }
}
