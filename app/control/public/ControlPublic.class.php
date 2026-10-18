<?php

class ControlPublic extends TPage
{
    public function __construct()
    {
        parent::__construct();

        // Container principal da página pública
        $container = new TElement('div');
        $container->class = 'container-fluid py-4';
        $container->style = 'max-width: 1400px; margin: 0 auto;';

        // Título dinâmico (compatível com modo claro e escuro)
        $header = new TElement('div');
        $header->class = 'd-flex justify-content-between align-items-center mb-4 pb-2 border-bottom';
        
        $title = new TElement('h2');
        $title->class = 'font-weight-bold page-title-custom m-0';
        $title->add('ControlPublic');
        $header->add($title);

        $container->add($header);

        // =====================================================================
        // MOCK / DADOS INICIAIS
        // =====================================================================
        $data = [
            'total_colaboradores'  => 0,
            'clientes_15_dias'     => 0,
            'clientes_30_dias'     => 0,
            'clientes_40_dias'     => 0,
            'clientes_60_dias'     => 0,
            'propostas_mes'        => 0,
            'pedidos_mes'          => 0,
            'conversas_ativas'     => 0,
            'conversas_espera'     => 0
        ];

        // SEÇÃO 1: EQUIPE E ATENDIMENTO / CONVERSAS
        $row1 = new TElement('div');
        $row1->class = 'row mb-4';

        $row1->add($this->createStatCard(
            'col-12 col-sm-6 col-lg-4',
            'Total de Colaboradores',
            $data['total_colaboradores'],
            'fas fa-user-tie',
            '#2563eb',
            'Equipe cadastrada'
        ));

        $row1->add($this->createStatCard(
            'col-12 col-sm-6 col-lg-4',
            'Conversas Ativas',
            $data['conversas_ativas'],
            'fas fa-comments',
            '#059669',
            'Atendimentos em andamento'
        ));

        $row1->add($this->createStatCard(
            'col-12 col-sm-6 col-lg-4',
            'Conversas em Espera',
            $data['conversas_espera'],
            'fas fa-user-clock',
            '#d97706',
            'Aguardando atendimento'
        ));

        $container->add($row1);

        // SEÇÃO 2: COMERCIAL (PROPOSTAS E PEDIDOS DO MÊS)
        $row2 = new TElement('div');
        $row2->class = 'row mb-4';

        $row2->add($this->createStatCard(
            'col-12 col-md-6',
            'Propostas no Mês',
            $data['propostas_mes'],
            'fas fa-file-invoice-dollar',
            '#4f46e5',
            'Total emitido no mês corrente'
        ));

        $row2->add($this->createStatCard(
            'col-12 col-md-6',
            'Pedidos no Mês',
            $data['pedidos_mes'],
            'fas fa-shopping-cart',
            '#0284c7',
            'Total fechado no mês corrente'
        ));

        $container->add($row2);

        // SEÇÃO 3: CLIENTES ATIVOS POR PERÍODO
        $row3 = new TElement('div');
        $row3->class = 'row mb-4';

        $row3->add($this->createStatCard(
            'col-12 col-sm-6 col-xl-3',
            'Clientes Ativos (15d)',
            $data['clientes_15_dias'],
            'fas fa-user-check',
            '#10b981',
            'Últimos 15 dias'
        ));

        $row3->add($this->createStatCard(
            'col-12 col-sm-6 col-xl-3',
            'Clientes Ativos (30d)',
            $data['clientes_30_dias'],
            'fas fa-user-check',
            '#06b6d4',
            'Últimos 30 dias'
        ));

        $row3->add($this->createStatCard(
            'col-12 col-sm-6 col-xl-3',
            'Clientes Ativos (40d)',
            $data['clientes_40_dias'],
            'fas fa-user-check',
            '#6366f1',
            'Últimos 40 dias'
        ));

        $row3->add($this->createStatCard(
            'col-12 col-sm-6 col-xl-3',
            'Clientes Ativos (60d)',
            $data['clientes_60_dias'],
            'fas fa-user-check',
            '#8b5cf6',
            'Últimos 60 dias'
        ));

        $container->add($row3);

        parent::add($container);
    }

    private function createStatCard($colClass, $title, $value, $iconClass, $accentColor, $footerText = '')
    {
        $col = new TElement('div');
        $col->class = $colClass . ' mb-3';

        $card = new TElement('div');
        $card->class = 'card h-100 shadow-sm border-0 stat-card-custom';
        $card->style = "border-top: 4px solid {$accentColor} !important; border-radius: 8px;";

        $cardBody = new TElement('div');
        $cardBody->class = 'card-body d-flex align-items-center justify-content-between p-3';

        $info = new TElement('div');
        
        $titleEl = new TElement('span');
        $titleEl->class = 'text-muted text-uppercase font-weight-bold';
        $titleEl->style = 'font-size: 0.75rem; letter-spacing: 0.5px; display: block; margin-bottom: 4px;';
        $titleEl->add($title);

        $valueEl = new TElement('h3');
        $valueEl->class = 'font-weight-bold mb-0 stat-card-value';
        $valueEl->add($value);

        if (!empty($footerText)) {
            $footerEl = new TElement('small');
            $footerEl->class = 'text-muted d-block mt-1';
            $footerEl->style = 'font-size: 0.75rem;';
            $footerEl->add($footerText);
            $info->add($footerEl);
        }

        $iconWrapper = new TElement('div');
        $iconWrapper->style = "width: 48px; height: 48px; border-radius: 50%; background: {$accentColor}18; display: flex; align-items: center; justify-content: center;";
        
        $icon = new TElement('i');
        $icon->class = $iconClass;
        $icon->style = "font-size: 1.35rem; color: {$accentColor};";
        $iconWrapper->add($icon);

        $cardBody->add($info);
        $cardBody->add($iconWrapper);
        $card->add($cardBody);
        $col->add($card);

        return $col;
    }
}