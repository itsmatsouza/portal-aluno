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
}
