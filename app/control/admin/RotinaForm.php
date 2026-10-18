<?php

class RotinaForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_rotina');
        $this->form->setFormTitle('<i class="fas fa-cogs"></i> Cadastro de Rotina do Sistema');

        // 1. Campos
        $id           = new THidden('id');
        $nome         = new TEntry('nome');
        $controller   = new TEntry('controller');
        $modulo       = new TEntry('modulo');
        $icone        = new TEntry('icone');
        $ordem        = new TEntry('ordem');
        $visivel_menu = new TCombo('visivel_menu');

        $nome->setSize('100%');
        $controller->setSize('100%');
        $modulo->setSize('100%');
        $icone->setSize('100%');
        $ordem->setSize('100%');
        $visivel_menu->setSize('100%');

        $ordem->setNumericMask(0, '', '', false);
        $ordem->setValue(10);

        $icone->placeholder      = 'fas:folder ou fas:building';
        $controller->placeholder = 'Ex: EmpresaList';
        $modulo->placeholder     = 'Ex: Cadastros, Segurança, Relatórios';

        $visivel_menu->addItems([
            '1' => 'Sim (Visível no Menu Lateral)',
            '0' => 'Não (Rotina Interna / Subformulário)'
        ]);
        $visivel_menu->setDefaultOption(false);
        $visivel_menu->setValue('1');

        // Validações
        $nome->addValidation('Nome da Rotina', new TRequiredValidator);
        $controller->addValidation('Controller (Classe)', new TRequiredValidator);
        $modulo->addValidation('Módulo', new TRequiredValidator);
        $ordem->addValidation('Ordem de Exibição', new TRequiredValidator);
        $visivel_menu->addValidation('Visível no Menu', new TRequiredValidator);

        // 2. Montagem dos Campos
        $this->form->addFields([$id]);

        $this->form->addFields(
            [new TLabel('Nome da Rotina <span class="text-danger">*</span>')], [$nome],
            [new TLabel('Controller / Classe <span class="text-danger">*</span>')], [$controller]
        );

        $this->form->addFields(
            [new TLabel('Módulo Agrupador <span class="text-danger">*</span>')], [$modulo],
            [new TLabel('Ícone FontAwesome')], [$icone]
        );

        $this->form->addFields(
            [new TLabel('Ordem no Menu <span class="text-danger">*</span>')], [$ordem],
            [new TLabel('Exibir no Menu Lateral? <span class="text-danger">*</span>')], [$visivel_menu]
        );

        // 3. Ações
        $btnSave = $this->form->addAction('Salvar Rotina', new TAction([$this, 'onSave']), 'fas:save');
        $btnSave->class = 'btn btn-primary';

        $btnBack = $this->form->addAction('Voltar', new TAction(['RotinaList', 'onReload']), 'fas:arrow-left');
        $btnBack->class = 'btn btn-default';

        parent::add($this->form);
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                $id = $param['id'];

                TTransaction::open('libsql_admin');

                $rotina = new AdminRotina($id);

                if (empty($rotina->id) || !empty($rotina->delete_date)) {
                    throw new Exception('Rotina não localizada ou inativa.');
                }

                $data = new stdClass;
                $data->id           = $rotina->id;
                $data->nome         = $rotina->nome;
                $data->controller   = $rotina->controller;
                $data->modulo       = $rotina->modulo;
                $data->icone        = $rotina->icone;
                $data->ordem        = $rotina->ordem;
                $data->visivel_menu = (string) $rotina->visivel_menu;

                $this->form->setData($data);

                TTransaction::close();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            TTransaction::open('libsql_admin');

            // Evita duplicação do mesmo controller ativo
            $existente = AdminRotina::where('controller', '=', $data->controller)
                                    ->where('delete_date', 'IS', NULL)
                                    ->first();

            if ($existente && (empty($data->id) || $existente->id != $data->id)) {
                throw new Exception('Já existe uma rotina cadastrada para esta classe/controller.');
            }

            $rotina = new AdminRotina;
            if (!empty($data->id)) {
                $rotina = new AdminRotina($data->id);
            }

            $rotina->nome         = $data->nome;
            $rotina->controller   = $data->controller;
            $rotina->modulo       = $data->modulo;
            $rotina->icone        = $data->icone;
            $rotina->ordem        = (int) $data->ordem;
            $rotina->visivel_menu = (int) $data->visivel_menu;

            $rotina->store();

            $data->id = $rotina->id;
            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', 'Rotina salva com sucesso!', new TAction(['RotinaList', 'onReload']));

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}