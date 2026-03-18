@extends('layouts.app')

@section('title', 'Configuração de Vagas - Sistema de Classificação')

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
                <form action="{{ route('classification.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Configure o número de vagas e os percentuais de cada cota para cada polo identificado na planilha.
                    </div>

                    @foreach($polos as $polo)
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">{{ $polo }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="vagas_{{ $loop->index }}" class="form-label">
                                        <strong>Total de Vagas</strong>
                                    </label>
                                    <input type="number" class="form-control"
                                        id="vagas_{{ $loop->index }}"
                                        name="vagas[{{ $polo }}]"
                                        min="1" required>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label">
                                        <strong>Percentuais por Cota (%)</strong>
                                    </label>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="ac_{{ $loop->index }}" class="form-label">Ampla Concorrência</label>
                                            <input type="number" class="form-control percentage-input"
                                                id="ac_{{ $loop->index }}"
                                                name="percentages[{{ $polo }}][AC]"
                                                min="0" max="100" step="0.1" value="60" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="ppi_{{ $loop->index }}" class="form-label">PPI</label>
                                            <input type="number" class="form-control percentage-input"
                                                id="ppi_{{ $loop->index }}"
                                                name="percentages[{{ $polo }}][PPI]"
                                                min="0" max="100" step="0.1" value="20" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="pci_{{ $loop->index }}" class="form-label">PCI</label>
                                            <input type="number" class="form-control percentage-input"
                                                id="pci_{{ $loop->index }}"
                                                name="percentages[{{ $polo }}][PCI]"
                                                min="0" max="100" step="0.1" value="10" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="pcd_{{ $loop->index }}" class="form-label">PcD</label>
                                            <input type="number" class="form-control percentage-input"
                                                id="pcd_{{ $loop->index }}"
                                                name="percentages[{{ $polo }}][PcD]"
                                                min="0" max="100" step="0.1" value="10" required>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Total: <span class="total-percentage" data-polo="{{ $loop->index }}">100%</span>
                                        </small>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-play-circle"></i>
                            Processar Classificação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para calcular e atualizar o total de percentuais
        function updatePercentageTotal(poloIndex) {
            const inputs = document.querySelectorAll(`input[name*="percentages"][name*="[${poloIndex}]"]`);
            let total = 0;

            inputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const totalSpan = document.querySelector(`[data-polo="${poloIndex}"]`);
            totalSpan.textContent = total.toFixed(1) + '%';

            // Destacar se o total não for 100%
            if (Math.abs(total - 100) > 0.1) {
                totalSpan.classList.add('text-warning');
                totalSpan.classList.remove('text-muted');
            } else {
                totalSpan.classList.remove('text-warning');
                totalSpan.classList.add('text-muted');
            }
        }

        // Adicionar event listeners para todos os inputs de percentual
        document.querySelectorAll('.percentage-input').forEach(input => {
            input.addEventListener('input', function() {
                const poloIndex = this.name.match(/\[(\d+)\]/)?.[1] ||
                    this.name.match(/\[(.*?)\]/)?.[1];
                if (poloIndex !== null) {
                    updatePercentageTotal(poloIndex);
                }
            });
        });
        // @foreach($polos as $polo)
        // updatePercentageTotal({
        //     {
        //         $loop - > index
        //     }
        // });
        // @endforeach
    });
</script>
@endpush