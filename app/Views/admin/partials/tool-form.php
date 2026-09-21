<?php declare(strict_types=1); ?>
<a href="/admin/tools" class="admin-table-link">Voltar para ferramentas</a>
<?php if ($errors !== []): ?><div class="admin-course-feedback is-error" role="alert">Confira os campos indicados antes de salvar.</div><?php endif; ?>
<?php if ($statusError !== null): ?><div class="admin-course-feedback is-error" role="alert"><?= $escape($statusError) ?></div><?php endif; ?>
<form method="POST" action="/admin/tools/<?= $tool->getId() ?>" class="admin-course-form">
    <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
    <div class="admin-field">
        <label for="name">Nome da ferramenta *</label>
        <input type="text" id="name" name="name" value="<?= $escape($values['name']) ?>" required maxlength="200" <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
        <?php if (isset($errors['name'])): ?><span class="admin-field-error" id="name-error"><?= $escape($errors['name']) ?></span><?php endif; ?>
    </div>
    <div class="admin-field">
        <label for="description">Descrição</label>
        <textarea id="description" name="description" rows="6" <?= isset($errors['description']) ? 'aria-invalid="true" aria-describedby="description-error"' : '' ?>><?= $escape($values['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><span class="admin-field-error" id="description-error"><?= $escape($errors['description']) ?></span><?php endif; ?>
    </div>
    <div class="admin-filter-actions"><button class="admin-button admin-button-primary" type="submit">Salvar ferramenta</button><a class="admin-button admin-button-secondary" href="/admin/tools">Cancelar</a></div>
</form>
<section class="content-section admin-tool-section">
    <div class="section-heading"><h2>Disponibilidade</h2></div>
    <dl class="admin-tool-info">
        <div><dt>Identificador</dt><dd><?= $escape($tool->getSlug()) ?></dd></div>
        <div><dt>Arquivo</dt><dd><?= $hasFile ? 'Disponível' : 'Ausente ou inacessível' ?></dd></div>
        <div><dt>Status global</dt><dd><?= $tool->isActive() ? 'Ativa' : 'Inativa' ?></dd></div>
    </dl>
    <p class="admin-muted">A presença do arquivo não confirma revisão técnica. Inativar bloqueia a ferramenta em todos os cursos vinculados.</p>
    <form method="POST" action="/admin/tools/<?= $tool->getId() ?>/status" <?= $tool->isActive() ? 'data-confirm-tool-inactive' : '' ?> data-course-count="<?= count($courses) ?>">
        <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
        <input type="hidden" name="active" value="<?= $tool->isActive() ? '0' : '1' ?>">
        <button class="admin-button admin-button-secondary" type="submit" <?= !$tool->isActive() && !$hasFile ? 'disabled title="Arquivo ausente ou inacessível"' : '' ?>><?= $tool->isActive() ? 'Inativar ferramenta' : 'Ativar ferramenta' ?></button>
    </form>
</section>
<section class="content-section" id="linkedCourses">
    <div class="section-heading"><div><h2>Cursos vinculados</h2><p><?= count($courses) ?> curso(s).</p></div></div>
    <?php if ($courses === []): ?>
        <p class="admin-muted">Nenhum curso vinculado.</p>
        <a href="/admin/courses" class="admin-table-link">Ir para cursos</a>
    <?php else: ?>
        <ul class="admin-tool-courses">
            <?php foreach ($courses as $linkedCourse): ?>
                <li>
                    <a class="admin-table-link" href="/admin/courses/<?= (int) $linkedCourse['id'] ?>/tools"><?= $escape($linkedCourse['name']) ?></a>
                    <span class="admin-status <?= $linkedCourse['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $linkedCourse['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
