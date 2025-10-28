<?php
// config/courses.php - Definição dos cursos
$courses = [
    'module_1' => [
        'title' => 'Fundamentos das Finanças Pessoais',
        'icon' => 'piggy-bank-fill',
        'color' => 'success',
        'quiz_nivel' => 1,
        'lessons' => [
            ['id' => 'm1_l1', 'title' => 'Curso de finanças pessoais: controle financeiro', 'youtube' => 'https://www.youtube.com/watch?v=HjVaW41ucMc', 'desc' => 'Aprenda como ter controle financeiro e investir o seu dinheiro.'],
            ['id' => 'm1_l2', 'title' => 'Os tipos de receitas de um profissional', 'youtube' => 'https://www.youtube.com/watch?v=5qjfdz02lXY', 'desc' => 'Entenda os diferentes tipos de receitas e como gerenciá-las.'],
            ['id' => 'm1_l3', 'title' => 'Os principais tipos de despesas', 'youtube' => 'https://www.youtube.com/watch?v=KV37n4l8nGY', 'desc' => 'Conheça as categorias de despesas e como controlá-las.'],
            ['id' => 'm1_l4', 'title' => 'Controle financeiro para freelancer', 'youtube' => 'https://www.youtube.com/watch?v=q0XI-lfoWTs', 'desc' => 'Técnicas específicas para quem trabalha como freelancer.'],
            ['id' => 'm1_l5', 'title' => 'Reserva de emergência: o que é e como fazer?', 'youtube' => 'https://www.youtube.com/watch?v=FIbNFAw1C_w', 'desc' => 'A importância e estratégias para construir sua reserva.'],
            ['id' => 'm1_l6', 'title' => 'Instabilidade financeira: como lidar?', 'youtube' => 'https://www.youtube.com/watch?v=FLTiYZdMuTI', 'desc' => 'Aprenda a navegar em momentos de incerteza financeira.'],
        ]
    ],
    'module_2' => [
        'title' => 'Planejamento e Orçamento',
        'icon' => 'clipboard-check',
        'color' => 'info',
        'quiz_nivel' => 2,
        'lessons' => [
            ['id' => 'm2_l1', 'title' => 'Riscos da falta de planejamento', 'youtube' => 'https://www.youtube.com/watch?v=I4Z2-xatfFE', 'desc' => 'Entenda os perigos de não planejar suas finanças.'],
            ['id' => 'm2_l2', 'title' => 'Planilha para orçamento doméstico', 'youtube' => 'https://www.youtube.com/watch?v=d40sN3vGa2o', 'desc' => 'Como criar e usar planilhas para controle financeiro.'],
            ['id' => 'm2_l3', 'title' => 'Planilha para controle financeiro', 'youtube' => 'https://www.youtube.com/watch?v=jFmQ_bRugKM', 'desc' => 'Ferramentas práticas para monitorar suas finanças.'],
            ['id' => 'm2_l4', 'title' => 'Os perigos das dívidas', 'youtube' => 'https://www.youtube.com/watch?v=G5rnCijgyiw', 'desc' => 'Como as dívidas afetam sua saúde financeira.'],
            ['id' => 'm2_l5', 'title' => 'Dívida no cartão de crédito', 'youtube' => 'https://www.youtube.com/watch?v=domq4G0nHSY', 'desc' => 'Pagamento mínimo ou parcelamento? Entenda a melhor opção.'],
            ['id' => 'm2_l6', 'title' => 'Como pagar dívidas e limpar o nome', 'youtube' => 'https://www.youtube.com/watch?v=kz1gt10qyw0', 'desc' => 'Estratégias para sair do vermelho.'],
        ]
    ],
    'module_3' => [
        'title' => 'Introdução aos Investimentos',
        'icon' => 'graph-up-arrow',
        'color' => 'warning',
        'quiz_nivel' => 3,
        'lessons' => [
            ['id' => 'm3_l1', 'title' => 'Dicas para não se endividar', 'youtube' => 'https://www.youtube.com/watch?v=hmnUF2qq60E', 'desc' => 'Prevenção é o melhor remédio financeiro.'],
            ['id' => 'm3_l2', 'title' => 'Por que investir meu dinheiro?', 'youtube' => 'https://www.youtube.com/watch?v=rt-RRKkUpxU', 'desc' => 'A importância de fazer seu dinheiro trabalhar para você.'],
            ['id' => 'm3_l3', 'title' => 'Banco ou corretora: onde investir?', 'youtube' => 'https://www.youtube.com/watch?v=bOQuEsx_YRk', 'desc' => 'Compare as opções e escolha a melhor para você.'],
            ['id' => 'm3_l4', 'title' => 'Taxa Selic, inflação e juros reais', 'youtube' => 'https://www.youtube.com/watch?v=dhd0ErwqUk4', 'desc' => 'Entenda os indicadores econômicos essenciais.'],
            ['id' => 'm3_l5', 'title' => 'Perfil de investidor', 'youtube' => 'https://www.youtube.com/watch?v=OZd3I_QEYmI', 'desc' => 'Descubra se você é conservador, moderado ou agressivo.'],
            ['id' => 'm3_l6', 'title' => 'O que é renda fixa?', 'youtube' => 'https://www.youtube.com/watch?v=E6BqS9HQzNQ', 'desc' => 'Aprenda a investir com segurança.'],
        ]
    ],
    'module_4' => [
        'title' => 'Renda Fixa e Títulos',
        'icon' => 'bank',
        'color' => 'primary',
        'quiz_nivel' => 3,
        'lessons' => [
            ['id' => 'm4_l1', 'title' => 'A poupança ainda vale a pena?', 'youtube' => 'https://www.youtube.com/watch?v=mnv9HZdden4', 'desc' => 'Análise crítica do investimento mais tradicional.'],
            ['id' => 'm4_l2', 'title' => 'Títulos públicos: Tesouro Direto', 'youtube' => 'https://www.youtube.com/watch?v=Wd3yWtH82ic', 'desc' => 'Como investir em títulos do governo.'],
            ['id' => 'm4_l3', 'title' => 'Títulos bancários: CDB, LCA e LCI', 'youtube' => 'https://www.youtube.com/watch?v=1_EY1wW1cbc', 'desc' => 'Entenda as opções de renda fixa dos bancos.'],
            ['id' => 'm4_l4', 'title' => 'Títulos privados: debêntures, CRA e CRI', 'youtube' => 'https://www.youtube.com/watch?v=VQNubjKl9yc', 'desc' => 'Investimentos em títulos de empresas privadas.'],
        ]
    ],
    'module_5' => [
        'title' => 'Fundos de Investimento',
        'icon' => 'briefcase-fill',
        'color' => 'danger',
        'quiz_nivel' => 4,
        'lessons' => [
            ['id' => 'm5_l1', 'title' => 'O que são fundos de investimentos?', 'youtube' => 'https://www.youtube.com/watch?v=R2G33ELy8HM', 'desc' => 'Introdução aos fundos e como funcionam.'],
            ['id' => 'm5_l2', 'title' => 'Tipos e estratégias dos fundos', 'youtube' => 'https://www.youtube.com/watch?v=Dm_FYK6eaOg', 'desc' => 'Conheça as diferentes categorias de fundos.'],
            ['id' => 'm5_l3', 'title' => 'Como analisar indicadores de fundos', 'youtube' => 'https://www.youtube.com/watch?v=TBbnttrlzZE', 'desc' => 'Aprenda a comparar e escolher os melhores fundos.'],
            ['id' => 'm5_l4', 'title' => 'Fundos imobiliários (FIIs)', 'youtube' => 'https://www.youtube.com/watch?v=ugL_p4xym2c', 'desc' => 'Como investir no mercado imobiliário via fundos.'],
            ['id' => 'm5_l5', 'title' => 'Fundos de tijolo', 'youtube' => 'https://www.youtube.com/watch?v=6e_tx4h12mg', 'desc' => 'FIIs que pagam dividendos mensais.'],
            ['id' => 'm5_l6', 'title' => 'Fundos de papel', 'youtube' => 'https://www.youtube.com/watch?v=WXjomuCDs9o', 'desc' => 'Investindo em FIIs de CRI e LCI.'],
        ]
    ],
    'module_6' => [
        'title' => 'Bolsa de Valores e Ações',
        'icon' => 'bar-chart-fill',
        'color' => 'success',
        'quiz_nivel' => 5,
        'lessons' => [
            ['id' => 'm6_l1', 'title' => 'Tipos de fundos imobiliários', 'youtube' => 'https://www.youtube.com/watch?v=i9XT6yg6eoU', 'desc' => 'Desenvolvimento e FOFs explicados.'],
            ['id' => 'm6_l2', 'title' => 'Como analisar fundos imobiliários', 'youtube' => 'https://www.youtube.com/watch?v=_03lcnQiHlU', 'desc' => 'Principais indicadores de FIIs.'],
            ['id' => 'm6_l3', 'title' => 'Como funciona a Bolsa de Valores', 'youtube' => 'https://www.youtube.com/watch?v=ov0n9hs7SeA', 'desc' => 'Entenda o mercado de ações.'],
            ['id' => 'm6_l4', 'title' => 'Principais setores da Bolsa', 'youtube' => 'https://www.youtube.com/watch?v=B7CN2N1bd9k', 'desc' => 'Conheça os setores econômicos negociados.'],
            ['id' => 'm6_l5', 'title' => 'Análise fundamentalista vs técnica', 'youtube' => 'https://www.youtube.com/watch?v=3LRQzOMa46s', 'desc' => 'Duas formas de analisar ações.'],
            ['id' => 'm6_l6', 'title' => 'Value Investing', 'youtube' => 'https://www.youtube.com/watch?v=Oh6aHryWAmE', 'desc' => 'Estratégia de investimento em valor.'],
        ]
    ],
    'module_7' => [
        'title' => 'Investimentos Avançados',
        'icon' => 'lightning-charge-fill',
        'color' => 'dark',
        'quiz_nivel' => 6,
        'lessons' => [
            ['id' => 'm7_l1', 'title' => 'Como analisar ações na bolsa', 'youtube' => 'https://www.youtube.com/watch?v=1o-LxtTgihE', 'desc' => 'Técnicas de análise de ações.'],
            ['id' => 'm7_l2', 'title' => 'COE: Certificado de Operações Estruturadas', 'youtube' => 'https://www.youtube.com/watch?v=lDshAnz7TxY', 'desc' => 'Entenda se COE vale a pena.'],
            ['id' => 'm7_l3', 'title' => 'Fundos Alternativos', 'youtube' => 'https://www.youtube.com/watch?v=xwRIbognuIw', 'desc' => 'Private Equity, Venture Capital e FIDC.'],
            ['id' => 'm7_l4', 'title' => 'Mercado de derivativos', 'youtube' => 'https://www.youtube.com/watch?v=BibJpkLZA94', 'desc' => 'Ações, índices, commodities e juros.'],
            ['id' => 'm7_l5', 'title' => 'Mercado Forex', 'youtube' => 'https://www.youtube.com/watch?v=z-ebbTacucA', 'desc' => 'Como negociar moedas internacionais.'],
            ['id' => 'm7_l6', 'title' => 'Criptomoedas e Bitcoin', 'youtube' => 'https://www.youtube.com/watch?v=j1nakeUCwIA', 'desc' => 'Vale a pena investir em criptomoedas?'],
        ]
    ],
    'module_8' => [
        'title' => 'Gestão de Carteira',
        'icon' => 'wallet2',
        'color' => 'info',
        'quiz_nivel' => 6,
        'lessons' => [
            ['id' => 'm8_l1', 'title' => 'Investimentos Esportivos', 'youtube' => 'https://www.youtube.com/watch?v=L46S2_WwCxw', 'desc' => 'É possível ganhar dinheiro? Análise completa.'],
            ['id' => 'm8_l2', 'title' => 'Como montar uma carteira', 'youtube' => 'https://www.youtube.com/watch?v=veoELni5hsw', 'desc' => 'Passo a passo para criar sua carteira de investimentos.'],
            ['id' => 'm8_l3', 'title' => 'A importância dos aportes mensais', 'youtube' => 'https://www.youtube.com/watch?v=esN7m_i17IM', 'desc' => 'Como os aportes regulares potencializam seus investimentos.'],
            ['id' => 'm8_l4', 'title' => 'Como revisar uma carteira', 'youtube' => 'https://www.youtube.com/watch?v=soQanKWv4Rk', 'desc' => 'Aprenda a fazer o rebalanceamento da sua carteira.'],
        ]
    ]
];
?>