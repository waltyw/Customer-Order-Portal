'use strict';

// Auto-dismiss flash alerts after 6 seconds
document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});

// Confirm before destructive form submissions
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('submit', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

// Highlight current nav item (already handled server-side, but fix edge cases)
const path = window.location.pathname;
document.querySelectorAll('.nav-item').forEach(link => {
    if (link.getAttribute('href') === path) {
        link.classList.add('active');
    }
});
