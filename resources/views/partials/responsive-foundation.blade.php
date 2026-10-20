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
    @media (min-width: 1024px) {
        html {
            font-size: 15px;
        }
        .p-6 { padding: 1.25rem !important; }
        .p-8 { padding: 1.5rem !important; }
        .p-10 { padding: 1.75rem !important; }
        .px-6 { padding-left: 1.25rem !important; padding-right: 1.25rem !important; }
        .px-8 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
        .px-10 { padding-left: 1.75rem !important; padding-right: 1.75rem !important; }
        .py-6 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }
        .py-8 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
        .py-10 { padding-top: 1.75rem !important; padding-bottom: 1.75rem !important; }
        .gap-6 { gap: 1.25rem !important; }
        .gap-8 { gap: 1.5rem !important; }
        .gap-10 { gap: 1.75rem !important; }
        .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.25rem !important; }
        .space-y-8 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.5rem !important; }
        .space-y-10 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.75rem !important; }
        .space-y-12 > :not([hidden]) ~ :not([hidden]) { margin-top: 2rem !important; }
        .text-3xl { font-size: 1.5rem !important; line-height: 1.25 !important; }
        .text-4xl { font-size: 1.75rem !important; line-height: 1.2 !important; }
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
