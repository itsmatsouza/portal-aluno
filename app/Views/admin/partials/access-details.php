<?php

declare(strict_types=1);

$userState = $enrollment['user_name'] === null ? 'Indisponível'
    : ($enrollment['user_deleted_at'] !== null ? 'Excluída' : ($enrollment['user_active'] ? 'Ativa' : 'Inativa'));
$courseState = $enrollment['course_name'] === null ? 'Indisponível'
    : ($enrollment['course_deleted_at'] !== null ? 'Excluído' : ($enrollment['course_active'] ? 'Ativo' : 'Inativo'));
$fields = [
    'Número da matrícula' => (string) $enrollment['id'],
    'Aluno' => $enrollment['user_name'] ?? 'Usuário indisponível',
    'E-mail' => $enrollment['user_email'] ?? 'Não informado',
    'Situação da conta' => $userState,
    'Curso' => $enrollment['course_name'] ?? 'Curso indisponível',
    'Situação do curso' => $courseState,
    'Situação da matrícula' => $statusLabels[$enrollment['status']] ?? $enrollment['status'],
    'Transação Hotmart' => $enrollment['hotmart_transaction_id'] ?? 'Não informada',
    'Compra' => $formatDate($enrollment['purchased_at']),
    'Vencimento' => $formatDate($enrollment['access_expires_at'], 'Sem vencimento'),
    'Cadastro' => $formatDate($enrollment['created_at']),
    'Atualização' => $formatDate($enrollment['updated_at']),
];
?>
<a href="/admin/access" class="admin-table-link">Voltar para acessos</a>
<section class="content-section admin-access-details">
    <div class="section-heading"><h2>Matrícula #<?= (int) $enrollment['id'] ?></h2></div>
    <dl class="admin-user-details">
        <?php foreach ($fields as $label => $value): ?>
            <div><dt><?= $escape($label) ?></dt><dd><?= $escape($value) ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <p class="admin-muted">Matrícula ativa não libera acesso quando a conta ou o curso está inativo ou excluído.</p>
    <div class="admin-access-links">
        <?php if ($enrollment['user_name'] !== null && $enrollment['user_deleted_at'] === null): ?>
            <a href="/admin/users/<?= (int) $enrollment['user_id'] ?>" class="admin-button admin-button-secondary">Ver aluno</a>
        <?php endif; ?>
        <?php if ($enrollment['course_name'] !== null && $enrollment['course_deleted_at'] === null): ?>
            <a href="/admin/courses/<?= (int) $enrollment['course_id'] ?>/edit" class="admin-button admin-button-secondary">Ver curso</a>
            <a href="/admin/courses/<?= (int) $enrollment['course_id'] ?>/tools" class="admin-button admin-button-secondary">Ferramentas do curso</a>
        <?php endif; ?>
    </div>
</section>
