<?php declare(strict_types=1); ?>
<a href="/admin/courses" class="admin-table-link">Voltar para cursos</a>
<form method="POST" action="/admin/courses/<?= $course->getId() ?>" class="admin-course-form">
    <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
    <p class="admin-muted">Os dados do curso vêm da Hotmart. Ferramentas e vencimento são definidos nas turmas. Curso inativo impede apenas o cadastro de novas turmas.</p>
    <div class="admin-field"><label for="name">Nome do curso</label><input id="name" value="<?= $escape($values['name']) ?>" readonly></div>
    <div class="admin-field"><label for="ucode">Identificador Hotmart</label><input id="ucode" value="<?= $escape($values['ucode']) ?>" readonly></div>
    <div class="admin-field">
        <label for="description">Descrição</label>
        <textarea id="description" name="description" rows="7"><?= $escape($values['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><span class="admin-field-error" role="alert"><?= $escape($errors['description']) ?></span><?php endif; ?>
    </div>
    <div class="admin-filter-actions"><button type="submit" class="admin-button admin-button-primary">Salvar descrição</button><a href="/admin/classes" class="admin-button admin-button-secondary">Gerenciar turmas</a></div>
</form>
