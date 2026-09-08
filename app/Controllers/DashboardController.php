<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Controllers;

use Leilabrito\PortalAluno\Core\Response;
use Leilabrito\PortalAluno\Core\SessionManager;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;
use Leilabrito\PortalAluno\Services\CourseToolService;

class DashboardController
{
    public function __construct(
        private UserCourseRepository $userCourses,
        private CourseToolService $courseTools
    ) {
    }

    public function index(): never
    {
        $userId = (int) SessionManager::get('user_id');

        $courses = $this->userCourses->findActiveCoursesByUser(
            $userId
        );

        $courseIds = [];

        foreach ($courses as $course) {
            $courseIds[] = $course->getId();
        }

        $toolsByCourse = $this->courseTools->getToolsForCourses(
            $courseIds
        );

        $result = [];

        foreach ($courses as $course) {
            $courseId = $course->getId();

            $tools = $toolsByCourse[$courseId] ?? [];

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

            $result[] = [
                'id' => $courseId,
                'name' => $course->getName(),
                'description' => $course->getDescription(),
                'tools' => $toolsResult,
            ];
        }

        Response::json([
            'success' => true,
            'courses' => $result,
        ]);
    }
}