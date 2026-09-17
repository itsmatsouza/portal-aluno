'use strict';

const sidebar = document.getElementById('portalSidebar');
const overlay = document.getElementById('portalOverlay');
const mobileMenuButton = document.getElementById('mobileMenuButton');
const logoutButton = document.getElementById('logoutButton');

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

logoutButton.addEventListener(
    'click',
    logout
);