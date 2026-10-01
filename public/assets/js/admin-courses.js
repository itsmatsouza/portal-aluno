'use strict';

document.querySelectorAll('form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Salvando...';
        });
    });
});
