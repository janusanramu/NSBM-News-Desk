// header.js - dropdowns + mobile menu for NSBM NewsDesk
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = document.querySelectorAll('.dropdown');
    const navToggle = document.getElementById('navToggle');
    const mainNav   = document.getElementById('mainNav');

    // Toggle dropdowns (Categories + user menu)
    dropdowns.forEach(function (dd) {
        const trigger = dd.querySelector('.dropdown-toggle, .user-toggle');
        if (!trigger) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dd.classList.contains('open');
            dropdowns.forEach(d => d.classList.remove('open'));
            if (!isOpen) dd.classList.add('open');
        });
    });

    // Close dropdowns on outside click or Escape
    document.addEventListener('click', function () {
        dropdowns.forEach(d => d.classList.remove('open'));
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdowns.forEach(d => d.classList.remove('open'));
    });

    // Mobile menu
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('open');
        });
    }

    // Clear the notification dot when the bell is clicked
    const bell = document.querySelector('.icon-btn');
    if (bell) {
        bell.addEventListener('click', function () {
            const dot = bell.querySelector('.notif-dot');
            if (dot) dot.remove();
        });
    }
});
