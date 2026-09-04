<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Models\Course;
use Leilabrito\PortalAluno\Repositories\CourseRepository;
use Leilabrito\PortalAluno\Repositories\UserCourseRepository;

class CourseAccessService
{
    public function __construct(
        private UserCourseRepository $userCourses,
        private CourseRepository $courses
    ) {
    }

    public function canAccess(
        int $userId,
        int $courseId
    ): bool {
        $course = $this->courses->findById($courseId);

        if ($course === null) {
            return false;
        }

        if (!$course->isAvailable()) {
            return false;
        }

        return $this->userCourses->hasAccess(
            $userId,
            $courseId
        );
    }

    public function getCourseForUser(
        int $userId,
        int $courseId
    ): ?Course {
        if (!$this->canAccess($userId, $courseId)) {
            return null;
        }

        return $this->courses->findById($courseId);
    }
}