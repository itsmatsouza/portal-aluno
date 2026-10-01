'use strict';

const expiration = document.getElementById('expiration');
const accessDays = document.getElementById('access_days');
function updateExpiration() {
    if (!expiration || !accessDays) return;
    accessDays.required = expiration.value === 'days';
    accessDays.disabled = expiration.value !== 'days';
}
if (expiration) expiration.addEventListener('change', updateExpiration);
updateExpiration();
