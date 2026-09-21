'use strict';

document.querySelectorAll('form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.hasAttribute('data-confirm-tool-inactive')) {
            const count = form.dataset.courseCount;
            if (!window.confirm(`Inativar esta ferramenta? O bloqueio vale para todos os ${count} curso(s) vinculados.`)) {
                event.preventDefault();
                return;
            }
        }
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Salvando...';
        });
    });
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});
