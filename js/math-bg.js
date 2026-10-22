/**
 * math-bg.js — AeroFocus Interactive Floating Math Background
 * Renders floating mathematical equations, symbols, and operations on an ambient canvas.
 */
(function () {
    'use strict';

    // =========================================================================
    // ⚙️ QUICK CONFIGURATION — Adjust Opacity, Brightness & Animation Speed
    // =========================================================================
    const CONFIG = {
        // Overall background canvas opacity (0.0 = invisible, 1.0 = full strength)
        canvasOpacity: 0.90,

        // Individual symbol brightness range (between 0.0 and 1.0)
        // Lower values make it subtle & ambient; higher values make it bold & vibrant
        minAlpha: 0.10,
        maxAlpha: 0.60,

        // Float speed multiplier (1.0 = normal, 0.5 = slow & subtle, 1.5 = faster)
        speedMultiplier: 1.0,

        // Neon glow intensity (in pixels)
        glowBlur: 20
    };

    // Curated collection of equations, calculus formulas, Greek symbols, and mathematical operations
    const MATH_ITEMS = [
        // Notable Equations & Theorems
        { text: 'E = mc²', type: 'eq', weight: 2 },
        { text: 'e^{iπ} + 1 = 0', type: 'eq', weight: 2 },
        { text: 'a² + b² = c²', type: 'eq', weight: 2 },
        { text: '∫ e^{-x²} dx = √π', type: 'eq', weight: 1.5 },
        { text: 'lim (x→0) sin(x)/x = 1', type: 'eq', weight: 1.5 },
        { text: '∑ 1/n² = π²/6', type: 'eq', weight: 1.5 },
        { text: 'x = (-b ± √Δ) / 2a', type: 'eq', weight: 2 },
        { text: 'f\'(x) = df/dx', type: 'eq', weight: 1.5 },
        { text: 'sin²θ + cos²θ = 1', type: 'eq', weight: 2 },
        { text: '∇ × B = μ₀J', type: 'eq', weight: 1 },
        { text: 'λ = h / p', type: 'eq', weight: 1.5 },
        { text: 'A = πr²', type: 'eq', weight: 1.5 },
        { text: 'i² = -1', type: 'eq', weight: 2 },
        { text: 'F = ma', type: 'eq', weight: 1.5 },
        { text: 'PV = nRT', type: 'eq', weight: 1.5 },
        { text: 'H|ψ⟩ = E|ψ⟩', type: 'eq', weight: 1 },
        { text: '∂u/∂t = α∇²u', type: 'eq', weight: 1 },
        { text: '∫₀^∞ x^{n-1}e^{-x}dx', type: 'eq', weight: 1 },
        { text: 'log(ab) = log a + log b', type: 'eq', weight: 1.5 },
        { text: 'd/dx [ln x] = 1/x', type: 'eq', weight: 1.5 },

        // Greek & Calculus Symbols
        { text: 'π', type: 'sym', weight: 3 },
        { text: '∞', type: 'sym', weight: 3 },
        { text: '∑', type: 'sym', weight: 3 },
        { text: '∫', type: 'sym', weight: 3 },
        { text: '∬', type: 'sym', weight: 2 },
        { text: '∮', type: 'sym', weight: 2 },
        { text: '∂', type: 'sym', weight: 2 },
        { text: '√x', type: 'sym', weight: 2 },
        { text: 'Δ', type: 'sym', weight: 2 },
        { text: '∇', type: 'sym', weight: 2 },
        { text: 'θ', type: 'sym', weight: 2 },
        { text: 'α', type: 'sym', weight: 2 },
        { text: 'β', type: 'sym', weight: 2 },
        { text: 'γ', type: 'sym', weight: 2 },
        { text: 'λ', type: 'sym', weight: 2 },
        { text: 'μ', type: 'sym', weight: 2 },
        { text: 'σ', type: 'sym', weight: 2 },
        { text: 'Ω', type: 'sym', weight: 2 },
        { text: 'ω', type: 'sym', weight: 2 },
        { text: 'φ', type: 'sym', weight: 2 },
        { text: 'ψ', type: 'sym', weight: 2 },
        { text: 'ε', type: 'sym', weight: 2 },
        { text: 'ℏ', type: 'sym', weight: 2 },

        // Operations & Relations
        { text: '±', type: 'op', weight: 2 },
        { text: '≠', type: 'op', weight: 2 },
        { text: '≈', type: 'op', weight: 2 },
        { text: '≤', type: 'op', weight: 2 },
        { text: '≥', type: 'op', weight: 2 },
        { text: '≡', type: 'op', weight: 2 },
        { text: '∈', type: 'op', weight: 2 },
        { text: '∀x', type: 'op', weight: 1.5 },
        { text: '∃y', type: 'op', weight: 1.5 },
        { text: '∴', type: 'op', weight: 1.5 },
        { text: '∵', type: 'op', weight: 1.5 },
        { text: '∝', type: 'op', weight: 1.5 },
        { text: '×', type: 'op', weight: 2 },
        { text: '÷', type: 'op', weight: 2 },
        { text: '⊕', type: 'op', weight: 1.5 },
        { text: '⊗', type: 'op', weight: 1.5 },
        { text: 'f(x)', type: 'op', weight: 2 }
    ];

    // Theme Color Palette
    const PALETTES = [
        { color: '56, 189, 248', glow: 'rgba(56, 189, 248, 0.45)' },  // Sky blue
        { color: '255, 140, 26', glow: 'rgba(255, 140, 26, 0.45)' },  // Orange
        { color: '57, 255, 138', glow: 'rgba(57, 255, 138, 0.35)' },  // Green
        { color: '148, 163, 184', glow: 'rgba(148, 163, 184, 0.25)' },// Muted slate
        { color: '129, 140, 248', glow: 'rgba(129, 140, 248, 0.35)' } // Indigo
    ];

    function init() {
        let canvas = document.getElementById('math-bg-canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            canvas.id = 'math-bg-canvas';
            canvas.style.position = 'fixed';
            canvas.style.inset = '0';
            canvas.style.width = '100vw';
            canvas.style.height = '100vh';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '1';
            canvas.style.opacity = CONFIG.canvasOpacity;
            document.body.insertBefore(canvas, document.body.firstChild);
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let width = 0;
        let height = 0;
        let dpr = window.devicePixelRatio || 1;
        let particles = [];
        let mouse = { x: -1000, y: -1000, targetX: -1000, targetY: -1000 };

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            dpr = window.devicePixelRatio || 1;

            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.scale(dpr, dpr);

            generateParticles();
        }

        function createParticle(spawnAnywhere = false) {
            const item = MATH_ITEMS[Math.floor(Math.random() * MATH_ITEMS.length)];
            const palette = PALETTES[Math.floor(Math.random() * PALETTES.length)];

            // Size determination based on type
            let fontSize;
            if (item.type === 'eq') {
                fontSize = Math.floor(16 + Math.random() * 9); // 16px - 25px
            } else if (item.type === 'sym') {
                fontSize = Math.floor(20 + Math.random() * 18); // 20px - 38px
            } else {
                fontSize = Math.floor(18 + Math.random() * 12); // 18px - 30px
            }

            const x = Math.random() * width;
            const y = spawnAnywhere ? Math.random() * height : height + 30 + Math.random() * 40;

            const vy = -(0.3 + Math.random() * 0.5); // Gentle upward float
            const vx = (Math.random() - 0.5) * 0.25;

            const swaySpeed = 0.001 + Math.random() * 0.0025;
            const swayAmp = 18 + Math.random() * 30;
            const swayOffset = Math.random() * Math.PI * 2;

            // Opacity range derived from CONFIG
            const alphaSpread = Math.max(0, CONFIG.maxAlpha - CONFIG.minAlpha);
            const baseAlpha = CONFIG.minAlpha + Math.random() * alphaSpread;
            const pulseSpeed = 0.0018 + Math.random() * 0.0025;

            const rotation = (Math.random() - 0.5) * 0.22;
            const spinSpeed = (Math.random() - 0.5) * 0.0009;

            return {
                text: item.text,
                type: item.type,
                x,
                y,
                vx,
                vy,
                originX: x,
                fontSize,
                palette,
                baseAlpha,
                alpha: baseAlpha,
                pulseSpeed,
                swaySpeed,
                swayAmp,
                swayOffset,
                rotation,
                spinSpeed,
                depth: 0.5 + Math.random() * 0.8
            };
        }

        function generateParticles() {
            // Adaptive particle count based on screen area
            const count = Math.max(32, Math.min(65, Math.floor((width * height) / 18000)));
            particles = [];
            for (let i = 0; i < count; i++) {
                particles.push(createParticle(true));
            }
        }

        window.addEventListener('resize', resize);
        window.addEventListener('mousemove', function (e) {
            mouse.targetX = e.clientX;
            mouse.targetY = e.clientY;
        });
        window.addEventListener('mouseleave', function () {
            mouse.targetX = -1000;
            mouse.targetY = -1000;
        });

        resize();

        let lastTime = performance.now();

        function animate(now) {
            const dt = Math.min(50, now - lastTime);
            lastTime = now;

            // Smooth mouse easing
            mouse.x += (mouse.targetX - mouse.x) * 0.05;
            mouse.y += (mouse.targetY - mouse.y) * 0.05;

            ctx.clearRect(0, 0, width, height);

            for (let i = 0; i < particles.length; i++) {
                const p = particles[i];

                // Vertical float
                p.y += p.vy * (dt / 16) * CONFIG.speedMultiplier;

                // Horizontal sway
                const sway = Math.sin(now * p.swaySpeed + p.swayOffset) * p.swayAmp;
                let currentX = p.originX + sway + p.vx * now * 0.05;

                // Subtle mouse repulsion / parallax
                if (mouse.x > -500) {
                    const dx = currentX - mouse.x;
                    const dy = p.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    const maxDist = 160;
                    if (dist < maxDist && dist > 0) {
                        const force = (1 - dist / maxDist) * 15 * p.depth;
                        currentX += (dx / dist) * force;
                    }
                }

                // Breathing alpha pulse
                const pulse = Math.sin(now * p.pulseSpeed + p.swayOffset);
                p.alpha = Math.max(0.05, p.baseAlpha + pulse * 0.08);

                p.rotation += p.spinSpeed;

                // Render mathematical expression
                ctx.save();
                ctx.translate(currentX, p.y);
                ctx.rotate(p.rotation);

                ctx.font = `italic 600 ${p.fontSize}px "Cambria Math", "KaTeX_Math", "Times New Roman", "STIXGeneral", serif`;
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                // Soft ambient neon glow
                ctx.shadowColor = p.palette.glow;
                ctx.shadowBlur = CONFIG.glowBlur;
                ctx.fillStyle = `rgba(${p.palette.color}, ${p.alpha})`;

                ctx.fillText(p.text, 0, 0);
                ctx.restore();

                // Recycle particle if it floats above top of screen
                if (p.y < -50) {
                    particles[i] = createParticle(false);
                    particles[i].y = height + 30;
                    particles[i].originX = Math.random() * width;
                }
            }

            requestAnimationFrame(animate);
        }

        requestAnimationFrame(animate);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
