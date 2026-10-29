'use strict';

const toolName = document.getElementById('toolName');
const toolDescription = document.getElementById('toolDescription');
const toolAlert = document.getElementById('toolAlert');
const toolFrame = document.getElementById('toolFrame');
const toolFrameLoading = document.getElementById('toolFrameLoading');

const headerUserName = document.getElementById('headerUserName');
const headerAvatar = document.getElementById('headerAvatar');

const sidebar = document.getElementById('portalSidebar');
const overlay = document.getElementById('portalOverlay');
const mobileMenuButton = document.getElementById('mobileMenuButton');
const logoutButton = document.getElementById('logoutButton');

function showAlert(message) {
    toolAlert.textContent = message;
    toolAlert.classList.add('is-visible');
}

function getInitial(name) {
    const normalized = String(name ?? '').trim();

    return normalized !== ''
        ? normalized.charAt(0).toUpperCase()
        : 'A';
}

function openMobileMenu() {
    sidebar.classList.add('is-open');
    overlay.classList.add('is-visible');

    mobileMenuButton.setAttribute(
        'aria-expanded',
        'true'
    );
}

function closeMobileMenu() {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-visible');

    mobileMenuButton.setAttribute(
        'aria-expanded',
        'false'
    );
}

function getToolSlug() {
    const configuredSlug = window.TOOL_SLUG;

    if (
        typeof configuredSlug === 'string'
        && configuredSlug.trim() !== ''
    ) {
        return configuredSlug.trim();
    }

    const parts = window.location.pathname
        .split('/')
        .filter(Boolean);

    return parts[1] || '';
}

async function loadUser() {
    const response = await fetch('/me', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    });

    if (!response.ok) {
        throw new Error('Não foi possível carregar o usuário.');
    }

    const data = await response.json();
    const user = data.user || data.data || data;

    const name = user.name || 'Aluno';

    headerUserName.textContent = name;
    headerAvatar.textContent = getInitial(name);
}

async function loadTool() {
    const slug = getToolSlug();

    if (!slug) {
        throw new Error('Ferramenta não informada.');
    }

    const response = await fetch(
        `/tool/${encodeURIComponent(slug)}`,
        {
            method: 'GET',
            headers: {
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        }
    );

    if (response.status === 401) {
        window.location.href = '/login';
        return;
    }

    if (response.status === 403) {
        throw new Error(
            'Você não possui acesso a esta ferramenta.'
        );
    }

    if (!response.ok) {
        throw new Error(
            'Não foi possível carregar a ferramenta.'
        );
    }

    const html = await response.text();

    const parser = new DOMParser();
    const documentHtml = parser.parseFromString(
        html,
        'text/html'
    );

    const title = documentHtml.querySelector('title');
    const heading = documentHtml.querySelector('h1');
    const lead = documentHtml.querySelector('.lead');

    toolName.textContent = heading?.textContent?.trim()
        || title?.textContent?.trim()
        || 'Ferramenta exclusiva';

    toolDescription.textContent = lead?.textContent?.trim()
        || 'Recurso exclusivo disponível para seu curso.';

    toolFrame.src = `/tool/${encodeURIComponent(slug)}`;

    toolFrame.addEventListener('load', () => {
        toolFrameLoading.classList.add('is-hidden');
    }, { once: true });
}

async function logout() {
    logoutButton.disabled = true;

    try {
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });
    } finally {
        window.location.href = '/login';
    }
}

mobileMenuButton?.addEventListener('click', () => {
    const isOpen = sidebar.classList.contains('is-open');

    if (isOpen) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
});

overlay?.addEventListener('click', closeMobileMenu);
logoutButton?.addEventListener('click', logout);

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await Promise.all([
            loadUser(),
            loadTool()
        ]);
    } catch (error) {
        toolName.textContent = 'Não foi possível carregar a ferramenta.';
        toolDescription.textContent = '';
        showAlert(error.message);
        toolFrameLoading.classList.add('is-hidden');
    }
});


const toolContentCard = document.getElementById('toolContentCard');
const toolFullscreenButton = document.getElementById('toolFullscreenButton');

function updateFullscreenButton() {
    const expanded = document.fullscreenElement === toolContentCard
        || toolContentCard.classList.contains('is-expanded');
    toolFullscreenButton.textContent = expanded ? 'Sair da tela cheia' : 'Tela cheia';
    toolFullscreenButton.setAttribute('aria-pressed', String(expanded));
}

function closeExpandedTool() {
    toolContentCard.classList.remove('is-expanded');
    document.body.classList.remove('tool-expanded');
    updateFullscreenButton();
    toolFullscreenButton.focus();
}

toolFullscreenButton.addEventListener('click', async () => {
    try {
        if (document.fullscreenElement === toolContentCard) {
            await document.exitFullscreen();
        } else if (toolContentCard.classList.contains('is-expanded')) {
            closeExpandedTool();
        } else if (document.fullscreenEnabled && toolContentCard.requestFullscreen) {
            await toolContentCard.requestFullscreen();
        } else {
            toolContentCard.classList.add('is-expanded');
            document.body.classList.add('tool-expanded');
        }
        updateFullscreenButton();
    } catch {
        showAlert('Não foi possível abrir em tela cheia. Tente novamente.');
    }
});

document.addEventListener('fullscreenchange', updateFullscreenButton);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toolContentCard.classList.contains('is-expanded')) {
        closeExpandedTool();
    }
});
