<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root { --navy:#000638; --navy-2:#11194f; --gold:#ffd22d; --ink:#111827; --muted:#64748b; --line:#dbe3ef; }
    * { box-sizing:border-box; }
    body.verify-page { margin:0; min-height:100vh; display:flex; flex-direction:column; background:#f5f7fb; color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,sans-serif; }
    .verify-shell { width:min(100% - 32px,1080px); margin-inline:auto; }
    .verify-header { border-bottom:1px solid rgba(255,255,255,.12); background:var(--navy); color:#fff; box-shadow:0 8px 28px rgba(0,6,56,.14); }
    .verify-header-inner { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
    .verify-brand { display:inline-flex; min-width:0; align-items:center; gap:12px; text-decoration:none; }
    .verify-brand img { width:48px; height:48px; flex:0 0 auto; border-radius:50%; object-fit:contain; background:#fff; box-shadow:0 0 0 2px var(--gold); }
    .verify-brand strong { display:block; overflow:hidden; color:#fff; font-size:15px; text-overflow:ellipsis; white-space:nowrap; }
    .verify-brand small { display:block; margin-top:3px; color:var(--gold); font-size:11px; font-weight:600; }
    .verify-header-link { display:inline-flex; align-items:center; gap:7px; border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:10px 14px; color:#fff; font-size:12px; font-weight:700; text-decoration:none; transition:.18s ease; }
    .verify-header-link:hover { border-color:var(--gold); background:rgba(255,210,45,.1); color:var(--gold); }
    .verify-header-link svg { width:16px; height:16px; }
    .verify-main { flex:1; padding-block:48px; }
    .eyebrow { display:inline-flex; align-items:center; gap:7px; margin:0 0 14px; border-radius:999px; padding:7px 11px; background:#fff4bd; color:var(--navy); font-size:11px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; }
    .lookup-layout { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(360px,.95fr); overflow:hidden; border:1px solid var(--line); border-radius:22px; background:#fff; box-shadow:0 24px 64px rgba(15,23,42,.10); }
    .lookup-intro { position:relative; overflow:hidden; padding:52px 48px; background:linear-gradient(145deg,var(--navy),#101958); color:#fff; }
    .lookup-intro::after { content:''; position:absolute; width:260px; height:260px; right:-130px; bottom:-140px; border:46px solid rgba(255,210,45,.10); border-radius:50%; }
    .lookup-intro .eyebrow,.lookup-intro h1,.lookup-intro>p,.trust-list { position:relative; z-index:1; }
    .lookup-intro h1 { max-width:520px; margin:0; font-size:clamp(31px,4vw,46px); line-height:1.1; letter-spacing:-.035em; }
    .lookup-intro>p { max-width:540px; margin:18px 0 0; color:#dbe5f4; font-size:15px; line-height:1.7; }
    .trust-list { display:grid; gap:13px; margin-top:34px; }
    .trust-item { display:flex; align-items:center; gap:11px; color:#f8fafc; font-size:13px; font-weight:600; }
    .trust-icon { display:grid; width:29px; height:29px; flex:0 0 auto; place-items:center; border-radius:9px; background:rgba(255,210,45,.14); color:var(--gold); }
    .trust-icon svg { width:15px; height:15px; }
    .lookup-card { align-self:center; padding:48px 42px; }
    .lookup-card-icon { display:grid; width:52px; height:52px; place-items:center; border-radius:15px; background:var(--navy); color:var(--gold); }
    .lookup-card-icon svg { width:25px; height:25px; }
    .lookup-card h2 { margin:20px 0 8px; color:var(--navy); font-size:25px; letter-spacing:-.02em; }
    .lookup-card>p { margin:0; color:var(--muted); font-size:13px; line-height:1.65; }
    .lookup-form label { display:block; margin:25px 0 8px; color:#334155; font-size:12px; font-weight:800; }
    .lookup-input-wrap { position:relative; }
    .lookup-input-wrap svg { position:absolute; top:50%; left:14px; width:18px; height:18px; color:#94a3b8; transform:translateY(-50%); }
    .lookup-form input { width:100%; height:50px; border:1px solid #b9c5d5; border-radius:11px; outline:0; padding:0 14px 0 43px; color:var(--navy); background:#fff; font:700 14px/1 Inter,sans-serif; letter-spacing:.035em; text-transform:uppercase; transition:.18s ease; }
    .lookup-form input:focus { border-color:var(--navy); box-shadow:0 0 0 4px rgba(255,210,45,.34); }
    .primary-button { display:inline-flex; width:100%; min-height:48px; align-items:center; justify-content:center; gap:9px; margin-top:12px; border:0; border-radius:11px; background:var(--navy); color:#fff; font:800 13px/1 Inter,sans-serif; cursor:pointer; transition:.18s ease; }
    .primary-button:hover { background:var(--navy-2); transform:translateY(-1px); box-shadow:0 10px 22px rgba(0,6,56,.18); }
    .primary-button svg { width:17px; height:17px; color:var(--gold); }
    .lookup-hint { display:flex; align-items:flex-start; gap:8px; margin-top:17px!important; color:#718096!important; font-size:11px!important; }
    .lookup-hint svg { width:15px; height:15px; flex:0 0 auto; margin-top:1px; }
    .verify-error { display:flex; align-items:flex-start; gap:9px; margin:18px 0 0; border:1px solid #fecaca; border-radius:10px; padding:12px 13px; background:#fff1f2; color:#b42318; font-size:12px; line-height:1.5; }
    .verify-error svg { width:17px; height:17px; flex:0 0 auto; }
    .verify-footer { border-top:1px solid #e2e8f0; padding:22px 16px; background:#fff; color:#64748b; text-align:center; font-size:11px; line-height:1.6; }
    .verify-footer strong { color:var(--navy); }
    .result-container { width:min(100% - 32px,980px); margin-inline:auto; }
    .result-banner { display:grid; grid-template-columns:auto 1fr; gap:18px; align-items:center; margin-bottom:20px; border:1px solid; border-radius:18px; padding:25px 27px; box-shadow:0 12px 32px rgba(15,23,42,.06); }
    .result-banner.authentic { border-color:#a7f3d0; background:#ecfdf5; color:#05603a; }
    .result-banner.expired,.result-banner.superseded { border-color:#fde68a; background:#fffbeb; color:#854d0e; }
    .result-banner.revoked,.result-banner.tampered,.result-banner.invalid_link,.result-banner.not_found,.result-banner.invalid { border-color:#fecaca; background:#fff1f2; color:#9f1d1d; }
    .result-icon { display:grid; width:54px; height:54px; place-items:center; border-radius:50%; background:currentColor; }
    .result-icon svg { width:26px; height:26px; color:#fff; }
    .result-kicker { margin:0 0 5px; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; opacity:.78; }
    .result-banner h1 { margin:0; font-size:clamp(22px,4vw,30px); line-height:1.2; letter-spacing:-.02em; }
    .result-message { margin:7px 0 0; color:currentColor; font-size:13px; line-height:1.6; opacity:.88; }
    .evidence-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
    .evidence-item { display:flex; align-items:center; gap:10px; border:1px solid var(--line); border-radius:12px; padding:13px 14px; background:#fff; color:#334155; font-size:11px; font-weight:700; }
    .evidence-dot { width:9px; height:9px; flex:0 0 auto; border-radius:50%; background:#ef4444; box-shadow:0 0 0 4px #fee2e2; }
    .evidence-item.valid .evidence-dot { background:#10b981; box-shadow:0 0 0 4px #d1fae5; }
    .document-card { overflow:hidden; margin-bottom:20px; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 16px 42px rgba(15,23,42,.07); }
    .card-heading { display:flex; align-items:center; justify-content:space-between; gap:16px; border-bottom:1px solid #e7ecf3; padding:20px 24px; background:#fbfcfe; }
    .card-heading h2 { margin:0; color:var(--navy); font-size:17px; }
    .card-heading p { margin:4px 0 0; color:var(--muted); font-size:11px; }
    .control-badge { border-radius:9px; padding:8px 10px; background:var(--navy); color:var(--gold); font:800 11px/1 Inter,sans-serif; white-space:nowrap; }
    .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); padding:8px 24px 20px; }
    .detail-item { min-width:0; border-bottom:1px solid #edf1f6; padding:15px 0; }
    .detail-item:nth-child(odd) { margin-right:22px; }
    .detail-label { display:block; margin-bottom:5px; color:var(--muted); font-size:10px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; }
    .detail-value { display:block; overflow-wrap:anywhere; color:#1e293b; font-size:13px; font-weight:700; line-height:1.5; }
    .technical { margin:0 24px 24px; border:1px solid #dbe3ef; border-radius:12px; overflow:hidden; }
    .technical summary { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; background:#f8fafc; color:var(--navy); font-size:12px; font-weight:800; cursor:pointer; list-style:none; }
    .technical summary::-webkit-details-marker { display:none; }
    .technical summary::after { content:'+'; display:grid; width:22px; height:22px; place-items:center; border-radius:7px; background:#e8edf5; font-size:16px; }
    .technical[open] summary::after { content:'−'; }
    .technical-list { margin:0; padding:2px 16px 12px; }
    .technical-row { display:grid; grid-template-columns:190px minmax(0,1fr); gap:14px; border-bottom:1px solid #edf1f6; padding:12px 0; }
    .technical-row:last-child { border-bottom:0; }
    .technical-row dt,.technical-row dd { margin:0; }
    .technical-row dt { color:var(--muted); font-size:11px; }
    .technical-row dd { overflow-wrap:anywhere; color:#334155; font:600 11px/1.55 Inter,sans-serif; }
    .technical-row code { color:var(--navy); font:600 10px/1.6 ui-monospace,SFMono-Regular,Consolas,monospace; }
    .acceptance-warning { display:flex; align-items:flex-start; gap:11px; margin:0 0 20px; border:1px solid #f6d675; border-left:4px solid var(--gold); border-radius:11px; padding:14px 15px; background:#fffaf0; color:#744b00; font-size:11px; line-height:1.6; }
    .acceptance-warning svg { width:18px; height:18px; flex:0 0 auto; margin-top:1px; }
    .file-check { margin-bottom:20px; border:1px solid var(--line); border-radius:18px; padding:24px; background:#fff; box-shadow:0 14px 36px rgba(15,23,42,.06); }
    .file-check-heading { display:flex; align-items:flex-start; gap:13px; margin-bottom:18px; }
    .file-check-icon { display:grid; width:40px; height:40px; flex:0 0 auto; place-items:center; border-radius:12px; background:var(--navy); color:var(--gold); }
    .file-check-icon svg { width:20px; height:20px; }
    .file-check h2 { margin:0; color:var(--navy); font-size:17px; }
    .file-check p { margin:5px 0 0; color:var(--muted); font-size:11px; line-height:1.6; }
    .file-check input[type=file] { display:block; width:100%; border:1px solid #b9c5d5; border-radius:10px; background:#fff; color:#64748b; font:500 12px/1 Inter,sans-serif; }
    .file-check input[type=file]::file-selector-button { margin-right:12px; border:0; border-right:1px solid #dbe3ef; padding:13px; background:#fff5c4; color:var(--navy); font-weight:800; cursor:pointer; }
    .file-result { margin-bottom:16px; border-radius:11px; padding:14px 15px; font-size:12px; line-height:1.55; }
    .file-result strong { display:block; margin-bottom:3px; }
    .file-result code { overflow-wrap:anywhere; font-size:10px; }
    .file-match { border:1px solid #a7f3d0; background:#ecfdf5; color:#05603a; }
    .file-mismatch { border:1px solid #fecaca; background:#fff1f2; color:#9f1d1d; }
    .error-list { margin:0 0 13px; padding-left:18px; color:#b42318; font-size:11px; }
    .result-footer { display:flex; align-items:center; justify-content:space-between; gap:16px; border:1px solid var(--line); border-radius:13px; padding:15px 17px; background:#fff; color:var(--muted); font-size:10px; line-height:1.55; }
    .result-footer a { color:var(--navy); font-size:11px; font-weight:800; text-decoration:none; }
    @media(max-width:760px){.verify-main{padding-block:28px}.lookup-layout{grid-template-columns:1fr}.lookup-intro,.lookup-card{padding:34px 26px}.evidence-strip{grid-template-columns:1fr}.detail-grid{grid-template-columns:1fr}.detail-item:nth-child(odd){margin-right:0}.technical-row{grid-template-columns:1fr;gap:5px}.result-footer{align-items:flex-start;flex-direction:column}}
    @media(max-width:520px){.verify-shell,.result-container{width:min(100% - 20px,1080px)}.verify-header-inner{min-height:68px}.verify-brand img{width:41px;height:41px}.verify-brand strong{font-size:13px}.verify-header-link span{display:none}.verify-header-link{padding:10px}.lookup-intro,.lookup-card{padding:28px 21px}.result-banner{grid-template-columns:1fr;padding:21px}.result-icon{width:46px;height:46px}.card-heading{align-items:flex-start;flex-direction:column;padding:18px}.detail-grid{padding-inline:18px}.technical{margin-inline:18px}.file-check{padding:19px}}
</style>
