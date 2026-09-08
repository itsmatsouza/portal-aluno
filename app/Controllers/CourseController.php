<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Services\CourseAccessService;
use Leilabrito\PortalAluno\Services\CourseToolService;

class CourseController
{
    public function __construct(
        private CourseAccessService $courseAccess,
        private CourseToolService $courseTools
    ) {
    }

    public function show(int $courseId): never
    {
        $userId = (int) SessionManager::get('user_id');

        $course = $this->courseAccess->getCourseForUser(
            $userId,
            $courseId
        );

        if ($course === null) {
            Response::json([
                'success' => false,
                'message' => 'Acesso ao curso não permitido.'
            ], 403);
        }

        $tools = $this->courseTools->getToolsForCourse(
            $courseId
        );

        $toolsResult = [];

        foreach ($tools as $tool) {
            $toolsResult[] = [
                'id' => $tool->getId(),
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'slug' => $tool->getSlug(),
                'url' => $tool->getUrl(),
            ];
        }

        Response::json([
            'success' => true,
            'course' => [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'tools' => $toolsResult,
            ]
        ]);
    }
}
