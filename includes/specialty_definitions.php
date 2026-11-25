<?php

function clinic_specialty_form_definitions_catalog() {
    static $definitions = null;
    if ($definitions !== null) {
        return $definitions;
    }

    $yesNo = ['nao' => 'Não', 'sim' => 'Sim'];

    $definitions = [
        'psiquiatria' => [
            'title' => 'Prontuário Psiquiátrico Estruturado',
            'description' => 'História psiquiátrica, exame do estado mental, risco e plano terapêutico.',
            'sections' => [
                [
                    'title' => '1. Queixa atual e história psiquiátrica',
                    'fields' => [
                        ['name' => 'psiq_tipo_atendimento', 'label' => 'Tipo de atendimento', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['primeira_consulta' => 'Primeira consulta', 'retorno' => 'Retorno', 'interconsulta' => 'Interconsulta']],
                        ['name' => 'psiq_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full', 'min_length' => 10],
                        ['name' => 'psiq_inicio_sintomas', 'label' => 'Início dos sintomas', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'psiq_fatores_desencadeantes', 'label' => 'Fatores desencadeantes', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_historico_psiquiatrico', 'label' => 'Histórico psiquiátrico prévio', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'psiq_internacoes_previas', 'label' => 'Internações psiquiátricas prévias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_historico_familiar', 'label' => 'Histórico familiar relevante', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_uso_substancias', 'label' => 'Uso de substâncias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_comorbidades', 'label' => 'Comorbidades clínicas', 'type' => 'textarea', 'rows' => 2, 'width' => 'full'],
                    ],
                ],
                [
                    'title' => '2. Estado mental e avaliação de risco',
                    'fields' => [
                        ['name' => 'psiq_humor_afeto', 'label' => 'Humor / afeto', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_pensamento', 'label' => 'Pensamento / curso / conteúdo', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_percepcao', 'label' => 'Sensopercepção', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_cognicao', 'label' => 'Cognição / orientação', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_juizo_critica', 'label' => 'Juízo crítico / insight', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_risco_suicidio', 'label' => 'Risco de suicídio', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['ausente' => 'Ausente', 'baixo' => 'Baixo', 'moderado' => 'Moderado', 'alto' => 'Alto']],
                        ['name' => 'psiq_plano_seguranca', 'label' => 'Plano de segurança / suporte', 'type' => 'textarea', 'rows' => 2, 'width' => 'full', 'required_if' => ['field' => 'psiq_risco_suicidio', 'values' => ['moderado', 'alto']], 'min_length' => 10],
                        ['name' => 'psiq_risco_heteroagressao', 'label' => 'Risco de heteroagressão', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Diagnóstico e conduta',
                    'fields' => [
                        ['name' => 'psiq_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 5],
                        ['name' => 'psiq_cid10', 'label' => 'CID-10 / CID-11', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'psiq_medicacoes', 'label' => 'Medicações prescritas / ajustadas', 'type' => 'textarea', 'rows' => 3, 'width' => 'half'],
                        ['name' => 'psiq_orientacoes_efeitos', 'label' => 'Orientações / efeitos colaterais', 'type' => 'textarea', 'rows' => 3, 'width' => 'half'],
                        ['name' => 'psiq_psicoterapia_indicada', 'label' => 'Psicoterapia indicada', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'psiq_encaminhamentos', 'label' => 'Encaminhamentos', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'psiq_conduta_urgente', 'label' => 'Conduta urgente / internação', 'type' => 'textarea', 'rows' => 2, 'width' => 'full', 'required_if' => ['field' => 'psiq_risco_suicidio', 'values' => ['alto']]],
                        ['name' => 'psiq_plano_retorno', 'label' => 'Plano de seguimento / retorno', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full', 'min_length' => 10],
                    ],
                ],
            ],
        ],
        'pediatria' => [
            'title' => 'Prontuário Pediátrico Estruturado',
            'description' => 'Puericultura, intercorrências agudas, crescimento, vacinação e orientações ao responsável.',
            'sections' => [
                [
                    'title' => '1. Responsável e quadro atual',
                    'fields' => [
                        ['name' => 'ped_responsavel_nome', 'label' => 'Nome do responsável', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'ped_responsavel_parentesco', 'label' => 'Parentesco', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'ped_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full', 'min_length' => 10],
                        ['name' => 'ped_inicio_quadro', 'label' => 'Início do quadro', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'ped_febre', 'label' => 'Febre', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'ped_tempo_febre', 'label' => 'Tempo de febre', 'type' => 'text', 'width' => 'half', 'required_if' => ['field' => 'ped_febre', 'values' => ['sim']]],
                        ['name' => 'ped_sintomas_associados', 'label' => 'Sintomas associados', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_contato_doentes', 'label' => 'Contato com doentes', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '2. Crescimento, desenvolvimento e prevenção',
                    'fields' => [
                        ['name' => 'ped_peso_atual', 'label' => 'Peso atual (kg)', 'type' => 'number', 'required' => true, 'min' => 0.1, 'step' => '0.01', 'width' => 'half'],
                        ['name' => 'ped_altura_atual', 'label' => 'Altura atual (cm)', 'type' => 'number', 'required' => true, 'min' => 1, 'step' => '0.1', 'width' => 'half'],
                        ['name' => 'ped_perimetro_cefalico', 'label' => 'Perímetro cefálico (cm)', 'type' => 'number', 'min' => 1, 'step' => '0.1', 'width' => 'half'],
                        ['name' => 'ped_curva_crescimento', 'label' => 'Curva de crescimento', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['adequado' => 'Adequado', 'atencao' => 'Atenção', 'alterado' => 'Alterado']],
                        ['name' => 'ped_desenvolvimento_neuropsicomotor', 'label' => 'Desenvolvimento neuropsicomotor', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_aleitamento', 'label' => 'Aleitamento / alimentação', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_vacinacao_em_dia', 'label' => 'Vacinação em dia', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'incompleta' => 'Incompleta']],
                        ['name' => 'ped_vacinas_pendentes', 'label' => 'Vacinas pendentes', 'type' => 'textarea', 'rows' => 2, 'width' => 'half', 'required_if' => ['field' => 'ped_vacinacao_em_dia', 'values' => ['nao', 'incompleta']]],
                        ['name' => 'ped_alergias', 'label' => 'Alergias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_medicacoes_uso', 'label' => 'Medicações em uso', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Exame e conduta',
                    'fields' => [
                        ['name' => 'ped_temperatura', 'label' => 'Temperatura (°C)', 'type' => 'number', 'step' => '0.1', 'width' => 'half'],
                        ['name' => 'ped_fc', 'label' => 'FC', 'type' => 'number', 'step' => '1', 'width' => 'half'],
                        ['name' => 'ped_fr', 'label' => 'FR', 'type' => 'number', 'step' => '1', 'width' => 'half'],
                        ['name' => 'ped_saturacao', 'label' => 'SatO2 (%)', 'type' => 'number', 'step' => '1', 'width' => 'half'],
                        ['name' => 'ped_exame_fisico', 'label' => 'Exame físico', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'ped_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'ped_sinais_alarme', 'label' => 'Sinais de alarme orientados', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'ped_retorno', 'label' => 'Retorno', 'type' => 'date', 'width' => 'half'],
                    ],
                ],
            ],
        ],
        'ginecologia_obstetricia' => [
            'title' => 'Prontuário Ginecológico / Obstétrico',
            'description' => 'Avaliação ginecológica, obstétrica e pré-natal com rastreios e sinais de risco.',
            'sections' => [
                [
                    'title' => '1. História gineco-obstétrica',
                    'fields' => [
                        ['name' => 'go_tipo_atendimento', 'label' => 'Tipo de atendimento', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['ginecologia' => 'Ginecologia', 'obstetricia' => 'Obstetrícia', 'pre_natal' => 'Pré-natal', 'puerperio' => 'Puerpério']],
                        ['name' => 'go_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'go_dum', 'label' => 'DUM', 'type' => 'date', 'required' => true, 'width' => 'half'],
                        ['name' => 'go_gpa', 'label' => 'G / P / A', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'go_idade_gestacional', 'label' => 'Idade gestacional', 'type' => 'text', 'width' => 'half', 'required_if' => ['field' => 'go_tipo_atendimento', 'values' => ['obstetricia', 'pre_natal', 'puerperio']]],
                        ['name' => 'go_dpp', 'label' => 'DPP', 'type' => 'date', 'width' => 'half'],
                        ['name' => 'go_ciclo_menstrual', 'label' => 'Ciclo menstrual', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_contracepcao', 'label' => 'Método contraceptivo', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_sangramento', 'label' => 'Sangramento / corrimento / dor pélvica', 'type' => 'textarea', 'rows' => 2, 'width' => 'full'],
                    ],
                ],
                [
                    'title' => '2. Antecedentes, rastreios e pré-natal',
                    'fields' => [
                        ['name' => 'go_patologias_previas', 'label' => 'Patologias prévias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_cirurgias_previas', 'label' => 'Cirurgias prévias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_ist_risco', 'label' => 'Risco / histórico de IST', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_ultimo_papanicolau', 'label' => 'Último preventivo / Papanicolau', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_ultima_mamografia', 'label' => 'Última mamografia', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_pre_natal_risco', 'label' => 'Gestação de risco', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'go_movimentos_fetais', 'label' => 'Movimentos fetais', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_sinais_alarme', 'label' => 'Sinais de alarme', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Exame, hipótese e conduta',
                    'fields' => [
                        ['name' => 'go_pa', 'label' => 'PA', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_peso', 'label' => 'Peso', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_bcf', 'label' => 'BCF / dinâmica fetal', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_altura_uterina', 'label' => 'Altura uterina', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'go_exame_fisico', 'label' => 'Exame físico / ginecológico', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'go_exames_solicitados', 'label' => 'Exames solicitados / avaliados', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'go_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'go_orientacoes', 'label' => 'Orientações / retorno', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                    ],
                ],
            ],
        ],
        'ortopedia_traumatologia' => [
            'title' => 'Prontuário Ortopédico / Traumatológico',
            'description' => 'Dor, mecanismo de trauma, exame musculoesquelético, imagem e plano terapêutico.',
            'sections' => [
                [
                    'title' => '1. Dor, trauma e funcionalidade',
                    'fields' => [
                        ['name' => 'orto_regiao', 'label' => 'Região anatômica principal', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'orto_lateralidade', 'label' => 'Lateralidade', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['direita' => 'Direita', 'esquerda' => 'Esquerda', 'bilateral' => 'Bilateral', 'nao_aplica' => 'Não se aplica']],
                        ['name' => 'orto_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'orto_mecanismo_trauma', 'label' => 'Mecanismo de trauma', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_tempo_sintomas', 'label' => 'Tempo de sintomas', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'orto_escala_dor', 'label' => 'Escala de dor (0-10)', 'type' => 'number', 'required' => true, 'min' => 0, 'max' => 10, 'step' => '1', 'width' => 'half'],
                        ['name' => 'orto_limitacao_funcional', 'label' => 'Limitação funcional', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_trauma_recente', 'label' => 'Trauma recente', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'orto_neurovascular', 'label' => 'Estado neurovascular distal', 'type' => 'textarea', 'rows' => 2, 'width' => 'half', 'required_if' => ['field' => 'orto_trauma_recente', 'values' => ['sim']]],
                    ],
                ],
                [
                    'title' => '2. Exame físico e exames complementares',
                    'fields' => [
                        ['name' => 'orto_inspecao', 'label' => 'Inspeção', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_amplitude_movimento', 'label' => 'Amplitude de movimento', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_forca', 'label' => 'Força muscular', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_testes_especificos', 'label' => 'Testes específicos', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_rx', 'label' => 'Raio-X', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_rm', 'label' => 'Ressonância / tomografia', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_usg', 'label' => 'Ultrassom / outros exames', 'type' => 'textarea', 'rows' => 2, 'width' => 'full'],
                    ],
                ],
                [
                    'title' => '3. Diagnóstico e conduta',
                    'fields' => [
                        ['name' => 'orto_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_classificacao_fratura', 'label' => 'Classificação / tipo de lesão', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'orto_imobilizacao', 'label' => 'Imobilização / órtese', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_medicacoes', 'label' => 'Medicações', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_fisioterapia', 'label' => 'Fisioterapia / reabilitação', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'orto_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'orto_orientacoes', 'label' => 'Orientações e retorno', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full', 'min_length' => 10],
                    ],
                ],
            ],
        ],
        'cardiologia' => [
            'title' => 'Prontuário Cardiológico Estruturado',
            'description' => 'Sintomas cardiovasculares, fatores de risco, exame, estratificação e metas terapêuticas.',
            'sections' => [
                [
                    'title' => '1. Queixa cardiovascular e fatores de risco',
                    'fields' => [
                        ['name' => 'cardio_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'cardio_dor_toracica', 'label' => 'Dor torácica', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'cardio_dispneia', 'label' => 'Dispneia', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['nao' => 'Não', 'aos_grandes_esforcos' => 'Grandes esforços', 'aos_medios_esforcos' => 'Médios esforços', 'aos_pequenos_esforcos' => 'Pequenos esforços', 'repouso' => 'Repouso']],
                        ['name' => 'cardio_palpitacoes', 'label' => 'Palpitações', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'cardio_sincope', 'label' => 'Síncope / pré-síncope', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'cardio_hipertensao', 'label' => 'Hipertensão arterial', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'cardio_diabetes', 'label' => 'Diabetes', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'cardio_tabagismo', 'label' => 'Tabagismo', 'type' => 'select', 'width' => 'half', 'options' => ['nao' => 'Não', 'atual' => 'Atual', 'cessado' => 'Cessado']],
                        ['name' => 'cardio_hist_familiar', 'label' => 'História familiar cardiovascular', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '2. Exame, sinais vitais e exames',
                    'fields' => [
                        ['name' => 'cardio_pa', 'label' => 'PA', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'cardio_fc', 'label' => 'FC', 'type' => 'number', 'step' => '1', 'required' => true, 'width' => 'half'],
                        ['name' => 'cardio_imc', 'label' => 'IMC', 'type' => 'number', 'step' => '0.1', 'width' => 'half'],
                        ['name' => 'cardio_exame_fisico', 'label' => 'Exame físico', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'cardio_ecg', 'label' => 'ECG', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'cardio_eco', 'label' => 'Ecocardiograma', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'cardio_teste_ergometrico', 'label' => 'Teste ergométrico / MAPA / Holter', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'cardio_laboratoriais', 'label' => 'Exames laboratoriais', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Diagnóstico, risco e metas',
                    'fields' => [
                        ['name' => 'cardio_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'cardio_risco_cv', 'label' => 'Estratificação de risco cardiovascular', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['baixo' => 'Baixo', 'intermediario' => 'Intermediário', 'alto' => 'Alto', 'muito_alto' => 'Muito alto']],
                        ['name' => 'cardio_medicacoes', 'label' => 'Medicações / ajuste terapêutico', 'type' => 'textarea', 'rows' => 3, 'width' => 'half'],
                        ['name' => 'cardio_metas', 'label' => 'Metas terapêuticas', 'type' => 'textarea', 'rows' => 3, 'width' => 'half'],
                        ['name' => 'cardio_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half'],
                        ['name' => 'cardio_orientacoes', 'label' => 'Orientações e retorno', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half'],
                    ],
                ],
            ],
        ],
        'oftalmologia' => [
            'title' => 'Prontuário Oftalmológico Estruturado',
            'description' => 'Acuidade visual, refração, biomicroscopia, fundo de olho e conduta.',
            'sections' => [
                [
                    'title' => '1. Queixa visual e antecedentes',
                    'fields' => [
                        ['name' => 'oftalmo_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'oftalmo_lateralidade', 'label' => 'Olho acometido', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['od' => 'OD', 'oe' => 'OE', 'ou' => 'OU']],
                        ['name' => 'oftalmo_inicio', 'label' => 'Início dos sintomas', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'oftalmo_dor_ocular', 'label' => 'Dor ocular', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'oftalmo_trauma', 'label' => 'Trauma ocular', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'oftalmo_cirurgias_previas', 'label' => 'Cirurgias oculares prévias', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_comorbidades', 'label' => 'Comorbidades relevantes', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '2. Exame oftalmológico',
                    'fields' => [
                        ['name' => 'oftalmo_av_sc_od', 'label' => 'AV sem correção OD', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'oftalmo_av_sc_oe', 'label' => 'AV sem correção OE', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'oftalmo_av_cc_od', 'label' => 'AV com correção OD', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'oftalmo_av_cc_oe', 'label' => 'AV com correção OE', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'oftalmo_tonometria', 'label' => 'Tonometria', 'type' => 'text', 'width' => 'half'],
                        ['name' => 'oftalmo_biomicroscopia', 'label' => 'Biomicroscopia', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_fundo_olho', 'label' => 'Fundo de olho', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_refracao', 'label' => 'Refração', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Hipótese e conduta',
                    'fields' => [
                        ['name' => 'oftalmo_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_exames_complementares', 'label' => 'Exames complementares', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_prescricao', 'label' => 'Prescrição / lente / colírio', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'oftalmo_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half'],
                        ['name' => 'oftalmo_orientacoes', 'label' => 'Orientações e retorno', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                    ],
                ],
            ],
        ],
        'neurologia' => [
            'title' => 'Prontuário Neurológico Estruturado',
            'description' => 'Queixa neurológica, exame focal, crises, imagem e plano diagnóstico-terapêutico.',
            'sections' => [
                [
                    'title' => '1. História neurológica atual',
                    'fields' => [
                        ['name' => 'neuro_queixa_principal', 'label' => 'Queixa principal', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'full'],
                        ['name' => 'neuro_inicio_quadro', 'label' => 'Início do quadro', 'type' => 'text', 'required' => true, 'width' => 'half'],
                        ['name' => 'neuro_instalacao', 'label' => 'Instalação', 'type' => 'select', 'required' => true, 'width' => 'half', 'options' => ['subita' => 'Súbita', 'subaguda' => 'Subaguda', 'cronica' => 'Crônica']],
                        ['name' => 'neuro_cefaleia', 'label' => 'Cefaleia', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'neuro_tontura', 'label' => 'Tontura / vertigem', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'neuro_fraqueza', 'label' => 'Fraqueza', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'neuro_parestesia', 'label' => 'Parestesias', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'neuro_crises_convulsivas', 'label' => 'Crises convulsivas', 'type' => 'select', 'width' => 'half', 'options' => $yesNo],
                        ['name' => 'neuro_descricao_crise', 'label' => 'Descrição das crises', 'type' => 'textarea', 'rows' => 2, 'width' => 'half', 'required_if' => ['field' => 'neuro_crises_convulsivas', 'values' => ['sim']], 'min_length' => 10],
                        ['name' => 'neuro_frequencia_crises', 'label' => 'Frequência das crises', 'type' => 'text', 'width' => 'half', 'required_if' => ['field' => 'neuro_crises_convulsivas', 'values' => ['sim']]],
                        ['name' => 'neuro_deficit_focal', 'label' => 'Déficit focal', 'type' => 'textarea', 'rows' => 2, 'width' => 'half', 'required_if' => ['field' => 'neuro_fraqueza', 'values' => ['sim']]],
                        ['name' => 'neuro_ultimo_evento', 'label' => 'Último evento', 'type' => 'text', 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '2. Antecedentes e exame neurológico',
                    'fields' => [
                        ['name' => 'neuro_antecedentes', 'label' => 'Antecedentes neurológicos / clínicos', 'type' => 'textarea', 'rows' => 2, 'width' => 'full'],
                        ['name' => 'neuro_estado_mental', 'label' => 'Estado mental / linguagem', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_pares_cranianos', 'label' => 'Pares cranianos', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_forca_tono', 'label' => 'Força / tônus', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_reflexos', 'label' => 'Reflexos', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_sensibilidade', 'label' => 'Sensibilidade', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_coordenacao', 'label' => 'Coordenação', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_marcha', 'label' => 'Marcha', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_sinais_meningeos', 'label' => 'Sinais meníngeos', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                    ],
                ],
                [
                    'title' => '3. Exames, hipótese e conduta',
                    'fields' => [
                        ['name' => 'neuro_tc', 'label' => 'Tomografia', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_rm', 'label' => 'Ressonância', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_eeg', 'label' => 'EEG', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_enmg', 'label' => 'ENMG', 'type' => 'textarea', 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_liquor', 'label' => 'Líquor / outros exames', 'type' => 'textarea', 'rows' => 2, 'width' => 'full'],
                        ['name' => 'neuro_hipotese_diag', 'label' => 'Hipótese diagnóstica', 'type' => 'textarea', 'required' => true, 'rows' => 2, 'width' => 'half'],
                        ['name' => 'neuro_conduta', 'label' => 'Conduta', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'neuro_orientacoes', 'label' => 'Orientações', 'type' => 'textarea', 'required' => true, 'rows' => 3, 'width' => 'half', 'min_length' => 10],
                        ['name' => 'neuro_retorno', 'label' => 'Retorno', 'type' => 'date', 'width' => 'half'],
                    ],
                ],
            ],
        ],
    ];

    return $definitions;
}
