<?php declare(strict_types=1); ?>
<a href="/admin/courses" class="admin-table-link">Voltar para cursos</a>
<?php if ($toolsError !== null): ?>
    <div class="admin-course-feedback is-error" role="alert"><?= $escape($toolsError) ?> A seleção salva foi mantida.</div>
<?php endif; ?>
<?php if (!$course->isActive()): ?>
    <p class="admin-muted">Curso inativo: ferramentas vinculadas permanecem indisponíveis por este curso.</p>
<?php endif; ?>
<?php if ($tools === []): ?>
    <p class="admin-table-empty">Nenhuma ferramenta cadastrada.</p>
<?php else: ?>
    <form method="POST" action="/admin/courses/<?= $course->getId() ?>/tools" class="admin-course-form" id="courseToolsForm">
        <input type="hidden" name="csrf_token" value="<?= $escape($token) ?>">
        <div class="admin-field" id="courseToolSearchField" hidden>
            <label for="courseToolSearch">Pesquisar ferramentas</label>
            <input type="search" id="courseToolSearch" placeholder="Nome ou identificador" autocomplete="off">
        </div>
        <p class="admin-muted">Ferramentas inativas não liberam acesso. Remover vínculo afeta somente este curso.</p>
        <fieldset class="admin-course-tools">
            <legend>Ferramentas vinculadas</legend>
            <?php foreach ($tools as $tool): ?>
                <label class="admin-course-tool" data-tool-search="<?= $escape($tool['name'] . ' ' . $tool['slug']) ?>">
                    <input type="checkbox" name="tools[]" value="<?= (int) $tool['id'] ?>" <?= $tool['linked'] ? 'checked' : '' ?> data-original-linked="<?= $tool['linked'] ? '1' : '0' ?>">
                    <span class="admin-course-tool-copy">
                        <strong><?= $escape($tool['name']) ?></strong>
                        <span class="admin-muted"><?= $escape($tool['slug']) ?></span>
                        <?php if ($tool['description']): ?><span><?= $escape($tool['description']) ?></span><?php endif; ?>
                    </span>
                    <span class="admin-status <?= $tool['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $tool['is_active'] ? 'Ativa' : 'Inativa' ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p id="courseToolsEmpty" hidden>Nenhuma ferramenta encontrada.</p>
        <p id="courseToolsCount" class="admin-muted" role="status" aria-live="polite"></p>
        <input type="hidden" name="selection_complete" value="1">
        <div class="admin-filter-actions">
            <button type="submit" class="admin-button admin-button-primary">Salvar vínculos</button>
            <a href="/admin/courses/<?= $course->getId() ?>/tools" class="admin-button admin-button-secondary">Cancelar</a>
        </div>
    </form>
<?php endif; ?>
