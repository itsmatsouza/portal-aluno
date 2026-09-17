'use strict';

const loginForm = document.getElementById('loginForm');
const loginButton = document.getElementById('loginButton');
const loginAlert = document.getElementById('loginAlert');
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const successOverlay = document.getElementById('loginSuccessOverlay');

function showAlert(message) {
    loginAlert.textContent = message;
    loginAlert.classList.add('is-visible');
}

function hideAlert() {
    loginAlert.textContent = '';
    loginAlert.classList.remove('is-visible');
}

function clearFieldErrors() {
    document
        .querySelectorAll('.form-field.has-error')
        .forEach((field) => {
            field.classList.remove('has-error');
        });

    document
        .querySelectorAll('.field-error')
        .forEach((error) => {
            error.textContent = '';
        });
}

function showFieldError(fieldName, message) {
    const input = document.getElementById(fieldName);
    const field = input.closest('.form-field');
    const error = document.querySelector(
        `[data-error-for="${fieldName}"]`
    );

    field.classList.add('has-error');
    error.textContent = message;
}

function setLoading(isLoading) {
    loginButton.classList.toggle('is-loading', isLoading);
    loginButton.disabled = isLoading;
}

function showSuccessAnimation(redirectUrl) {
    successOverlay.classList.add('is-visible');
    successOverlay.setAttribute('aria-hidden', 'false');

    window.setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1350);

}

togglePassword.addEventListener('click', () => {
    const isPassword = passwordInput.type === 'password';

    passwordInput.type = isPassword
        ? 'text'
        : 'password';

    togglePassword.setAttribute(
        'aria-label',
        isPassword
            ? 'Ocultar senha'
            : 'Mostrar senha'
    );
});

loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    clearFieldErrors();
    hideAlert();

    const email = document
        .getElementById('email')
        .value
        .trim();

    const password = passwordInput.value;

    let hasError = false;

    if (email === '') {
        showFieldError(
            'email',
            'Informe seu e-mail.'
        );

        hasError = true;
    }

    if (password === '') {
        showFieldError(
            'password',
            'Informe sua senha.'
        );

        hasError = true;
    }

    if (hasError) {
        return;
    }

    setLoading(true);

    try {
        const response = await fetch('/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                email,
                password
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            showAlert(
                data.message
                || 'Não foi possível realizar o login.'
            );

            setLoading(false);
            return;
        }

        const redirectUrl = data.redirect || '/portal';

        showSuccessAnimation(redirectUrl);

    } catch (error) {
        showAlert(
            'Não foi possível conectar ao servidor. '
            + 'Tente novamente.'
        );

        setLoading(false);
    }
});

