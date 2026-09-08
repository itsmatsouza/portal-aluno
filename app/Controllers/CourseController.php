<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Services\CourseAccessService;

class CourseController
{
    public function __construct(
        private CourseAccessService $courseAccess
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

        Response::json([
            'success' => true,
            'course' => [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
            ]
        ]);
    }
}
