'use strict';

document.querySelectorAll('form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const active = form.querySelector('[name="active"]');
        const deactivating = active && (active.type === 'checkbox' ? !active.checked : active.value === '0');
        if (form.hasAttribute('data-confirm-inactive') && deactivating
            && !window.confirm('Inativar este curso? Alunos matriculados também perderão acesso enquanto o curso estiver inativo.')) {
            event.preventDefault();
            return;
        }
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Salvando...';
        });
    });
});
