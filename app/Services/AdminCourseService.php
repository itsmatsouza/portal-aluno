<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Repositories\CourseRepository;

class AdminCourseService
{
    public function __construct(private CourseRepository $courses)
    {
    }

    public function save(?int $id, array $input): array
    {
        $course = $id === null ? null : $this->courses->findById($id);
        if ($course === null || $course->isDeleted()) {
            throw new \InvalidArgumentException('Curso não encontrado.');
        }
        $description = is_string($input['description'] ?? null) ? trim($input['description']) : '';
        $errors = [];
        if (!is_string($input['description'] ?? null) || preg_match('//u', $description) !== 1 || strlen($description) > 65535) {
            $errors['description'] = 'Informe uma descrição válida com até 65535 bytes.';
        }
        if ($errors === []) {
            $this->courses->updateDescription($id, $description === '' ? null : $description);
        }
        return ['id' => $id, 'values' => ['name' => $course->getName(),
            'ucode' => $course->getHotmartProductUcode() ?? '', 'description' => $description], 'errors' => $errors];
    }
}
