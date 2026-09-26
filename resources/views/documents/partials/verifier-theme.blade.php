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
    .verify-header-link:not(a) { border:0; border-radius:0; padding-right:0; color:#dbe5f4; font-weight:600; }
    .verify-header-link:hover { border-color:var(--gold); background:rgba(255,210,45,.1); color:var(--gold); }
    .verify-header-link svg { width:16px; height:16px; }
    .verify-main { flex:1; max-width:920px; padding-block:54px 64px; }
    .lookup-page-heading { margin-bottom:26px; }
    .lookup-page-heading p { margin:0 0 7px; color:#59677a; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .lookup-page-heading h1 { margin:0; color:var(--navy); font-size:32px; line-height:1.2; letter-spacing:-.025em; }
    .lookup-page-heading span { display:block; margin-top:10px; color:var(--muted); font-size:14px; line-height:1.6; }
    .lookup-layout { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(260px,.8fr); gap:0; overflow:hidden; border:1px solid #cfd8e5; border-radius:6px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.06); }
    .lookup-card { padding:34px 38px 38px; }
    .lookup-card-heading { display:flex; align-items:flex-start; gap:14px; border-bottom:1px solid #e2e8f0; padding-bottom:22px; }
    .lookup-card-icon { display:grid; width:38px; height:38px; flex:0 0 auto; place-items:center; border-radius:4px; background:var(--navy); color:#fff; }
    .lookup-card-icon svg { width:20px; height:20px; }
    .lookup-card h2 { margin:1px 0 5px; color:var(--navy); font-size:18px; line-height:1.35; }
    .lookup-card-heading p { margin:0; color:var(--muted); font-size:12px; line-height:1.55; }
    .lookup-form label { display:block; margin:24px 0 8px; color:#334155; font-size:12px; font-weight:700; }
    .lookup-input-wrap { position:relative; }
    .lookup-form input { width:100%; height:48px; border:1px solid #aeb9c8; border-radius:4px; outline:0; padding:0 14px; color:var(--navy); background:#fff; font:600 14px/1 Inter,sans-serif; letter-spacing:.035em; text-transform:uppercase; transition:.18s ease; }
    .lookup-form input:focus { border-color:var(--navy); box-shadow:0 0 0 4px rgba(255,210,45,.34); }
    .primary-button { display:inline-flex; min-height:44px; align-items:center; justify-content:center; margin-top:12px; border:0; border-radius:4px; padding:0 23px; background:var(--navy); color:#fff; font:700 13px/1 Inter,sans-serif; cursor:pointer; transition:.18s ease; }
    .primary-button:hover { background:var(--navy-2); }
    .lookup-help { border-left:1px solid #e2e8f0; padding:35px 30px; background:#f8fafc; }
    .lookup-help h2 { margin:0 0 18px; color:var(--navy); font-size:15px; }
    .lookup-help ol { display:grid; gap:15px; margin:0; padding-left:20px; color:#334155; font-size:12px; line-height:1.55; }
    .lookup-help li { padding-left:4px; }
    .lookup-help li::marker { color:var(--navy); font-weight:700; }
    .lookup-help p { margin:23px 0 0; border-top:1px solid #dce3ec; padding-top:19px; color:var(--muted); font-size:11px; line-height:1.65; }
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
    @media(max-width:760px){.verify-main{padding-block:34px}.lookup-layout{grid-template-columns:1fr}.lookup-card{padding:30px 26px}.lookup-help{border-top:1px solid #e2e8f0;border-left:0;padding:27px 26px}.evidence-strip{grid-template-columns:1fr}.detail-grid{grid-template-columns:1fr}.detail-item:nth-child(odd){margin-right:0}.technical-row{grid-template-columns:1fr;gap:5px}.result-footer{align-items:flex-start;flex-direction:column}}
    @media(max-width:520px){.verify-shell,.result-container{width:min(100% - 20px,1080px)}.verify-header-inner{min-height:68px}.verify-brand img{width:41px;height:41px}.verify-brand strong{font-size:13px}.verify-header-link span{display:none}.verify-header-link{padding:10px}.lookup-page-heading h1{font-size:27px}.lookup-card{padding:25px 20px}.lookup-card-heading{gap:11px}.lookup-help{padding:24px 20px}.primary-button{width:100%}.result-banner{grid-template-columns:1fr;padding:21px}.result-icon{width:46px;height:46px}.card-heading{align-items:flex-start;flex-direction:column;padding:18px}.detail-grid{padding-inline:18px}.technical{margin-inline:18px}.file-check{padding:19px}}
</style>
