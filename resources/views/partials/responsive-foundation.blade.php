<style>
    html {
        -webkit-text-size-adjust: 100%;
        text-size-adjust: 100%;
    }
    body {
        overflow-x: hidden;
        overflow-wrap: anywhere;
    }
    .min-h-screen {
        min-height: 100vh;
        min-height: 100dvh;
    }
    button, a, input, select, textarea {
        touch-action: manipulation;
    }
    img, svg, video, canvas {
        max-width: 100%;
    }
    [data-horizontal-scroll] {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 639px) {
        input:not([type="checkbox"]):not([type="radio"]),
        select,
        textarea {
            font-size: 16px !important;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            scroll-behavior: auto !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
