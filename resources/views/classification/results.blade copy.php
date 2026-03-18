@extends('layouts.app')

@section('title', 'Resultados da Classificação - Sistema de Classificação')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-trophy"></i>
                    Resultados da Classificação
                </h4>
                <form action="{{ route('classification.downloadResults') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="uuid" value="{{ $uuid }}">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-download"></i>
                        Baixar Planilha
                    </button>
                </form>
            </div>
            <div class="card-body">
                @if(empty($results))
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    Nenhum resultado encontrado.
                </div>
                @else
                {{-- Navegação das abas --}}
                <ul class="nav nav-tabs mb-4" id="poloTabs" role="tablist">
                    @foreach($results_por_cota as $polo => $valor_polo)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if ($loop->first) active @endif"
                            id="tab-{{ Str::slug($polo) }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-{{ Str::slug($polo) }}"
                            type="button"
                            role="tab"
                            aria-controls="tab-{{ Str::slug($polo) }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $polo }}
                        </button>
                    </li>
                    @endforeach
                </ul>

                {{-- Conteúdo das abas --}}
                <div class="tab-content" id="poloTabsContent">
                    @foreach($results_por_cota as $polo => $valor_polo)
                    <div class="tab-pane fade @if ($loop->first) show active @endif"
                        id="tab-{{ Str::slug($polo) }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ Str::slug($polo) }}-tab">

                        <h5 class="border-bottom pb-2 mb-3">
                            <i class="bi bi-geo-alt"></i>
                            {{ $polo }}
                        </h5>

                        @foreach($valor_polo as $tipo_classificacao => $objetos_de_cada_classificacao)

                            @if(!empty($valor_polo[$tipo_classificacao]))

                                <h6 class="text-success mb-3">
                                    <i class="bi bi-check-circle"></i>
                                    @php
                                    $contador = 0;
                                    foreach($valor_polo[$tipo_classificacao] as $titulo => $valor_titulo_candiates) {
                                    $contador += count($valor_titulo_candiates);
                                    }
                                    @endphp
                                    Classificados ({{ $contador }})
                                </h6>

                                <div class="table-responsive mb-4">
                                    @foreach($valor_polo[$tipo_classificacao] as $titulo => $valor_titulo_candiates)
                                    <h6 class="text-success mb-2">
                                        <i class="bi bi-check-circle"></i> {{ $titulo }}
                                    </h6>
                                    <table class="table table-striped table-hover">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Posição</th>
                                                <th>Inscrição</th>
                                                <th>Nome</th>
                                                <th>CPF</th>
                                                <th>Data Nascimento</th>
                                                <th>Diploma</th>
                                                <th>Vínculo EPT</th>
                                                <th>Pontuação</th>
                                                <th>Cota</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($valor_titulo_candiates as $candidate)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $candidate['inscricao'] ?? '-' }}</td>
                                                <td>{{ $candidate['nome'] ?? '-' }}</td>
                                                <td>{{ $candidate['cpf'] ?? '-' }}</td>
                                                <td>{{ $candidate['data_nascimento'] ?? '-' }}</td>
                                                <td>{{ $candidate['diploma'] ?? '-' }}</td>
                                                <td>{{ $candidate['vinculo_ept'] ?? '-' }}</td>
                                                <td>{{ $candidate['pontuacao_final'] ?? '-' }}</td>
                                                <td><span class="badge bg-info">{{ $candidate['cota'] ?? '-' }}</span></td>
                                                <td><span class="badge bg-success">{{ $candidate['resultado'] ?? '-' }}</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <br>
                                    @endforeach
                                </div>
                            @endif
                            {{--------------------------------------------------------------------------}}

                            @if(!empty($valor_polo[$tipo_classificacao]) and $tipo_classificacao === 'reserva')
                                <h6 class="text-warning mb-3">
                                    <i class="bi bi-clock"></i>
                                    @php
                                    $contador = 0;
                                    foreach($valor_polo[$tipo_classificacao] as $titulo => $valor_titulo_candiates) {
                                    $contador += count($valor_titulo_candiates);
                                    }
                                    @endphp
                                    Candidatos Reservas ({{ $contador }})
                                </h6>
                                <div class="table-responsive">
                                    @foreach($valor_polo[$tipo_classificacao] as $titulo => $valor_titulo_candiates)
                                    <h6 class="text-warning mb-3">
                                        <i class="bi bi-clock"></i> {{ $titulo }}
                                    </h6>
                                    <table class="table table-striped table-hover">
                                        <thead class="table-warning">
                                            <tr>
                                                <th>Posição</th>
                                                <th>Inscrição</th>
                                                <th>Nome</th>
                                                <th>CPF</th>
                                                <th>Data Nascimento</th>
                                                <th>Diploma</th>
                                                <th>Vínculo EPT</th>
                                                <th>Pontuação</th>
                                                <th>Cota</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($valor_titulo_candiates as $candidate)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $candidate['inscricao'] ?? '-' }}</td>
                                                <td>{{ $candidate['nome'] ?? '-' }}</td>
                                                <td>{{ $candidate['cpf'] ?? '-' }}</td>
                                                <td>{{ $candidate['data_nascimento'] ?? '-' }}</td>
                                                <td>{{ $candidate['diploma'] ?? '-' }}</td>
                                                <td>{{ $candidate['vinculo_ept'] ?? '-' }}</td>
                                                <td>{{ $candidate['pontuacao_final'] ?? '-' }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ $candidate['cota'] ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">{{ $candidate['resultado'] ?? '-' }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <br>
                                    @endforeach
                                </div>
                            @endif

                        {{--------------------------------------------------------------------------}}

                        @endforeach
                    </div>
                    @endforeach
                </div>

                @endif

                <div class="mt-4 text-center">
                    <a href="{{ route('classification.showUploadForm') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i>
                        Nova Classificação
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection