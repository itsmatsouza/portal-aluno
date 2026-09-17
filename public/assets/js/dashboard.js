'use strict';

const coursesGrid = document.getElementById('coursesGrid');
const toolsGrid = document.getElementById('toolsGrid');
const coursesCount = document.getElementById('coursesCount');
const toolsCount = document.getElementById('toolsCount');
const coursesCounter = document.getElementById('coursesCounter');
const toolsCounter = document.getElementById('toolsCounter');
const welcomeName = document.getElementById('welcomeName');
const headerUserName = document.getElementById('headerUserName');
const headerAvatar = document.getElementById('headerAvatar');
const portalAlert = document.getElementById('portalAlert');
const emptyState = document.getElementById('emptyState');

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
    portalAlert.textContent = message;
    portalAlert.classList.add('is-visible');
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

function renderCourses(courses) {
    coursesGrid.innerHTML = '';

    if (courses.length === 0) {
        coursesGrid.innerHTML = `
            <div class="content-loading">
                Nenhum curso ativo encontrado.
            </div>
        `;

        return;
    }

    courses.forEach((course, index) => {
        const description = course.description
            || 'Conteúdos e recursos exclusivos deste curso.';

        const tools = Array.isArray(course.tools)
            ? course.tools
            : [];

        const card = document.createElement('article');

        card.className = 'course-card';
        card.style.animationDelay = `${index * 70}ms`;

        card.innerHTML = `
            <div class="card-topline">
                <span class="card-number">
                    ${String(index + 1).padStart(2, '0')}
                </span>

                <span class="card-status">
                    Acesso ativo
                </span>
            </div>

            <h3>${escapeHtml(course.name)}</h3>

            <p>${escapeHtml(description)}</p>

            <div class="course-card-footer">
                <span>
                    ${tools.length}
                    ${tools.length === 1
                        ? 'ferramenta disponível'
                        : 'ferramentas disponíveis'}
                </span>

                <a
                    href="/course/${encodeURIComponent(course.id)}/view"
                    class="card-link"
                >
                    Ver curso

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

        coursesGrid.appendChild(card);
    });
}

function renderTools(courses) {
    const toolsMap = new Map();

    courses.forEach((course) => {
        const tools = Array.isArray(course.tools)
            ? course.tools
            : [];

        tools.forEach((tool) => {
            if (!toolsMap.has(tool.id)) {
                toolsMap.set(tool.id, tool);
            }
        });
    });

    const tools = Array.from(toolsMap.values());

    toolsGrid.innerHTML = '';

    if (tools.length === 0) {
        toolsGrid.innerHTML = `
            <div class="content-loading">
                Nenhuma ferramenta liberada encontrada.
            </div>
        `;

        return;
    }

    tools.forEach((tool, index) => {
        const description = tool.description
            || 'Acesse este recurso exclusivo do Portal ELO.';

        const card = document.createElement('article');

        card.className = 'tool-card';
        card.style.animationDelay = `${index * 70}ms`;

        card.innerHTML = `
            <div class="tool-icon">
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

            <div class="tool-card-footer">
                <span>Recurso exclusivo</span>

                <a
                    href="/tool/${encodeURIComponent(tool.slug)}/view"
                    class="tool-button"
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

        toolsGrid.appendChild(card);
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

    welcomeName.textContent = name.split(' ')[0];
    headerUserName.textContent = name;
    headerAvatar.textContent = getInitial(name);
}

async function loadDashboard() {
    const response = await fetch('/dashboard', {
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

    if (!response.ok) {
        throw new Error('Não foi possível carregar o dashboard.');
    }

    const data = await response.json();
    const courses = Array.isArray(data.courses)
        ? data.courses
        : [];

    const uniqueTools = new Set();

    courses.forEach((course) => {
        const tools = Array.isArray(course.tools)
            ? course.tools
            : [];

        tools.forEach((tool) => {
            uniqueTools.add(tool.id);
        });
    });

    coursesCount.textContent = courses.length;
    toolsCount.textContent = uniqueTools.size;

    coursesCounter.textContent = `${courses.length} ${
        courses.length === 1 ? 'curso' : 'cursos'
    }`;

    toolsCounter.textContent = `${uniqueTools.size} ${
        uniqueTools.size === 1 ? 'ferramenta' : 'ferramentas'
    }`;

    renderCourses(courses);
    renderTools(courses);

    emptyState.hidden = courses.length !== 0;
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

mobileMenuButton.addEventListener(
    'click',
    () => {
        if (sidebar.classList.contains('is-open')) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
);

overlay.addEventListener(
    'click',
    closeMobileMenu
);

document
    .querySelectorAll('.sidebar-nav-link')
    .forEach((link) => {
        link.addEventListener('click', closeMobileMenu);
    });

logoutButton.addEventListener('click', logout);

Promise.all([
    loadUser(),
    loadDashboard()
]).catch((error) => {
    console.error(error);

    showAlert(
        'Não foi possível carregar todos os dados do portal. '
        + 'Atualize a página e tente novamente.'
    );
});
