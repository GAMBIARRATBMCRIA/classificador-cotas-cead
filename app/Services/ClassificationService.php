<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClassificationService
{


    public function classify(Collection $allCandidates, array $vagasPorPolo, array $percentages, array $tieBreakersOrder): array
    {
        $finalResults = [];

        foreach ($vagasPorPolo as $polo => $totalVagas) {
            $candidates = $allCandidates->where('polo', $polo)->values();
            if ($candidates->isEmpty()) {
                continue;
            }

            // 1. Adicionar colunas de desempate e ordenar
            $sortedCandidates = $this->sortCandidates($candidates, $tieBreakersOrder);

            // 2. Calcular vagas por cota para este polo
            $vagasCotas = $this->calculateQuotaSeats($totalVagas, $percentages[$polo]);

            // 3. Lógica de classificação e remanejamento
            $classifiedPolo = $this->processClassificationForPolo($sortedCandidates, $vagasCotas, $percentages[$polo]);

            $finalResults[$polo] = $classifiedPolo;
        }

        return $finalResults;
    }


    private function decideAndConvertClassifyItens(Collection $candidates, array $tieBreakersOrder): Collection
    {
        $keys_and_tipes_found = [];

        $chaves_cadastradas = config('cotas.tie_breakers');

        foreach ($chaves_cadastradas as $chave => $chavesCriterios) {
            if (!isset($keys_and_tipes_found[$chave])) {
                $keys_and_tipes_found[$chavesCriterios['key_tie_breakers']]['title'] = $chavesCriterios['tipe_column'];
                $keys_and_tipes_found[$chavesCriterios['key_tie_breakers']]['order'] = $chavesCriterios['order_values'];
                $keys_and_tipes_found[$chavesCriterios['key_tie_breakers']]['order_classify_form'] = $chavesCriterios['order_classify'];
                $keys_and_tipes_found[$chavesCriterios['key_tie_breakers']]['column_table_readed'] = $chavesCriterios['column_dictionary_table'];
            }
        }
        $addedKeyToSort = $candidates->map(function ($c) use ($keys_and_tipes_found) {
            //convert string in integer to reposition

            foreach ($keys_and_tipes_found as $chave => $valor) {

                if ($valor['title'] == 'STRING') {

                    $var_count_level = null;
                    $array_order_keys_and_tipes = $keys_and_tipes_found[$chave]['order'];

                    foreach ($array_order_keys_and_tipes as $orderPosition => $value_poisition) {
                        //dd($array_order_keys_and_tipes);
                        $var_count_level = (int) Str::startsWith(Str::upper($c[$chave] ?? ''), $value_poisition);

                        if ($var_count_level ==  1) {
                            $var_count_level = $var_count_level - $orderPosition;
                            break;
                        } else {
                            $var_count_level = null;
                        }
                    }

                    if ($var_count_level === null) {
                        $var_count_level = 1 - count($array_order_keys_and_tipes);
                    }

                    $c['sort_key_' . $chave] = $var_count_level;
                } else if ($valor['title'] == 'DATE') {
                    $value_poisitionDate = $keys_and_tipes_found[$chave]['column_table_readed'];
                    try {
                        $date = $c[$keys_and_tipes_found[$chave]['column_table_readed']];

                        if (is_numeric($date)) {
                            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d');
                        }
                        $c['sort_key_' . $chave . "_" . $value_poisitionDate] = Carbon::parse($date)->timestamp;
                    } catch (\Exception $e) {
                        $c['sort_key_' . $chave . "_" . $value_poisitionDate] = PHP_INT_MAX; // Usar o maior possível (mais jovem)
                    }
                }
            }

            return $c;
        });

        $addedKeyToSort['internal_keys_registreds'] = $keys_and_tipes_found;
        return $addedKeyToSort;
    }


    // private function sortCandidates(Collection $candidates, array $tieBreakersOrder): Collection
    // {
    //     $candidatesWithSortKeys = $this->decideAndConvertClassifyItens($candidates, $tieBreakersOrder);
    //     // Ordenação múltipla encadeada
    //     $keys_resgistreds_in_bd = $candidatesWithSortKeys['internal_keys_registreds'];
    //     unset($candidatesWithSortKeys['internal_keys_registreds']);

    //     $keys_in_array = [];
    //     foreach (array_keys($candidatesWithSortKeys[0]) as $keysInArray) {
    //         $keys_in_array[] = $keysInArray;
    //     }

    //     $sorted = $candidatesWithSortKeys->sort(function ($a, $b) use ($tieBreakersOrder, $keys_resgistreds_in_bd, $keys_in_array) {
    //         $cmp = 0;
    //         // dd($keys_resgistreds_in_bd);
    //         foreach ($tieBreakersOrder as $criterion) {

    //             foreach ($keys_resgistreds_in_bd as $criterion_registred_key => $value) {
    //                 $key_mountened = 'sort_key_' . $criterion;

    //                 if ($criterion === $criterion_registred_key) {
    //                     //firts test if it contains the key exactly in the array
    //                     $verified = false;
    //                     if (array_key_exists($key_mountened, $a)) {

    //                         if ($keys_resgistreds_in_bd[$criterion]['order_classify_form'] === 'DESC') {
    //                             $cmp = $b[$key_mountened] <=> $a[$key_mountened];
    //                         } else {
    //                             $cmp = $a[$key_mountened] <=> $b[$key_mountened];
    //                         }

    //                         if ($key_mountened === 'sort_key_diploma' and $cmp != 0 and ($a['inscricao'] == 1015 or $b['inscricao'] == 1073)) {
    //                            dd($key_mountened, $a, $b);
    //                         } //aqui

    //                         // echo "<br>1" . $a['nome'] . " com " . $b['nome'] . ' ' . $criterion . " key:" . $key_mountened;
    //                         $verified = true;
    //                         if ($cmp !== 0) return $cmp;
    //                     } else {
    //                         //secondly check if the key start with $key_mountened to indefy if it is date 
    //                         foreach ($keys_in_array as $key_registreds_in_candidate) {
    //                             if (str_starts_with($key_registreds_in_candidate, $key_mountened.'_')) {

    //                                 $column_Table_Date_to_ordenate = str_replace($key_mountened . '_', '', $key_registreds_in_candidate);

    //                                 if ($keys_resgistreds_in_bd[$criterion]['order_classify_form'] === 'DESC') {
    //                                     $cmp = $a[$column_Table_Date_to_ordenate] <=> $b[$column_Table_Date_to_ordenate];
    //                                 } else {
    //                                     $cmp = $b[$column_Table_Date_to_ordenate] <=> $a[$column_Table_Date_to_ordenate];
    //                                 }
    //                                 // echo "<br>2" . $a['nome'] . " com " . $b['nome'] . ' ' . $criterion . " key:" . $key_mountened;
    //                                 $verified = true;
    //                             }
    //                         }

    //                         if ($cmp !== 0) return $cmp;
    //                     }
    //                     if ($verified != true) {
    //                         // dd($keys_resgistreds_in_bd[$criterion]['order_classify_form']);
    //                         if ($keys_resgistreds_in_bd[$criterion]['order_classify_form'] === 'DESC') {
    //                             $cmp = $b[$keys_resgistreds_in_bd[$criterion]['column_table_readed']] <=> $a[$keys_resgistreds_in_bd[$criterion]['column_table_readed']];
    //                         } else {
    //                             $cmp = $a[$keys_resgistreds_in_bd[$criterion]['column_table_readed']] <=> $b[$keys_resgistreds_in_bd[$criterion]['column_table_readed']];
    //                         }

    //                         // echo "<br>3" . $a['nome'] . " com " . $b['nome'] . ' ' . $criterion . " key:" . $criterion;
    //                         if ($cmp !== 0) return $cmp;
    //                     }
    //                 }

    //                 if ($cmp !== 0) return $cmp;
    //             }
    //         }


    //         return 0; // totalmente empatados
    //     });

    //     return $sorted->values();
    // }

    private function sortCandidates(Collection $candidates, array $tieBreakersOrder): Collection
    {
        $candidatesWithSortKeys = $this->decideAndConvertClassifyItens($candidates, $tieBreakersOrder);

        $keysRegistered = $candidatesWithSortKeys['internal_keys_registreds'];
        unset($candidatesWithSortKeys['internal_keys_registreds']);

        $candidateKeys = array_keys($candidatesWithSortKeys[0]);

        $sorted = $candidatesWithSortKeys->sort(function ($a, $b) use ($tieBreakersOrder, $keysRegistered, $candidateKeys) {
            foreach ($tieBreakersOrder as $criterion) {
                if (!isset($keysRegistered[$criterion])) {
                    continue;
                }

                $keySort = 'sort_key_' . $criterion;
                $orderDir = strtoupper($keysRegistered[$criterion]['order_classify_form'] ?? 'ASC');

                // Verifica se o candidato tem a chave direta (string ou data normalizada)
                // Checks if the candidate has the direct key (string or normalized date)
                if (array_key_exists($keySort, $a) && array_key_exists($keySort, $b)) {
                    $cmp = ($orderDir === 'DESC')
                        ? ($b[$keySort] <=> $a[$keySort])
                        : ($a[$keySort] <=> $b[$keySort]);

                    if ($cmp !== 0) return $cmp;

                    continue; // Empate, vai para o próximo critério
                }

                // Verifica chaves derivadas, ex: sort_key_criterio_coluna (datas compostas)
                foreach ($candidateKeys as $keyCandidate) {
                    if (str_starts_with($keyCandidate, $keySort . '_')) {
                        $col = substr($keyCandidate, strlen($keySort) + 1);

                        $valA = $a[$col] ?? null;
                        $valB = $b[$col] ?? null;

                        if ($valA === null || $valB === null) {
                            // Se faltar valor, joga para o final (considera empate aqui)
                            continue;
                        }

                        $cmp = ($orderDir === 'DESC')
                            ? ($valB <=> $valA)
                            : ($valA <=> $valB);

                        if ($cmp !== 0) return $cmp;

                        // Se empatou nesse, testa próximos keys
                    }
                }

                // Por fim, compara direto o valor cru na chave original
                $valA = $a[$keysRegistered[$criterion]['column_table_readed']] ?? null;
                $valB = $b[$keysRegistered[$criterion]['column_table_readed']] ?? null;

                if ($valA === null || $valB === null) {
                    // Empata para valores faltantes
                    continue;
                }

                $cmp = ($orderDir === 'DESC')
                    ? ($valB <=> $valA)
                    : ($valA <=> $valB);

                if ($cmp !== 0) return $cmp;
            }

            return 0; // Empate total em todos os critérios
        });

        return $sorted->values();
    }


    private function calculateQuotaSeats(int $totalVagas, array $percentages): array
    {
        $vagas = [];
        $totalCalculado = 0;
        foreach ($percentages as $cota => $percent) {
            $vagas[$cota] = (int) floor($totalVagas * ($percent / 100));
            $totalCalculado += $vagas[$cota];
        }

        // Distribui as vagas restantes (arredondamento) para a Ampla Concorrência
        if ($totalCalculado < $totalVagas) {
            $vagas['AC'] += $totalVagas - $totalCalculado;
        }
        return $vagas;
    }

    private function processClassificationForPolo(Collection $candidates, array $vagasCotas, array $percentages): array
    {
        $classificados = collect();
        $idsClassificados = [];
        $cotasEspecificas = [];

        $cotasDaConfiguracao = config('cotas');

        // dd($cotasDaConfiguracao);
        foreach ($cotasDaConfiguracao['quotas'] as $chave => $c) {
            if ($chave != 'AC') {
                $cotasEspecificas[] = $chave;
            }
        }

        $candidatosOrdenados = $candidates;

        // 1. Classificar por Ampla Concorrência (AC)
        $acClassificados = $candidates->filter(function ($c) use ($idsClassificados) {
            return !in_array($c['inscricao'], $idsClassificados);
        })->take($vagasCotas['AC']);

        foreach ($acClassificados as $c) {
            $c['resultado'] = $cotasDaConfiguracao["mensagem_titulo_publicacao"]["APROVADO"] . ' - ' . $cotasDaConfiguracao["quotas"]["AC"];
            $classificados->push($c);
            $idsClassificados[] = $c['inscricao'];
        }

        // 2. Ajustar ocupação real por cota (cotistas aprovados em AC não concorrem mais pela cota)
        $inscritosPorCota = [];
        foreach (array_merge($cotasEspecificas, ['AC']) as $cota) {
            $inscritosPorCota[$cota] = $candidates->filter(function ($c) use ($cota, $idsClassificados) {
                //return $c['cota'] === $cota;
                return $c['cota'] === $cota && !in_array($c['inscricao'], $idsClassificados);
            })->count();
        }

        $vagasFinais = $vagasCotas;

        // 3. Identificar déficit de candidatos nas cotas e calcular vagas ociosas
        $deficit = [];
        $sobra = 0;

        $conta_vagas = 0;
        foreach ($cotasEspecificas as $cota) {
            $disponiveis = $candidates->filter(function ($c) use ($cota, $idsClassificados) {
                return $c['cota'] === $cota && !in_array($c['inscricao'], $idsClassificados);
            })->count();

            if ($disponiveis < $vagasFinais[$cota]) {
                $deficit[$cota] = $vagasFinais[$cota] - $disponiveis;
                $sobra += $deficit[$cota];
                $conta_vagas += $deficit[$cota];
                $vagasFinais[$cota] = $disponiveis;
            }
        }

        // dd($vagasCotas);
        $total_vagas = 0;
        foreach ($vagasCotas as $cotas) {
            $total_vagas += $cotas;
        }
        // dd($total_vagas);
        // 3. Aplicar algoritmo de remanejamento proporcional
        $remanejamento = $this->calcularRemanejamentoProporcional($total_vagas, $inscritosPorCota, $percentages, $vagasCotas);
        $vagasFinais = $remanejamento['vagas_finais'];

        // dd($vagasFinais);

        // 4. Classificar cotistas com base nas vagas finais
        foreach (array_merge($cotasEspecificas, ['AC']) as $cota) {

            if ($cota === 'AC') {
                $classificadosCota = $candidatosOrdenados->filter(function ($c) use ($cota, $idsClassificados) {
                    return $c['cota'] === $cota && !in_array($c['inscricao'], $idsClassificados);
                })->take($vagasFinais[$cota] - $vagasCotas[$cota]);

                // dd($vagasFinais[$cota], $classificadosCota);


            } else {
                $classificadosCota = $candidatosOrdenados->filter(function ($c) use ($cota, $idsClassificados) {
                    return $c['cota'] === $cota && !in_array($c['inscricao'], $idsClassificados);
                })->take($vagasFinais[$cota]);
            }
            foreach ($classificadosCota as $c) {
                $c['resultado'] = $cotasDaConfiguracao["mensagem_titulo_publicacao"]["APROVADO"] . ' - ' . $cotasDaConfiguracao["quotas"][$cota];
                $classificados->push($c);
                $idsClassificados[] = $c['inscricao'];
            }
        }

        // dd($classificadosCota);

        // 5. Cadastro de Reserva (quem não foi classificado nem por AC nem por cota)
        $reserva = $candidates->filter(function ($c) use ($idsClassificados) {
            return !in_array($c['inscricao'], $idsClassificados);
        })->map(function ($c) use ($cotasDaConfiguracao, $cotasEspecificas) {

            if (in_array($c['cota'], array_merge($cotasEspecificas, ['AC']))) {
                $c['resultado'] = $cotasDaConfiguracao["mensagem_titulo_publicacao"]["RESERVA"] . ' - ' . $cotasDaConfiguracao["quotas"][$c['cota']];
            } else {
                $c['resultado'] = $cotasDaConfiguracao["mensagem_titulo_publicacao"]["DESCLASSIFICADO"];
            }

            return $c;
        });

        return [
            'classificados' => $classificados->values(),
            'reserva' => $reserva->values(),
        ];
    }
    private function calcularRemanejamentoProporcional(int $totalVagas, array $inscritos, array $proporcao, array $vagasIniciaisCotas): array
    {
        $cotas = array_keys($proporcao);

        foreach ($cotas as $cota) {
            $proporcao[$cota] = $proporcao[$cota] / 100;
            if ($cota === 'AC') { //corrigir a contagem de incritos de AC
                $inscritos[$cota] += $vagasIniciaisCotas[$cota];
            }
        }

        // dd($inscritos);

        $vagasIniciais = $vagasIniciaisCotas;

        // // Corrigir discrepância de arredondamento

        // 2. Calcular sobras
        $sobras = [];
        $ocupadas = [];
        $totalSobra = 0;

        foreach ($cotas as $cota) {
            $inscritosCota = $inscritos[$cota] ?? 0;
            $vagas = $vagasIniciais[$cota];
            $ocupadas[$cota] = min($vagas, $inscritosCota);
            $sobras[$cota] = $vagas - $ocupadas[$cota];
            $totalSobra += $sobras[$cota];
        }

        // 3. Quem pode receber mais vagas
        $podeReceber = [];
        foreach ($cotas as $cota) {
            $podeReceber[$cota] = ($inscritos[$cota] ?? 0) > $ocupadas[$cota];
        }

        // dd($podeReceber);

        // 4. Redistribuição proporcional
        $totalProporcaoRecebedores = array_sum(array_map(function ($cota) use ($podeReceber, $proporcao) {
            return $podeReceber[$cota] ? $proporcao[$cota] : 0;
        }, $cotas));

        $remanejadas = array_fill_keys($cotas, 0);
        if ($totalSobra > 0 && $totalProporcaoRecebedores > 0) {
            foreach ($cotas as $cota) {
                if ($podeReceber[$cota]) {
                    $parte = $proporcao[$cota] / $totalProporcaoRecebedores;
                    $remanejadas[$cota] = floor($parte * $totalSobra);
                }
            }

            // Distribuir sobra de inteiros
            $usadas = array_sum($remanejadas);
            $restantes = $totalSobra - $usadas;
            $ordenadas = collect($cotas)->filter(fn($c) => $podeReceber[$c])
                ->sortByDesc(fn($c) => $proporcao[$c])
                ->values();

            for ($i = 0; $i < $restantes; $i++) {
                $cota = $ordenadas[$i % $ordenadas->count()];
                $remanejadas[$cota]++;
            }
        }


        // dd($remanejadas);

        // 5. Vagas finais
        $vagasFinais = [];
        foreach ($cotas as $cota) {
            $vagasFinais[$cota] = $ocupadas[$cota] + $remanejadas[$cota];
        }

        return [
            'distribuicao_inicial' => $vagasIniciais,
            'sobras' => $sobras,
            'vagas_ocupadas' => $ocupadas,
            'vagas_remanejadas' => $remanejadas,
            'vagas_finais' => $vagasFinais,
        ];
    }
}
