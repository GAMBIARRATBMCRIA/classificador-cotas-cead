<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelWriterService
{
    public function generate( array $results_por_cota): Xlsx
    {


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resultado da Classificação');

        // Cabeçalho
        $headers = [
            'A' => 'POLO',
            'B' => 'INSCRIÇÃO',
            'C' => 'NOME',
            'D' => 'CPF',
            'E' => 'DATA DE NASCIMENTO',
            'F' => 'DIPLOMA',
            'G' => 'VÍNCULO ATUAL EPT',
            'H' => 'COTA',
            'I' => 'PONTUAÇÃO FINAL',
            'J' => 'RESULTADO'
        ];

        // Estilo do cabeçalho
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];


        $row = 0;

        foreach ($results_por_cota as $polo => $chaveClassificacao) {

            foreach ($chaveClassificacao as $classificacaoCotas) {

                foreach ($classificacaoCotas as $chave => $chaveTituloCandidatos) {
                    $row++;
                    $sheet->setCellValue("A{$row}", $chave);
                    $row++;
                    foreach ($headers as $cell => $value) {
                        $sheet->setCellValue(str($cell . $row), $value); // renderizando cabeçalho a cada cota
                        $sheet->getStyle(str($cell . $row))->applyFromArray($headerStyle);
                    }
                    $row++;
                    foreach ($chaveTituloCandidatos as $candidatos) {
                        $this->writeCandidate($sheet, $row, $polo, $candidatos);
                        $row++;
                    }
                }
            }
        }


        // Auto-ajustar largura das colunas
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Aplicar bordas a toda a tabela
        $tableRange = 'A1:J' . ($row - 1);
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        return new Xlsx($spreadsheet);
    }

    private function writeCandidate($sheet, int $row, string $polo, array $candidate): void
    {
        $sheet->setCellValue("A{$row}", $polo);
        $sheet->setCellValue("B{$row}", $candidate['inscricao'] ?? '');
        $sheet->setCellValue("C{$row}", $candidate['nome'] ?? '');
        $sheet->setCellValue("D{$row}", $candidate['cpf'] ?? '');
        $sheet->setCellValue("E{$row}", $candidate['data_nascimento'] ?? '');
        $sheet->setCellValue("F{$row}", $candidate['diploma'] ?? '');
        $sheet->setCellValue("G{$row}", $candidate['vinculo_ept'] ?? '');
        $sheet->setCellValue("H{$row}", $candidate['cota'] ?? '');
        $sheet->setCellValue("I{$row}", $candidate['pontuacao_final'] ?? '');
        $sheet->setCellValue("J{$row}", $candidate['resultado'] ?? '');

        $cotas = config('cotas.mensagem_titulo_publicacao');
        

        // Colorir linha baseado no resultado
        if (str_starts_with($candidate['resultado'] ?? '', $cotas['APROVADO'])) {
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
            ]);
        } else {
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
            ]);
        }
    }
}
