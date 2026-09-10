<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Repositories\ToolRepository;

class CourseToolService
{
    public function __construct(
        private ToolRepository $tools
    ) {
    }

    public function getToolsForCourse(int $courseId): array
    {
        return $this->tools->findActiveByCourseId($courseId);
    }

    public function getToolsForCourses(array $courseIds): array
    {
        $tools = $this->tools->findActiveByCourseIds(
            $courseIds
        );

        $result = [];

        foreach ($tools as $item) {
            $courseId = $item['course_id'];

            if (!isset($result[$courseId])) {
                $result[$courseId] = [];
            }

            $result[$courseId][] = $item['tool'];
        }

        return $result;
    }

    public function userCanAccessTool(
        int $userId,
        int $toolId
    ): bool {
        return $this->tools->hasActiveAccessForUser(
            $userId,
            $toolId
        );
    }
}