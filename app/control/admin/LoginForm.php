<?php

class LoginForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // 1. Tema unicamente via sessão (padrão 'light' se não autenticado)
        $currentTheme = TSession::getValue('theme') ?? 'light';

        $wrapper = new TElement('div');
        $wrapper->class = 'login-wrapper ' . ($currentTheme === 'dark' ? 'theme-dark' : 'theme-light');
        $wrapper->style = 'display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px;';

        $card = new TElement('div');
        $card->class = 'login-card card shadow-lg';
        $card->style = 'width:100%; max-width:420px; border-radius:12px; overflow:hidden; border:none;';

        $header = new TElement('div');
        $header->class = 'card-header text-center py-4 position-relative';
        $header->style = ($currentTheme === 'dark')
            ? 'background:#1e293b; color:#f8fafc; border-bottom:1px solid #334155;' 
            : 'background:#2563eb; color:#ffffff;';

        // Logótipo e título
        $icon = new TElement('i');
        $icon->class = 'fas fa-shield-alt fa-3x mb-2';
        
        $title = new TElement('h4');
        $title->class = 'font-weight-bold mb-0';
        $title->add('NakaControl Admin');

        $subtitle = new TElement('small');
        $subtitle->style = 'opacity:0.8;';
        $subtitle->add('Acesso Restrito ao Painel de Gestão');

        $header->add($icon);
        $header->add($title);
        $header->add($subtitle);

        $body = new TElement('div');
        $body->class = 'card-body p-4';
        if ($currentTheme === 'dark') {
            $body->style = 'background:#0f172a; color:#f8fafc;';
        }

        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle('');

        $login    = new TEntry('login');
        $password = new TPassword('password');

        $login->setSize('100%');
        $password->setSize('100%');

        $login->placeholder    = 'seu.email@dominio.com';
        $password->placeholder = '••••••••';

        $login->addValidation('E-mail / Login', new TRequiredValidator);
        $password->addValidation('Senha', new TRequiredValidator);

        $this->form->addFields([new TLabel('E-mail')], [$login]);
        $this->form->addFields([new TLabel('Senha')], [$password]);

        $btnSubmit = $this->form->addAction('Entrar no Sistema', new TAction([$this, 'onLogin']), 'fas:sign-in-alt');
        $btnSubmit->class = 'btn btn-primary btn-block w-100 py-2 mt-3 font-weight-bold';

        $body->add($this->form);
        $card->add($header);
        $card->add($body);
        $wrapper->add($card);

        $this->injectCustomCss($currentTheme);

        parent::add($wrapper);
    }

    public function onLogin($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            // O AdminAuthService valida e grava automaticamente: TSession::setValue('theme', $usuario->theme)
            $usuario = AdminAuthService::authenticate($data->login, $data->password, 'web');

            AdiantiCoreApplication::gotoPage('HomeView');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    private function injectCustomCss($theme)
    {
        $style = new TElement('style');
        if ($theme === 'dark') {
            $style->add("
                body { background-color: #0b1120 !important; color: #e2e8f0; }
                .theme-dark { background-color: #0b1120; }
                .theme-dark .card { background-color: #0f172a; border-color: #1e293b; }
                .theme-dark .form-control { background-color: #1e293b !important; border-color: #334155 !important; color: #f8fafc !important; }
                .theme-dark .form-control:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important; }
                .theme-dark label { color: #cbd5e1 !important; font-weight: 500; }
            ");
        } else {
            $style->add("
                body { background-color: #f1f5f9 !important; }
                .theme-light { background-color: #f1f5f9; }
                .theme-light .card { background-color: #ffffff; }
                .theme-light label { color: #475569 !important; font-weight: 500; }
            ");
        }
        parent::add($style);
    }
}