@extends('layouts.app')

@section('title', 'Upload de Planilha - Sistema de Classificação')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="bi bi-upload"></i>
                    Upload da Planilha de Candidatos
                </h4>
            </div>
            <div class="card-body">
                <form action="{{ route('classification.handleUpload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="candidates_file" class="form-label">
                            <strong>Arquivo de Candidatos (.xlsx)</strong>
                        </label>
                        <input type="file" class="form-control" id="candidates_file" name="candidates_file" 
                               accept=".xlsx" required>
                        <div class="form-text">
                            Selecione um arquivo Excel (.xlsx) contendo os dados dos candidatos.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <strong>Critérios de Desempate (em ordem de prioridade)</strong>
                        </label>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Arraste os critérios para definir a ordem de aplicação em caso de empate na pontuação.
                        </div>
                        
                        <div id="tie-breakers-list" class="list-group">
                            @foreach($tieBreakers as $key => $label)
                                <div class="list-group-item d-flex justify-content-between align-items-center" 
                                     data-key="{{ $key }}">
                                    <div>
                                        <i class="bi bi-grip-vertical text-muted me-2"></i>
                                        {{ $label }}
                                    </div>
                                    <span class="badge bg-primary rounded-pill order-number">{{ $loop->iteration }}</span>
                                    <input type="hidden" name="tie_breakers_order[]" value="{{ $key }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-right"></i>
                            Prosseguir para Configuração de Vagas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const list = document.getElementById('tie-breakers-list');
        
        new Sortable(list, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                // Atualizar os números de ordem e os valores dos inputs hidden
                const items = list.querySelectorAll('.list-group-item');
                items.forEach((item, index) => {
                    const orderBadge = item.querySelector('.order-number');
                    orderBadge.textContent = index + 1;
                    item.querySelector('input[type=hidden]').name = `tie_breakers_order[${index}]`;
                });
            }
        });
    });
</script>
@endpush

