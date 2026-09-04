<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

class DashboardController
{
    public function __construct(
        private UserCourseRepository $userCourses
    ) {
    }

    public function index(): never
    {
        $userId = (int) SessionManager::get('user_id');

        $courses = $this->userCourses->findActiveCoursesByUser(
            $userId
        );

        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->getId(),
                'name' => $course->getName(),
                'description' => $course->getDescription(),
            ];
        }

        Response::json([
            'success' => true,
            'courses' => $result,
        ]);
    }
}