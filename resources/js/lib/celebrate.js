import confetti from 'canvas-confetti';

const COLORS = ['#ff6b2c', '#ffb224', '#34d888', '#ffc53d', '#5aa9ff', '#ffffff'];

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** Two bursts from the bottom corners — for a correct answer. */
export function celebrate() {
    if (reducedMotion()) return;

    const shoot = (originX, angle) =>
        confetti({
            particleCount: 70,
            spread: 62,
            startVelocity: 52,
            angle,
            origin: { x: originX, y: 0.92 },
            colors: COLORS,
            scalar: 0.95,
            disableForReducedMotion: true,
        });

    shoot(0.12, 62);
    shoot(0.88, 118);
}

/** A slow shower from the top — for badges and level-ups. */
export function shower() {
    if (reducedMotion()) return;

    const end = Date.now() + 900;
    (function frame() {
        confetti({
            particleCount: 5,
            spread: 80,
            startVelocity: 28,
            gravity: 0.9,
            origin: { x: Math.random(), y: -0.05 },
            colors: COLORS,
            disableForReducedMotion: true,
        });
        if (Date.now() < end) requestAnimationFrame(frame);
    })();
}
