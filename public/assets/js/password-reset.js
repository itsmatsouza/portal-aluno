'use strict';

const token = window.PASSWORD_RESET_TOKEN || '';

function clearErrors(form) {
    form.querySelectorAll('.reset-field.has-error')
        .forEach((field) => {
            field.classList.remove('has-error');
        });

    form.querySelectorAll('.reset-field-error')
        .forEach((error) => {
            error.textContent = '';
        });
}

function showFieldError(form, fieldName, message) {
    const input = form.querySelector(`#${fieldName}`);
    const field = input.closest('.reset-field');
    const error = form.querySelector(
        `[data-error-for="${fieldName}"]`
    );

    field.classList.add('has-error');
    error.textContent = message;
}

function showAlert(element, message) {
    element.textContent = message;
    element.classList.add('is-visible');
}

function hideAlert(element) {
    element.textContent = '';
    element.classList.remove('is-visible');
}

function setLoading(button, loading) {
    button.classList.toggle('is-loading', loading);
    button.disabled = loading;
}

document
    .querySelectorAll('.reset-toggle-password')
    .forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(
                button.dataset.target
            );

            const showing = input.type === 'password';

            input.type = showing
                ? 'text'
                : 'password';

            button.setAttribute(
                'aria-label',
                showing
                    ? 'Ocultar senha'
                    : 'Mostrar senha'
            );
        });
    });

const forgotForm = document.getElementById(
    'forgotPasswordForm'
);

if (forgotForm) {
    const button = document.getElementById('forgotButton');
    const alert = document.getElementById('forgotAlert');
    const success = document.getElementById('forgotSuccess');

    forgotForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearErrors(forgotForm);
        hideAlert(alert);
        success.classList.remove('is-visible');

        const email = document
            .getElementById('forgotEmail')
            .value
            .trim();

        if (email === '') {
            showFieldError(
                forgotForm,
                'forgotEmail',
                'Informe seu e-mail.'
            );

            return;
        }

        setLoading(button, true);

        try {
            const response = await fetch(
                '/password/forgot',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email })
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                showAlert(
                    alert,
                    data.message
                    || 'Não foi possível processar a solicitação.'
                );

                setLoading(button, false);
                return;
            }

            success.classList.add('is-visible');
            forgotForm.reset();

            setLoading(button, false);

        } catch (error) {
            showAlert(
                alert,
                'Não foi possível conectar ao servidor. '
                + 'Tente novamente.'
            );

            setLoading(button, false);
        }
    });
}

const resetForm = document.getElementById(
    'resetPasswordForm'
);

if (resetForm) {
    const button = document.getElementById('resetButton');
    const alert = document.getElementById('resetAlert');

    resetForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        clearErrors(resetForm);
        hideAlert(alert);

        const newPassword = document
            .getElementById('newPassword')
            .value;

        const confirmPassword = document
            .getElementById('confirmPassword')
            .value;

        let hasError = false;

        if (token === '') {
            showAlert(
                alert,
                'O link de recuperação é inválido ou está incompleto.'
            );

            return;
        }

        if (newPassword.length < 8) {
            showFieldError(
                resetForm,
                'newPassword',
                'A senha deve possuir pelo menos 8 caracteres.'
            );

            hasError = true;
        }

        if (confirmPassword === '') {
            showFieldError(
                resetForm,
                'confirmPassword',
                'Confirme sua nova senha.'
            );

            hasError = true;
        } else if (newPassword !== confirmPassword) {
            showFieldError(
                resetForm,
                'confirmPassword',
                'As senhas não coincidem.'
            );

            hasError = true;
        }

        if (hasError) {
            return;
        }

        setLoading(button, true);

        try {
            const response = await fetch(
                '/password/reset',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        token,
                        password: newPassword
                    })
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                showAlert(
                    alert,
                    data.message
                    || 'Não foi possível redefinir a senha.'
                );

                setLoading(button, false);
                return;
            }

            window.location.href = '/login';

        } catch (error) {
            showAlert(
                alert,
                'Não foi possível conectar ao servidor. '
                + 'Tente novamente.'
            );

            setLoading(button, false);
        }
    });
}
