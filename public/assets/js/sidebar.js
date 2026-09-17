'use strict';

function updateSidebarActiveLink() {
    const path = window.location.pathname;

    document
        .querySelectorAll('[data-sidebar-link]')
        .forEach(link => {
            link.classList.remove('is-active');
        });

    if (
        path === '/portal'
        || path === '/dashboard'
    ) {
        document
            .querySelector('[data-sidebar-link="overview"]')
            ?.classList.add('is-active');

        return;
    }

    if (path.startsWith('/course/')) {
        document
            .querySelector('[data-sidebar-link="courses"]')
            ?.classList.add('is-active');

        return;
    }

    if (path.startsWith('/tool/')) {
        document
            .querySelector('[data-sidebar-link="tools"]')
            ?.classList.add('is-active');
    }

    if (
        path === '/support' ||
        path.startsWith('/support/')
    ) {
        document
            .querySelector('[data-sidebar-link="support"]')
            ?.classList.add('is-active');
    }
}

function updateAdminSidebarActiveLink() {
    const path = window.location.pathname;

    document
        .querySelectorAll('[data-admin-sidebar-link]')
        .forEach(link => {
            link.classList.remove('is-active');
        });

    if (path === '/admin') {
        document
            .querySelector('[data-admin-sidebar-link="overview"]')
            ?.classList.add('is-active');

        return;
    }

    if (
        path === '/admin/users' ||
        path.startsWith('/admin/users/')
    ) {
        document
            .querySelector('[data-admin-sidebar-link="users"]')
            ?.classList.add('is-active');

        return;
    }

    if (
        path === '/admin/courses' ||
        path.startsWith('/admin/courses/')
    ) {
        document
            .querySelector('[data-admin-sidebar-link="courses"]')
            ?.classList.add('is-active');

        return;
    }

    if (
        path === '/admin/tools' ||
        path.startsWith('/admin/tools/')
    ) {
        document
            .querySelector('[data-admin-sidebar-link="tools"]')
            ?.classList.add('is-active');

        return;
    }

    if (
        path === '/admin/access' ||
        path.startsWith('/admin/access/')
    ) {
        document
            .querySelector('[data-admin-sidebar-link="access"]')
            ?.classList.add('is-active');
    }
}

updateAdminSidebarActiveLink();

updateSidebarActiveLink();
