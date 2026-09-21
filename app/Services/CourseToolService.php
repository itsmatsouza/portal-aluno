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

    public function getToolsForAdministration(int $courseId): array
    {
        return $this->tools->findForCourseAdmin($courseId);
    }

    public function saveCourseTools(int $courseId, mixed $selection): void
    {
        if (!is_array($selection)) {
            throw new \InvalidArgumentException('Seleção de ferramentas inválida.');
        }
        $ids = [];
        foreach ($selection as $value) {
            if (!is_int($value) && !is_string($value)) {
                throw new \InvalidArgumentException('Seleção de ferramentas inválida.');
            }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new \InvalidArgumentException('Seleção de ferramentas inválida.');
            }
            $ids[$id] = $id;
        }
        $this->tools->syncCourseTools($courseId, array_values($ids));
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
