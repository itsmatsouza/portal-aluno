'use strict';

document.querySelectorAll('form[method="POST"]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const removedTools = [...form.querySelectorAll('[data-original-linked="1"]')]
            .some((checkbox) => !checkbox.checked);
        if (removedTools && !window.confirm('Remover os vínculos desmarcados? Alunos deste curso perderão acesso a essas ferramentas, salvo quando outro curso também conceder acesso.')) {
            event.preventDefault();
            return;
        }
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

const courseToolsForm = document.getElementById('courseToolsForm');
if (courseToolsForm) {
    const search = document.getElementById('courseToolSearch');
    const rows = [...courseToolsForm.querySelectorAll('[data-tool-search]')];
    const normalize = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    const updateTools = () => {
        const query = normalize(search.value.trim());
        let visible = 0;
        rows.forEach((row) => {
            row.hidden = !normalize(row.dataset.toolSearch).includes(query);
            if (!row.hidden) visible++;
        });
        const selected = courseToolsForm.querySelectorAll('[name="tools[]"]:checked').length;
        document.getElementById('courseToolsCount').textContent = `${selected} selecionada(s) no total. ${visible} ferramenta(s) encontrada(s).`;
        document.getElementById('courseToolsEmpty').hidden = visible !== 0;
    };
    document.getElementById('courseToolSearchField').hidden = false;
    search.addEventListener('input', updateTools);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
    });
    courseToolsForm.addEventListener('change', updateTools);
    updateTools();
}
