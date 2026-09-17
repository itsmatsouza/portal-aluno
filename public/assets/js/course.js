'use strict';

const courseName = document.getElementById('courseName');
const courseDescription = document.getElementById('courseDescription');
const courseToolsSummary = document.getElementById('courseToolsSummary');
const courseToolsGrid = document.getElementById('courseToolsGrid');
const courseAlert = document.getElementById('courseAlert');

const headerUserName = document.getElementById('headerUserName');
const headerAvatar = document.getElementById('headerAvatar');

const sidebar = document.getElementById('portalSidebar');
const overlay = document.getElementById('portalOverlay');
const mobileMenuButton = document.getElementById('mobileMenuButton');
const logoutButton = document.getElementById('logoutButton');

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showAlert(message) {
    courseAlert.textContent = message;
    courseAlert.classList.add('is-visible');
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

function getCourseId() {
    const parts = window.location.pathname
        .split('/')
        .filter(Boolean);

    return parts[1] || '';
}

function renderTools(tools) {
    courseToolsGrid.innerHTML = '';

    if (tools.length === 0) {
        courseToolsGrid.innerHTML = `
            <div class="course-empty">
                Nenhuma ferramenta está disponível neste curso.
            </div>
        `;

        return;
    }

    tools.forEach((tool, index) => {
        const description = tool.description
            || 'Recurso exclusivo disponível para este curso.';

        const card = document.createElement('article');

        card.className = 'course-tool-card';
        card.style.animationDelay = `${index * 70}ms`;

        card.innerHTML = `
            <div class="course-tool-icon">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="m14.5 6.5 3-3 3 3-3 3"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="m17.5 6.5-7 7"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                    <path
                        d="M14 14 6.5 21.5H3v-3.5L10.5 10"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>

            <h3>${escapeHtml(tool.name)}</h3>

            <p>${escapeHtml(description)}</p>

            <div class="course-tool-footer">
                <span>Recurso exclusivo</span>

                <a
                    href="/tool/${encodeURIComponent(tool.slug)}/view"
                    class="course-tool-button"
                >
                    Acessar

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M5 12h13M13 6l6 6-6 6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </a>
            </div>
        `;

        courseToolsGrid.appendChild(card);
    });
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

async function loadCourse() {
    const courseId = getCourseId();

    if (!courseId) {
        throw new Error('Curso não informado.');
    }

    const response = await fetch(`/course/${encodeURIComponent(courseId)}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    });

    if (response.status === 401) {
        window.location.href = '/login';
        return;
    }

    if (response.status === 403) {
        throw new Error('Você não possui acesso a este curso.');
    }

    if (!response.ok) {
        throw new Error('Não foi possível carregar o curso.');
    }

    const data = await response.json();
    const course = data.course || data.data || data;

    const tools = Array.isArray(course.tools)
        ? course.tools
        : [];

    courseName.textContent = course.name || 'Curso';
    courseDescription.textContent = course.description
        || 'Conteúdos e recursos exclusivos deste curso.';

    courseToolsSummary.textContent = tools.length === 1
        ? '1 ferramenta disponível'
        : `${tools.length} ferramentas disponíveis`;

    renderTools(tools);
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
            loadCourse()
        ]);
    } catch (error) {
        courseName.textContent = 'Não foi possível carregar o curso.';
        courseDescription.textContent = '';
        courseToolsSummary.textContent = '';
        courseToolsGrid.innerHTML = '';

        showAlert(error.message);
    }
});
