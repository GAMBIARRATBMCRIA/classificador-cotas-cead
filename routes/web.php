<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rota principal que exibe o formulário de upload
Route::get(
    '/',
    [ClassificationController::class, 'showUploadForm']
)->name('classification.showUploadForm');


// Rota que recebe o arquivo e redireciona para a configuração de vagas
Route::post(
    '/uploads',
    [ClassificationController::class, 'handleUpload']
)->name('classification.handleUpload');


Route::post('/process', [ClassificationController::class, 'processClassification'])->name('classification.process');

// Rota para baixar o arquivo Excel com os resultados
// Usaremos um POST para poder passar os dados já processados e armazenados na sessão
Route::post(
    '/download',
    [ClassificationController::class, 'downloadResults']
)->name('classification.downloadResults');


