<?php

declare(strict_types=1);

$fields = [
    'Nome' => $detailUser->getName(),
    'E-mail' => $detailUser->getEmail(),
    'Perfil' => adminUsersRoleLabel($detailUser->getRole()),
    'Status da conta' => adminUsersStatusLabel($detailUser->isActive()),
    'Identificador Hotmart' => $detailUser->getHotmartBuyerId() ?? 'Não vinculado',
    'E-mail Hotmart' => $detailUser->getHotmartEmail() ?? 'Não informado',
    'Último acesso' => adminUsersFormatDate($detailUser->getLastLoginAt()),
    'Cadastro' => adminUsersFormatDate($detailUser->getCreatedAt()),
    'Atualização' => adminUsersFormatDate($detailUser->getUpdatedAt()),
];
$statusLabels = [
    'ACTIVE' => 'Ativa',
    'CANCELLED' => 'Cancelada',
    'REFUNDED' => 'Reembolsada',
    'CHARGEBACK' => 'Contestada',
    'EXPIRED' => 'Expirada',
    'SUSPENDED' => 'Suspensa',
];
?>
<a href="/admin/users" class="admin-table-link">Voltar para usuários</a>

<section class="content-section">
    <div class="section-heading"><h2>Dados cadastrais</h2></div>
    <dl class="admin-user-details">
        <?php foreach ($fields as $label => $value): ?>
            <div>
                <dt><?= adminUsersEscape($label) ?></dt>
                <dd><?= adminUsersEscape($value) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>

<section class="content-section">
    <div class="section-heading"><h2>Matrículas</h2></div>
    <div class="admin-table-wrapper" tabindex="0" role="region" aria-label="Matrículas do usuário">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col">Curso</th>
                    <th scope="col">Situação da matrícula</th>
                    <th scope="col">Transação Hotmart</th>
                    <th scope="col">Compra</th>
                    <th scope="col">Vencimento</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($enrollments === []): ?>
                    <tr><td colspan="5" class="admin-table-empty">Nenhuma matrícula encontrada.</td></tr>
                <?php endif; ?>
                <?php foreach ($enrollments as $enrollment): ?>
                    <tr>
                        <td><?= adminUsersEscape($enrollment['course_name'] ?? 'Curso indisponível') ?></td>
                        <td><?= adminUsersEscape($statusLabels[$enrollment['status']] ?? $enrollment['status']) ?></td>
                        <td><?= adminUsersEscape($enrollment['hotmart_transaction_id'] ?? 'Não informada') ?></td>
                        <td><?= $enrollment['purchased_at'] ? adminUsersFormatDate($enrollment['purchased_at']) : 'Não informada' ?></td>
                        <td><?= $enrollment['access_expires_at'] ? adminUsersFormatDate($enrollment['access_expires_at']) : 'Sem vencimento' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
