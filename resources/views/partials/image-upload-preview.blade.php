<style>
    .fla-upload-preview { margin-top: .75rem; overflow: hidden; border: 1px solid #cbd5e1; border-radius: .85rem; background: #f8fafc; }
    .fla-upload-preview[hidden] { display: none !important; }
    .fla-upload-preview__head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.65rem .8rem; border-bottom:1px solid #e2e8f0; background:#fff; }
    .fla-upload-preview__title { color:#0f172a; font-size:.78rem; font-weight:800; }
    .fla-upload-preview__clear { min-height:2rem; border:1px solid #fecaca; border-radius:.55rem; padding:.35rem .65rem; background:#fff; color:#b91c1c; font-size:.72rem; font-weight:800; cursor:pointer; }
    .fla-upload-preview__grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(135px,1fr)); gap:.65rem; padding:.75rem; }
    .fla-upload-preview__item { min-width:0; overflow:hidden; border:1px solid #e2e8f0; border-radius:.7rem; background:#fff; }
    .fla-upload-preview__image-button { display:block; width:100%; cursor:zoom-in; background:#e2e8f0; }
    .fla-upload-preview__image { width:100%; height:145px; object-fit:contain; background:#e2e8f0; }
    .fla-upload-preview__file { display:grid; height:145px; place-items:center; padding:1rem; color:#334155; text-align:center; font-size:.75rem; font-weight:800; }
    .fla-upload-preview__meta { padding:.55rem .65rem; }
    .fla-upload-preview__name { overflow:hidden; color:#0f172a; font-size:.72rem; font-weight:800; text-overflow:ellipsis; white-space:nowrap; }
    .fla-upload-preview__size { margin-top:.2rem; color:#64748b; font-size:.68rem; }
    .fla-image-lightbox { position:fixed; inset:0; z-index:11000; display:none; align-items:center; justify-content:center; padding:1rem; background:rgba(2,6,23,.9); }
    .fla-image-lightbox.is-open { display:flex; }
    .fla-image-lightbox__panel { position:relative; width:min(96vw,1100px); }
    .fla-image-lightbox__image { width:100%; max-height:85vh; border-radius:1rem; object-fit:contain; background:#fff; }
    .fla-image-lightbox__close { position:absolute; right:.6rem; top:.6rem; min-height:2.75rem; border-radius:.7rem; padding:.6rem .9rem; background:#000638; color:#fff; font-weight:900; box-shadow:0 8px 24px rgba(0,0,0,.3); }
    .fla-image-lightbox__caption { margin-top:.6rem; overflow-wrap:anywhere; color:#fff; text-align:center; font-size:.8rem; font-weight:700; }
    html.dark .fla-upload-preview { border-color:#334155; background:#111827; }
    html.dark .fla-upload-preview__head, html.dark .fla-upload-preview__item { border-color:#334155; background:#1e293b; }
    html.dark .fla-upload-preview__title, html.dark .fla-upload-preview__name { color:#f8fafc; }
</style>

<div class="fla-image-lightbox" data-fla-image-lightbox role="dialog" aria-modal="true" aria-label="Full image preview">
    <div class="fla-image-lightbox__panel">
        <button type="button" class="fla-image-lightbox__close" data-fla-lightbox-close aria-label="Close image preview">Close</button>
        <img class="fla-image-lightbox__image" data-fla-lightbox-image alt="Selected upload preview">
        <p class="fla-image-lightbox__caption" data-fla-lightbox-caption></p>
    </div>
</div>

<script>
(() => {
    const urls = new WeakMap();
    const lightbox = document.querySelector('[data-fla-image-lightbox]');
    const lightboxImage = lightbox?.querySelector('[data-fla-lightbox-image]');
    const lightboxCaption = lightbox?.querySelector('[data-fla-lightbox-caption]');
    const imagePattern = /image\/(jpeg|png|webp|gif)|\.(jpe?g|png|webp|gif)$/i;
    const formatSize = bytes => bytes < 1024 * 1024
        ? `${Math.max(1, Math.round(bytes / 1024))} KB`
        : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;

    const isImageInput = input => {
        const accept = (input.accept || '').toLowerCase();
        return accept.includes('image/') || /jpg|jpeg|png|webp|gif/.test(accept);
    };

    const closeLightbox = () => {
        if (!lightbox) return;
        lightbox.classList.remove('is-open');
        lightboxImage.removeAttribute('src');
    };

    const previewFor = input => {
        const anchor = input.closest('label') || input;
        let preview = anchor.nextElementSibling?.matches?.('[data-fla-upload-preview]') ? anchor.nextElementSibling : null;
        if (preview) return preview;
        preview = document.createElement('section');
        preview.className = 'fla-upload-preview';
        preview.hidden = true;
        preview.dataset.flaUploadPreview = '';
        preview.innerHTML = `<div class="fla-upload-preview__head"><p class="fla-upload-preview__title">Selected file preview</p><button type="button" class="fla-upload-preview__clear">Remove selection</button></div><div class="fla-upload-preview__grid"></div>`;
        anchor.insertAdjacentElement('afterend', preview);
        preview.querySelector('.fla-upload-preview__clear').addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        });
        return preview;
    };

    const render = input => {
        if (input.dataset.noUniversalPreview !== undefined || !isImageInput(input)) return;
        const previous = urls.get(input) || [];
        previous.forEach(url => URL.revokeObjectURL(url));
        const currentUrls = [];
        urls.set(input, currentUrls);
        const preview = previewFor(input);
        const grid = preview.querySelector('.fla-upload-preview__grid');
        const files = Array.from(input.files || []);
        grid.replaceChildren();
        preview.hidden = files.length === 0;
        preview.querySelector('.fla-upload-preview__title').textContent = files.length > 1 ? `${files.length} selected files` : 'Selected file preview';

        files.forEach(file => {
            const item = document.createElement('article');
            item.className = 'fla-upload-preview__item';
            const imageFile = imagePattern.test(file.type) || imagePattern.test(file.name);
            if (imageFile) {
                const url = URL.createObjectURL(file);
                currentUrls.push(url);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'fla-upload-preview__image-button';
                button.setAttribute('aria-label', `Enlarge preview of ${file.name}`);
                const image = document.createElement('img');
                image.className = 'fla-upload-preview__image';
                image.src = url;
                image.alt = `Preview of ${file.name}`;
                button.appendChild(image);
                button.addEventListener('click', () => {
                    if (!lightbox) return;
                    lightboxImage.src = url;
                    lightboxCaption.textContent = file.name;
                    lightbox.classList.add('is-open');
                    lightbox.querySelector('[data-fla-lightbox-close]').focus();
                });
                item.appendChild(button);
            } else {
                const fileTile = document.createElement('div');
                fileTile.className = 'fla-upload-preview__file';
                fileTile.textContent = 'Document selected — preview available after upload';
                item.appendChild(fileTile);
            }
            const meta = document.createElement('div');
            meta.className = 'fla-upload-preview__meta';
            const name = document.createElement('p');
            name.className = 'fla-upload-preview__name';
            name.title = file.name;
            name.textContent = file.name;
            const size = document.createElement('p');
            size.className = 'fla-upload-preview__size';
            size.textContent = formatSize(file.size);
            meta.append(name, size);
            item.appendChild(meta);
            grid.appendChild(item);
        });
    };

    document.addEventListener('change', event => {
        if (event.target instanceof HTMLInputElement && event.target.type === 'file') render(event.target);
    });
    document.querySelectorAll('input[type="file"]').forEach(render);
    lightbox?.querySelector('[data-fla-lightbox-close]')?.addEventListener('click', closeLightbox);
    lightbox?.addEventListener('click', event => { if (event.target === lightbox) closeLightbox(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeLightbox(); });
    window.addEventListener('beforeunload', () => document.querySelectorAll('input[type="file"]').forEach(input => (urls.get(input) || []).forEach(url => URL.revokeObjectURL(url))));
})();
</script>
