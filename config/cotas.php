<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Cotas
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações relacionadas ao sistema de cotas
    | e critérios de desempate para a classificação de candidatos.
    |
    */

    'tie_breakers' => [
        '1' => [
            'key_tie_breakers' => 'pontuacao_final',
            'column_dictionary_table' => 'pontuacao_final',
            'title_tie_breakers' => 'Pontuação Final',
            'order_classify' => 'DESC', //Valor ASC em ordem crecente, DESC em ordem decrecente
            'tipe_column' => 'INT', // Specifies the type column
            'order_values' => []
        ],
        '2' => [
            'key_tie_breakers' => 'diploma',
            'column_dictionary_table' => 'diploma',// Name entered in the xlsx reading servicde dictionay
            'title_tie_breakers' => 'Diploma (Bacharelado/Tecnólogo)',
            'order_classify' => 'DESC', //Valor ASC em ordem crecente, DESC em ordem decrecente
            'tipe_column' => 'STRING', // Specifies the type column
            'order_values' => [ // esses são os valores possiveis para a coluna que tem em string para ordenar
                ['BACH', 'TEC'], // Aqui são os valores possíveis para posicionar primeiro 
                ['LICENCIATURA'], // Aqui são para os da segunda
                ['OUTROS'] // Aqui são os da terceira e em diante
            ]
        ],
        '3' => [
            'key_tie_breakers' => 'vinculo_ept',
            'column_dictionary_table' => 'vinculo_ept',
            'title_tie_breakers' => 'Vínculo atual com EPT',
            'order_classify' => 'DESC', //Valor ASC em ordem crecente, DESC em ordem decrecente
            'tipe_column' => 'STRING', // Specifies the type column
            'order_values' => [ // esses são os valores possiveis para a coluna que tem em string para ordenar
                ['SIM'], // Aqui são os valores possíveis para posicionar primeiro 
                ['OUTROS'] // Aqui são os da terceira e em diante
            ]
        ],
        '4' => [
            'key_tie_breakers' => 'maior_idade',
            'column_dictionary_table' => 'data_nascimento',
            'title_tie_breakers' => 'Data de Nascimento',
            'order_classify' => 'ASC', //Valor ASC em ordem crecente, DESC em ordem decrecente
            'tipe_column' => 'DATE', // Specifies the type column
            'order_values' => [] 
        ],
        '5' => [
            'key_tie_breakers' => 'maior_inscricao',
            'column_dictionary_table' => 'inscricao',
            'title_tie_breakers' => 'Inscrição mais Recente',
            'order_classify' => 'DESC', //Valor ASC em ordem crecente, DESC em ordem decrecente
            'tipe_column' => 'INT', // Specifies the type column
            'order_values' => []
        ]
        

        // 'maior_inscricao' => 'Inscrição mais Recente'

    ],


    'quotas' => [
        'AC' => 'Ampla Concorrência',
        'PPI' => 'Pretos, Pardos e Indígenas',
        'PCI' => 'Fucionário da Instituição',
        'PcD' => 'Pessoa com Deficiência',
        // 'NADA' => 'Inscrição Desclassificada'
    ],

    'mensagem_titulo_publicacao' => [
        'APROVADO' => 'CLASSIFICADO/APROVADO', //nao mudar a chave, apenas valores
        'RESERVA' => 'CADASTRO RESERVA',
        'DESCLASSIFICADO' => 'DESCLASSIFICADO'
    ],

];
