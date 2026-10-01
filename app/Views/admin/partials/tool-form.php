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
    <p class="admin-muted">A presença do arquivo não confirma revisão técnica. Inativar bloqueia a ferramenta em todas as turmas vinculadas.</p>
    <form method="POST" action="/admin/tools/<?= $tool->getId() ?>/status" <?= $tool->isActive() ? 'data-confirm-tool-inactive' : '' ?> data-class-count="<?= count($classes) ?>">
        <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
        <input type="hidden" name="active" value="<?= $tool->isActive() ? '0' : '1' ?>">
        <button class="admin-button admin-button-secondary" type="submit" <?= !$tool->isActive() && !$hasFile ? 'disabled title="Arquivo ausente ou inacessível"' : '' ?>><?= $tool->isActive() ? 'Inativar ferramenta' : 'Ativar ferramenta' ?></button>
    </form>
</section>
<section class="content-section" id="linkedClasses">
    <div class="section-heading"><div><h2>Turmas vinculadas</h2><p><?= count($classes) ?> turma(s).</p></div></div>
    <?php if ($classes === []): ?>
        <p class="admin-muted">Nenhuma turma vinculada.</p>
        <a href="/admin/classes" class="admin-table-link">Ir para turmas</a>
    <?php else: ?>
        <ul class="admin-tool-courses">
            <?php foreach ($classes as $linkedClass): ?>
                <li>
                    <a class="admin-table-link" href="/admin/classes/<?= (int) $linkedClass['id'] ?>/edit"><?= $escape($linkedClass['name']) ?></a>
                    <span class="admin-muted"><?= $escape($linkedClass['course_name']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
