<?php

class EmpresaForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // Construtor do formulário no padrão global
        $this->form = new BootstrapFormBuilder('form_empresa');
        $this->form->setFormTitle('<i class="fas fa-building"></i> Cadastro de Empresa');

        // 1. Instanciação dos Campos
        $id                = new THidden('id');
        $razao_social      = new TEntry('razao_social');
        $nome_fantasia     = new TEntry('nome_fantasia');
        $status            = new TCombo('status');

        // Dados Fiscais (Armazenamento criptografado no banco)
        $cnpj              = new TEntry('cnpj');
        $ie                = new TEntry('ie');
        $im                = new TEntry('im');
        $regime_tributario = new TCombo('regime_tributario');
        $cnae_principal    = new TEntry('cnae_principal');

        // Contato
        $email_contato     = new TEntry('email_contato');
        $telefone          = new TEntry('telefone');
        $whatsapp          = new TEntry('whatsapp');

        // Endereço
        $cep               = new TEntry('cep');
        $logradouro        = new TEntry('logradouro');
        $numero            = new TEntry('numero');
        $complemento       = new TEntry('complemento');
        $bairro            = new TEntry('bairro');
        $cidade            = new TEntry('cidade');
        $uf                = new TCombo('uf');
        $codigo_ibge       = new TEntry('codigo_ibge');

        // 2. Tamanhos e Máscaras
        $razao_social->setSize('100%');
        $nome_fantasia->setSize('100%');
        $status->setSize('100%');

        $cnpj->setSize('100%');
        $ie->setSize('100%');
        $im->setSize('100%');
        $regime_tributario->setSize('100%');
        $cnae_principal->setSize('100%');

        $email_contato->setSize('100%');
        $telefone->setSize('100%');
        $whatsapp->setSize('100%');

        $cep->setSize('100%');
        $logradouro->setSize('100%');
        $numero->setSize('100%');
        $complemento->setSize('100%');
        $bairro->setSize('100%');
        $cidade->setSize('100%');
        $uf->setSize('100%');
        $codigo_ibge->setSize('100%');

        $cnpj->setMask('99.999.999/9999-99');
        $telefone->setMask('(99) 9999-9999');
        $whatsapp->setMask('(99) 99999-9999');
        $cep->setMask('99999-999');
        $cnae_principal->setMask('9999-9/99');

        // Itens de Seleção
        $status->addItems([
            'ativo'    => 'Ativo',
            'inativo'  => 'Inativo',
            'suspenso' => 'Suspenso'
        ]);
        $status->setDefaultOption(false);
        $status->setValue('ativo');

        $regime_tributario->addItems([
            'simples_nacional' => 'Simples Nacional',
            'lucro_presumido'  => 'Lucro Presumido',
            'lucro_real'       => 'Lucro Real',
            'mei'              => 'MEI'
        ]);

        $ufs = [
            'AC'=>'AC','AL'=>'AL','AP'=>'AP','AM'=>'AM','BA'=>'BA','CE'=>'CE','DF'=>'DF','ES'=>'ES',
            'GO'=>'GO','MA'=>'MA','MT'=>'MT','MS'=>'MS','MG'=>'MG','PA'=>'PA','PB'=>'PB','PR'=>'PR',
            'PE'=>'PE','PI'=>'PI','RJ'=>'RJ','RN'=>'RN','RS'=>'RS','RO'=>'RO','RR'=>'RR','SC'=>'SC',
            'SP'=>'SP','SE'=>'SE','TO'=>'TO'
        ];
        $uf->addItems($ufs);
        $uf->enableSearch();

        // Validações Obrigatórias
        $razao_social->addValidation('Razão Social', new TRequiredValidator);
        $cnpj->addValidation('CNPJ', new TRequiredValidator);
        $email_contato->addValidation('E-mail de Contato', new TRequiredValidator);
        $email_contato->addValidation('E-mail de Contato', new TEmailValidator);
        $status->addValidation('Status', new TRequiredValidator);

        // 3. Organização do Formulário em Abas
        $this->form->appendPage('Dados Principais');
        $this->form->addFields([$id]);
        $this->form->addFields(
            [new TLabel('Razão Social <span class="text-danger">*</span>')], [$razao_social],
            [new TLabel('Nome Fantasia')], [$nome_fantasia]
        );
        $this->form->addFields(
            [new TLabel('Status <span class="text-danger">*</span>')], [$status],
            [], []
        );

        $this->form->appendPage('Dados Fiscais & Tributários');
        $this->form->addFields(
            [new TLabel('CNPJ (Criptografado) <span class="text-danger">*</span>')], [$cnpj],
            [new TLabel('Inscrição Estadual (Criptografado)')], [$ie]
        );
        $this->form->addFields(
            [new TLabel('Inscrição Municipal (Criptografado)')], [$im],
            [new TLabel('Regime Tributário')], [$regime_tributario]
        );
        $this->form->addFields(
            [new TLabel('CNAE Principal')], [$cnae_principal],
            [], []
        );

        $this->form->appendPage('Endereço & Contatos');
        $this->form->addFields(
            [new TLabel('E-mail de Contato <span class="text-danger">*</span>')], [$email_contato],
            [new TLabel('Telefone Fixo')], [$telefone]
        );
        $this->form->addFields(
            [new TLabel('WhatsApp')], [$whatsapp],
            [new TLabel('CEP')], [$cep]
        );
        $this->form->addFields(
            [new TLabel('Logradouro')], [$logradouro],
            [new TLabel('Número')], [$numero]
        );
        $this->form->addFields(
            [new TLabel('Complemento')], [$complemento],
            [new TLabel('Bairro')], [$bairro]
        );
        $this->form->addFields(
            [new TLabel('Cidade')], [$cidade],
            [new TLabel('UF')], [$uf]
        );
        $this->form->addFields(
            [new TLabel('Código IBGE Município')], [$codigo_ibge],
            [], []
        );

        // 4. Ações
        $btnSave = $this->form->addAction('Salvar Empresa', new TAction([$this, 'onSave']), 'fas:save');
        $btnSave->class = 'btn btn-primary';

        $btnBack = $this->form->addAction('Voltar', new TAction(['EmpresaList', 'onReload']), 'fas:arrow-left');
        $btnBack->class = 'btn btn-default';

        parent::add($this->form);
    }

    /**
     * Carrega os dados da empresa para edição e decripta os documentos fiscais em runtime
     */
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                $id = $param['id'];

                TTransaction::open('libsql_admin');

                $empresa = new AdminEmpresa($id);

                if (empty($empresa->id) || !empty($empresa->delete_date)) {
                    throw new Exception('Empresa não encontrada ou inativa.');
                }

                $data = new stdClass;
                $data->id                = $empresa->id;
                $data->razao_social      = $empresa->razao_social;
                $data->nome_fantasia     = $empresa->nome_fantasia;
                $data->status            = $empresa->status;

                // Descriptografia em memória via métodos getters do model AdminEmpresa
                $data->cnpj              = $empresa->get_cnpj();
                $data->ie                = $empresa->get_ie();
                $data->im                = $empresa->get_im();

                $data->regime_tributario = $empresa->regime_tributario;
                $data->cnae_principal    = $empresa->cnae_principal;
                $data->email_contato     = $empresa->email_contato;
                $data->telefone          = $empresa->telefone;
                $data->whatsapp          = $empresa->whatsapp;
                $data->logradouro        = $empresa->logradouro;
                $data->numero            = $empresa->numero;
                $data->complemento       = $empresa->complemento;
                $data->bairro            = $empresa->bairro;
                $data->cep               = $empresa->cep;
                $data->cidade            = $empresa->cidade;
                $data->uf                = $empresa->uf;
                $data->codigo_ibge       = $empresa->codigo_ibge;

                $this->form->setData($data);

                TTransaction::close();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Grava os dados da empresa criptografando os documentos fiscais sensíveis via model
     */
    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            TTransaction::open('libsql_admin');

            $empresa = new AdminEmpresa;
            if (!empty($data->id)) {
                $empresa = new AdminEmpresa($data->id);
            }

            $empresa->razao_social      = $data->razao_social;
            $empresa->nome_fantasia     = $data->nome_fantasia;
            $empresa->status            = $data->status;

            // Setters do model cifram via SecurityHelper (AES-256-CBC)
            $empresa->set_cnpj($data->cnpj);
            $empresa->set_ie($data->ie);
            $empresa->set_im($data->im);

            $empresa->regime_tributario = $data->regime_tributario;
            $empresa->cnae_principal    = $data->cnae_principal;
            $empresa->email_contato     = $data->email_contato;
            $empresa->telefone          = $data->telefone;
            $empresa->whatsapp          = $data->whatsapp;
            $empresa->logradouro        = $data->logradouro;
            $empresa->numero            = $data->numero;
            $empresa->complemento       = $data->complemento;
            $empresa->bairro            = $data->bairro;
            $empresa->cep               = $data->cep;
            $empresa->cidade            = $data->cidade;
            $empresa->uf                = $data->uf;
            $empresa->codigo_ibge       = $data->codigo_ibge;

            $empresa->store();

            $data->id = $empresa->id;
            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', 'Empresa salva com sucesso!', new TAction(['EmpresaList', 'onReload']));

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}