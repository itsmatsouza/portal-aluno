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

    public function show(string $slug): never
    {
        $userId = (int) SessionManager::get('user_id');

        $tool = $this->tools->findBySlug($slug);

        if ($tool === null) {
            Response::json([
                'success' => false,
                'message' => 'Ferramenta não encontrada.'
            ], 404);
        }

        if (!$tool->isAvailable()) {
            Response::json([
                'success' => false,
                'message' => 'Ferramenta não disponível.'
            ], 404);
        }

        if (!$this->courseTools->userCanAccessTool(
            $userId,
            $tool->getId()
        )) {
            Response::json([
                'success' => false,
                'message' => 'Você não possui acesso a esta ferramenta.'
            ], 403);
        }

        $toolPath = dirname(__DIR__, 2)
            . '/storage/tools/'
            . $tool->getSlug()
            . '/index.html';

        if (!is_file($toolPath)) {
            Response::json([
                'success' => false,
                'message' => 'Arquivo da ferramenta não encontrado.'
            ], 500);
        }

        $content = file_get_contents($toolPath);

        if ($content === false) {
            Response::json([
                'success' => false,
                'message' => 'Não foi possível carregar a ferramenta.'
            ], 500);
        }

        Response::html($content);
    }
}