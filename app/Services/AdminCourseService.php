<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Repositories\CourseRepository;
use PDOException;

class AdminCourseService
{
    public function __construct(private CourseRepository $courses)
    {
    }

    public function save(?int $id, array $input): array
    {
        $values = [];
        $errors = [];
        foreach (['name', 'description', 'ucode'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
            if (isset($input[$field]) && !is_string($input[$field])) {
                $errors[$field] = 'Valor inválido.';
            }
            if (preg_match('//u', $values[$field]) !== 1) {
                $errors[$field] = 'Texto inválido.';
            }
        }
        $values['active'] = ($input['active'] ?? '') === '1';
        if (isset($input['active']) && $input['active'] !== '1') {
            $errors['active'] = 'Status inválido.';
        }
        if ($values['name'] === '' || preg_match_all('/./us', $values['name']) > 200) {
            $errors['name'] = 'Informe um nome com até 200 caracteres.';
        }
        if (preg_match_all('/./us', $values['ucode']) > 100) {
            $errors['ucode'] = 'Informe até 100 caracteres.';
        }
        if (strlen($values['description']) > 65535) {
            $errors['description'] = 'A descrição excede o tamanho permitido.';
        }
        if ($errors === [] && $values['ucode'] !== '') {
            $existing = $this->courses->findByHotmartProductUcode($values['ucode']);
            if ($existing !== null && $existing->getId() !== $id) {
                $errors['ucode'] = 'Este identificador Hotmart já está vinculado a outro curso.';
            }
        }
        if ($errors === []) {
            try {
                $arguments = [$values['name'], $values['description'] === '' ? null : $values['description'], $values['ucode'] === '' ? null : $values['ucode'], $values['active']];
                if ($id === null) {
                    $id = $this->courses->create(...$arguments);
                } else {
                    $this->courses->update($id, ...$arguments);
                }
            } catch (PDOException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }
                $errors['ucode'] = 'Este identificador Hotmart já está vinculado a outro curso.';
            }
        }

        return ['id' => $id, 'values' => $values, 'errors' => $errors];
    }
}
