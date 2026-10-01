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

    public function getToolsForCourse(int $userId, int $courseId): array
    {
        return array_column($this->tools->findActiveForUser($userId, [$courseId]), 'tool');
    }

    public function getToolsForCourses(int $userId, array $courseIds): array
    {
        $result = [];
        foreach ($this->tools->findActiveForUser($userId, $courseIds) as $item) {
            $result[$item['course_id']][] = $item['tool'];
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
