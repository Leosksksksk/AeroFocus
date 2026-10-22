/**
 * bg-music.js — AeroFocus Ambient Background Music
 * A generative, minimal, synth-driven ambient/electronic piece:
 *   - slow evolving detuned synth pads
 *   - deep sine sub-bass
 *   - sparse pentatonic-style synth plucks through a tape-style echo
 *   - long, soft reverb
 * Everything is synthesized live with the Web Audio API (no audio files).
 * This script does not touch any markup or styles (no visible controls).
 *
 * Controls:  press "M" to mute / unmute the music (saved across pages).
 *
 * CONTINUITY ACROSS DASHBOARDS / PAGES
 * Every full page load destroys the page's AudioContext, so music cannot
 * literally keep running through a navigation. Instead this script saves the
 * exact playback position (chord + step + wall-clock time) continuously, and
 * the next page/tab picks it up where the last one left off: same chord,
 * same position, short fade-in, no restart from the intro, no gap in the
 * harmony. Mute state is also synced live between open tabs.
 */
(function () {
    'use strict';

    // =========================================================================
    // ⚙️ QUICK CONFIGURATION
    // =========================================================================
    const CONFIG = {
        volume: 0.30,          // Master music volume (0.0 – 1.0)
        fadeInSeconds: 4,      // Fade-in when music first starts (fresh start)
        resumeFadeSeconds: 0.8,// Fade-in when continuing from another page
        resumeAttack: 0.8,     // Pad/bass attack when continuing mid-chord
        maxResumeGapSeconds: 60,// If the last page stopped longer ago than this, start fresh
        stepSeconds: 0.5,      // Length of one sequencer step
        stepsPerChord: 20,     // Steps per chord (20 x 0.5s = 10s per chord)
        pluckChance: 0.30,     // Chance of a pluck note on each step (0 – 1)
        muteKey: 'm'           // Keyboard key to toggle music
    };

    const STORAGE_MUTED = 'aerofocus_music_muted';
    const STORAGE_STATE = 'aerofocus_music_state'; // shared across pages AND tabs

    // Chord progression (A minor feel): Am9 – Fmaj7 – Cmaj7 – G add9
    // bass = MIDI note for the sub-bass, pad = MIDI notes for the pad voices
    const CHORDS = [
        { bass: 45, pad: [57, 60, 64, 67, 71] },
        { bass: 41, pad: [57, 60, 64, 65, 69] },
        { bass: 48, pad: [55, 60, 64, 67, 71] },
        { bass: 43, pad: [55, 59, 62, 64, 69] }
    ];

    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;

    function mtof(m) {
        return 440 * Math.pow(2, (m - 69) / 12);
    }

    function readStore(store, key) {
        try { return store.getItem(key); } catch (e) { return null; }
    }
    function writeStore(store, key, value) {
        try { store.setItem(key, value); } catch (e) { /* ignore */ }
    }

    let muted = readStore(window.localStorage, STORAGE_MUTED) === '1';
    let chordIndex = 0;

    let ctx = null;
    let master = null;
    let padBus = null;
    let pluckBus = null;
    let reverbSend = null;
    let echoSend = null;
    let timer = null;
    let nextStepTime = 0;
    let stepInChord = 0;
    let faded = false;
    let resumedFromSave = false;
    let suspendTimer = null;

    // --------------------------------------------------- cross-page state
    // Saves WHERE the sequencer is, tied to wall-clock time, so the next page
    // can work out exactly how far the music would have progressed meanwhile.
    function saveState() {
        if (!ctx || ctx.state !== 'running' || muted) return;
        const secondsUntilNext = Math.max(0, nextStepTime - ctx.currentTime);
        writeStore(window.localStorage, STORAGE_STATE, JSON.stringify({
            chord: chordIndex,
            step: stepInChord,                              // next step to be scheduled
            wall: Date.now() + secondsUntilNext * 1000      // wall time that step plays
        }));
    }

    function loadState() {
        const raw = readStore(window.localStorage, STORAGE_STATE);
        if (!raw) return null;
        try {
            const s = JSON.parse(raw);
            if (typeof s.chord !== 'number' || typeof s.step !== 'number' || typeof s.wall !== 'number') return null;
            if (s.chord < 0 || s.chord >= CHORDS.length || s.step < 0 || s.step >= CONFIG.stepsPerChord) return null;
            return s;
        } catch (e) { return null; }
    }

    // Work out where the music should be "now" and restore it.
    // Returns the number of steps remaining in the current chord if we are
    // joining mid-chord (so its pads can be started immediately), else 0.
    function restorePosition() {
        const s = loadState();
        resumedFromSave = false;
        if (!s) return 0;

        const gap = (Date.now() - s.wall) / 1000;
        if (gap > CONFIG.maxResumeGapSeconds) return 0;   // too old -> fresh start

        let steps = Math.max(0, Math.round(gap / CONFIG.stepSeconds));
        let c = s.chord;
        let st = s.step + steps;
        while (st >= CONFIG.stepsPerChord) {
            st -= CONFIG.stepsPerChord;
            c = (c + 1) % CHORDS.length;
        }
        chordIndex = c;
        stepInChord = st;
        resumedFromSave = true;
        return st > 0 ? CONFIG.stepsPerChord - st : 0;
    }

    // ------------------------------------------------------------------ graph
    function makeImpulse(c, seconds, decay) {
        const rate = c.sampleRate;
        const len = Math.floor(rate * seconds);
        const buf = c.createBuffer(2, len, rate);
        for (let ch = 0; ch < 2; ch++) {
            const data = buf.getChannelData(ch);
            for (let i = 0; i < len; i++) {
                data[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, decay);
            }
        }
        return buf;
    }

    function buildGraph() {
        master = ctx.createGain();
        master.gain.value = 0;
        master.connect(ctx.destination);

        // Reverb
        const convolver = ctx.createConvolver();
        convolver.buffer = makeImpulse(ctx, 4, 3);
        const reverbReturn = ctx.createGain();
        reverbReturn.gain.value = 0.55;
        convolver.connect(reverbReturn);
        reverbReturn.connect(master);
        reverbSend = ctx.createGain();
        reverbSend.gain.value = 1;
        reverbSend.connect(convolver);

        // Tape-style echo (for plucks)
        const delay = ctx.createDelay(2);
        delay.delayTime.value = CONFIG.stepSeconds * 3;
        const feedback = ctx.createGain();
        feedback.gain.value = 0.38;
        const echoTone = ctx.createBiquadFilter();
        echoTone.type = 'lowpass';
        echoTone.frequency.value = 1800;
        delay.connect(echoTone);
        echoTone.connect(feedback);
        feedback.connect(delay);
        const echoReturn = ctx.createGain();
        echoReturn.gain.value = 0.5;
        echoTone.connect(echoReturn);
        echoReturn.connect(master);
        echoReturn.connect(reverbSend);
        echoSend = ctx.createGain();
        echoSend.gain.value = 1;
        echoSend.connect(delay);

        // Buses
        padBus = ctx.createGain();
        padBus.gain.value = 1;
        padBus.connect(master);
        padBus.connect(reverbSend);

        pluckBus = ctx.createGain();
        pluckBus.gain.value = 1;
        pluckBus.connect(master);
        pluckBus.connect(echoSend);
        pluckBus.connect(reverbSend);
    }

    // ----------------------------------------------------------------- voices
    function padVoice(midi, start, duration, attack) {
        const release = 3.2;
        const end = start + duration;
        const freq = mtof(midi);

        const filter = ctx.createBiquadFilter();
        filter.type = 'lowpass';
        filter.Q.value = 0.8;
        filter.frequency.setValueAtTime(500, start);
        filter.frequency.linearRampToValueAtTime(1100, start + duration * 0.6);
        filter.frequency.linearRampToValueAtTime(600, end + release);

        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.linearRampToValueAtTime(0.045, start + attack);
        gain.gain.setValueAtTime(0.045, end);
        gain.gain.linearRampToValueAtTime(0.0001, end + release);

        // Slow tremolo for gentle movement
        const lfo = ctx.createOscillator();
        const lfoGain = ctx.createGain();
        lfo.frequency.value = 0.12 + Math.random() * 0.15;
        lfoGain.gain.value = 0.012;
        lfo.connect(lfoGain);
        lfoGain.connect(gain.gain);

        [-7, 7].forEach(function (cents, i) {
            const osc = ctx.createOscillator();
            osc.type = i === 0 ? 'sawtooth' : 'triangle';
            osc.frequency.value = freq;
            osc.detune.value = cents;
            osc.connect(filter);
            osc.start(start);
            osc.stop(end + release + 0.1);
        });

        filter.connect(gain);
        gain.connect(padBus);
        lfo.start(start);
        lfo.stop(end + release + 0.1);
    }

    function bassVoice(midi, start, duration, attack) {
        const end = start + duration;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.value = mtof(midi);
        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.linearRampToValueAtTime(0.16, start + attack);
        gain.gain.setValueAtTime(0.16, end);
        gain.gain.linearRampToValueAtTime(0.0001, end + 2.5);
        osc.connect(gain);
        gain.connect(master);
        osc.start(start);
        osc.stop(end + 2.6);
    }

    function pluckVoice(midi, start) {
        const freq = mtof(midi);
        const osc = ctx.createOscillator();
        const osc2 = ctx.createOscillator();
        const filter = ctx.createBiquadFilter();
        const gain = ctx.createGain();

        osc.type = 'square';
        osc2.type = 'sawtooth';
        osc.frequency.value = freq;
        osc2.frequency.value = freq;
        osc2.detune.value = 9;

        filter.type = 'lowpass';
        filter.Q.value = 3;
        filter.frequency.setValueAtTime(2600, start);
        filter.frequency.exponentialRampToValueAtTime(500, start + 0.45);

        gain.gain.setValueAtTime(0.0001, start);
        gain.gain.exponentialRampToValueAtTime(0.05, start + 0.012);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.7);

        osc.connect(filter);
        osc2.connect(filter);
        filter.connect(gain);
        gain.connect(pluckBus);

        osc.start(start);
        osc2.start(start);
        osc.stop(start + 0.75);
        osc2.stop(start + 0.75);
    }

    // -------------------------------------------------------------- sequencer
    // stepsLeft: how many steps of this chord remain (full chord when omitted)
    // soft: use a short attack (continuing mid-chord from another page)
    function startChord(time, stepsLeft, soft) {
        const chord = CHORDS[chordIndex];
        const steps = stepsLeft || CONFIG.stepsPerChord;
        const duration = steps * CONFIG.stepSeconds;
        const padAttack = soft ? Math.min(CONFIG.resumeAttack, duration) : 2.8;
        const bassAttack = soft ? Math.min(CONFIG.resumeAttack, duration) : 1.8;
        chord.pad.forEach(function (note) { padVoice(note, time, duration, padAttack); });
        bassVoice(chord.bass, time, duration, bassAttack);
    }

    function maybePluck(time) {
        if (Math.random() > CONFIG.pluckChance) return;
        const pad = CHORDS[chordIndex].pad;
        const note = pad[Math.floor(Math.random() * pad.length)] + (Math.random() < 0.65 ? 12 : 24);
        pluckVoice(note, time);
    }

    function scheduleStep(time) {
        if (stepInChord === 0) startChord(time);
        maybePluck(time);
        stepInChord++;
        if (stepInChord >= CONFIG.stepsPerChord) {
            stepInChord = 0;
            chordIndex = (chordIndex + 1) % CHORDS.length;
        }
    }

    function tick() {
        if (!ctx || ctx.state !== 'running' || muted) return;
        if (nextStepTime < ctx.currentTime) nextStepTime = ctx.currentTime + 0.05;
        while (nextStepTime < ctx.currentTime + 1.0) {
            scheduleStep(nextStepTime);
            nextStepTime += CONFIG.stepSeconds;
        }
        saveState();
    }

    // ---------------------------------------------------------------- control
    function fadeTo(value, seconds) {
        if (!master) return;
        const now = ctx.currentTime;
        master.gain.cancelScheduledValues(now);
        master.gain.setValueAtTime(master.gain.value, now);
        master.gain.linearRampToValueAtTime(value, now + seconds);
    }

    function begin() {
        if (!ctx || ctx.state !== 'running' || muted) return;
        if (!timer) {
            nextStepTime = ctx.currentTime + 0.1;

            // Continue from where the previous page / tab left off
            const remaining = restorePosition();
            if (remaining > 0) startChord(nextStepTime, remaining, true);

            timer = setInterval(tick, 250);
            tick();
        }
        const fade = faded ? 1.2 : (resumedFromSave ? CONFIG.resumeFadeSeconds : CONFIG.fadeInSeconds);
        fadeTo(CONFIG.volume, fade);
        faded = true;
    }

    function ensureContext() {
        if (!ctx) {
            try {
                ctx = new AudioCtx();
            } catch (e) {
                return null;
            }
            buildGraph();
            ctx.onstatechange = function () {
                if (ctx.state === 'running') begin();
            };
        }
        return ctx;
    }

    function tryStart() {
        if (muted) return;
        if (!ensureContext()) return;
        if (suspendTimer) { clearTimeout(suspendTimer); suspendTimer = null; }
        if (ctx.state === 'running') {
            begin();
        } else {
            ctx.resume().catch(function () {});
        }
    }

    function setMuted(value, fromOtherTab) {
        muted = value;
        if (!fromOtherTab) writeStore(window.localStorage, STORAGE_MUTED, muted ? '1' : '0');
        if (muted) {
            if (ctx && master) {
                fadeTo(0, 0.4);
                suspendTimer = setTimeout(function () {
                    if (ctx && muted) ctx.suspend().catch(function () {});
                }, 500);
            }
        } else {
            tryStart();
        }
    }

    // First user gesture unlocks audio (browser autoplay policy)
    const GESTURES = ['pointerdown', 'mousedown', 'touchstart', 'keydown', 'click'];
    function onGesture() {
        if (muted) return;
        tryStart();
        if (ctx && ctx.state === 'running') {
            GESTURES.forEach(function (evt) {
                window.removeEventListener(evt, onGesture, true);
            });
        }
    }
    GESTURES.forEach(function (evt) {
        window.addEventListener(evt, onGesture, { capture: true, passive: true });
    });

    // "M" toggles the music (ignored while typing in a field)
    window.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        if (!e.key || e.key.toLowerCase() !== CONFIG.muteKey) return;
        const t = e.target;
        const tag = t && t.tagName ? t.tagName.toLowerCase() : '';
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || (t && t.isContentEditable)) return;
        setMuted(!muted, false);
    });

    // Keep mute state in sync when another tab/dashboard toggles it
    window.addEventListener('storage', function (e) {
        if (e.key !== STORAGE_MUTED) return;
        const m = e.newValue === '1';
        if (m !== muted) setMuted(m, true);
    });

    // Leaving this page (link click, dashboard switch, close): save the exact
    // position so the next page continues seamlessly.
    function onLeave() {
        saveState();
    }
    window.addEventListener('pagehide', onLeave);
    window.addEventListener('beforeunload', onLeave);

    // Pause when the tab is hidden (e.g. another dashboard opened in a new tab,
    // which carries on the music from the saved position), resume when it returns
    document.addEventListener('visibilitychange', function () {
        if (!ctx || muted) return;
        if (document.hidden) {
            saveState();
            ctx.suspend().catch(function () {});
        } else {
            ctx.resume().catch(function () {});
        }
    });

    // Try to start straight away (works if the browser already allows autoplay)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryStart);
    } else {
        tryStart();
    }
})();