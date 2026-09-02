<style>
    #fla-page-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: grid;
        place-items: center;
        padding: 1.5rem;
        background: rgba(248, 250, 252, 0.76);
        -webkit-backdrop-filter: blur(7px);
        backdrop-filter: blur(7px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 180ms ease, visibility 180ms ease;
    }
    #fla-page-loader.is-active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .fla-loader-mark {
        position: relative;
        display: grid;
        place-items: center;
        width: 6rem;
        height: 6rem;
        transform: scale(0.88);
        transition: transform 180ms ease;
    }
    #fla-page-loader.is-active .fla-loader-mark { transform: scale(1); }
    .fla-loader-ring {
        position: absolute;
        inset: 0;
        border: 3px solid rgba(37, 99, 235, 0.14);
        border-top-color: #000638;
        border-right-color: #ffd22d;
        border-radius: 999px;
        animation: fla-loader-spin 0.85s linear infinite;
    }
    .fla-loader-orbit {
        position: absolute;
        inset: -0.5rem;
        border: 1px solid rgba(255, 210, 45, 0.75);
        border-right-color: transparent;
        border-bottom-color: rgba(0, 6, 56, 0.28);
        border-radius: 999px;
        animation: fla-loader-spin-reverse 1.45s linear infinite;
    }
    .fla-loader-logo {
        width: 4.5rem;
        height: 4.5rem;
        padding: 0.22rem;
        border-radius: 999px;
        background: #fff;
        object-fit: contain;
        box-shadow: 0 8px 24px rgba(0, 6, 56, 0.2), 0 0 0 2px rgba(255, 255, 255, 0.9);
    }
    .fla-action-loading { cursor: wait !important; opacity: 0.78; pointer-events: none; }
    html.dark #fla-page-loader { background: rgba(2, 6, 23, 0.78); }
    @keyframes fla-loader-spin { to { transform: rotate(360deg); } }
    @keyframes fla-loader-spin-reverse { to { transform: rotate(-360deg); } }
    @media (prefers-reduced-motion: reduce) {
        #fla-page-loader, .fla-loader-mark { transition-duration: 1ms; }
        .fla-loader-ring, .fla-loader-orbit { animation-duration: 2.4s; }
    }
</style>

<div id="fla-page-loader" aria-hidden="true">
    <div class="fla-loader-mark" role="status" aria-label="Loading">
        <span class="fla-loader-orbit" aria-hidden="true"></span>
        <span class="fla-loader-ring" aria-hidden="true"></span>
        <img class="fla-loader-logo" src="{{ asset('images/fiat.png') }}" alt="">
    </div>
</div>

<script>
    (() => {
        const loader = document.getElementById('fla-page-loader');
        if (!loader || loader.dataset.ready === 'true') return;
        loader.dataset.ready = 'true';

        let fallbackTimer;
        const pendingForms = new WeakSet();
        const showLoader = () => {
            window.clearTimeout(fallbackTimer);
            loader.classList.add('is-active');
            loader.setAttribute('aria-hidden', 'false');
            document.body.setAttribute('aria-busy', 'true');
            fallbackTimer = window.setTimeout(hideLoader, 45_000);
        };
        const hideLoader = () => {
            window.clearTimeout(fallbackTimer);
            loader.classList.remove('is-active');
            loader.setAttribute('aria-hidden', 'true');
            document.body.removeAttribute('aria-busy');
            document.querySelectorAll('.fla-action-loading').forEach((element) => {
                element.classList.remove('fla-action-loading');
                element.removeAttribute('aria-disabled');
            });
        };
        const markAction = (element) => {
            if (!element) return;
            element.classList.add('fla-action-loading');
            element.setAttribute('aria-disabled', 'true');
        };
        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.matches('[data-no-loading]')) return;
            if (pendingForms.has(form)) {
                event.preventDefault();
                return;
            }

            pendingForms.add(form);
            const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"], button:not([type])');
            markAction(submitter);
            showLoader();

            if ((form.getAttribute('target') || '').toLowerCase() === '_blank') {
                window.setTimeout(() => {
                    pendingForms.delete(form);
                    hideLoader();
                }, 1200);
            }
        });

        document.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest('a[href]');
            if (!link || link.matches('[data-no-loading]')) return;

            const rawHref = link.getAttribute('href') || '';
            if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:') || rawHref.startsWith('tel:')) return;

            let destination;
            try { destination = new URL(link.href, window.location.href); } catch (_) { return; }
            if (!['http:', 'https:'].includes(destination.protocol) || destination.origin !== window.location.origin) return;

            markAction(link);
            showLoader();
            const opensSeparately = (link.getAttribute('target') || '').toLowerCase() === '_blank';
            const isDownload = link.hasAttribute('download')
                || /\bdownload\b/i.test(link.textContent || '')
                || /\/(?:download|template)(?:\/|$)/i.test(destination.pathname);
            if (opensSeparately || isDownload) {
                window.setTimeout(hideLoader, 900);
            }
        });

        window.addEventListener('pageshow', hideLoader);
        window.addEventListener('pagehide', () => window.clearTimeout(fallbackTimer));
        window.FlaLoader = { show: showLoader, hide: hideLoader };
    })();
</script>
