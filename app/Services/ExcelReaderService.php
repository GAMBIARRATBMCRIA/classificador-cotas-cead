<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExcelReaderService
{
    // Mapeamento de possíveis nomes de colunas para um nome padrão
    private const COLUMN_ALIASES = [
        'inscrição' => 'inscricao',
        'nome' => 'nome',
        'cpf' => 'cpf',
        'data de nascimento' => 'data_nascimento',
        'diploma' => 'diploma',
        'vínculo atual ept' => 'vinculo_ept',
        'vinculo atual ept' => 'vinculo_ept',
        'ficha cota (b)' => 'cota',
        'ficha cota  (b)' => 'cota',
        'ficha cota' => 'cota', // Note os dois espaços extras na sua planilha
        'cota' => 'cota',
        'pontuacao final' => 'pontuacao_final',
        'polo' => 'polo',

        'pontuação ana. curricular' => 'pontuacao_final', // Se esta for a coluna de pontuação final
        'pontuacao ana. curricular' => 'pontuacao_final', // Se esta for a coluna de pontuação final 
        'pontuação final' => 'pontuacao_final', // Mapeamento explícito para o nome da sua coluna
    ];

    /**
     * Lê a planilha e retorna uma lista de polos únicos.
     */
    public function getUniquePolos(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
        $normalizedHeader = $this->normalizeHeader($header);

        $poloColumnIndex = array_search('polo', $normalizedHeader);
        if ($poloColumnIndex === false) {
            throw new \Exception("A coluna 'POLO' não foi encontrada na planilha.");
        }

        $polos = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            $polo = $rowData[$poloColumnIndex] ?? null;
            if ($polo) {
                $polos[$polo] = true;
            } 
        }

        return array_keys($polos);
    }

    /**
     * Lê a planilha e retorna uma coleção de todos os candidatos.
     */
    public function getAllCandidates(string $filePath): Collection
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $header = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];
        $normalizedHeader = $this->normalizeHeader($header);

        $data = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];
            foreach ($cellIterator as $cell) {
                // Se a célula contém uma fórmula, lê o valor calculado
                if ($cell->getDataType() == DataType::TYPE_FORMULA) {
                    $rowData[] = $cell->getCalculatedValue();
                } else {
                    $rowData[] = $cell->getValue();
                }
            }

            // Combina o cabeçalho normalizado com os dados da linha
            $candidateData = array_combine($normalizedHeader, array_slice($rowData, 0, count($normalizedHeader)));
            // Normalizar valor da cota se existir
            if (isset($candidateData['cota'])) {
                $candidateData['cota'] = $this->normalizeCota($candidateData['cota']);
            }
            $data[] = $candidateData;
        }

        return collect($data);
    }

    /**
     * Normaliza o cabeçalho para um formato padrão.
     */
    private function normalizeHeader(array $header): array
    {
        return array_map(function ($col) {
            $normalized = Str::lower(trim($col));
            return self::COLUMN_ALIASES[$normalized] ?? $normalized;
        }, $header);
    }

    /**
     * Normaliza o valor da cota para os formatos esperados: AC, PPI, PCI, PcD
     */
    private function normalizeCota(?string $raw): string
    {
        if (!$raw) return 'NADA'; // padrão

        // Remove prefixos tipo "1-", "3." etc
        $raw = preg_replace('/^[0-9\.\-_\s]+/', '', $raw);

        // Normaliza letras
        $raw = strtoupper(trim($raw));

        // Corrige variações comuns
        return match (true) {
            str_contains($raw, 'PPI') => 'PPI',
            str_contains($raw, 'PCI') => 'PCI',
            str_contains($raw, 'PCD') => 'PcD',
            str_contains($raw, 'AC')  => 'AC',
            default => 'NADA', // fallback seguro
        };
    }
}
