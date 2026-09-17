<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\ToolRepository;
use Leilabrito\PortalAluno\Services\CourseToolService;

class ToolController
{
    public function __construct(
        private ToolRepository $tools,
        private CourseToolService $courseTools
    ) {
    }

    private function error(
        string $view,
        int $status,
        string $message
    ): never {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        $expectsJson = str_contains(
            strtolower($accept),
            'application/json'
        );

        if ($expectsJson) {
            Response::json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        Response::viewError($view, $status, [
            'statusCode' => $status,
            'title' => match ($status) {
                401 => 'Sua sessão expirou',
                403 => 'Acesso não disponível',
                404 => 'Página não encontrada',
                default => 'Ocorreu um erro',
            },
            'message' => $message,
            'buttonText' => $status === 401
                ? 'Ir para o login'
                : 'Voltar ao portal',
            'buttonUrl' => $status === 401
                ? '/login'
                : '/portal',
        ]);
    }

    private function getToolFilePath(
        \Leilabrito\PortalAluno\Models\Tool $tool
    ): string {
        $baseToolsPath = realpath(
            dirname(__DIR__, 2) . '/storage/tools'
        );

        if ($baseToolsPath === false) {
            $this->error(
                '500',
                500,
                'Diretório das ferramentas não encontrado.'
            );
        }

        $toolDirectory = realpath(
            $baseToolsPath . DIRECTORY_SEPARATOR . $tool->getSlug()
        );

        if (
            $toolDirectory === false ||
            !is_dir($toolDirectory) ||
            !str_starts_with(
                $toolDirectory . DIRECTORY_SEPARATOR,
                $baseToolsPath . DIRECTORY_SEPARATOR
            )
        ) {
            $this->error(
                '500',
                500,
                'Esta ferramenta ainda não está disponível.'
            );
        }

        $toolPath = $toolDirectory . DIRECTORY_SEPARATOR . 'index.html';

        if (!is_file($toolPath) || !is_readable($toolPath)) {
            $this->error(
                '500',
                500,
                'Esta ferramenta ainda não está disponível.'
            );
        }

        return $toolPath;
    }

    public function view(string $slug): never
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error(
                '404',
                404,
                'Ferramenta inválida.'
            );
        }

        $userId = (int) SessionManager::get('user_id');

        if ($userId <= 0) {
            $this->error(
                'session-expired',
                401,
                'Sua sessão expirou. Faça login novamente para continuar.'
            );
        }

        $tool = $this->tools->findBySlug($slug);

        if ($tool === null) {
            $this->error(
                '404',
                404,
                'Ferramenta não encontrada.'
            );
        }

        if (!$tool->isAvailable()) {
            $this->error(
                '404',
                404,
                'Ferramenta não disponível.'
            );
        }

        if (!$this->courseTools->userCanAccessTool(
            $userId,
            $tool->getId()
        )) {
            $this->error(
                '403',
                403,
                'Você não possui acesso a esta ferramenta.'
            );
        }

        $this->getToolFilePath($tool);

        $_GET['slug'] = $tool->getSlug();

        require dirname(__DIR__) . '/Views/tool.php';

        exit;
    }

    public function show(string $slug): never
    {
        /*
         * Aceitamos somente slugs no formato:
         *
         * diagnostico-precificacao
         * ferramenta-01
         * curso-avancado
         *
         * Isso impede tentativas de manipulação de caminho.
         */
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $this->error(
                '404',
                404,
                'Ferramenta inválida.'
            );
        }

        $userId = (int) SessionManager::get('user_id');

        if ($userId <= 0) {
            $this->error(
                'session-expired',
                401,
                'Sua sessão expirou. Faça login novamente para continuar.'
            );
        }

        $tool = $this->tools->findBySlug($slug);

        if ($tool === null) {
            $this->error(
                '404',
                404,
                'Ferramenta não encontrada.'
            );
        }

        if (!$tool->isAvailable()) {
            $this->error(
                '404',
                404,
                'Ferramenta não disponível.'
            );
        }

        if (!$this->courseTools->userCanAccessTool(
            $userId,
            $tool->getId()
        )) {
            $this->error(
                '403',
                403,
                'Você não possui acesso a esta ferramenta.'
            );
        }

        /*
         * O caminho é montado somente a partir do slug
         * retornado pelo banco após todas as validações.
         */
        $toolPath = $this->getToolFilePath($tool);

        $content = file_get_contents($toolPath);

        if ($content === false) {
            $this->error(
                '500',
                500,
                'Não foi possível carregar a ferramenta.'
            );
        }

        Response::html($content);
    }
}