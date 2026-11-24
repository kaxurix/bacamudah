/* Pengaturan dan alat bantu pembaca, tanpa framework. */
(function () {
    'use strict';
    var body = document.body;
    var paragraphs = Array.from(document.querySelectorAll('.isi-bacaan p'));
    var focusButton = document.getElementById('btn-fokus');
    var readButton = document.getElementById('btn-baca');
    var stopButton = document.getElementById('btn-stop-reading');
    var toolbar = document.querySelector('.reader-topline');
    var current = 0;
    var reading = false;
    var speechGeneration = 0;
    var speechSupported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
    var status = document.getElementById('settings-status');
    var info = document.getElementById('info-suara');
    var size = document.getElementById('ukuran');
    var spacing = document.getElementById('jarak_huruf');
    var line = document.getElementById('tinggi_baris');
    var font = document.getElementById('font');
    var themeInputs = document.querySelectorAll('input[name="tema"]');
    var previous = document.getElementById('focus-prev');
    var next = document.getElementById('focus-next');
    var presets = { lapang: { size: 22, spacing: 0.08, line: 2 }, besar: { size: 28, spacing: 0.06, line: 1.9 } };
    var settingsForm = document.getElementById('form-atur');
    function settingsSignature() { return JSON.stringify(Array.from(new FormData(settingsForm).entries())); }
    var savedSettings = settingsSignature();

    settingsForm.addEventListener('submit', async function (event) {
        if (!window.fetch) return;
        event.preventDefault();
        var submit = settingsForm.querySelector('[type="submit"]');
        if (submit.disabled) return;
        var signature = settingsSignature();
        var controller = new AbortController();
        var timeout = window.setTimeout(function () { controller.abort(); }, 10000);
        submit.disabled = true;
        settingsForm.setAttribute('aria-busy', 'true');
        status.textContent = 'Menyimpan pengaturan…';
        status.classList.remove('is-error');
        var failureMessage = 'Pengaturan belum berhasil disimpan. Periksa koneksi atau muat ulang halaman jika sesi berakhir, lalu coba lagi.';
        try {
            var response = await fetch(settingsForm.action, {
                method: 'POST', body: new FormData(settingsForm),
                headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal
            });
            var result = await response.json();
            if (response.status === 401 || response.status === 400) failureMessage = result.pesan || failureMessage;
            if (!response.ok || !result.sukses) throw new Error(result.pesan || 'Pengaturan belum berhasil disimpan.');
            savedSettings = signature;
            status.textContent = signature === settingsSignature() ? result.pesan : 'Pengaturan tersimpan. Ada perubahan baru yang belum disimpan.';
        } catch (error) {
            status.classList.add('is-error');
            status.textContent = failureMessage;
        } finally {
            window.clearTimeout(timeout);
            submit.disabled = false;
            settingsForm.removeAttribute('aria-busy');
        }
    });
    window.addEventListener('beforeunload', function (event) {
        if (settingsSignature() !== savedSettings) { event.preventDefault(); event.returnValue = ''; }
    });

    function number(value, decimals) { return Number(value).toFixed(decimals).replace('.', ','); }
    function markChanged() { if (status) status.textContent = 'Ada perubahan yang belum disimpan.'; }
    function applySettings() {
        body.style.setProperty('--size', size.value + 'px');
        body.style.setProperty('--spacing', spacing.value + 'em');
        body.style.setProperty('--line', line.value);
        document.getElementById('out-ukuran').textContent = size.value + ' px';
        document.getElementById('out-jarak').textContent = number(spacing.value, 2) + ' em';
        document.getElementById('out-baris').textContent = number(line.value, 1);
        document.querySelectorAll('[data-reading-preset]').forEach(function (button) {
            var preset = presets[button.dataset.readingPreset];
            button.setAttribute('aria-pressed', String(Number(size.value) === preset.size && Number(spacing.value) === preset.spacing && Number(line.value) === preset.line));
        });
    }
    document.querySelectorAll('[data-reading-preset]').forEach(function (button) { button.addEventListener('click', function () {
        var preset = presets[button.dataset.readingPreset];
        size.value = preset.size; spacing.value = preset.spacing; line.value = preset.line;
        applySettings(); markChanged(); updateProgress();
    }); });
    [size, spacing, line].forEach(function (input) { input.addEventListener('input', function () { applySettings(); markChanged(); updateProgress(); }); });
    font.addEventListener('change', function () { body.dataset.font = font.value; markChanged(); updateProgress(); });
    themeInputs.forEach(function (input) { input.addEventListener('change', function () { if (input.checked) { body.dataset.tema = input.value; markChanged(); } }); });
    document.getElementById('btn-reset').addEventListener('click', function () {
        size.value = 18; spacing.value = 0.05; line.value = 1.8; font.value = 'sans';
        themeInputs.forEach(function (input) { input.checked = input.value === 'normal'; });
        body.dataset.font = 'sans'; body.dataset.tema = 'normal';
        applySettings(); markChanged(); updateProgress();
    });

    function focusParagraph(index, scroll) {
        current = Math.max(0, Math.min(paragraphs.length - 1, index));
        body.classList.add('mode-fokus');
        paragraphs.forEach(function (paragraph, i) { paragraph.classList.toggle('aktif', i === current); });
        focusButton.setAttribute('aria-pressed', 'true');
        focusButton.querySelector('[data-fokus-label]').textContent = 'Keluar fokus';
        document.getElementById('focus-position').textContent = 'Paragraf ' + (current + 1) + ' dari ' + paragraphs.length;
        previous.disabled = current === 0;
        next.disabled = current === paragraphs.length - 1;
        if (scroll && paragraphs[current]) paragraphs[current].scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'center' });
    }
    function endFocus() {
        body.classList.remove('mode-fokus');
        paragraphs.forEach(function (paragraph) { paragraph.classList.remove('aktif'); });
        focusButton.setAttribute('aria-pressed', 'false');
        focusButton.querySelector('[data-fokus-label]').textContent = 'Mode fokus';
    }
    focusButton.addEventListener('click', function () {
        if (body.classList.contains('mode-fokus')) { stopReading(); endFocus(); }
        else focusParagraph(current, false);
    });
    var touchGesture = null;
    var lastTouchTap = null;
    var handledTouchAt = -Infinity;
    function nearTouch(first, second, distance) {
        return Math.hypot(first.clientX - second.clientX, first.clientY - second.clientY) <= distance;
    }
    function clearTouchGesture() { touchGesture = null; lastTouchTap = null; }
    function isTouchCompatibilityEvent(event) {
        if (event.detail === 0) return false;
        if (event.pointerType) return event.pointerType === 'touch';
        if (event.sourceCapabilities) return event.sourceCapabilities.firesTouchEvents;
        return performance.now() - handledTouchAt < 750;
    }
    document.addEventListener('touchstart', function (event) {
        if (event.touches.length !== 1) { clearTouchGesture(); return; }
        var target = event.target.closest ? event.target : event.target.parentElement;
        var paragraph = target && target.closest('#isi-bacaan p');
        var touch = event.touches[0];
        // Navigasi fokus dapat muncul di posisi ketukan pertama pada teks pendek.
        var previousTarget = lastTouchTap && target && target.closest('.baca-artikel')
            && performance.now() - lastTouchTap.time <= 450 && nearTouch(lastTouchTap, touch, 24);
        if (!paragraph && !previousTarget) { clearTouchGesture(); return; }
        touchGesture = { identifier: touch.identifier, clientX: touch.clientX, clientY: touch.clientY,
            time: performance.now(), paragraph: paragraph };
    }, { passive: true });
    document.addEventListener('touchmove', function (event) {
        if (!touchGesture) return;
        var touch = Array.from(event.touches).find(function (item) { return item.identifier === touchGesture.identifier; });
        if (event.touches.length !== 1 || !touch || !nearTouch(touchGesture, touch, 12)) clearTouchGesture();
    }, { passive: true });
    document.addEventListener('touchcancel', clearTouchGesture, { passive: true });
    window.addEventListener('pagehide', clearTouchGesture);
    document.addEventListener('touchend', function (event) {
        if (!touchGesture) return;
        var gesture = touchGesture;
        touchGesture = null;
        handledTouchAt = performance.now();
        var touch = Array.from(event.changedTouches).find(function (item) { return item.identifier === gesture.identifier; });
        if (event.touches.length || !touch || handledTouchAt - gesture.time > 450 || !nearTouch(gesture, touch, 12)) {
            lastTouchTap = null;
            return;
        }
        if (lastTouchTap && handledTouchAt - lastTouchTap.time <= 450 && nearTouch(lastTouchTap, touch, 24)) {
            var index = lastTouchTap.index;
            lastTouchTap = null;
            event.preventDefault();
            clearParagraphSelection(paragraphs[index]);
            startReading(index);
            return;
        }
        if (!gesture.paragraph) { lastTouchTap = null; return; }
        var index = paragraphs.indexOf(gesture.paragraph);
        lastTouchTap = { index: index, time: handledTouchAt, clientX: touch.clientX, clientY: touch.clientY };
        var before = gesture.paragraph.getBoundingClientRect().top;
        stopReading();
        focusParagraph(index, false);
        window.scrollBy(0, gesture.paragraph.getBoundingClientRect().top - before);
    }, { passive: false });
    function clearParagraphSelection(paragraph) {
        var selection = window.getSelection();
        if (speechSupported && selection && paragraph.contains(selection.anchorNode) && paragraph.contains(selection.focusNode)) selection.removeAllRanges();
    }
    paragraphs.forEach(function (paragraph, index) {
        paragraph.addEventListener('click', function (event) {
            if (isTouchCompatibilityEvent(event)) return;
            clearTouchGesture();
            stopReading();
            focusParagraph(index, false);
        });
        paragraph.addEventListener('dblclick', function (event) {
            if (isTouchCompatibilityEvent(event)) return;
            event.preventDefault();
            clearParagraphSelection(paragraph);
            startReading(index);
        });
        paragraph.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                event.preventDefault();
                if (!event.repeat) startReading(index);
                return;
            }
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); stopReading(); focusParagraph(index, false); }
        });
    });
    previous.addEventListener('click', function () { stopReading(); focusParagraph(current - 1, true); });
    next.addEventListener('click', function () { stopReading(); focusParagraph(current + 1, true); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !event.defaultPrevented && !document.querySelector('dialog[open], [role="dialog"][aria-modal="true"]')) { stopReading(); endFocus(); }
    });

    function showInfo(message) { info.textContent = message; info.hidden = !message; }
    function syncToolbarHeight() {
        if (toolbar) body.style.setProperty('--reader-toolbar-height', toolbar.offsetHeight + 'px');
    }
    if (toolbar && window.ResizeObserver) new ResizeObserver(syncToolbarHeight).observe(toolbar);
    window.addEventListener('resize', syncToolbarHeight);
    syncToolbarHeight();
    function setReadButton(active) {
        readButton.setAttribute('aria-pressed', String(active));
        readButton.querySelector('[data-baca-label]').textContent = active ? 'Hentikan bacaan' : 'Bacakan teks';
        stopButton.hidden = !active;
        syncToolbarHeight();
    }
    function stopReading() {
        reading = false; speechGeneration++;
        if (speechSupported) window.speechSynthesis.cancel();
        var returnToParagraph = document.activeElement === stopButton;
        setReadButton(false);
        if (returnToParagraph && paragraphs[current]) paragraphs[current].focus({ preventScroll: true });
    }
    function speakParagraph(index, generation) {
        if (!reading || generation !== speechGeneration) return;
        if (index >= paragraphs.length) { stopReading(); showInfo('Bacaan selesai. Kamu bisa mendengarkannya kembali kapan saja.'); return; }
        var utterance = new SpeechSynthesisUtterance(paragraphs[index].textContent);
        utterance.lang = 'id-ID';
        var voice = window.speechSynthesis.getVoices().find(function (item) { return item.lang.toLowerCase().startsWith('id'); });
        if (voice) utterance.voice = voice;
        utterance.onstart = function () { if (generation === speechGeneration) focusParagraph(index, true); };
        utterance.onend = function () { if (generation === speechGeneration) speakParagraph(index + 1, generation); };
        utterance.onerror = function (event) {
            if (generation !== speechGeneration) return;
            stopReading();
            if (event.error !== 'canceled' && event.error !== 'interrupted') showInfo('Suara belum bisa diputar di perangkat ini. Pastikan suara baca tersedia pada browser atau sistemmu.');
        };
        try {
            window.speechSynthesis.speak(utterance);
        } catch (error) {
            if (generation !== speechGeneration) return;
            stopReading();
            showInfo('Suara belum bisa diputar di perangkat ini. Coba lagi atau gunakan mode fokus.');
        }
    }
    function startReading(index) {
        if (!speechSupported) {
            showInfo('Browser ini belum menyediakan baca nyaring. Kamu tetap bisa memakai pengaturan dan mode fokus.');
            return;
        }
        stopReading();
        var voices = window.speechSynthesis.getVoices();
        var hasIndonesian = voices.some(function (voice) { return voice.lang.toLowerCase().startsWith('id'); });
        showInfo(voices.length && !hasIndonesian ? 'Suara bahasa Indonesia belum tersedia. Bacaan akan menggunakan suara bawaan perangkat.' : '');
        focusParagraph(index, false);
        reading = true;
        setReadButton(true);
        speakParagraph(current, speechGeneration);
    }
    stopButton.addEventListener('click', stopReading);
    if (!speechSupported) {
        readButton.disabled = true;
        showInfo('Browser ini belum menyediakan baca nyaring. Kamu tetap bisa memakai pengaturan dan mode fokus.');
    } else {
        readButton.addEventListener('click', function () {
            if (reading) { stopReading(); return; }
            startReading(body.classList.contains('mode-fokus') ? current : 0);
        });
        window.addEventListener('pagehide', stopReading);
    }

    var progress = document.querySelector('.reader-progress');
    var progressLine = document.getElementById('progress-line');
    var article = document.querySelector('.baca-artikel');
    var positionKey = 'baca-posisi:' + article.dataset.userId + ':' + article.dataset.textId;
    window.addEventListener('pagehide', function () {
        try {
            var index = paragraphs.findIndex(function (paragraph) { return paragraph.getBoundingClientRect().bottom > 100; });
            if (index < 0) index = paragraphs.length - 1;
            sessionStorage.setItem(positionKey, JSON.stringify({ index: index, top: paragraphs[index].getBoundingClientRect().top }));
        } catch (error) { /* Membaca tetap bekerja jika penyimpanan browser dibatasi. */ }
    });
    function restorePosition() {
        try {
            var position = JSON.parse(sessionStorage.getItem(positionKey));
            if (position && Number.isInteger(position.index) && paragraphs[position.index] && Number.isFinite(position.top)) {
                var top = paragraphs[position.index].getBoundingClientRect().top + window.scrollY - position.top;
                window.scrollTo({ top: Math.max(0, top), behavior: 'instant' });
            }
        } catch (error) { /* Nilai tersimpan bukan syarat untuk membuka bacaan. */ }
        updateProgress();
    }
    var queued = false;
    function updateProgress() {
        var end = article.getBoundingClientRect().bottom + window.scrollY - window.innerHeight;
        var percent = end <= 0 ? 100 : Math.max(0, Math.min(100, Math.round(window.scrollY / end * 100)));
        progress.setAttribute('aria-valuenow', String(percent));
        progressLine.style.width = percent + '%';
        queued = false;
    }
    window.addEventListener('scroll', function () { if (!queued) { queued = true; window.requestAnimationFrame(updateProgress); } }, { passive: true });
    window.addEventListener('resize', updateProgress);
    if (document.fonts) document.fonts.ready.then(restorePosition);
    else restorePosition();
    updateProgress();
})();
