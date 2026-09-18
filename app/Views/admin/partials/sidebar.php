<?php

declare(strict_types=1);

$currentPath = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

$currentPath = rtrim(
    (string) $currentPath,
    '/'
);

if ($currentPath === '') {
    $currentPath = '/';
}

$isDashboardActive = $currentPath === '/admin';
$isUsersActive = $currentPath === '/admin/users'
    || str_starts_with($currentPath, '/admin/users/');
$isCoursesActive = $currentPath === '/admin/courses'
    || str_starts_with($currentPath, '/admin/courses/');
$isToolsActive = $currentPath === '/admin/tools';
$isAccessActive = $currentPath === '/admin/access';

?>

<aside
    class="portal-sidebar"
    id="portalSidebar"
>
    <div class="sidebar-top">
        <a
            href="/admin"
            class="sidebar-logo-link"
        >
            <img
                src="/assets/images/logo-elo-horizontal.jpg"
                alt="Portal ELO"
                class="sidebar-logo"
            >
        </a>

        <div class="sidebar-divider"></div>

        <nav class="sidebar-nav">

            <a
              href="/admin"
              class="sidebar-nav-link<?= $isDashboardActive ? ' is-active' : '' ?>"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <rect
                        x="4"
                        y="4"
                        width="6"
                        height="6"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <rect
                        x="14"
                        y="4"
                        width="6"
                        height="6"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <rect
                        x="4"
                        y="14"
                        width="6"
                        height="6"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <rect
                        x="14"
                        y="14"
                        width="6"
                        height="6"
                        rx="1"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                </svg>

                Visão geral
            </a>

            <a
                href="/admin/users"
                class="sidebar-nav-link<?= $isUsersActive ? ' is-active' : '' ?>"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-3A4.5 4.5 0 0 0 4 18.5V20"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                    <circle
                        cx="10"
                        cy="7"
                        r="3"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <path
                        d="M16 4.5a3 3 0 0 1 0 5.8M17 14.2a4.5 4.5 0 0 1 3 4.3V20"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

                Usuários
            </a>

            <a
                href="/admin/courses"
                class="sidebar-nav-link<?= $isCoursesActive ? ' is-active' : '' ?>"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

                Cursos
            </a>

            <a
                href="/admin/tools"
                class="sidebar-nav-link<?= $isToolsActive ? ' is-active' : '' ?>"
            >
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

                Ferramentas
            </a>

            <a
                href="/admin/access"
                class="sidebar-nav-link<?= $isAccessActive ? ' is-active' : '' ?>"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z"
                        stroke="currentColor"
                        stroke-width="1.7"
                    />
                    <path
                        d="M8 12h8M12 8v8"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                    />
                </svg>

                Acessos
            </a>

        </nav>
    </div>

    <div class="sidebar-bottom">

        <div class="sidebar-support">
            <span class="support-dot"></span>

            <div>
                <strong>Área administrativa</strong>
                <span>Controle do portal</span>
            </div>
        </div>

        <button
            type="button"
            class="sidebar-logout"
            id="logoutButton"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                />
                <path
                    d="M14 8l4 4-4 4M9 12h9"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            Sair
        </button>

    </div>
</aside>

<div
    class="portal-overlay"
    id="portalOverlay"
></div>
