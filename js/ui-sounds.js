/**
 * ui-sounds.js — AeroFocus UI Sound Effects
 * Plays a soft tick when hovering a button and a click sound when pressing one.
 * Sounds are synthesized with the Web Audio API (no audio files required).
 * This script does not touch any markup or styles.
 */
(function () {
    'use strict';

    // =========================================================================
    // ⚙️ QUICK CONFIGURATION
    // =========================================================================
    const CONFIG = {
        hoverVolume: 0.60,   // 0.0 – 1.0
        clickVolume: 0.40    // 0.0 – 1.0
    };

    // Everything that should be treated as a "button"
    const BUTTON_SELECTOR = [
        'button',
        'input[type="submit"]',
        'input[type="button"]',
        'input[type="reset"]',
        '[role="button"]',
        '.btn',
        '.btn-submit',
        '.show-pass',
        '.nav-logout',
        '.app-header nav a'
    ].join(',');

    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;

    let ctx = null;

    function getContext() {
        if (!ctx) {
            try {
                ctx = new AudioCtx();
            } catch (e) {
                return null;
            }
        }
        if (ctx.state === 'suspended') {
            ctx.resume().catch(function () {});
        }
        return ctx;
    }

    // Browsers only allow audio after a user gesture — unlock on the first one.
    function unlock() {
        getContext();
    }
    ['pointerdown', 'mousedown', 'touchstart', 'keydown'].forEach(function (evt) {
        window.addEventListener(evt, unlock, { once: true, passive: true });
    });

    function tone(opts) {
        const c = getContext();
        if (!c || c.state !== 'running') return;

        const now = c.currentTime + opts.delay;
        const osc = c.createOscillator();
        const gain = c.createGain();

        osc.type = opts.type;
        osc.frequency.setValueAtTime(opts.from, now);
        if (opts.to && opts.to !== opts.from) {
            osc.frequency.exponentialRampToValueAtTime(opts.to, now + opts.duration);
        }

        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(opts.volume, now + 0.008);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + opts.duration);

        osc.connect(gain);
        gain.connect(c.destination);
        osc.start(now);
        osc.stop(now + opts.duration + 0.02);
    }

    function playHover() {
        tone({ type: 'sine', from: 880, to: 1320, duration: 0.07, volume: CONFIG.hoverVolume, delay: 0 });
    }

    function playClick() {
        tone({ type: 'triangle', from: 520, to: 260, duration: 0.09, volume: CONFIG.clickVolume, delay: 0 });
        tone({ type: 'sine', from: 1040, to: 780, duration: 0.07, volume: CONFIG.clickVolume * 0.5, delay: 0.015 });
    }

    function findButton(target) {
        if (!target || !target.closest) return null;
        const el = target.closest(BUTTON_SELECTOR);
        if (!el) return null;
        if (el.disabled || el.getAttribute('aria-disabled') === 'true') return null;
        return el;
    }

    // ---- Hover sound (fires once when the pointer enters a button) ----
    document.addEventListener('mouseover', function (e) {
        const btn = findButton(e.target);
        if (!btn) return;
        // Ignore movement between children of the same button
        if (e.relatedTarget && btn.contains(e.relatedTarget)) return;
        playHover();
    }, true);

    // ---- Press sound ----
    // Mouse/touch/pen: play on press so it is heard before any page navigation.
    document.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        if (findButton(e.target)) playClick();
    }, true);

    // Keyboard activation (Enter / Space triggers click with detail === 0)
    document.addEventListener('click', function (e) {
        if (e.detail !== 0) return;
        if (findButton(e.target)) playClick();
    }, true);
})();
