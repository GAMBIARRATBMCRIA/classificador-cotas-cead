<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class DirectoryService
{
    /**
     * Deleta o conteúdo de uma pasta (arquivos e subpastas).
     *
     * @param string $dir Caminho da pasta
     * @return bool Retorna true se o conteúdo foi deletado com sucesso, caso contrário false.
     */
    public function deleteDirectoryContents($dir)
    {
        // Verifica se o diretório existe
        if (!File::isDirectory($dir)) {
            return false;
        }

        // Obtém todos os arquivos e subpastas no diretório
        $files = array_diff(scandir($dir), ['.', '..']); // Ignora '.' e '..'

        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            // Se for um arquivo, deleta o arquivo
            if (File::isFile($filePath)) {
                File::delete($filePath);
            }
            // Se for uma pasta, chama recursivamente a função para deletar o conteúdo
            elseif (File::isDirectory($filePath)) {
                $this->deleteDirectoryContents($filePath); // Deleta conteúdo da subpasta
                File::deleteDirectory($filePath); // Deleta a subpasta vazia
            }
        }

        return true;
    }
}
