/* Interaksi antarmuka dengan JavaScript biasa. Form tetap bekerja tanpa JS. */
(function () {
    'use strict';
    document.documentElement.classList.add('js');
    var body = document.body;
    var errorMessage = document.querySelector('.pesan-galat');
    if (errorMessage) { errorMessage.tabIndex = -1; errorMessage.focus(); }
    var nav = document.getElementById('navigasi-utama');
    var navToggle = document.querySelector('[data-nav-toggle]');
    var navScrim = document.querySelector('.nav-scrim');
    var navMedia = window.matchMedia('(max-width: 760px)');
    var panel = document.getElementById('panel-atur');
    var settingsToggle = document.querySelector('[data-settings-toggle]');
    var settingsScrim = document.querySelector('.settings-scrim');
    var settingsMedia = window.matchMedia('(max-width: 1060px)');
    var activeOverlay = null;
    var returnFocus = null;
    var isolated = [];
    var panelSizingFrame = 0;

    function fitSettingsPanel() {
        if (!panel) return;
        if (settingsMedia.matches) {
            panel.style.removeProperty('max-height');
            return;
        }
        var top = Math.max(16, panel.getBoundingClientRect().top);
        panel.style.maxHeight = Math.max(180, Math.floor(window.innerHeight - top - 16)) + 'px';
    }
    function schedulePanelSizing() {
        if (!panel || panelSizingFrame) return;
        panelSizingFrame = window.requestAnimationFrame(function () {
            panelSizingFrame = 0;
            fitSettingsPanel();
        });
    }
    window.addEventListener('scroll', schedulePanelSizing, { passive: true });
    window.addEventListener('resize', schedulePanelSizing);
    window.addEventListener('load', fitSettingsPanel);
    fitSettingsPanel();

    function isolateBackground(target, scrim) {
        var node = target;
        while (node.parentElement && node !== body) {
            Array.from(node.parentElement.children).forEach(function (sibling) {
                if (sibling !== node && sibling !== scrim && !['SCRIPT', 'STYLE'].includes(sibling.tagName)) {
                    isolated.push({ element: sibling, inert: sibling.inert });
                    sibling.inert = true;
                }
            });
            node = node.parentElement;
        }
    }

    function closeOverlay(restore) {
        if (!activeOverlay) return;
        isolated.forEach(function (entry) { entry.element.inert = entry.inert; });
        isolated = [];
        var isNav = activeOverlay === nav;
        activeOverlay.classList.remove(isNav ? 'is-open' : 'panel-open');
        activeOverlay.removeAttribute('role');
        activeOverlay.removeAttribute('aria-modal');
        var toggle = isNav ? navToggle : settingsToggle;
        var scrim = isNav ? navScrim : settingsScrim;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        if (scrim) scrim.hidden = true;
        if (isNav && navMedia.matches) nav.inert = true;
        body.style.overflow = '';
        activeOverlay = null;
        if (nav) nav.inert = navMedia.matches && !nav.classList.contains('is-open');
        if (restore !== false && returnFocus) returnFocus.focus();
    }

    function openOverlay(target, toggle, scrim, isNav) {
        if (!target) return;
        if (activeOverlay === target) { closeOverlay(); return; }
        closeOverlay(false);
        returnFocus = toggle;
        activeOverlay = target;
        target.inert = false;
        target.classList.add(isNav ? 'is-open' : 'panel-open');
        target.setAttribute('role', 'dialog');
        target.setAttribute('aria-modal', 'true');
        if (isNav) target.setAttribute('aria-label', 'Navigasi utama');
        toggle.setAttribute('aria-expanded', 'true');
        scrim.hidden = false;
        body.style.overflow = 'hidden';
        isolateBackground(target, scrim);
        var first = target.querySelector(isNav ? '[data-nav-close]' : '[data-settings-close]');
        if (first) first.focus();
    }

    if (navToggle) navToggle.addEventListener('click', function () { openOverlay(nav, navToggle, navScrim, true); });
    if (settingsToggle) settingsToggle.addEventListener('click', function () { openOverlay(panel, settingsToggle, settingsScrim, false); });
    document.querySelectorAll('[data-nav-close], [data-settings-close]').forEach(function (el) { el.addEventListener('click', function () { closeOverlay(); }); });
    function syncNav() {
        if (nav) nav.inert = Boolean(activeOverlay && activeOverlay !== nav) || (navMedia.matches && !nav.classList.contains('is-open'));
        if (activeOverlay === nav && !navMedia.matches) closeOverlay(false);
    }
    navMedia.addEventListener('change', syncNav);
    settingsMedia.addEventListener('change', function () { if (activeOverlay === panel && !settingsMedia.matches) closeOverlay(false); });
    syncNav();

    document.addEventListener('keydown', function (event) {
        if (activeOverlay) {
            if (event.key === 'Escape') { event.preventDefault(); closeOverlay(); return; }
            if (event.key === 'Tab') {
                var focusable = Array.from(activeOverlay.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, summary')).filter(function (el) { return el.getClientRects().length > 0; });
                var first = focusable[0]; var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        } else if (event.key === 'Escape') {
            document.querySelectorAll('.workspace-actions details[open]').forEach(function (el) { el.open = false; el.querySelector('summary').focus(); });
        }
    });
    document.addEventListener('click', function (event) {
        document.querySelectorAll('.workspace-actions details[open]').forEach(function (el) { if (!el.contains(event.target)) el.open = false; });
    });

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.dataset.passwordToggle);
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
        });
    });
    document.querySelectorAll('form[data-guard-unsaved]').forEach(function (form) {
        var changed = false;
        form.addEventListener('input', function () { changed = true; });
        form.addEventListener('change', function () { changed = true; });
        form.addEventListener('submit', function () { changed = false; });
        window.addEventListener('beforeunload', function (event) {
            if (changed) { event.preventDefault(); event.returnValue = ''; }
        });
    });
    var editor = document.getElementById('isi_teks');
    var wordCount = document.getElementById('editor-count');
    if (editor && wordCount) {
        function updateCount() { var text = editor.value.trim(); wordCount.textContent = (text ? text.split(/\s+/u).length : 0) + ' kata'; }
        editor.addEventListener('input', updateCount);
        updateCount();
    }
    var composer = document.querySelector('details#tulis');
    function openComposerFromHash() {
        if (composer && window.location.hash === '#tulis') composer.open = true;
    }
    openComposerFromHash();
    window.addEventListener('hashchange', openComposerFromHash);

    var confirmDialog = document.getElementById('confirm-dialog');
    var pendingForm = null;
    var approvedForm = null;
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (approvedForm === form) { approvedForm = null; return; }
            if (confirmDialog && typeof confirmDialog.showModal === 'function') {
                event.preventDefault(); pendingForm = form;
                document.getElementById('confirm-message').textContent = form.dataset.confirm;
                confirmDialog.returnValue = '';
                confirmDialog.showModal();
            } else if (!window.confirm(form.dataset.confirm)) { event.preventDefault(); }
        });
    });
    if (confirmDialog) confirmDialog.addEventListener('close', function () {
        if (confirmDialog.returnValue === 'hapus' && pendingForm) { approvedForm = pendingForm; pendingForm.requestSubmit(); }
        pendingForm = null;
    });
})();
