<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use Leilabrito\PortalAluno\Repositories\ClassRepository;

class AdminClassService
{
    public function __construct(private ClassRepository $classes)
    {
    }

    public function save(?int $id, array $input): array
    {
        $values = [];
        $errors = [];
        foreach (['name', 'hotmart_class_id', 'course_id', 'expiration', 'access_days'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
        }
        if ($values['name'] === '' || preg_match('//u', $values['name']) !== 1 || preg_match_all('/./us', $values['name']) > 200) {
            $errors['name'] = 'Informe um nome válido com até 200 caracteres.';
        }
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $values['hotmart_class_id'])) {
            $errors['hotmart_class_id'] = 'Informe o class_id da turma no Hotmart Club.';
        }
        $courseId = filter_var($values['course_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($courseId === false) {
            $errors['course_id'] = 'Selecione um curso.';
        }
        $accessDays = null;
        if ($values['expiration'] === 'days') {
            $accessDays = filter_var($values['access_days'], FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 36500]]);
            if ($accessDays === false) {
                $errors['access_days'] = 'Informe de 1 a 36500 dias de acesso.';
            }
        } elseif ($values['expiration'] !== 'lifetime') {
            $errors['expiration'] = 'Escolha duração em dias ou acesso vitalício.';
        }
        $tools = [];
        if (!is_array($input['tools'] ?? [])) {
            $errors['tools'] = 'Seleção de ferramentas inválida.';
        } else {
            foreach ($input['tools'] ?? [] as $value) {
                $toolId = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
                if ($toolId === false) {
                    $errors['tools'] = 'Seleção de ferramentas inválida.';
                    break;
                }
                $tools[$toolId] = $toolId;
            }
        }
        if (($input['selection_complete'] ?? null) !== '1') {
            $errors['tools'] = 'Formulário incompleto. Recarregue a página.';
        }
        $values['tools'] = array_values($tools);
        if ($errors === []) {
            try {
                $id = $this->classes->save($id, [
                    'course_id' => (int) $courseId, 'name' => $values['name'],
                    'hotmart_class_id' => $values['hotmart_class_id'],
                    'is_lifetime' => (int) ($values['expiration'] === 'lifetime'), 'access_days' => $accessDays,
                ], $values['tools']);
            } catch (\InvalidArgumentException $error) {
                $errors['form'] = $error->getMessage();
            } catch (\PDOException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1062) {
                    throw $error;
                }
                $errors['hotmart_class_id'] = 'Esta turma Hotmart já está cadastrada neste curso.';
            }
        }
        return ['id' => $id, 'values' => $values, 'errors' => $errors];
    }
}
