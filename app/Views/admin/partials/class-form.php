<?php declare(strict_types=1); ?>
<a href="/admin/classes" class="admin-table-link">Voltar para turmas</a>
<?php if ($errors !== []): ?><div class="admin-course-feedback is-error" role="alert"><?php foreach ($errors as $error): ?><p><?= $escape($error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="POST" action="<?= $class === null ? '/admin/classes' : '/admin/classes/' . (int) $class['id'] ?>" class="admin-course-form">
    <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
    <?php if ($class !== null): ?><p>ID da turma: <?= (int) $class['id'] ?></p><?php endif; ?>
    <div class="admin-field"><label for="name">Nome da turma *</label><input id="name" name="name" value="<?= $escape($values['name']) ?>" maxlength="200" required></div>
    <div class="admin-field">
        <label for="course_id">Curso *</label><p class="admin-muted">Novas turmas exigem curso ativo na Hotmart. Turmas existentes mantêm suas regras de acesso.</p>
        <?php if ($class !== null): ?>
            <input id="course_id" value="<?= $escape($class['course_name']) ?>" readonly>
            <input type="hidden" name="course_id" value="<?= (int) $class['course_id'] ?>">
        <?php else: ?>
            <select id="course_id" name="course_id" required><option value="">Selecione um curso</option>
                <?php foreach ($courses as $course): ?><option value="<?= $course->getId() ?>" <?= (string) $course->getId() === (string) $values['course_id'] ? 'selected' : '' ?>><?= $escape($course->getName()) ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
    <div class="admin-field"><label for="hotmart_class_id">ID da turma Hotmart (class_id) *</label><input id="hotmart_class_id" name="hotmart_class_id" value="<?= $escape($values['hotmart_class_id']) ?>" maxlength="100" required <?= $class !== null ? 'readonly' : '' ?>></div>
    <p class="admin-muted">Curso e ID Hotmart identificam a turma e ficam fixos após o cadastro. O ID interno é gerado automaticamente.</p>
    <div class="admin-field"><label for="expiration">Duração do acesso *</label><select id="expiration" name="expiration" required>
        <option value="">Selecione</option><option value="days" <?= $values['expiration'] === 'days' ? 'selected' : '' ?>>Dias desde a compra</option><option value="lifetime" <?= $values['expiration'] === 'lifetime' ? 'selected' : '' ?>>Vitalício</option>
    </select></div>
    <div class="admin-field"><label for="access_days">Dias de acesso</label><input type="number" id="access_days" name="access_days" min="1" max="36500" step="1" value="<?= $escape($values['access_days']) ?>"><p class="admin-muted">O vencimento de cada aluno é a data da compra mais esta quantidade de dias. Exemplo: compra em março com 365 dias de acesso vence em março do ano seguinte.</p></div>
    <fieldset class="admin-class-tools"><legend>Ferramentas da turma</legend>
        <?php if ($tools === []): ?><p class="admin-muted">Nenhuma ferramenta cadastrada.</p><?php endif; ?>
        <?php foreach ($tools as $tool): ?><label class="admin-course-checkbox"><input type="checkbox" name="tools[]" value="<?= (int) $tool['id'] ?>" <?= in_array((int) $tool['id'], $values['tools'], true) ? 'checked' : '' ?>> <?= $escape($tool['name']) ?><?= !$tool['is_active'] ? ' (inativa globalmente)' : '' ?></label><?php endforeach; ?>
    </fieldset>
    <p class="admin-muted">Alterar ferramentas ou duração afeta imediatamente todos os alunos vinculados a esta turma.</p>
    <input type="hidden" name="selection_complete" value="1">
    <div class="admin-filter-actions"><button class="admin-button admin-button-primary" type="submit">Salvar turma</button><a href="/admin/classes" class="admin-button admin-button-secondary">Cancelar</a></div>
</form>
