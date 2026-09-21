<?php declare(strict_types=1); ?>
<a href="/admin/courses" class="admin-table-link">Voltar para cursos</a>
<?php if ($errors !== []): ?><div class="admin-course-feedback is-error" role="alert">Confira os campos indicados antes de salvar.</div><?php endif; ?>
<form method="POST" action="<?= $course === null ? '/admin/courses' : '/admin/courses/' . $course->getId() ?>" class="admin-course-form" <?= $course?->isActive() ? 'data-confirm-inactive' : '' ?>>
    <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
    <?php foreach (['name' => 'Nome do curso', 'ucode' => 'Identificador do produto Hotmart', 'description' => 'Descrição'] as $field => $label): ?>
        <div class="admin-field">
            <label for="<?= $field ?>"><?= $label ?><?= $field === 'name' ? ' *' : '' ?></label>
            <?php if ($field === 'description'): ?>
                <textarea id="description" name="description" rows="7" <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="description-error"' : '' ?>><?= $escape($values[$field]) ?></textarea>
            <?php else: ?>
                <input id="<?= $field ?>" name="<?= $field ?>" type="text" value="<?= $escape($values[$field]) ?>" maxlength="<?= $field === 'name' ? '200' : '100' ?>" <?= $field === 'name' ? 'required' : '' ?> <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . $field . '-error"' : '' ?>>
            <?php endif; ?>
            <?php if (isset($errors[$field])): ?><span class="admin-field-error" id="<?= $field ?>-error"><?= $escape($errors[$field]) ?></span><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="admin-field">
        <label for="access_days">Duração do acesso em dias</label>
        <input id="access_days" name="access_days" type="number" min="1" max="36500" value="<?= $escape($values['access_days'] ?? '') ?>" aria-describedby="access-days-help">
        <p id="access-days-help" class="admin-muted">Vazio significa vitalício. O prazo começa na aprovação da compra Hotmart. Alterações valem para novas transações.</p>
        <?php if (isset($errors['access_days'])): ?><span class="admin-field-error"><?= $escape($errors['access_days']) ?></span><?php endif; ?>
        <p class="admin-muted">No identificador Hotmart, informe o product.ucode recebido no webhook, não o ID numérico.</p>
    </div>
    <div>
        <label class="admin-course-checkbox"><input type="checkbox" name="active" value="1" <?= $values['active'] ? 'checked' : '' ?> aria-describedby="active-help<?= isset($errors['active']) ? ' active-error' : '' ?>"> Curso ativo</label>
        <p id="active-help" class="admin-muted">Cursos inativos ficam indisponíveis aos alunos, inclusive aos já matriculados.</p>
        <?php if (isset($errors['active'])): ?><span class="admin-field-error" id="active-error"><?= $escape($errors['active']) ?></span><?php endif; ?>
    </div>
    <div class="admin-filter-actions"><button type="submit" class="admin-button admin-button-primary">Salvar curso</button><a href="/admin/courses" class="admin-button admin-button-secondary">Cancelar</a></div>
</form>
