<?php

declare(strict_types=1);

namespace Leilabrito\PortalAluno\Services;

use InvalidArgumentException;
use Leilabrito\PortalAluno\Models\Tool;
use Leilabrito\PortalAluno\Repositories\ToolRepository;

class AdminToolService
{
    public function __construct(private ToolRepository $tools)
    {
    }

    public function hasFile(Tool $tool): bool
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $tool->getSlug())) {
            return false;
        }
        $base = realpath(dirname(__DIR__, 2) . '/storage/tools');
        if ($base === false) {
            return false;
        }
        $directory = realpath($base . DIRECTORY_SEPARATOR . $tool->getSlug());
        if ($directory === false || !str_starts_with($directory . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR)) {
            return false;
        }
        $file = realpath($directory . DIRECTORY_SEPARATOR . 'index.html');

        return $file !== false
            && str_starts_with($file, $directory . DIRECTORY_SEPARATOR)
            && is_file($file) && is_readable($file);
    }

    public function save(Tool $tool, array $input): array
    {
        $values = [];
        $errors = [];
        foreach (['name', 'description'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
            if ((isset($input[$field]) && !is_string($input[$field])) || preg_match('//u', $values[$field]) !== 1) {
                $errors[$field] = 'Texto inválido.';
            }
        }
        if ($values['name'] === '' || preg_match_all('/./us', $values['name']) > 200) {
            $errors['name'] = 'Informe um nome com até 200 caracteres.';
        }
        if (strlen($values['description']) > 65535) {
            $errors['description'] = 'A descrição excede o tamanho permitido.';
        }
        if ($errors === []) {
            $this->tools->updateMetadata($tool->getId(), $values['name'], $values['description'] === '' ? null : $values['description']);
        }

        return ['values' => $values, 'errors' => $errors];
    }

    public function setActive(Tool $tool, bool $active): void
    {
        if ($active && !$this->hasFile($tool)) {
            throw new InvalidArgumentException('Não foi possível ativar: arquivo da ferramenta ausente ou inacessível.');
        }
        $this->tools->setActive($tool->getId(), $active);
    }
}
