<?php
require_once __DIR__ . '/auth.php';
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DSSO · Plataforma de Gestión de Compras</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Slab:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="public/js/plotly.min.js"></script>
  <style>
    :root {
      --gob-primary:#0F69C4; --gob-primary-dark:#0A4F96; --gob-primary-light:#D6E8F9;
      --gob-success:#15803D; --gob-warning:#B45309; --gob-danger:#B91C1C;
      --gob-dark:#1A1A2E; --gob-gray-900:#212529; --gob-gray-500:#6C757D;
      --gob-gray-300:#DEE2E6; --gob-gray-100:#F4F6F8; --gob-white:#FFFFFF;
      --gob-border:#D0D7E2; --shadow-sm:0 1px 6px rgba(15,105,196,0.08);
    }
    *,*::before,*::after{box-sizing:border-box;}
    body{font-family:'Roboto',sans-serif;background:#fff;color:var(--gob-gray-900);font-size:14px;line-height:1.6;margin:0;}
    h1,h2,h3,h4,h5{font-family:'Roboto Slab',serif;color:var(--gob-dark);}
    a{color:var(--gob-primary);}
    /* TOPSTRIP */
    .gob-topstrip{background:#D0202F;height:8px;width:100%;}
    /* HEADER */
    .gob-header{background:#fff;border-bottom:3px solid var(--gob-primary);position:sticky;top:0;z-index:1000;box-shadow:var(--shadow-sm);}
    .gob-header-inner{display:flex;align-items:center;justify-content:space-between;padding:10px 32px;max-width:1600px;margin:0 auto;}
    .gob-header-brand{display:flex;align-items:center;gap:14px;text-decoration:none!important;}
    .gob-escudo{width:44px;height:44px;background:var(--gob-primary);border-radius:4px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;font-family:'Roboto Slab',serif;}
    .gob-header-titles .ministry{font-family:'Roboto Slab',serif;font-size:1rem;font-weight:700;color:var(--gob-dark);}
    .gob-header-titles .service{font-size:0.72rem;color:var(--gob-gray-500);}
    .gob-header-meta{display:flex;align-items:center;gap:16px;font-size:0.78rem;color:var(--gob-gray-500);}
    .periodo-badge{background:var(--gob-primary-light);color:var(--gob-primary-dark);padding:4px 12px;border-radius:20px;font-weight:600;font-size:0.72rem;}
    .af-badge{background:#FEF3C7;color:#92400E;padding:4px 12px;border-radius:20px;font-weight:600;font-size:0.72rem;}
    /* SUBNAV */
    .gob-subnav{background:var(--gob-primary);}
    .gob-subnav-inner{max-width:1600px;margin:0 auto;padding:0 32px;display:flex;}
    .gob-subnav a{display:inline-flex;align-items:center;padding:11px 18px;color:rgba(255,255,255,0.75);font-size:0.8rem;font-weight:500;text-decoration:none;border-bottom:3px solid transparent;transition:all 0.15s;gap:6px;}
    .gob-subnav a:hover{color:#fff;background:rgba(255,255,255,0.08);}
    .gob-subnav a.active{color:#fff;border-bottom-color:#FFD100;background:rgba(255,255,255,0.1);font-weight:600;}
    /* HERO */
    .gob-page-hero{background:linear-gradient(135deg,#0A4F96 0%,#0F69C4 55%,#1a8fd1 100%);padding:36px 32px 30px;position:relative;overflow:hidden;}
    .gob-page-hero-inner{max-width:1600px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:32px;}
    .hero-texto{flex:1;}
    .breadcrumb-gov{display:flex;align-items:center;gap:6px;font-size:0.73rem;color:rgba(255,255,255,0.6);margin-bottom:12px;}
    .gob-page-hero h1{font-family:'Roboto Slab',serif;font-size:1.75rem;font-weight:700;color:#fff;margin:0 0 6px;}
    .gob-page-hero p{color:rgba(255,255,255,0.7);font-size:0.88rem;margin:0 0 16px;max-width:600px;}
    .tags-row{display:flex;gap:8px;flex-wrap:wrap;}
    .gob-tag{background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);color:rgba(255,255,255,0.9);padding:3px 12px;border-radius:2px;font-size:0.7rem;font-weight:500;}
    .hero-logo img{height:110px;width:auto;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,0.4);}
    /* PAGE HEADER SIMPLE */
    .page-header-simple{display:none;background:#F8F9FA;border-bottom:1px solid #DEE2E6;padding:18px 0 14px;}
    .page-header-simple .phi{max-width:1600px;margin:0 auto;padding:0 32px;}
    .page-header-simple h2{font-size:1.25rem;font-weight:700;color:#1A1A2E;margin:0 0 2px;}
    .page-header-simple p{font-size:0.8rem;color:#6C757D;margin:0;}
    .phi-breadcrumb{font-size:0.72rem;color:#6C757D;margin-bottom:4px;}
    /* FILTROS */
    .gob-filtros{background:#fff;border:1px solid var(--gob-border);border-left:4px solid var(--gob-primary);border-radius:0 4px 4px 0;padding:16px 24px;margin-bottom:28px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;box-shadow:var(--shadow-sm);}
    .fgroup{display:flex;flex-direction:column;gap:4px;}
    .fgroup label{font-size:0.67rem;font-weight:700;text-transform:uppercase;letter-spacing:0.09em;color:var(--gob-gray-500);}
    .fgroup input,.fgroup select{font-family:'Roboto',sans-serif;font-size:0.83rem;border:1.5px solid var(--gob-border);border-radius:3px;padding:7px 12px;background:#fff;outline:none;min-width:150px;transition:border-color 0.15s;}
    .fgroup input:focus,.fgroup select:focus{border-color:var(--gob-primary);}
    .btn-gob-primary{background:var(--gob-primary);color:#fff;border:none;border-radius:3px;padding:8px 22px;font-family:'Roboto',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;transition:background 0.15s;}
    .btn-gob-primary:hover{background:var(--gob-primary-dark);}
    .btn-gob-secondary{background:transparent;color:var(--gob-primary);border:1.5px solid var(--gob-primary);border-radius:3px;padding:7px 18px;font-family:'Roboto',sans-serif;font-size:0.83rem;cursor:pointer;}
    .btn-gob-secondary:hover{background:var(--gob-primary-light);}
    /* MAIN */
    .gob-main{max-width:1600px;margin:0 auto;padding:28px 32px 60px;}
    /* KPI */
    .kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:32px;}
    .kpi-card{background:#fff;border:1px solid var(--gob-border);border-top:4px solid var(--gob-primary);border-radius:0 0 4px 4px;padding:20px 22px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;}
    .kpi-card.kpi-c2{border-top-color:var(--gob-success);}
    .kpi-card.kpi-c3{border-top-color:#D97706;}
    .kpi-label{font-size:0.67rem;text-transform:uppercase;letter-spacing:0.11em;color:var(--gob-gray-500);font-weight:700;margin-bottom:6px;}
    .kpi-value{font-family:'Roboto Slab',serif;font-size:1.6rem;font-weight:700;color:var(--gob-dark);line-height:1.1;}
    .kpi-sub{font-size:0.72rem;color:var(--gob-gray-500);margin-top:5px;}
    .kpi-icon{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:2.2rem;opacity:0.06;}
    /* SECTION TITLE */
    .gob-section-title{display:flex;align-items:center;gap:10px;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid var(--gob-gray-300);}
    .gob-section-title .ico{width:32px;height:32px;background:var(--gob-primary-light);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:16px;}
    .gob-section-title h2{font-family:'Roboto Slab',serif;font-size:1rem;font-weight:700;color:var(--gob-primary-dark);margin:0;}
    .gob-section-title small{font-size:0.73rem;color:var(--gob-gray-500);display:block;margin-top:1px;}
    /* CARDS */
    .chart-card{background:#fff;border:1px solid var(--gob-border);border-radius:4px;padding:18px 20px;box-shadow:var(--shadow-sm);margin-bottom:4px;}
    .chart-title{font-family:'Roboto Slab',serif;font-size:0.88rem;font-weight:600;color:var(--gob-dark);margin-bottom:4px;}
    .chart-sub{font-size:0.72rem;color:var(--gob-gray-500);margin-bottom:12px;display:block;}
    .chart-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
    .chart-grid-1{margin-bottom:16px;}
    /* TABLES */
    .gob-table{width:100%;border-collapse:collapse;font-size:0.8rem;}
    .gob-table thead th{background:var(--gob-primary);color:#fff;font-size:0.67rem;text-transform:uppercase;letter-spacing:0.1em;padding:10px 14px;text-align:left;font-weight:600;white-space:nowrap;}
    .gob-table tbody tr{border-bottom:1px solid var(--gob-gray-300);transition:background 0.12s;}
    .gob-table tbody tr:hover{background:var(--gob-primary-light);}
    .gob-table tbody tr:nth-child(even){background:var(--gob-gray-100);}
    .gob-table td{padding:9px 14px;}
    .mono{font-family:'Roboto Mono','Courier New',monospace;font-size:0.78rem;}
    /* BADGES */
    .gob-badge{display:inline-block;padding:2px 10px;border-radius:2px;font-size:0.67rem;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;}
    .badge-primary{background:var(--gob-primary-light);color:var(--gob-primary-dark);}
    .badge-success{background:#DCFCE7;color:#166534;}
    .badge-warning{background:#FEF3C7;color:#92400E;}
    .badge-danger{background:#FEE2E2;color:#991B1B;}
    .badge-gray{background:var(--gob-gray-300);color:var(--gob-gray-500);}
    /* MINI KPI */
    .mini-kpi{background:#F0F4FF;border-radius:4px;padding:14px;text-align:center;border:1px solid #DBEAFE;}
    .mini-kpi .mlabel{font-size:0.7rem;color:#6C757D;text-transform:uppercase;letter-spacing:.5px;}
    .mini-kpi .mvalue{font-size:1.5rem;font-weight:700;color:#0F69C4;margin-top:4px;}
    /* COLLAPSE */
    .gob-collapse-btn{background:none;border:1px solid var(--gob-border);border-radius:3px;padding:6px 14px;font-family:'Roboto',sans-serif;font-size:0.78rem;color:var(--gob-primary);cursor:pointer;margin-bottom:12px;}
    .collapse-pane{display:none;margin-top:10px;}
    .collapse-pane.open{display:block;}
    /* DIVIDER */
    .gob-divider{height:1px;background:var(--gob-gray-300);margin:32px 0;}
    /* SEARCH */
    .gob-search-box{display:flex;gap:0;margin-bottom:16px;}
    .gob-search-box input{flex:1;border:1.5px solid var(--gob-border);border-right:none;border-radius:3px 0 0 3px;padding:9px 14px;font-family:'Roboto',sans-serif;font-size:0.83rem;outline:none;}
    .gob-search-box button{background:var(--gob-primary);color:#fff;border:none;border-radius:0 3px 3px 0;padding:9px 20px;font-family:'Roboto',sans-serif;font-size:0.83rem;font-weight:600;cursor:pointer;}
    /* TAB VIEWS */
    .tab-view{display:none;}
    .tab-view.active{display:block;}
    body.tab-hero-hidden .gob-page-hero{display:none;}
    body.tab-hero-hidden .gob-filtros{display:none;}
    body.tab-hero-hidden .page-header-simple{display:block;}
    /* INF.EJEC */
    .ej-semaforo-card{background:#fff;border:1px solid var(--gob-border);border-radius:8px;padding:18px 20px;position:relative;overflow:hidden;box-shadow:var(--shadow-sm);}
    .ej-semaforo-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;border-radius:8px 8px 0 0;}
    .ej-sem-verde::before{background:#15803D;} .ej-sem-amarillo::before{background:#D97706;} .ej-sem-rojo::before{background:#B91C1C;}
    .ej-sem-nivel{display:inline-flex;align-items:center;gap:6px;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:3px 10px;border-radius:20px;margin-bottom:10px;}
    .ej-sem-verde   .ej-sem-nivel{background:#DCFCE7;color:#15803D;}
    .ej-sem-amarillo .ej-sem-nivel{background:#FEF3C7;color:#B45309;}
    .ej-sem-rojo    .ej-sem-nivel{background:#FEF2F2;color:#B91C1C;}
    .ej-sem-value{font-family:'Roboto Slab',serif;font-size:1.8rem;font-weight:700;line-height:1.1;margin-bottom:4px;}
    .ej-sem-verde   .ej-sem-value{color:#15803D;}
    .ej-sem-amarillo .ej-sem-value{color:#B45309;}
    .ej-sem-rojo    .ej-sem-value{color:#B91C1C;}
    .ej-sem-label{font-size:0.72rem;color:#6C757D;}
    .ej-conc-kpi{background:#F8FAFC;border:1px solid var(--gob-border);border-radius:6px;padding:14px 16px;text-align:center;}
    .ej-conc-kpi-val{font-family:'Roboto Slab',serif;font-size:1.6rem;font-weight:700;color:var(--gob-primary);}
    .ej-conc-kpi-lbl{font-size:0.67rem;text-transform:uppercase;letter-spacing:.08em;color:#6C757D;margin-top:4px;}
    /* ── COMPRA ÁGIL: CANCELADAS/DESIERTAS ─────────────────── */
    .ag-can-kpi{background:#fff;border:1px solid var(--gob-border);border-radius:8px;padding:14px 16px;text-align:center;box-shadow:var(--shadow-sm);}
    .ag-can-kpi-val{font-family:'Roboto Slab',serif;font-size:1.6rem;font-weight:700;}
    .ag-can-kpi-lbl{font-size:0.64rem;text-transform:uppercase;letter-spacing:.07em;color:#6C757D;margin-top:4px;}
    .ag-can-motivo-input{width:100%;box-sizing:border-box;border:1px solid #E2E8F0;border-radius:5px;
      padding:5px 8px;font-size:0.75rem;color:#1E293B;background:#FAFAFA;resize:none;}
    .ag-can-motivo-input:focus{outline:none;border-color:var(--gob-primary);background:#fff;}
    .ag-can-save-btn{margin-top:4px;font-size:0.68rem;background:#1D4ED8;color:#fff;border:none;
      border-radius:4px;padding:3px 8px;cursor:pointer;display:none;}
    .ag-can-saved{font-size:0.68rem;color:#15803D;margin-top:3px;display:none;}
    /* ── GARANTÍAS ─────────────────────────────────────────── */
    .gar-kpi-card{background:#fff;border:1px solid var(--gob-border);border-radius:8px;padding:16px 20px;text-align:center;box-shadow:var(--shadow-sm);}
    .gar-kpi-val{font-family:'Roboto Slab',serif;font-size:1.9rem;font-weight:700;line-height:1;}
    .gar-kpi-lbl{font-size:0.67rem;text-transform:uppercase;letter-spacing:.08em;color:#6C757D;margin-top:5px;}
    /* Semáforos de fila */
    .gar-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:0.68rem;font-weight:700;text-transform:uppercase;white-space:nowrap;}
    .gar-b-vigente   {background:#DCFCE7;color:#15803D;}
    .gar-b-alerta    {background:#FEF3C7;color:#B45309;}
    .gar-b-critico   {background:#FEE2E2;color:#B91C1C;}
    .gar-b-vencida   {background:#1E293B;color:#F1F5F9;}
    .gar-b-plazo_legal{background:#FFF7ED;color:#C2410C;}
    .gar-b-devuelta  {background:#F0FDF4;color:#166534;}
    /* Formulario de registro */
    .gar-form{background:#F8FAFC;border:1px solid var(--gob-border);border-radius:10px;padding:22px 24px;margin-bottom:20px;}
    .gar-form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
    .gar-form label{font-size:0.72rem;font-weight:600;color:#374151;display:block;margin-bottom:4px;}
    .gar-form input,.gar-form select,.gar-form textarea{width:100%;box-sizing:border-box;border:1px solid #D1D5DB;border-radius:6px;padding:7px 10px;font-size:0.82rem;color:#1E293B;background:#fff;}
    .gar-form input:focus,.gar-form select:focus{outline:none;border-color:var(--gob-primary);}
    .gar-form .gar-lookup-row{display:flex;gap:8px;align-items:flex-end;}
    /* Modal devolución */
    .gar-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9000;display:flex;align-items:center;justify-content:center;}
    .gar-modal{background:#fff;border-radius:12px;padding:28px 30px;width:460px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,0.25);}
    .gar-modal h3{font-family:'Roboto Slab',serif;font-size:1.1rem;color:#1E293B;margin-bottom:16px;}
    .gar-modal label{font-size:0.72rem;font-weight:600;color:#374151;display:block;margin:10px 0 4px;}
    .gar-modal input,.gar-modal textarea{width:100%;box-sizing:border-box;border:1px solid #D1D5DB;border-radius:6px;padding:7px 10px;font-size:0.83rem;}
    /* FOOTER */
    .gob-footer{background:#1A1A2E;color:rgba(255,255,255,0.65);margin-top:40px;}
    .gob-footer-main{max-width:1600px;margin:0 auto;padding:36px 32px 24px;display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:40px;}
    .gob-footer h5{font-family:'Roboto Slab',serif;font-size:0.88rem;color:#fff;margin-bottom:14px;}
    .gob-footer p,.gob-footer a{font-size:0.78rem;color:rgba(255,255,255,0.55);line-height:1.7;}
    .gob-footer ul{list-style:none;padding:0;margin:0;}
    .gob-footer ul li{margin-bottom:5px;}
    .gob-footer-strip{background:var(--gob-primary);padding:12px 32px;display:flex;justify-content:space-between;align-items:center;font-size:0.72rem;color:rgba(255,255,255,0.8);}
    @keyframes fadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
    .kpi-card{animation:fadeUp .35s ease both;}
    @media(max-width:1100px){.chart-grid-2{grid-template-columns:1fr;}.gob-footer-main{grid-template-columns:1fr;}}
    @media(max-width:700px){.kpi-grid{grid-template-columns:1fr;}.gob-main{padding:16px;}.gob-filtros{flex-direction:column;}}
    /* INVERSIONES OC */
    .inv-kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin-bottom:28px;}
    @media(max-width:1300px){.inv-kpi-grid{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:700px){.inv-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .inv-badge-unit{display:inline-flex;align-items:center;gap:6px;background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;margin-bottom:20px;}
    .inv-ag-comp-wrap{display:flex;align-items:center;gap:16px;margin-bottom:12px;flex-wrap:wrap;}
    /* KPI tendencia AG */
    .inv-tend-wrap{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
    .inv-tend-card{flex:1;min-width:190px;background:#fff;border:1px solid var(--gob-border);border-radius:8px;padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;}
    .inv-tend-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;border-radius:8px 8px 0 0;}
    .inv-tend-proy::before{background:linear-gradient(90deg,#0F69C4,#38BDF8);}
    .inv-tend-ytd::before {background:linear-gradient(90deg,#15803D,#4ADE80);}
    .inv-tend-peak::before{background:linear-gradient(90deg,#D97706,#FCD34D);}
    .inv-tend-label{font-size:0.6rem;text-transform:uppercase;letter-spacing:.1em;color:#6C757D;font-weight:700;margin-bottom:8px;}
    .inv-tend-value{font-family:'Roboto Slab',serif;font-size:2rem;font-weight:700;line-height:1.1;}
    .inv-tend-sub{font-size:0.71rem;color:#6C757D;margin-top:6px;}
    .inv-tend-sub2{font-size:0.68rem;color:#94A3B8;margin-top:3px;}
    .inv-comp-card{flex:1;min-width:160px;border-radius:6px;padding:16px 20px;text-align:center;}
    .inv-comp-arrow{display:flex;align-items:center;justify-content:center;flex-direction:column;padding:0 6px;white-space:nowrap;}
    .inv-comp-pct{font-size:1.3rem;font-weight:700;}
    .inv-comp-note{font-size:0.65rem;color:#6C757D;margin-top:3px;text-align:center;max-width:80px;}
    .inv-nota{font-size:0.7rem;color:#6C757D;font-style:italic;margin-top:6px;}
    /* Patrones de compra — KPI cards */
    .patron-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
    @media(max-width:900px){.patron-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .patron-kpi-card{border-radius:6px;padding:14px 18px;text-align:center;}
    .patron-kpi-card .pk-label{font-size:0.64rem;text-transform:uppercase;letter-spacing:.08em;font-weight:700;margin-bottom:4px;}
    .patron-kpi-card .pk-num{font-size:2.2rem;font-weight:700;font-family:'Roboto Slab',serif;line-height:1;margin:4px 0;}
    .patron-kpi-card .pk-sub{font-size:0.7rem;color:inherit;opacity:.75;}
    .pk-monopolio{background:#FEE2E2;border:1px solid #FCA5A5;color:#B91C1C;}
    .pk-alta{background:#FEF3C7;border:1px solid #FCD34D;color:#D97706;}
    .pk-media{background:#FFFBEB;border:1px solid #FDE68A;color:#CA8A04;}
    .pk-diversif{background:#DCFCE7;border:1px solid #86EFAC;color:#15803D;}
    .conc-badge{display:inline-block;padding:2px 9px;border-radius:12px;font-size:0.62rem;font-weight:700;color:white;}
    .conc-monopolio{background:#B91C1C;} .conc-alta{background:#D97706;} .conc-media{background:#CA8A04;} .conc-comp{background:#15803D;}
    /* COMPRA ÁGIL */
    .ag-kpi-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:12px;margin-bottom:28px;}
    @media(max-width:1400px){.ag-kpi-grid{grid-template-columns:repeat(4,1fr);}}
    @media(max-width:900px){.ag-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .ag-kpi-card{background:#fff;border:1px solid var(--gob-border);border-radius:6px;padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;border-top:4px solid var(--gob-primary);}
    .ag-kpi-card.ak-adj{border-top-color:#15803D;}
    .ag-kpi-card.ak-des{border-top-color:#B91C1C;}
    .ag-kpi-card.ak-can{border-top-color:#6B7280;}
    .ag-kpi-card.ak-ppto{border-top-color:#0891B2;}
    .ag-kpi-card.ak-real{border-top-color:#7C3AED;}
    .ag-kpi-card.ak-ahorro{border-top-color:#15803D;}
    .ag-kpi-label{font-size:0.62rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gob-gray-500);font-weight:700;margin-bottom:5px;}
    .ag-kpi-value{font-family:'Roboto Slab',serif;font-size:1.45rem;font-weight:700;color:var(--gob-dark);line-height:1.1;}
    .ag-kpi-sub{font-size:0.68rem;color:var(--gob-gray-500);margin-top:4px;}
    .ag-kpi-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:2rem;opacity:0.06;}
    .ag-badge-src{display:inline-flex;align-items:center;gap:6px;background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;margin-bottom:20px;}
    .ag-etl-box{background:#F8F9FA;border:1px solid #DEE2E6;border-radius:6px;padding:18px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
    .ag-etl-status{font-size:0.8rem;color:#6C757D;}
    .ag-tasa-pill{display:inline-block;padding:3px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;background:#DCFCE7;color:#15803D;margin-top:4px;}
    .ag-mes-btn{padding:4px 12px;border-radius:20px;border:1px solid #CBD5E1;background:#F8FAFC;color:#475569;font-size:0.72rem;font-weight:600;cursor:pointer;transition:all .15s;}
    .ag-mes-btn:hover{border-color:#0F69C4;color:#0F69C4;}
    .ag-mes-btn-act{background:#EFF6FF;border-color:#0F69C4;color:#0F69C4;}
    /* TRAZABILIDAD */
    .traz-kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:24px;}
    @media(max-width:1300px){.traz-kpi-grid{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:700px){.traz-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .traz-kpi-card{background:#fff;border:1px solid var(--gob-border);border-radius:6px;padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;border-top:4px solid var(--gob-primary);}
    .traz-kpi-card.tk-ok{border-top-color:#15803D;} .traz-kpi-card.tk-warn{border-top-color:#D97706;}
    .traz-kpi-card.tk-danger{border-top-color:#B91C1C;} .traz-kpi-card.tk-monto{border-top-color:#7C3AED;}
    .traz-kpi-card.tk-link{border-top-color:#0891B2;}
    .traz-kpi-label{font-size:0.62rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gob-gray-500);font-weight:700;margin-bottom:5px;}
    .traz-kpi-value{font-family:'Roboto Slab',serif;font-size:1.45rem;font-weight:700;color:var(--gob-dark);line-height:1.1;}
    .traz-kpi-sub{font-size:0.68rem;color:var(--gob-gray-500);margin-top:4px;}
    .traz-kpi-icon{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:2rem;opacity:0.06;}
    .traz-pct-pill{display:inline-block;padding:2px 10px;border-radius:20px;font-size:0.73rem;font-weight:700;margin-top:4px;}
    .traz-pct-ok{background:#DCFCE7;color:#15803D;} .traz-pct-warn{background:#FEF3C7;color:#D97706;} .traz-pct-danger{background:#FEE2E2;color:#B91C1C;}
    /* Search box */
    .traz-search-box{background:#EFF6FF;border:2px solid #BFDBFE;border-radius:8px;padding:20px 24px;margin-bottom:24px;}
    .traz-search-title{font-family:'Roboto Slab',serif;font-size:1rem;font-weight:700;color:#1E3A5F;margin-bottom:10px;display:flex;align-items:center;gap:8px;}
    .traz-search-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
    .traz-search-input{flex:1;min-width:220px;border:1.5px solid #BFDBFE;border-radius:4px;padding:9px 14px;font-family:'Roboto',sans-serif;font-size:0.9rem;outline:none;background:#fff;}
    .traz-search-input:focus{border-color:var(--gob-primary);}
    .traz-results{margin-top:16px;}
    /* OC Result cards */
    .traz-oc-card{border:1px solid var(--gob-border);border-radius:6px;overflow:hidden;margin-bottom:10px;background:#fff;box-shadow:var(--shadow-sm);}
    .traz-oc-header{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:#F8FAFC;border-bottom:1px solid var(--gob-border);gap:12px;flex-wrap:wrap;}
    .traz-oc-id{font-family:'Roboto Mono',monospace;font-size:0.88rem;font-weight:700;color:var(--gob-primary-dark);}
    .traz-oc-body{display:grid;grid-template-columns:1fr 1fr;gap:0;}
    .traz-oc-col{padding:14px 18px;}
    .traz-oc-col:first-child{border-right:1px solid var(--gob-border);}
    .traz-oc-col-title{font-size:0.67rem;text-transform:uppercase;letter-spacing:.1em;font-weight:700;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid currentColor;}
    .traz-oc-col.col-kimun .traz-oc-col-title{color:#0A4F96;}
    .traz-oc-col.col-mp .traz-oc-col-title{color:#15803D;}
    .traz-field{display:flex;justify-content:space-between;align-items:flex-start;padding:4px 0;border-bottom:1px solid #F1F5F9;font-size:0.78rem;gap:8px;}
    .traz-field-label{color:#6C757D;min-width:90px;flex-shrink:0;}
    .traz-field-value{font-weight:500;color:#1A1A2E;text-align:right;word-break:break-word;}
    .traz-badge-estado{display:inline-block;padding:2px 10px;border-radius:12px;font-size:0.7rem;font-weight:700;color:#fff;}
    .te-completa{background:#15803D;} .te-tramite{background:#D97706;} .te-parcial{background:#0891B2;}
    .te-anulada{background:#6B7280;} .te-cerrada{background:#374151;} .te-ok{background:#15803D;}
    .te-none{background:#94A3B8;}
    .traz-no-mp{font-size:0.78rem;color:#94A3B8;font-style:italic;padding:12px 0;}
    .traz-alerta-bar{display:flex;height:10px;border-radius:4px;overflow:hidden;margin-top:4px;}
    .traz-badge-kimun{background:#EFF6FF;border:1px solid #BFDBFE;color:#1D4ED8;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;margin-bottom:20px;display:inline-flex;align-items:center;gap:6px;}
    @media(max-width:700px){.traz-oc-body{grid-template-columns:1fr;}.traz-oc-col:first-child{border-right:none;border-bottom:1px solid var(--gob-border);}}
    /* LIBERACIÓN OC */
    .lib-kpi-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;}
    @media(max-width:1200px){.lib-kpi-grid{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:700px){.lib-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .lib-kpi-card{background:#fff;border:1px solid var(--gob-border);border-radius:6px;padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;border-top:4px solid #94A3B8;}
    .lib-kpi-label{font-size:0.62rem;text-transform:uppercase;letter-spacing:.1em;font-weight:700;color:#6C757D;margin-bottom:5px;}
    .lib-kpi-value{font-family:'Roboto Slab',serif;font-size:1.7rem;font-weight:700;color:#1A1A2E;line-height:1.1;}
    .lib-kpi-sub{font-size:0.7rem;color:#6C757D;margin-top:4px;}
    .lib-kpi-icon{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:2rem;opacity:0.07;}
    .lib-total   {border-top-color:#374151;}
    .lib-proceso  {border-top-color:#D97706;}
    .lib-enviada  {border-top-color:#0891B2;}
    .lib-cancelada{border-top-color:#B91C1C;}
    .lib-monto    {border-top-color:#7C3AED;}
    /* Estado badges en tabla */
    .lib-badge{display:inline-block;padding:2px 9px;border-radius:12px;font-size:0.68rem;font-weight:700;color:#fff;white-space:nowrap;}
    .lib-b-proceso  {background:#D97706;}
    .lib-b-enviada  {background:#0891B2;}
    .lib-b-cancelada{background:#B91C1C;}
    /* Acción sugerida chips */
    .lib-accion{display:inline-block;padding:2px 9px;border-radius:12px;font-size:0.67rem;font-weight:600;border:1px solid currentColor;white-space:nowrap;}
    .lib-ac-rojo   {color:#B91C1C;border-color:#FCA5A5;background:#FEF2F2;}
    .lib-ac-naranja{color:#D97706;border-color:#FCD34D;background:#FFFBEB;}
    .lib-ac-azul   {color:#0891B2;border-color:#BAE6FD;background:#F0F9FF;}
    /* Badges antigüedad */
    .lib-ant-badge  {display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.68rem;font-weight:700;white-space:nowrap;}
    .lib-ant-critico{background:#FEE2E2;color:#991B1B;}   /* >365 días — rojo */
    .lib-ant-alto   {background:#FEF3C7;color:#92400E;}   /* 181–365 días — ámbar */
    .lib-ant-medio  {background:#FFF7ED;color:#C2410C;}   /* 91–180 días — naranja */
    .lib-ant-reciente{background:#F0FDF4;color:#166534;}  /* ≤90 días — verde */

    /* ── OBRAS CIVILES ─────────────────────────────────── */
    .obras-kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:24px;}
    @media(max-width:1400px){.obras-kpi-grid{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:900px){.obras-kpi-grid{grid-template-columns:repeat(2,1fr);}}
    .obras-kpi-card{background:#fff;border:1px solid var(--gob-border);border-radius:6px;padding:16px 18px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;border-top:4px solid #0F69C4;}
    .obras-kpi-card.ok-ejec{border-top-color:#D97706;}
    .obras-kpi-card.ok-term{border-top-color:#15803D;}
    .obras-kpi-card.ok-gore{border-top-color:#7C3AED;}
    .obras-kpi-card.ok-prom{border-top-color:#0891B2;}
    .obras-kpi-card.ok-top{border-top-color:#B91C1C;}
    .obras-kpi-label{font-size:0.62rem;text-transform:uppercase;letter-spacing:.1em;color:var(--gob-gray-500);font-weight:700;margin-bottom:5px;}
    .obras-kpi-value{font-family:'Roboto Slab',serif;font-size:1.3rem;font-weight:700;color:var(--gob-dark);line-height:1.1;}
    .obras-kpi-sub{font-size:0.68rem;color:var(--gob-gray-500);margin-top:4px;}
    .obras-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.68rem;font-weight:700;}
    .obras-badge-ejec{background:#FEF3C7;color:#92400E;}
    .obras-badge-term{background:#DCFCE7;color:#166534;}
    .obras-badge-canc{background:#F3F4F6;color:#6B7280;}
  </style>
</head>
<body>

<div class="gob-topstrip"></div>

<?php if (!$embed): ?>
<header class="gob-header">
  <div class="gob-header-inner">
    <a href="#" class="gob-header-brand">
      <div class="gob-escudo">G</div>
      <div class="gob-header-titles">
        <div class="ministry">Ministerio de Salud · DSSO</div>
        <div class="service">Servicio de Salud Osorno — División de Gestión de Compras</div>
      </div>
    </a>
    <div class="gob-header-meta">
      <span class="periodo-badge" id="header-periodo">2017 — 2026</span>
      <span class="af-badge">📊 Gestión de Compras</span>
      <span id="topbar-estado" class="af-badge" style="background:#15803D;color:#fff;">✔ Listo</span>
      <a href="logout.php" title="Cerrar sesión (<?= htmlspecialchars(AUTH_USER) ?>)"
         style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.12);
                color:#fff;text-decoration:none;padding:5px 12px;border-radius:6px;
                font-size:0.75rem;font-weight:600;border:1px solid rgba(255,255,255,0.2);
                transition:background .15s"
         onmouseover="this.style.background='rgba(255,255,255,0.22)'"
         onmouseout="this.style.background='rgba(255,255,255,0.12)'">
        👤 <?= htmlspecialchars(AUTH_USER) ?> &nbsp;·&nbsp; Salir
      </a>
    </div>
  </div>
</header>
<?php endif; ?>

<?php if (!$embed): ?>
<nav class="gob-subnav">
  <div class="gob-subnav-inner">
    <a href="#" onclick="return showTab('estadisticas')" id="tab-estadisticas" class="active">📊 Estadísticas Históricas</a>
    <a href="#" onclick="return showTab('inversiones')"    id="tab-inversiones">📈 Inversiones</a>
    <a href="#" onclick="return showTab('obras-civiles')" id="tab-obras-civiles">🏗 Obras Civiles</a>
    <a href="#" onclick="return showTab('compra-agil')"   id="tab-compra-agil">🛒 Compra Ágil</a>
    <a href="#" onclick="return showTab('trazabilidad')" id="tab-trazabilidad">🔍 Trazabilidad</a>
    <a href="#" onclick="return showTab('liberacion')"   id="tab-liberacion">🔓 Liberación OC</a>
    <a href="#" onclick="return showTab('infejec')"      id="tab-infejec">📋 INF.EJEC</a>
    <a href="#" onclick="return showTab('garantias')"    id="tab-garantias">🔒 Garantías</a>
    <a href="#" onclick="return showTab('exportar')"     id="tab-exportar">📥 Exportar</a>
  </div>
</nav>
<?php endif; ?>

<div class="page-header-simple">
  <div class="phi">
    <div class="phi-breadcrumb">Inicio › DSSO › <span id="phi-breadcrumb">—</span></div>
    <h2 id="phi-title">—</h2>
    <p id="phi-desc">—</p>
  </div>
</div>

<div class="gob-page-hero">
  <div class="gob-page-hero-inner">
    <div class="hero-texto">
      <div class="breadcrumb-gov">
        <a href="#" style="color:rgba(255,255,255,0.6);text-decoration:none">Inicio</a>
        <span>›</span>
        <span id="hero-breadcrumb" style="color:#fff">Estadísticas Históricas</span>
      </div>
      <h1 id="hero-title">Estadísticas Históricas — SSO</h1>
      <p id="hero-desc">Consolidado histórico de Órdenes de Compra. Información oficial del sistema de compras públicas DSSO.</p>
      <div class="tags-row">
        <span class="gob-tag">Licitación Pública</span>
        <span class="gob-tag">Convenio Marco</span>
        <span class="gob-tag">Compra Ágil</span>
        <span class="gob-tag">Trato Directo</span>
        <span class="gob-tag">Datos Oficiales</span>
      </div>
    </div>
    <div class="hero-logo">
      <img src="public/img/logo_sso.png" alt="SSO" onerror="this.style.display='none'">
    </div>
  </div>
</div>

<div class="gob-filtros">
  <div class="fgroup" style="flex:1;min-width:220px;">
    <label>🔍 Buscar OC / Proveedor / Producto</label>
    <input type="text" id="inp-busqueda" placeholder="ej: ventilador, Siemens, Monitor...">
  </div>
  <div class="fgroup">
    <label>Año desde</label>
    <select id="sel-anio-desde"></select>
  </div>
  <div class="fgroup">
    <label>Año hasta</label>
    <select id="sel-anio-hasta"></select>
  </div>
  <div class="fgroup">
    <label>Unidad</label>
    <select id="sel-unidad"><option value="">Todas las unidades</option></select>
  </div>
  <div class="fgroup">
    <label>&nbsp;</label>
    <button class="btn-gob-primary" onclick="cargarDatos()">Aplicar filtros</button>
  </div>
  <div class="fgroup">
    <label>&nbsp;</label>
    <button class="btn-gob-secondary" onclick="limpiarFiltros()">↩ Limpiar</button>
  </div>
</div>

<main class="gob-main">

<!-- TAB 1: ESTADÍSTICAS -->
<div id="view-estadisticas" class="tab-view active">
  <div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">MONTO TOTAL TRANSADO</div><div class="kpi-value" id="kpi-total">$ —</div><div class="kpi-sub">Suma OCs adjudicadas (excluye canceladas)</div><div class="kpi-icon">💰</div></div>
    <div class="kpi-card kpi-c2"><div class="kpi-label">PROVEEDORES ÚNICOS</div><div class="kpi-value" id="kpi-prov">—</div><div class="kpi-sub">Empresas adjudicadas en el período</div><div class="kpi-icon">🏢</div></div>
    <div class="kpi-card kpi-c3"><div class="kpi-label">TOTAL REGISTROS</div><div class="kpi-value" id="kpi-ocs">—</div><div class="kpi-sub">Órdenes de compra</div><div class="kpi-icon">📋</div></div>
  </div>

  <!-- ── PRONÓSTICO ─────────────────────────────────── -->
  <div style="display:flex;align-items:center;gap:8px;margin:18px 0 10px">
    <span style="font-size:1rem">🔮</span>
    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#6C757D">Pronóstico</span>
    <div style="flex:1;height:1px;background:linear-gradient(90deg,#CBD5E1,transparent)"></div>
  </div>
  <div id="estad-tendencia" class="inv-tend-wrap"></div>

  <div class="gob-section-title"><div class="ico">📊</div><div><h2>Distribución y Liderazgo</h2><small>Proveedores principales, estados y origen de compras</small></div></div>
  <div class="chart-grid-2">
    <div class="chart-card"><div class="chart-title">🏆 Top 10 Proveedores por Inversión</div><span class="chart-sub">Monto acumulado en millones de pesos</span><div id="chart-top-prov" style="height:320px"></div></div>
    <div class="chart-card"><div class="chart-title">📋 Estado de Órdenes de Compra</div><span class="chart-sub">Distribución porcentual por estado</span><div id="chart-estado" style="height:320px"></div></div>
  </div>

  <div class="gob-section-title"><div class="ico">📅</div><div><h2>Evolución Temporal</h2><small>Comportamiento histórico por tipo de proceso</small></div></div>
  <div class="chart-grid-1">
    <div class="chart-card"><div class="chart-title">📈 Evolución Anual por Tipo de Proceso</div><span class="chart-sub">Cantidad de OCs por año y modalidad</span><div id="chart-evolucion" style="height:320px"></div></div>
  </div>
  <div class="chart-grid-2">
    <div class="chart-card"><div class="chart-title">🔘 Origen de Compras</div><span class="chart-sub">Distribución por modalidad</span><div id="chart-origen" style="height:300px"></div></div>
    <div class="chart-card"><div class="chart-title">🔄 Frecuencia Proveedores</div><span class="chart-sub">Top 10 por número de OCs</span><div id="chart-freq-prov" style="height:300px"></div></div>
  </div>

  <div class="gob-section-title"><div class="ico">📦</div><div><h2>Análisis de Ítems</h2><small>Especificaciones más frecuentes en el período</small></div></div>
  <div class="chart-grid-1">
    <div class="chart-card">
      <div class="chart-title">📦 Top 20 Especificaciones más Frecuentes</div>
      <div id="chart-items-espec" style="height:420px"></div>
      <button class="gob-collapse-btn" onclick="togglePane('pane-tabla-items',this)">📋 Ver tabla completa ▾</button>
      <div class="collapse-pane" id="pane-tabla-items">
        <table class="gob-table"><thead><tr><th>#</th><th>Especificación</th><th>Veces</th><th>Monto</th></tr></thead><tbody id="tbody-items-espec"></tbody></table>
      </div>
    </div>
  </div>

  <div class="gob-section-title"><div class="ico">🔬</div><div><h2>Búsqueda de Equipos</h2><small>Encuentra equipos específicos en el histórico</small></div></div>
  <div class="chart-card" style="margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px">
      <div style="flex:1"><label style="font-size:0.8rem;color:#6C757D;display:block;margin-bottom:4px">Nombre del equipo o ítem</label>
      <input type="text" id="busq-equipo" placeholder="ej: monitor, ventilador, desfibrilador..." style="width:100%;padding:9px 12px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.9rem"></div>
      <button class="btn-gob-primary" onclick="buscarEquipos()">🔍 Buscar</button>
    </div>
    <div id="equipo-kpis"></div>
    <div id="equipo-empty" style="color:#6C757D;font-size:0.85rem;padding:8px 0">Ingresa un término para buscar en el histórico de compras.</div>
    <div id="equipo-charts" style="display:none">
      <div id="chart-eq-prov" style="height:280px;margin-bottom:16px"></div>
      <button class="gob-collapse-btn" onclick="togglePane('pane-tabla-equipo',this)">📋 Ver registros completos ▾</button>
      <div class="collapse-pane" id="pane-tabla-equipo">
        <table class="gob-table"><thead><tr><th>Código OC</th><th>Especificación</th><th>Proveedor</th><th>Año</th><th>Monto</th></tr></thead><tbody id="tbody-equipo"></tbody></table>
      </div>
    </div>
  </div>

  <div class="gob-section-title"><div class="ico">📉</div><div><h2>Tendencia por Proveedor</h2><small>Evolución histórica de compras a un proveedor específico</small></div></div>
  <div class="chart-card" style="margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap">
      <div style="min-width:280px;flex:1"><label style="font-size:0.8rem;color:#6C757D;display:block;margin-bottom:4px">Proveedor</label>
      <select id="sel-prov-tend" style="width:100%;padding:9px 12px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.9rem"><option value="">— Seleccione proveedor —</option></select></div>
      <button class="btn-gob-primary" onclick="cargarTendencia()">Ver tendencia</button>
    </div>
    <div id="tend-empty" style="color:#6C757D;font-size:0.85rem;padding:8px 0">Selecciona un proveedor para ver su historial.</div>
    <div id="tend-charts" style="display:none">
      <div style="margin-bottom:10px"><label style="font-size:0.8rem;color:#6C757D">Filtrar productos:</label>
      <select id="sel-prod-tend" multiple style="width:100%;height:80px;border:1px solid #DEE2E6;border-radius:4px;padding:4px;font-size:0.8rem" onchange="renderTendencia()"></select></div>
      <div id="chart-tend-cantidad" style="height:320px;margin-bottom:16px"></div>
      <button class="gob-collapse-btn" onclick="togglePane('pane-tabla-tend',this)">📋 Ver tabla completa ▾</button>
      <div class="collapse-pane" id="pane-tabla-tend">
        <table class="gob-table"><thead><tr><th>Año</th><th>Producto</th><th>OCs</th><th>Monto</th></tr></thead><tbody id="tbody-tend"></tbody></table>
      </div>
    </div>
  </div>

  <div class="gob-section-title"><div class="ico">🏢</div><div><h2>Directorio de Proveedores</h2><small>Empresas con mayor actividad en el período</small></div></div>
  <div class="chart-card">
    <table class="gob-table"><thead><tr><th>#</th><th>Proveedor</th><th>OCs</th><th>Monto Total</th><th>Modalidad</th><th>Estado</th></tr></thead><tbody id="tbody-proveedores"></tbody></table>
  </div>
</div>

<!-- TAB 2: INVERSIONES OC -->
<div id="view-inversiones" class="tab-view">

  <div class="inv-badge-unit">📌 Unidad de Compras 1057922 — Activos Fijos · Servicio de Salud Osorno</div>

  <!-- KPIs PRINCIPALES -->
  <div class="inv-kpi-grid">
    <div class="kpi-card"><div class="kpi-label">INVERSIÓN TOTAL</div><div class="kpi-value" id="inv-kpi-total">—</div><div class="kpi-sub">Monto acumulado historico</div><div class="kpi-icon">💰</div></div>
    <div class="kpi-card kpi-c2"><div class="kpi-label">TOTAL OCs</div><div class="kpi-value" id="inv-kpi-ocs">—</div><div class="kpi-sub">Órdenes adjudicadas</div><div class="kpi-icon">📋</div></div>
    <div class="kpi-card kpi-c3"><div class="kpi-label">PROVEEDORES</div><div class="kpi-value" id="inv-kpi-provs">—</div><div class="kpi-sub">Empresas adjudicadas</div><div class="kpi-icon">🏢</div></div>
    <div class="kpi-card" style="border-top-color:#7C3AED"><div class="kpi-label">PROMEDIO OC</div><div class="kpi-value inv-kpi-sm" id="inv-kpi-prom">—</div><div class="kpi-sub">Monto promedio por OC</div><div class="kpi-icon">📊</div></div>
    <div class="kpi-card" style="border-top-color:#B45309"><div class="kpi-label">OCs COMPRA ÁGIL</div><div class="kpi-value" id="inv-kpi-ag-n">—</div><div class="kpi-sub">Procesos AG históricos</div><div class="kpi-icon">⚡</div></div>
    <div class="kpi-card" style="border-top-color:#0891B2"><div class="kpi-label">INVERSIÓN ÁGIL</div><div class="kpi-value inv-kpi-sm" id="inv-kpi-ag-m">—</div><div class="kpi-sub">Monto total Compra Ágil</div><div class="kpi-icon">💫</div></div>
  </div>

  <!-- HISTOGRAMA ANUAL -->
  <div class="gob-section-title">
    <div class="ico">📊</div>
    <div><h2>Histograma de Inversión por Año</h2><small>Evolución del gasto total por modalidad de compra — Unidad 1057922</small></div>
  </div>
  <div class="chart-grid-1">
    <div class="chart-card">
      <div class="chart-title">📊 Inversión Anual por Modalidad de Compra</div>
      <span class="chart-sub">Monto acumulado ($M) desglosado por origen — barras apiladas + línea de total</span>
      <div id="chart-inv-histograma" style="height:350px"></div>
    </div>
  </div>

  <!-- COMPRAS ÁGILES -->
  <div class="gob-section-title">
    <div class="ico">⚡</div>
    <div><h2>Compras Ágiles (AG) — Análisis Detallado</h2><small>Evolución histórica y comparativa interanual de la modalidad Compra Ágil</small></div>
  </div>
  <div id="inv-ag-comp" class="inv-ag-comp-wrap"></div>
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;margin-top:4px;">
    <span style="font-size:1rem;">🔮</span>
    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#6C757D;">Pronóstico</span>
    <div style="flex:1;height:1px;background:linear-gradient(90deg,#CBD5E1,transparent);"></div>
  </div>
  <div id="inv-ag-tendencia" class="inv-tend-wrap"></div>
  <div class="chart-grid-2">
    <div class="chart-card">
      <div class="chart-title">⚡ Evolución Compras Ágiles por Año</div>
      <span class="chart-sub">Cantidad de OCs (barras) y monto acumulado en $M (línea)</span>
      <div id="chart-inv-ag-anual" style="height:300px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">📅 N° OCs por Mes — Año Actual vs Año Anterior</div>
      <span class="chart-sub">Comparativa mes a mes entre los dos últimos períodos</span>
      <div style="display:flex;gap:6px;margin:8px 0 4px;align-items:center;">
        <span style="font-size:0.72rem;color:#6C757D;font-weight:600;">Ver:</span>
        <button class="ag-mes-btn ag-mes-btn-act" onclick="invAgMensualToggle(this,'n')">N° OCs</button>
        <button class="ag-mes-btn" onclick="invAgMensualToggle(this,'monto')">Monto ($M)</button>
      </div>
      <div id="chart-inv-ag-comp" style="height:260px"></div>
      <p class="inv-nota">* El año actual puede ser parcial según fecha de corte de datos.</p>
    </div>
  </div>

  <!-- PROVEEDORES: MONTO Y ADJUDICACIÓN -->
  <div class="gob-section-title">
    <div class="ico">🏆</div>
    <div><h2>Análisis de Proveedores</h2><small>Procesos adjudicados por monto e índice de adjudicación por cantidad</small></div>
  </div>
  <div class="chart-grid-2">
    <div class="chart-card">
      <div class="chart-title">💰 Procesos Adjudicados por Proveedor (Monto $)</div>
      <span class="chart-sub">Top 10 proveedores con mayor inversión acumulada en la unidad</span>
      <div id="chart-inv-top-prov" style="height:360px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">🎯 Índice de Adjudicación — Cantidad de Procesos</div>
      <span class="chart-sub">Top 15 proveedores con más OCs adjudicadas históricamente</span>
      <div id="chart-inv-adj" style="height:360px"></div>
    </div>
  </div>

  <!-- MODALIDAD DE COMPRA POR PROVEEDOR (vinculado al índice) -->
  <div class="chart-card" style="margin-bottom:20px">
    <div class="chart-title">🔗 Modalidad de Compra por Proveedor — Top 15</div>
    <span class="chart-sub">Desglose de procesos adjudicados por tipo de compra · identifica patrones de modalidad por proveedor</span>
    <div id="chart-inv-adj-modalidad" style="height:420px"></div>
  </div>

  <!-- PATRONES DE COMPRA PROVEEDOR × PRODUCTO -->
  <div class="gob-section-title">
    <div class="ico">🔍</div>
    <div><h2>Patrones de Compra — Concentración Proveedor × Producto</h2><small>Identifica si un proveedor concentra la adjudicación de un ítem específico · KPI de dependencia por producto</small></div>
  </div>
  <div class="patron-kpi-grid">
    <div class="patron-kpi-card pk-monopolio">
      <div class="pk-label">🔴 Monopolio (100%)</div>
      <div class="pk-num" id="inv-pat-monopolio">—</div>
      <div class="pk-sub">productos con 1 solo proveedor</div>
    </div>
    <div class="patron-kpi-card pk-alta">
      <div class="pk-label">🟠 Alta concentración (&gt;70%)</div>
      <div class="pk-num" id="inv-pat-alta">—</div>
      <div class="pk-sub">dominados por un proveedor</div>
    </div>
    <div class="patron-kpi-card pk-media">
      <div class="pk-label">🟡 Concentración media (50-70%)</div>
      <div class="pk-num" id="inv-pat-media">—</div>
      <div class="pk-sub">proveedor con ventaja</div>
    </div>
    <div class="patron-kpi-card pk-diversif">
      <div class="pk-label">🟢 Diversificado (&lt;50%)</div>
      <div class="pk-num" id="inv-pat-div">—</div>
      <div class="pk-sub">compra competitiva</div>
    </div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div class="chart-title">🔍 Índice de Concentración por Producto</div>
    <span class="chart-sub">% del ítem adjudicado al proveedor dominante — líneas de referencia en 50% y 70%</span>
    <div id="chart-inv-patron" style="height:420px"></div>
    <button class="gob-collapse-btn" onclick="togglePane('pane-inv-patron',this)">📋 Tabla detallada de patrones ▾</button>
    <div class="collapse-pane" id="pane-inv-patron">
      <table class="gob-table">
        <thead><tr><th>#</th><th>Producto / Especificación</th><th>Proveedor Dominante</th><th>Modalidad</th><th>Concentración</th><th>Veces (dom/total)</th><th>N° Proveedores</th></tr></thead>
        <tbody id="tbody-inv-patron"></tbody>
      </table>
    </div>
  </div>

  <!-- LICITACIONES POR AÑO -->
  <div class="gob-section-title">
    <div class="ico">📅</div>
    <div><h2>OCs Adjudicadas por Año y Modalidad</h2><small>Cantidad de OCs por modalidad de compra y monto total anual — Unidad 1057922</small></div>
  </div>
  <div class="chart-grid-1">
    <div class="chart-card">
      <div class="chart-title">📅 OCs Adjudicadas por Año y Modalidad</div>
      <span class="chart-sub">Barras apiladas por modalidad (Licitación, Compra Ágil, Conv. Marco, etc.) y monto total ($M, línea)</span>
      <div id="chart-inv-lit-anio" style="height:300px"></div>
    </div>
  </div>

  <!-- TOP PRODUCTOS -->
  <div class="gob-section-title">
    <div class="ico">📦</div>
    <div><h2>Top 10 Productos más Comprados</h2><small>Especificaciones con mayor frecuencia de adquisición y su proveedor principal</small></div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div class="chart-title">📦 Top 10 Productos más Comprados</div>
    <span class="chart-sub">Frecuencia de compra por especificación — Unidad 1057922</span>
    <div id="chart-inv-productos" style="height:380px"></div>
    <button class="gob-collapse-btn" onclick="togglePane('pane-inv-prod',this)">📋 Ver tabla con proveedores ▾</button>
    <div class="collapse-pane" id="pane-inv-prod">
      <table class="gob-table">
        <thead><tr><th>#</th><th>Especificación del Producto</th><th>Veces</th><th>Monto Acumulado</th><th>Principal Proveedor</th></tr></thead>
        <tbody id="tbody-inv-prod"></tbody>
      </table>
    </div>
  </div>

  <!-- TENDENCIA POR PROVEEDOR -->
  <div class="gob-section-title">
    <div class="ico">📉</div>
    <div><h2>Tendencia por Proveedor</h2><small>Evolución histórica del monto y OCs de un proveedor específico — Unidad 1057922</small></div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap">
      <div style="min-width:280px;flex:1">
        <label style="font-size:0.8rem;color:#6C757D;display:block;margin-bottom:4px">Seleccionar Proveedor</label>
        <select id="inv-sel-prov" style="width:100%;padding:9px 12px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.9rem">
          <option value="">— Seleccione proveedor —</option>
        </select>
      </div>
      <button class="btn-gob-primary" onclick="cargarTendenciaInv()">📈 Ver tendencia</button>
    </div>
    <div id="inv-tend-empty" style="color:#6C757D;font-size:0.85rem;padding:8px 0">Selecciona un proveedor para visualizar su historial de inversión.</div>
    <div id="inv-tend-charts" style="display:none">
      <div id="chart-inv-tend" style="height:340px"></div>
    </div>
  </div>

  <!-- BÚSQUEDA POR EQUIPO / PRODUCTO -->
  <div class="gob-section-title" style="margin-top:8px">
    <div class="ico">🔍</div>
    <div><h2>Búsqueda por Equipo o Producto</h2><small>Top 10 proveedores que adjudican más ese equipo y su evolución histórica — escribe un nombre para explorar</small></div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap">
      <div style="flex:1;min-width:260px">
        <label style="font-size:0.8rem;color:#6C757D;display:block;margin-bottom:4px">Nombre del equipo o ítem</label>
        <input type="text" id="inv-busq-eq" placeholder="ej: camilla, ventilador, monitor, desfibrilador..."
               style="width:100%;padding:9px 12px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.9rem">
      </div>
      <button class="btn-gob-primary" onclick="buscarEquiposInv()">🔍 Buscar</button>
    </div>
    <div id="inv-eq-empty" style="color:#6C757D;font-size:0.85rem;padding:8px 0">
      Ingresa el nombre de un equipo para ver qué proveedores lo adjudican con mayor frecuencia y su evolución a través del tiempo.
    </div>
    <div id="inv-eq-results" style="display:none">
      <div id="inv-eq-kpis" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;"></div>
      <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start">
        <div style="flex:1;min-width:260px">
          <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#6C757D;margin-bottom:8px">Top 10 Proveedores (N° OCs)</div>
          <div id="chart-inv-eq-prov" style="height:320px"></div>
        </div>
        <div style="flex:2;min-width:360px">
          <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:#6C757D;margin-bottom:8px">Tendencia Anual por Proveedor</div>
          <div id="chart-inv-eq-tend" style="height:320px"></div>
        </div>
      </div>
      <button class="gob-collapse-btn" style="margin-top:16px" onclick="togglePane('pane-inv-eq-tabla',this)">📋 Ver registros completos ▾</button>
      <div class="collapse-pane" id="pane-inv-eq-tabla">
        <table class="gob-table">
          <thead><tr><th>Código OC</th><th>Especificación</th><th>Proveedor</th><th>Año</th><th>Monto</th></tr></thead>
          <tbody id="tbody-inv-eq"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- TAB 3: COMPRA ÁGIL -->
<div id="view-compra-agil" class="tab-view">
<div class="gob-main">

  <!-- Encabezado + ETL -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
    <div>
      <div class="ag-badge-src">🛒 Fuente: Excel MP (actualización manual) · Unidad 1057922 — Activos Fijos</div>
      <p style="color:#6C757D;font-size:0.82rem;margin:0">Análisis de cotizaciones y órdenes de compra bajo modalidad <strong>Compra Ágil (AG)</strong>, basado en los archivos exportados de Mercado Público. Incluye todas las etapas: publicadas, adjudicadas, desiertas y canceladas.</p>
    </div>
    <div class="ag-etl-box" style="min-width:280px;">
      <div style="flex:1">
        <div style="font-size:0.78rem;font-weight:700;color:#1A1A2E;margin-bottom:4px;">Actualizar datos desde Excel</div>
        <div class="ag-etl-status" id="ag-etl-msg">Importa los archivos de data_compraagil/ a la base de datos.</div>
      </div>
      <button class="btn-gob-primary" onclick="agEjecutarETL()" id="ag-etl-btn" style="white-space:nowrap;font-size:0.78rem;padding:7px 14px;">🔄 Reimportar</button>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="ag-kpi-grid" id="ag-kpi-grid">
    <div class="ag-kpi-card"><div class="ag-kpi-label">Total Cotizaciones</div><div class="ag-kpi-value" id="ag-k-total">—</div><div class="ag-kpi-sub">Procesos lanzados</div><div class="ag-kpi-icon">📋</div></div>
    <div class="ag-kpi-card ak-adj"><div class="ag-kpi-label">OCs Emitidas</div><div class="ag-kpi-value" id="ag-k-oc">—</div><div class="ag-kpi-sub" id="ag-k-tasa">Tasa de adjudicación</div><div class="ag-kpi-icon">✅</div></div>
    <div class="ag-kpi-card ak-des"><div class="ag-kpi-label">Desiertas</div><div class="ag-kpi-value" id="ag-k-des">—</div><div class="ag-kpi-sub">Sin proveedor seleccionado</div><div class="ag-kpi-icon">🚫</div></div>
    <div class="ag-kpi-card ak-can"><div class="ag-kpi-label">Canceladas</div><div class="ag-kpi-value" id="ag-k-can">—</div><div class="ag-kpi-sub">Procesos anulados</div><div class="ag-kpi-icon">❌</div></div>
    <div class="ag-kpi-card ak-ppto"><div class="ag-kpi-label">Monto Presupuestado</div><div class="ag-kpi-value" id="ag-k-ppto" style="font-size:1.1rem">—</div><div class="ag-kpi-sub">Suma montos disponibles</div><div class="ag-kpi-icon">💰</div></div>
    <div class="ag-kpi-card ak-real"><div class="ag-kpi-label">Monto Ejecutado</div><div class="ag-kpi-value" id="ag-k-real" style="font-size:1.1rem">—</div><div class="ag-kpi-sub">OCs en oc_resumen</div><div class="ag-kpi-icon">💳</div></div>
    <div class="ag-kpi-card ak-ahorro"><div class="ag-kpi-label">Competencia Prom.</div><div class="ag-kpi-value" id="ag-k-comp">—</div><div class="ag-kpi-sub">Cotizaciones recibidas / proceso</div><div class="ag-kpi-icon">🏆</div></div>
  </div>

  <!-- Evolución Anual (full width) -->
  <div class="gob-section-title"><div class="ico">📅</div><div><h2>Evolución Anual de Cotizaciones</h2><small>Cotizaciones totales, OCs emitidas, desiertas y canceladas por año · tasa de adjudicación</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-ag-evolucion" style="height:320px"></div>
  </div>

  <!-- Comparativa Mensual Interanual -->
  <div class="gob-section-title" style="margin-top:20px">
    <div class="ico">📆</div>
    <div><h2>Comparativa por Mes — Mismos Periodos</h2>
    <small>Tendencia mensual año a año · permite comparar enero 2022 vs enero 2023 vs enero 2024…</small></div>
  </div>
  <div class="chart-card chart-grid-1">
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center;">
      <span style="font-size:0.75rem;color:#6C757D;font-weight:600;">Métrica:</span>
      <button class="ag-mes-btn ag-mes-btn-act" data-metric="total"       onclick="agCambiarMetrica(this,'total')">Total cotizaciones</button>
      <button class="ag-mes-btn"                data-metric="oc_emitidas" onclick="agCambiarMetrica(this,'oc_emitidas')">OCs emitidas</button>
      <button class="ag-mes-btn"                data-metric="desiertas"   onclick="agCambiarMetrica(this,'desiertas')">Desiertas</button>
      <button class="ag-mes-btn"                data-metric="monto_ppto"  onclick="agCambiarMetrica(this,'monto_ppto')">Monto presupuestado</button>
    </div>
    <div id="chart-ag-mensual" style="height:320px"></div>
  </div>

  <!-- Distribución Estado + Convocatoria -->
  <div class="chart-grid-2" style="margin-top:16px">
    <div class="chart-card">
      <div class="chart-title">Distribución por Estado</div>
      <span class="chart-sub">Proporción de cotizaciones según estado final</span>
      <div id="chart-ag-estado" style="height:280px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">Primer vs Segundo Llamado por Año</div>
      <span class="chart-sub">Cuántos procesos requirieron segunda convocatoria</span>
      <div id="chart-ag-convocatoria" style="height:280px"></div>
    </div>
  </div>

  <!-- Monto Presupuesto vs Ejecutado -->
  <div class="gob-section-title" style="margin-top:20px"><div class="ico">💵</div><div><h2>Presupuesto vs Monto Ejecutado por Año</h2><small>Comparación entre monto disponible declarado en cotización y monto real de la OC adjudicada</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-ag-monto" style="height:300px"></div>
    <p class="inv-nota">* Monto ejecutado proviene de oc_resumen (OCs con match en base de datos). Las diferencias pueden incluir presupuestos estimados.</p>
  </div>

  <!-- Competencia por Año -->
  <div class="gob-section-title" style="margin-top:20px"><div class="ico">🏆</div><div><h2>Nivel de Competencia por Año</h2><small>Cotizaciones recibidas por proceso adjudicado — indicador de participación de mercado</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-ag-competencia" style="height:290px"></div>
  </div>

  <!-- Top Proveedores + Top Ítems -->
  <div class="chart-grid-2" style="margin-top:16px">
    <div class="chart-card">
      <div class="chart-title">Top 10 Proveedores — Compra Ágil</div>
      <span class="chart-sub">Por monto total adjudicado (OCs en oc_resumen)</span>
      <div id="chart-ag-prov" style="height:340px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">Top 15 Ítems más Comprados</div>
      <span class="chart-sub">Descripción del proceso con OC emitida · frecuencia</span>
      <div id="chart-ag-items" style="height:340px"></div>
    </div>
  </div>

  <!-- Estado OC por Año -->
  <div class="gob-section-title" style="margin-top:20px"><div class="ico">📦</div><div><h2>Estado de las OC Emitidas por Año</h2><small>Recepción conforme, eliminadas, canceladas · seguimiento post-adjudicación</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-ag-estado-oc" style="height:280px"></div>
  </div>

  <!-- ── PROCESOS CANCELADOS / DESIERTOS ─────────────────────── -->
  <div class="gob-section-title" style="margin-top:28px">
    <div class="ico">🔎</div>
    <div>
      <h2>Procesos Cancelados y Desiertos</h2>
      <small>Cotizaciones que no llegaron a OC — clasificación automática por patrón + campo para registrar el motivo real</small>
    </div>
  </div>

  <!-- KPIs resumen -->
  <div id="ag-can-kpis" style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px"></div>

  <!-- Filtro año + botón -->
  <div style="display:flex;gap:12px;align-items:flex-end;margin-bottom:16px;flex-wrap:wrap">
    <div>
      <label style="font-size:0.72rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Filtrar por año</label>
      <select id="ag-can-anio" style="border:1px solid #D1D5DB;border-radius:6px;padding:6px 10px;font-size:0.82rem">
        <option value="0">Todos los años</option>
      </select>
    </div>
    <button class="btn-gob-primary" onclick="cargarAgCanceladas(true)">🔄 Aplicar</button>
  </div>

  <!-- Leyenda de categorías inferidas -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;font-size:0.7rem">
    <span style="background:#FEE2E2;color:#991B1B;padding:3px 9px;border-radius:20px;font-weight:600">❌ Cancelada sin ofertas</span>
    <span style="background:#FEF3C7;color:#92400E;padding:3px 9px;border-radius:20px;font-weight:600">⚠️ Desierta con ofertas</span>
    <span style="background:#FFF7ED;color:#C2410C;padding:3px 9px;border-radius:20px;font-weight:600">🔄 Cancelada 2° llamado</span>
    <span style="background:#F0FDF4;color:#166534;padding:3px 9px;border-radius:20px;font-weight:600">🔴 Cancelada con ofertas</span>
    <span style="background:#EFF6FF;color:#1D4ED8;padding:3px 9px;border-radius:20px;font-weight:600">🚫 Sin ofertas</span>
    <span style="background:#F8FAFC;color:#6C757D;padding:3px 9px;border-radius:20px;font-weight:600">📝 Guardada/no publicada</span>
  </div>

  <!-- Tabla -->
  <div style="overflow-x:auto">
    <table class="gob-table" id="ag-can-tabla">
      <thead>
        <tr>
          <th>Año</th>
          <th>ID Cotización</th>
          <th>Descripción</th>
          <th>Estado</th>
          <th>Llamado</th>
          <th style="text-align:center">Ofertas</th>
          <th style="text-align:right">Presupuesto</th>
          <th>Motivo inferido</th>
          <th style="min-width:240px">Motivo real <span style="font-weight:400;color:#94A3B8">(editable)</span></th>
        </tr>
      </thead>
      <tbody id="ag-can-tbody">
        <tr><td colspan="9" style="text-align:center;color:#94A3B8;padding:30px">Cargando...</td></tr>
      </tbody>
    </table>
  </div>

</div><!-- /gob-main -->
</div>

<!-- TAB: OBRAS CIVILES -->
<div id="view-obras-civiles" class="tab-view">
<div class="gob-main">

  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
    <div>
      <div style="display:inline-flex;align-items:center;gap:6px;background:#EFF6FF;border:1px solid #BFDBFE;color:#1E40AF;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;margin-bottom:10px;">
        🏗 Obras Civiles · Unidades 1057976 (Propio) + 1180747 (GORE)
      </div>
      <p style="color:#6C757D;font-size:0.82rem;margin:0">Análisis de inversión en infraestructura de salud — nuevas construcciones, conservación de recintos y obras financiadas con fondos GORE Región de Los Lagos.</p>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="obras-kpi-grid" id="obras-kpi-grid">
    <div class="obras-kpi-card"><div class="obras-kpi-label">Inversión Total</div><div class="obras-kpi-value" id="ob-kpi-total">—</div><div class="obras-kpi-sub">Histórico ambas unidades</div></div>
    <div class="obras-kpi-card ok-ejec"><div class="obras-kpi-label">En Ejecución</div><div class="obras-kpi-value" id="ob-kpi-ejec">—</div><div class="obras-kpi-sub" id="ob-kpi-ejec-m">—</div></div>
    <div class="obras-kpi-card ok-term"><div class="obras-kpi-label">Obras Terminadas</div><div class="obras-kpi-value" id="ob-kpi-term">—</div><div class="obras-kpi-sub" id="ob-kpi-tasa">—</div></div>
    <div class="obras-kpi-card ok-gore"><div class="obras-kpi-label">Financiamiento GORE</div><div class="obras-kpi-value" id="ob-kpi-gore">—</div><div class="obras-kpi-sub" id="ob-kpi-gore-pct">—</div></div>
    <div class="obras-kpi-card ok-prom"><div class="obras-kpi-label">Inversión Promedio</div><div class="obras-kpi-value" id="ob-kpi-prom">—</div><div class="obras-kpi-sub">Por obra mayor a $50M</div></div>
    <div class="obras-kpi-card ok-top"><div class="obras-kpi-label">Mayor Obra</div><div class="obras-kpi-value" style="font-size:0.9rem;line-height:1.3" id="ob-kpi-top">—</div><div class="obras-kpi-sub" id="ob-kpi-top-m">—</div></div>
  </div>

  <!-- Evolución + Distribución categoría -->
  <div class="chart-grid-2">
    <div class="chart-card">
      <div class="chart-title">📅 Inversión Anual por Fuente</div>
      <span class="chart-sub">Obras propias (1057976) vs financiamiento GORE (1180747) · monto en $M</span>
      <div id="chart-obras-evolucion" style="height:300px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">🏗 Inversión por Obra</div>
      <span class="chart-sub">Distribución del monto total por proyecto — incluye NC y adendums agrupados</span>
      <div id="chart-obras-categoria" style="height:340px"></div>
    </div>
  </div>

  <!-- Top Proveedores -->
  <div class="gob-section-title" style="margin-top:20px">
    <div class="ico">🏆</div>
    <div><h2>Top 10 Empresas Constructoras</h2><small>Por monto total adjudicado en obras civiles (incluye Notas de Cambio agrupadas)</small></div>
  </div>
  <div class="chart-card chart-grid-1">
    <div id="chart-obras-top-prov" style="height:360px"></div>
  </div>

  <!-- Obras activas -->
  <div class="gob-section-title" style="margin-top:20px">
    <div class="ico">⚙️</div>
    <div><h2>Obras en Ejecución</h2><small>Contratos con estado "Aceptada" — actualmente en proceso de construcción</small></div>
  </div>
  <div style="overflow-x:auto">
    <table class="gob-table" id="obras-activas-tabla">
      <thead>
        <tr>
          <th>Código OC</th>
          <th>Proyecto</th>
          <th>Empresa</th>
          <th style="text-align:right">Monto</th>
          <th>Inicio</th>
          <th style="text-align:right">Días activa</th>
          <th style="min-width:160px">Plazo Ejecución</th>
          <th>BIP</th>
        </tr>
      </thead>
      <tbody id="obras-activas-body">
        <tr><td colspan="8" style="text-align:center;color:#6C757D;padding:24px">Cargando...</td></tr>
      </tbody>
    </table>
  </div>

</div><!-- /gob-main -->
</div>
<!-- FIN TAB OBRAS CIVILES -->

<!-- TAB 4: TRAZABILIDAD -->
<div id="view-trazabilidad" class="tab-view">
<div class="gob-main">

  <!-- Encabezado + ETL -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
    <div>
      <div class="traz-badge-kimun">🏥 Fuente: Kimun (BDBODEGA) · Datos de recepción en bodega · Todos los servicios</div>
      <p style="color:#6C757D;font-size:0.82rem;margin:0">Cruce entre las <strong>Órdenes de Compra de Mercado Público</strong> y su estado de recepción en el sistema <strong>Kimun</strong>. Permite identificar qué OCs han sido recepcionadas en bodega y cuáles permanecen pendientes.</p>
    </div>
    <div class="ag-etl-box" style="min-width:280px;">
      <div style="flex:1">
        <div style="font-size:0.78rem;font-weight:700;color:#1A1A2E;margin-bottom:4px;">Actualizar desde bdbodega.xlsx</div>
        <div class="ag-etl-status" id="traz-etl-msg">Importa el archivo exportado desde Kimun.</div>
      </div>
      <button class="btn-gob-primary" onclick="trazEjecutarETL()" id="traz-etl-btn" style="white-space:nowrap;font-size:0.78rem;padding:7px 14px;">🔄 Reimportar</button>
    </div>
  </div>

  <!-- KPI Grid -->
  <div class="traz-kpi-grid">
    <div class="traz-kpi-card"><div class="traz-kpi-label">Total Registros</div><div class="traz-kpi-value" id="traz-k-total">—</div><div class="traz-kpi-sub">Ingresos en Kimun</div><div class="traz-kpi-icon">📦</div></div>
    <div class="traz-kpi-card tk-ok"><div class="traz-kpi-label">Recepción Completa</div><div class="traz-kpi-value" id="traz-k-completa">—</div><div class="traz-kpi-sub" id="traz-k-pct-completa">—</div><div class="traz-kpi-icon">✅</div></div>
    <div class="traz-kpi-card tk-danger"><div class="traz-kpi-label">En Trámite</div><div class="traz-kpi-value" id="traz-k-tramite">—</div><div class="traz-kpi-sub" id="traz-k-pct-tramite">pendientes de recepción</div><div class="traz-kpi-icon">⏳</div></div>
    <div class="traz-kpi-card tk-warn"><div class="traz-kpi-label">Recepción Parcial</div><div class="traz-kpi-value" id="traz-k-parcial">—</div><div class="traz-kpi-sub">Entregadas parcialmente</div><div class="traz-kpi-icon">⚡</div></div>
    <div class="traz-kpi-card tk-monto"><div class="traz-kpi-label">Monto En Trámite</div><div class="traz-kpi-value" id="traz-k-monto-tramite" style="font-size:1rem">—</div><div class="traz-kpi-sub">Pendiente en bodega (CLP)</div><div class="traz-kpi-icon">💰</div></div>
    <div class="traz-kpi-card tk-link"><div class="traz-kpi-label">OCs Trazables en MP</div><div class="traz-kpi-value" id="traz-k-link">—</div><div class="traz-kpi-sub">OCs cruzadas con oc_resumen</div><div class="traz-kpi-icon">🔗</div></div>
  </div>

  <!-- BÚSQUEDA DE OC -->
  <div class="traz-search-box">
    <div class="traz-search-title">🔍 Buscar OC / Proveedor</div>
    <div class="traz-search-row">
      <input type="text" id="traz-q" class="traz-search-input" placeholder="Ej: 1057922-14-AG25  ó  BLUEMEDICAL  ó  1057532-..." maxlength="80">
      <button class="btn-gob-primary" onclick="trazBuscar()">Buscar</button>
      <button class="btn-gob-secondary" onclick="trazLimpiar()">Limpiar</button>
    </div>
    <div style="font-size:0.72rem;color:#6C757D;margin-top:8px">Ingresa código OC, proveedor o parte del código. Los resultados muestran estado Kimun + estado Mercado Público.</div>
    <div id="traz-results" class="traz-results"></div>
  </div>

  <!-- Estado Global + Tipo OC (2 columnas) -->
  <div class="chart-grid-2" style="margin-top:4px">
    <div class="chart-card">
      <div class="chart-title">Estado Global de Recepciones</div>
      <span class="chart-sub">Distribución proporcional por estado en Kimun</span>
      <div id="chart-traz-estado" style="height:280px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">Recepción por Tipo de OC</div>
      <span class="chart-sub">Barras apiladas: completa / en trámite / parcial / anulada</span>
      <div id="chart-traz-tipo" style="height:280px"></div>
    </div>
  </div>

  <!-- Alertas: En Trámite por Tipo -->
  <div class="gob-section-title" style="margin-top:20px">
    <div class="ico" style="background:#FEE2E2">⚠️</div>
    <div><h2 style="color:#B91C1C">Alertas — OCs En Trámite por Tipo</h2><small>Modalidades con mayor cantidad de OCs pendientes de recepción en bodega</small></div>
  </div>
  <div class="chart-card chart-grid-1">
    <div id="chart-traz-alertas" style="height:320px"></div>
  </div>

  <!-- Bodegas -->
  <div class="gob-section-title" style="margin-top:20px"><div class="ico">🏪</div><div><h2>Recepción por Bodega</h2><small>Monto y volumen recepcionado por bodega · estado apilado</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-traz-bodega" style="height:360px"></div>
  </div>

  <!-- Evolución Mensual -->
  <div class="gob-section-title" style="margin-top:20px"><div class="ico">📅</div><div><h2>Evolución Mensual de Ingresos a Bodega</h2><small>Registros Kimun por mes · desglosado por estado</small></div></div>
  <div class="chart-card chart-grid-1">
    <div id="chart-traz-evolucion" style="height:300px"></div>
  </div>

</div><!-- /gob-main -->
</div>

<!-- TAB 5: LIBERACIÓN OC -->
<div id="view-liberacion" class="tab-view">
<div class="gob-main">

  <!-- Encabezado -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
    <div>
      <div style="display:inline-flex;align-items:center;gap:8px;background:#FEF3C7;border:1px solid #FCD34D;border-radius:4px;padding:6px 14px;font-size:0.8rem;font-weight:700;color:#92400E;margin-bottom:10px">
        ⚠️ Seguimiento requerido · Fuente: Mercado Público (oc_resumen)
      </div>
      <p style="color:#6C757D;font-size:0.82rem;margin:0">
        OCs en estado <strong>Cancelada</strong>, <strong>Enviada a proveedor</strong> o <strong>En proceso</strong> que requieren acción.
        Los compradores deben <strong>concluir, desistir o liberar el presupuesto</strong> comprometido en estas órdenes.
      </p>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="lib-kpi-grid">
    <div class="lib-kpi-card lib-total">
      <div class="lib-kpi-label">TOTAL OCs PENDIENTES</div>
      <div class="lib-kpi-value" id="lib-k-total">—</div>
      <div class="lib-kpi-sub">Requieren seguimiento</div>
      <div class="lib-kpi-icon">📋</div>
    </div>
    <div class="lib-kpi-card lib-proceso">
      <div class="lib-kpi-label">EN PROCESO</div>
      <div class="lib-kpi-value" id="lib-k-proceso">—</div>
      <div class="lib-kpi-sub" id="lib-k-proceso-fmt">$ —</div>
      <div class="lib-kpi-icon">🔄</div>
    </div>
    <div class="lib-kpi-card lib-enviada">
      <div class="lib-kpi-label">ENVIADA A PROVEEDOR</div>
      <div class="lib-kpi-value" id="lib-k-enviada">—</div>
      <div class="lib-kpi-sub" id="lib-k-enviada-fmt">$ —</div>
      <div class="lib-kpi-icon">📤</div>
    </div>
    <div class="lib-kpi-card lib-cancelada">
      <div class="lib-kpi-label">CANCELADAS</div>
      <div class="lib-kpi-value" id="lib-k-cancelada">—</div>
      <div class="lib-kpi-sub" id="lib-k-cancelada-fmt">$ —</div>
      <div class="lib-kpi-icon">❌</div>
    </div>
    <div class="lib-kpi-card lib-monto">
      <div class="lib-kpi-label">PRESUPUESTO BLOQUEADO</div>
      <div class="lib-kpi-value" id="lib-k-monto" style="font-size:1rem">—</div>
      <div class="lib-kpi-sub">Monto total comprometido sin ejecutar</div>
      <div class="lib-kpi-icon">🔒</div>
    </div>
  </div>

  <!-- Gráficos: Evolución por Año + Por Unidad -->
  <div class="chart-grid-2" style="margin-top:8px">
    <div class="chart-card">
      <div class="chart-title">📅 OCs Pendientes por Año</div>
      <span class="chart-sub">Cantidad de OCs en estado de alerta según año de emisión</span>
      <div id="chart-lib-anio" style="height:280px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">🏥 OCs Pendientes por Unidad</div>
      <span class="chart-sub">Top 10 unidades con mayor monto bloqueado</span>
      <div id="chart-lib-unidad" style="height:280px"></div>
    </div>
  </div>

  <!-- Filtros + Tabla Detallada -->
  <div class="gob-section-title" style="margin-top:20px">
    <div class="ico" style="background:#FEE2E2">🔍</div>
    <div><h2 style="color:#B91C1C">Detalle de OCs — Acción Requerida</h2><small>Filtra y gestiona las OCs pendientes · ordenadas por urgencia</small></div>
  </div>

  <div class="chart-card" style="margin-bottom:20px">
    <!-- Filtros -->
    <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px">
      <div>
        <label style="font-size:0.75rem;color:#6C757D;display:block;margin-bottom:3px">Estado</label>
        <select id="lib-filtro-estado" style="padding:7px 10px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.85rem;background:#fff">
          <option value="">Todos</option>
          <option value="En proceso">En proceso</option>
          <option value="Enviada a proveedor">Enviada a proveedor</option>
          <option value="Cancelada">Cancelada</option>
        </select>
      </div>
      <div style="flex:1;min-width:200px">
        <label style="font-size:0.75rem;color:#6C757D;display:block;margin-bottom:3px">Buscar OC / Proveedor</label>
        <input type="text" id="lib-filtro-busqueda" placeholder="Código OC, proveedor, nombre..." style="width:100%;padding:7px 10px;border:1px solid #DEE2E6;border-radius:4px;font-size:0.85rem">
      </div>
      <button class="btn-gob-primary" onclick="libBuscar()">🔍 Filtrar</button>
      <button class="btn-gob-secondary" onclick="libLimpiar()">✕ Limpiar</button>
    </div>

    <!-- Contador de resultados -->
    <div id="lib-tabla-info" style="font-size:0.78rem;color:#6C757D;margin-bottom:8px"></div>

    <!-- Tabla -->
    <div style="overflow-x:auto">
      <table class="gob-table" id="lib-tabla">
        <thead>
          <tr>
            <th>Estado</th>
            <th>Código OC</th>
            <th>Nombre / Descripción</th>
            <th>Proveedor</th>
            <th>Unidad</th>
            <th>Modalidad</th>
            <th>Año</th>
            <th>Fecha Envío</th>
            <th style="text-align:right">Días</th>
            <th>Antigüedad</th>
            <th style="text-align:right">Monto</th>
            <th>Acción sugerida</th>
          </tr>
        </thead>
        <tbody id="lib-tabla-body">
          <tr><td colspan="12" style="text-align:center;color:#6C757D;padding:24px">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
    <p style="font-size:0.72rem;color:#6C757D;margin-top:8px">* Máximo 500 registros. Usa los filtros para acotar la búsqueda.</p>
  </div>

</div>
</div>

<!-- TAB: INF.EJEC -->
<div id="view-infejec" class="tab-view">
<div class="gob-main">

  <!-- Filtros INF.EJEC -->
  <div style="background:#fff;border:1px solid var(--gob-border);border-left:4px solid #7C3AED;border-radius:0 4px 4px 0;padding:14px 22px;margin-bottom:24px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;box-shadow:var(--shadow-sm);">
    <div class="fgroup" style="flex:1;min-width:200px;">
      <label>Unidad de Compra</label>
      <select id="ej-unidad"><option value="">Todas las unidades</option></select>
    </div>
    <div class="fgroup">
      <label>Año desde</label>
      <select id="ej-desde"></select>
    </div>
    <div class="fgroup">
      <label>Año hasta</label>
      <select id="ej-hasta"></select>
    </div>
    <button class="btn-gob-primary" onclick="cargarInfEjec(true)">📊 Aplicar</button>
  </div>

  <!-- SEMÁFORO EJECUTIVO -->
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
    <span style="font-size:1rem">🚦</span>
    <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#6C757D">Semáforo Ejecutivo</span>
    <div style="flex:1;height:1px;background:linear-gradient(90deg,#CBD5E1,transparent)"></div>
  </div>
  <div id="ej-semaforo" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;"></div>

  <!-- CONCENTRACIÓN DE PROVEEDORES -->
  <div class="gob-section-title">
    <div class="ico" style="background:#EDE9FE">🏦</div>
    <div><h2>Concentración de Proveedores</h2><small>Refleja si el gasto está distribuido entre muchos proveedores (saludable) o concentrado en pocos (riesgo de dependencia y precios sin competencia). El índice HHI es el estándar internacional para medir esto.</small></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;" id="ej-conc-kpis"></div>
  <div class="chart-grid-2" style="margin-bottom:20px">
    <div class="chart-card">
      <div class="chart-title">🏦 Distribución del Gasto por Proveedor</div>
      <span class="chart-sub">Top 5 + Otros (% del monto total)</span>
      <div id="chart-ej-conc-donut" style="height:300px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">📊 Top 10 Proveedores por Monto</div>
      <span class="chart-sub">Monto acumulado y % del total del período</span>
      <div id="chart-ej-conc-bar" style="height:300px"></div>
    </div>
  </div>

  <!-- COMPOSICIÓN DEL GASTO Y TRATO DIRECTO -->
  <div class="gob-section-title">
    <div class="ico" style="background:#FEF2F2">🔴</div>
    <div><h2>Composición del Gasto por Modalidad</h2><small>Muestra cómo se distribuye el gasto según el tipo de proceso de compra. El Trato Directo (adjudicación sin licitación) es legal en ciertos casos, pero su uso excesivo indica menor transparencia y menor competencia de precios. La línea punteada del gráfico marca los umbrales de alerta (15% y 30%).</small></div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;" id="ej-td-kpis"></div>
  <div class="chart-grid-2" style="margin-bottom:20px">
    <div class="chart-card">
      <div class="chart-title">🥧 Gasto por Modalidad de Compra</div>
      <span class="chart-sub">Proporción del monto total por tipo de proceso</span>
      <div id="chart-ej-td-pie" style="height:300px"></div>
    </div>
    <div class="chart-card">
      <div class="chart-title">📈 Evolución Trato Directo (% del Gasto)</div>
      <span class="chart-sub">Porcentaje del monto total adjudicado por Trato Directo por año</span>
      <div id="chart-ej-td-evol" style="height:300px"></div>
    </div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div class="chart-title">📊 Cantidad de Tratos Directos por Año</div>
    <span class="chart-sub">Número absoluto de órdenes de compra adjudicadas por Trato Directo cada año, comparado con el total de OCs. Permite dimensionar el volumen real y detectar tendencias de crecimiento o reducción.</span>
    <div id="chart-ej-td-cant" style="height:320px"></div>
  </div>

  <!-- VARIACIÓN DE PRECIOS -->
  <div class="gob-section-title">
    <div class="ico" style="background:#FEF3C7">💹</div>
    <div><h2>Variación de Precios por Ítem</h2><small>Compara el precio unitario promedio de cada ítem entre el año más reciente y el anterior. Barras rojas indican alza de precio (posible inflación, cambio de proveedor o especificación distinta); barras verdes indican baja. Las líneas punteadas marcan el umbral del ±20%. Útil para detectar ítems con alzas atípicas que requieren justificación.</small></div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div id="chart-ej-precios" style="height:420px"></div>
    <button class="gob-collapse-btn" style="margin-top:12px" onclick="togglePane('pane-ej-precios',this)">📋 Ver tabla detallada ▾</button>
    <div class="collapse-pane" id="pane-ej-precios">
      <table class="gob-table">
        <thead><tr><th>Ítem / Especificación</th><th>Precio <span id="ej-anio-ant">—</span></th><th>Precio <span id="ej-anio-act">—</span></th><th>Variación %</th><th>N° OCs</th></tr></thead>
        <tbody id="tbody-ej-precios"></tbody>
      </table>
    </div>
  </div>

  <!-- ÍTEMS SIN COMPRA RECIENTE -->
  <div class="gob-section-title">
    <div class="ico" style="background:#FEF2F2">⚠️</div>
    <div><h2>Ítems sin Compra Reciente</h2><small>Lista productos que el servicio ha comprado al menos 2 veces históricamente, pero que no registran ninguna OC en los últimos 12 meses. Puede indicar desabastecimiento no detectado, cambio de especificación no documentado, o ítem discontinuado. Requiere revisión de stock y necesidad clínica.</small></div>
  </div>
  <div class="chart-card" style="margin-bottom:20px">
    <div id="ej-sincmp-empty" style="color:#6C757D;font-size:0.85rem;padding:8px 0">Cargando...</div>
    <table class="gob-table" id="tbl-ej-sincmp" style="display:none">
      <thead><tr><th>#</th><th>Especificación</th><th>Último Año</th><th>N° OCs Históricas</th><th>Monto Histórico</th></tr></thead>
      <tbody id="tbody-ej-sincmp"></tbody>
    </table>
  </div>

</div>
</div>

<!-- TAB: GARANTÍAS DE SERIEDAD -->
<div id="view-garantias" class="tab-view">
<div class="gob-main">

  <!-- KPIs resumen -->
  <div id="gar-kpis" style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px"></div>

  <!-- Formulario de registro -->
  <div class="gob-section-title" style="margin-top:4px">
    <div class="ico">➕</div>
    <div><h2>Registrar Garantía de Seriedad</h2><small>Ingresa los datos de la boleta o vale vista recibida de cada oferente</small></div>
  </div>
  <div class="gar-form" id="gar-form-wrapper">
    <!-- Fila 1: búsqueda de licitación -->
    <div style="margin-bottom:14px">
      <label>Código de Licitación <span style="color:#B91C1C">*</span></label>
      <div class="gar-lookup-row">
        <input type="text" id="gar-cod-licit" placeholder="ej: 1057532-64-LP25"
               style="flex:1;border:1px solid #D1D5DB;border-radius:6px;padding:7px 10px;font-size:0.85rem"
               onkeydown="if(event.key==='Enter')buscarLicitGar()">
        <button class="btn-gob-primary" onclick="buscarLicitGar()" style="white-space:nowrap">🔍 Buscar</button>
      </div>
      <div id="gar-licit-info" style="margin-top:8px;padding:10px 14px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:6px;font-size:0.78rem;color:#1D4ED8;display:none"></div>
    </div>
    <!-- Fila 2: datos de la empresa -->
    <div class="gar-form-grid">
      <div>
        <label>Empresa <span style="color:#B91C1C">*</span></label>
        <input type="text" id="gar-empresa" placeholder="Nombre de la empresa oferente">
      </div>
      <div>
        <label>RUT Empresa</label>
        <input type="text" id="gar-rut" placeholder="ej: 76.123.456-7">
      </div>
      <div>
        <label>Tipo de Documento</label>
        <select id="gar-tipo-doc">
          <option>Boleta de Garantía</option>
          <option>Vale Vista</option>
          <option>Depósito a Plazo</option>
          <option>Póliza de Seguro</option>
        </select>
      </div>
      <div>
        <label>N° Documento</label>
        <input type="text" id="gar-ndoc" placeholder="Número de boleta / vale vista">
      </div>
      <div>
        <label>Banco / Institución</label>
        <input type="text" id="gar-banco" placeholder="ej: Banco Estado, BCI...">
      </div>
      <div>
        <label>Monto ($)</label>
        <input type="number" id="gar-monto" placeholder="0" min="0">
      </div>
      <div>
        <label>Vencimiento <span style="color:#B91C1C">*</span></label>
        <input type="date" id="gar-vencimiento">
      </div>
      <div>
        <label>Fecha Adjudicación</label>
        <input type="date" id="gar-adj" title="Fecha en que se adjudicó el proceso. Plazo legal: 10 días para devolver a no adjudicados.">
      </div>
      <div>
        <label>Registrado por</label>
        <input type="text" id="gar-registrador" placeholder="Nombre del comprador">
      </div>
    </div>
    <div style="margin-top:12px">
      <label>Observación</label>
      <textarea id="gar-obs" rows="2" style="width:100%;box-sizing:border-box;border:1px solid #D1D5DB;border-radius:6px;padding:7px 10px;font-size:0.82rem"
                placeholder="Información adicional relevante..."></textarea>
    </div>
    <div style="margin-top:14px;display:flex;gap:10px;align-items:center">
      <button class="btn-gob-primary" onclick="registrarGarantia()">💾 Registrar Garantía</button>
      <button onclick="limpiarFormGar()" style="background:#F1F5F9;border:1px solid #D1D5DB;border-radius:6px;padding:7px 14px;font-size:0.82rem;cursor:pointer">🗑 Limpiar</button>
      <span id="gar-msg" style="font-size:0.8rem;font-weight:600"></span>
    </div>
  </div>

  <!-- Filtros de lista -->
  <div class="gob-section-title" style="margin-top:4px">
    <div class="ico">📋</div>
    <div><h2>Boletas Registradas</h2><small>Semáforo de vencimiento y estado de devolución</small></div>
  </div>
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
    <div>
      <label style="font-size:0.72rem;font-weight:600;color:#374151;display:block;margin-bottom:4px">Estado</label>
      <select id="gar-filtro-estado" style="border:1px solid #D1D5DB;border-radius:6px;padding:6px 10px;font-size:0.82rem">
        <option value="todas">Todas</option>
        <option value="pendiente" selected>Pendientes</option>
        <option value="devuelta">Devueltas</option>
      </select>
    </div>
    <button class="btn-gob-primary" onclick="cargarGarantias(true)">🔄 Actualizar</button>
  </div>

  <!-- Leyenda semáforo -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;font-size:0.72rem">
    <span class="gar-badge gar-b-vigente">✅ Vigente: >15 días</span>
    <span class="gar-badge gar-b-alerta">⚠️ Alerta: 6–15 días</span>
    <span class="gar-badge gar-b-critico">🔴 Crítico: ≤5 días</span>
    <span class="gar-badge gar-b-vencida">⛔ Vencida sin devolver</span>
    <span class="gar-badge gar-b-plazo_legal">⏰ Plazo legal excedido (>10 días desde adj.)</span>
    <span class="gar-badge gar-b-devuelta">✔ Devuelta</span>
  </div>

  <!-- Tabla de garantías -->
  <div style="overflow-x:auto">
    <table class="gob-table" id="gar-tabla">
      <thead>
        <tr>
          <th>Estado</th>
          <th>Licitación</th>
          <th>Empresa</th>
          <th>Documento</th>
          <th>Banco</th>
          <th>Monto</th>
          <th>Vencimiento</th>
          <th>Días rest.</th>
          <th>Adj.</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="gar-tbody">
        <tr><td colspan="10" style="text-align:center;color:#94A3B8;padding:30px">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
  <div id="gar-sin-datos" style="display:none;text-align:center;color:#94A3B8;padding:40px;font-size:0.9rem">
    No hay garantías registradas con los filtros seleccionados.
  </div>
</div>
</div><!-- /view-garantias -->

<!-- Modal: Registrar devolución -->
<div id="gar-modal" class="gar-modal-overlay" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="gar-modal">
    <h3>✔ Registrar Devolución de Garantía</h3>
    <input type="hidden" id="gar-modal-id">
    <div id="gar-modal-info" style="background:#F8FAFC;border:1px solid var(--gob-border);border-radius:6px;padding:10px 12px;font-size:0.8rem;margin-bottom:4px"></div>
    <label>Fecha de devolución <span style="color:#B91C1C">*</span></label>
    <input type="date" id="gar-modal-fecha">
    <label>Devuelta a (nombre de quien recibió)</label>
    <input type="text" id="gar-modal-devuelta-a" placeholder="ej: Juan Pérez — Empresa XYZ">
    <label>Observación</label>
    <textarea id="gar-modal-obs" rows="2" style="width:100%;box-sizing:border-box;border:1px solid #D1D5DB;border-radius:6px;padding:7px 10px;font-size:0.83rem" placeholder="Opcional..."></textarea>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
      <button onclick="document.getElementById('gar-modal').style.display='none'"
              style="background:#F1F5F9;border:1px solid #D1D5DB;border-radius:6px;padding:7px 16px;font-size:0.83rem;cursor:pointer">Cancelar</button>
      <button class="btn-gob-primary" onclick="confirmarDevolucion()">✔ Confirmar Devolución</button>
    </div>
  </div>
</div>

<!-- TAB 6: EXPORTAR -->
<div id="view-exportar" class="tab-view">
  <div style="padding:40px;text-align:center;color:#6C757D">
    <div style="font-size:3rem;margin-bottom:16px">📥</div>
    <h2 style="color:#0A4F96">Exportar — Próximamente</h2>
    <p>Módulo en construcción. Generación de reportes PDF.</p>
  </div>
</div>

</main>

<footer class="gob-footer">
  <div class="gob-footer-main">
    <div>
      <h5>Ministerio de Salud — DSSO</h5>
      <p>Servicio de Salud Osorno<br>División de Gestión de Compras<br>Plataforma Mark 1 — Datos históricos 2017–2026</p>
    </div>
    <div>
      <h5>Módulos</h5>
      <ul>
        <li><a href="#">📊 Estadísticas Históricas</a></li>
        <li><a href="#">📈 Inversiones OC</a></li>
        <li><a href="#">🛒 Compra Ágil</a></li>
        <li><a href="#">🔍 Trazabilidad</a></li>
        <li><a href="#">🔓 Liberación OC</a></li>
      </ul>
    </div>
    <div>
      <h5>Recursos</h5>
      <ul>
        <li><a href="https://www.mercadopublico.cl" target="_blank">Mercado Público</a></li>
        <li><a href="https://www.chilecompra.cl" target="_blank">ChileCompra</a></li>
        <li><a href="https://www.minsal.cl" target="_blank">MINSAL</a></li>
      </ul>
    </div>
  </div>
  <div class="gob-footer-strip">
    <span style="font-family:'Roboto Slab',serif;font-weight:700;color:#fff">● gob.cl</span>
    <span>© 2026 Gobierno de Chile — Ministerio de Salud · DSSO</span>
    <span>Mark 1 · Framework Gobierno Digital</span>
    <span style="opacity:0.4;font-size:0.65rem">Elaborado por Bastián Miranda Coronado 🐇</span>
  </div>
</footer>

<script>
// ═══════════════════════════════════════
// TABS
// ═══════════════════════════════════════
function showTab(tab) {
  document.querySelectorAll('.tab-view').forEach(v=>v.classList.remove('active'));
  document.querySelectorAll('.gob-subnav a').forEach(a=>a.classList.remove('active'));
  const view=document.getElementById('view-'+tab);
  const tabEl=document.getElementById('tab-'+tab);
  if(view) view.classList.add('active');
  if(tabEl) tabEl.classList.add('active');
  const HERO={
    'estadisticas':['Estadísticas Históricas — SSO','Estadísticas Históricas','Consolidado histórico de Órdenes de Compra del Servicio de Salud Osorno.'],
    'inversiones':['Inversiones OC','Inversiones OC','Detalle de inversiones por unidad.'],
    'obras-civiles':['Obras Civiles','Obras Civiles','Inversión en infraestructura de salud — unidades 1057976 y 1180747 (GORE).'],
    'compra-agil':['Compra Ágil','Compra Ágil','Órdenes bajo modalidad Compra Ágil.'],
    'trazabilidad':['Trazabilidad','Trazabilidad','Historial completo por producto o equipo.'],
    'liberacion':['Liberación OC','Liberación OC','OCs pendientes que requieren seguimiento o liberación de presupuesto.'],
    'infejec':  ['Informe de Ejecución — SSO','INF.EJEC','Indicadores de riesgo, concentración, trato directo y variación de precios por unidad de compra.'],
    'garantias':['Garantías de Seriedad de la Oferta','GARANTÍAS','Registro y control de boletas de garantía. Alertas automáticas de vencimiento y plazo legal de devolución a empresas no adjudicadas.'],
    'exportar':['Exportar Informe','Exportar','Genera reportes ejecutivos en PDF.'],
  };
  const h=HERO[tab];
  if(h){
    const t=document.getElementById('hero-title'); if(t) t.textContent=h[0];
    const b=document.getElementById('hero-breadcrumb'); if(b) b.textContent=h[1];
    const d=document.getElementById('hero-desc'); if(d) d.textContent=h[2];
    const pt=document.getElementById('phi-title'); if(pt) pt.textContent=h[0];
    const pb=document.getElementById('phi-breadcrumb'); if(pb) pb.textContent=h[1];
    const pd=document.getElementById('phi-desc'); if(pd) pd.textContent=h[2];
  }
  document.body.classList.toggle('tab-hero-hidden', tab!=='estadisticas');
  if (tab === 'inversiones') cargarInversiones();
  if (tab === 'obras-civiles') cargarObras();
  if (tab === 'compra-agil') { cargarCompraAgil(); agCanPoblarAnios(); cargarAgCanceladas(); }
  if (tab === 'trazabilidad') cargarTrazabilidad();
  if (tab === 'liberacion') cargarLiberacion();
  if (tab === 'infejec')   cargarInfEjec();
  if (tab === 'garantias') cargarGarantias();
  return false;
}

// ═══════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════
const CFG={responsive:true,displayModeBar:false};
const LB={
  paper_bgcolor:'rgba(0,0,0,0)',plot_bgcolor:'rgba(0,0,0,0)',
  font:{family:"'Roboto',sans-serif",color:'#212529',size:12},
  legend:{font:{color:'#212529',size:11},bgcolor:'rgba(255,255,255,0.85)',bordercolor:'#DEE2E6',borderwidth:1}
};
const PALETA=['#0F69C4','#15803D','#B45309','#B91C1C','#7C3AED','#0891B2','#BE185D','#D97706'];
const COLOR_ORIGEN={
  'Licitación / Directa (SE)':'#0F69C4','Convenio Marco':'#15803D',
  'Compra Ágil':'#B45309','Trato Directo':'#B91C1C','Otros Procesos':'#6B7280'
};
const API_URL='api/data.php';
const fmt=v=>'$ '+Math.round(v||0).toLocaleString('es-CL');
const hexRgba=(hex,a=0.10)=>{const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);return `rgba(${r},${g},${b},${a})`;};
let tendCache={};

// ═══════════════════════════════════════
// FILTROS
// ═══════════════════════════════════════
function getFiltros(extras={}) {
  return {
    anioDesde: document.getElementById('sel-anio-desde')?.value||2017,
    anioHasta: document.getElementById('sel-anio-hasta')?.value||2026,
    unidad:    document.getElementById('sel-unidad')?.value||'',
    busqueda:  document.getElementById('inp-busqueda')?.value||'',
    ...extras
  };
}
function limpiarFiltros() {
  const su=document.getElementById('sel-unidad'); if(su) su.value='';
  const ib=document.getElementById('inp-busqueda'); if(ib) ib.value='';
  const d=document.getElementById('sel-anio-desde'); if(d) d.selectedIndex=0;
  const h=document.getElementById('sel-anio-hasta'); if(h) h.selectedIndex=h.options.length-1;
  cargarDatos();
}
function setEstado(msg,color='#15803D') {
  const el=document.getElementById('topbar-estado');
  if(el){el.textContent=msg;el.style.background=color;}
}

// ═══════════════════════════════════════
// CARGA PRINCIPAL
// ═══════════════════════════════════════
async function cargarDatos() {
  setEstado('⏳ Cargando...','#B45309');
  try {
    const p=new URLSearchParams({action:'stats',...getFiltros()});
    const res=await fetch(API_URL+'?'+p);
    const d=await res.json();
    if(d.error) throw new Error(d.error);
    renderKPIs(d.kpis);
    renderEstadTendencia(d.tendenciaGeneral);
    renderTopProveedores(d.topProv);
    renderEstado(d.distEstado);
    renderEvolucion(d.evolucion);
    renderOrigen(d.distOrigen);
    renderTopItems(d.topItems);
    renderFrecuenciaProv(d.frecProv);
    renderFreqChart(d.frecProv);
    poblarSelectores(d.anios,d.proveedores,d.unidades);
    setEstado('✔ '+(d.meta?.total||'—')+' OCs','#15803D');
  } catch(err){console.error(err);setEstado('⚠ '+err.message,'#B91C1C');}
}

function renderEstadTendencia(t) {
  const el = document.getElementById('estad-tendencia');
  if (!el || !t) return;

  const oSym = v => v >= 0 ? '▲' : '▼';
  const oCol = v => v >= 0 ? '#15803D' : '#B91C1C';

  // KPI 1: Proyección anual
  const proyVsRef = t.proyAnual - t.refTotal;
  const proyCol   = proyVsRef >= 0 ? '#15803D' : '#B91C1C';
  const proySym   = proyVsRef >= 0 ? '▲' : '▼';

  // KPI 3: Peak — si ya pasamos todos los peaks, nota aclaratoria
  const peakNota  = t.peakMes <= new Date().getMonth() + 1
    ? `Mes histórico · Prom. ${t.peakProm} OCs`
    : `Próx. peak · Prom. ${t.peakProm} OCs`;

  // Contexto del filtro aplicado
  const ctxHtml = t.contexto
    ? `<div style="font-size:0.62rem;color:#94A3B8;margin-top:4px">Filtro: ${t.contexto}</div>`
    : '';

  el.innerHTML = `
    <!-- KPI 1: Proyección anual -->
    <div class="inv-tend-card inv-tend-proy">
      <div class="inv-tend-label">📈 Proyección Anual ${t.anioActual}</div>
      <div class="inv-tend-value" style="color:#0F69C4">~${t.proyAnual}
        <small style="font-size:0.85rem;font-weight:400;font-family:'Roboto',sans-serif"> OCs</small>
      </div>
      <div class="inv-tend-sub">Ritmo actual: <strong>${t.ritmo} OCs/mes</strong></div>
      <div class="inv-tend-sub2" style="color:${proyCol};font-weight:600">
        ${proySym} ${Math.abs(proyVsRef)} vs cierre ${t.anioPasado} (${t.refTotal} OCs)
      </div>
      ${ctxHtml}
    </div>

    <!-- KPI 2: Mismo período año anterior -->
    <div class="inv-tend-card inv-tend-ytd">
      <div class="inv-tend-label">📊 ${t.periodo} ${t.anioActual} vs ${t.periodo} ${t.anioPasado}</div>
      <div class="inv-tend-value" style="color:${oCol(t.varOcs)}">
        ${oSym(t.varOcs)} ${Math.abs(t.varOcs)}%
        <small style="font-size:0.85rem;font-weight:400;font-family:'Roboto',sans-serif"> OCs</small>
      </div>
      <div class="inv-tend-sub" style="display:flex;gap:12px;margin-top:8px;">
        <div style="text-align:center;flex:1;background:#EFF6FF;border-radius:6px;padding:5px 4px;">
          <div style="font-size:1.1rem;font-weight:700;color:#0F69C4">${t.ytdActual}</div>
          <div style="font-size:0.62rem;color:#6C757D;font-weight:600">${t.anioActual}</div>
        </div>
        <div style="text-align:center;flex:1;background:#FEF3C7;border-radius:6px;padding:5px 4px;">
          <div style="font-size:1.1rem;font-weight:700;color:#92400E">${t.ytdPasado}</div>
          <div style="font-size:0.62rem;color:#6C757D;font-weight:600">${t.anioPasado}</div>
        </div>
      </div>
      <div class="inv-tend-sub2" style="color:${oCol(t.varMonto)};font-weight:600;margin-top:5px;">
        ${oSym(t.varMonto)} ${Math.abs(t.varMonto)}% en monto &nbsp;·&nbsp; ${t.ytdMontoFmt}
      </div>
    </div>

    <!-- KPI 3: Peak histórico -->
    <div class="inv-tend-card inv-tend-peak">
      <div class="inv-tend-label">⚡ Peak Histórico</div>
      <div class="inv-tend-value" style="color:#D97706">${t.peakNombre}</div>
      <div class="inv-tend-sub">${peakNota}</div>
      ${t.peakMontoM > 0
        ? `<div class="inv-tend-sub2">Monto prom.: ~$${t.peakMontoM}M</div>`
        : ''}
      <div class="inv-tend-sub2">Basado en ${t.anioHistDesde}–${t.anioPasado}</div>
    </div>`;
}

function renderKPIs(k) {
  const set=(id,v)=>{const el=document.getElementById(id);if(el)el.textContent=v;};
  set('kpi-total',k?.inversionFormato||'—');
  set('kpi-prov', k?.proveedores||'—');
  set('kpi-ocs',  k?.registros||'—');
}

function renderTopProveedores(data) {
  if(!data?.length) return;
  const n=data.map(d=>d.nombre.length>30?d.nombre.slice(0,30)+'…':d.nombre).reverse();
  const m=data.map(d=>(d.monto||0)/1e6).reverse();
  Plotly.newPlot('chart-top-prov',[{
    type:'bar',orientation:'h',x:m,y:n,
    marker:{color:m,colorscale:[[0,'#93C5FD'],[1,'#0A4F96']],showscale:false,line:{color:'white',width:1}},
    text:m.map(v=>'$'+v.toFixed(1)+'M'),textposition:'outside',
    hovertemplate:'<b>%{y}</b><br>$%{x:.2f}M<extra></extra>'
  }],{...LB,margin:{t:10,b:30,l:210,r:65},
    xaxis:{title:{text:'Monto (M$)',font:{color:'#6C757D',size:11}},tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:11}}
  },CFG);
}

function renderEstado(dist) {
  if(!dist||!Object.keys(dist).length) return;
  Plotly.newPlot('chart-estado',[{
    type:'pie',hole:0.42,labels:Object.keys(dist),values:Object.values(dist),
    marker:{colors:['#15803D','#0F69C4','#0A4F96','#B91C1C','#B45309'],line:{color:'white',width:2}},
    textposition:'inside',textinfo:'percent+label',textfont:{color:'#fff',size:11},
    hovertemplate:'<b>%{label}</b><br>%{value} OCs<extra></extra>'
  }],{...LB,showlegend:false,margin:{t:10,b:10,l:10,r:10}},CFG);
}

function renderEvolucion(evolucion) {
  if(!evolucion||!Object.keys(evolucion).length) return;
  const anios=Object.keys(evolucion).sort().map(Number);  // numéricos para eje lineal
  const anioStr=anios.map(String);
  const origenes=[...new Set(anioStr.flatMap(a=>Object.keys(evolucion[a])))];
  const traces=origenes.map(o=>({
    type:'bar',name:o,x:anios,y:anioStr.map(a=>evolucion[a][o]||0),
    marker:{color:COLOR_ORIGEN[o]||'#888',line:{color:'white',width:1}},
    hovertemplate:'<b>'+o+'</b><br>Año: %{x}<br>OCs: %{y}<extra></extra>'
  }));
  const totales=anioStr.map(a=>Object.values(evolucion[a]).reduce((s,v)=>s+v,0));
  traces.push({type:'scatter',mode:'lines+markers',name:'Total',x:anios,y:totales,
    line:{color:'#1A1A2E',width:2,dash:'dot'},marker:{size:8,color:'#1A1A2E'}});
  Plotly.newPlot('chart-evolucion',traces,{...LB,barmode:'group',
    margin:{t:10,b:40,l:40,r:10},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:false,dtick:1,tickformat:'d'},
    yaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}}
  },CFG);
}

function renderOrigen(dist) {
  if(!dist||!Object.keys(dist).length) return;
  Plotly.newPlot('chart-origen',[{
    type:'pie',hole:0.42,labels:Object.keys(dist),values:Object.values(dist),
    marker:{colors:Object.keys(dist).map(k=>COLOR_ORIGEN[k]||'#888'),line:{color:'white',width:2}},
    textposition:'inside',textinfo:'percent+label',textfont:{color:'#fff',size:11},
    hovertemplate:'<b>%{label}</b><br>%{percent}<extra></extra>'
  }],{...LB,showlegend:false,margin:{t:10,b:10,l:10,r:10}},CFG);
}

function renderTopItems(items) {
  if(!items?.length) return;
  const labels=items.map(d=>(d.espec||'').length>44?(d.espec||'').slice(0,44)+'…':(d.espec||'')).reverse();
  const veces=items.map(d=>d.veces||0).reverse();
  Plotly.newPlot('chart-items-espec',[{
    type:'bar',orientation:'h',x:veces,y:labels,
    marker:{color:veces,colorscale:[[0,'#93C5FD'],[1,'#0A4F96']],showscale:false,line:{color:'white',width:1}},
    text:veces,textposition:'outside',textfont:{color:'#212529',size:10},
    hovertemplate:'<b>%{y}</b><br>Veces: %{x}<extra></extra>'
  }],{...LB,margin:{t:10,b:30,l:320,r:50},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:10},autorange:'reversed'}
  },CFG);
  const tbody=document.getElementById('tbody-items-espec');
  if(!tbody) return;
  tbody.innerHTML='';
  items.forEach((d,i)=>{
    const tr=document.createElement('tr');
    tr.innerHTML='<td class="mono" style="color:#6C757D">'+String(i+1).padStart(2,'0')+'</td>'
      +'<td style="font-size:0.75rem">'+(d.espec||'')+'</td>'
      +'<td><strong>'+(d.veces||0)+'</strong></td>'
      +'<td class="mono">'+fmt(d.monto||0)+'</td>';
    tbody.appendChild(tr);
  });
}

function renderFreqChart(lista) {
  if(!lista?.length) return;
  const top=lista.slice(0,10);
  Plotly.newPlot('chart-freq-prov',[{
    type:'bar',
    x:top.map(d=>(d.nombre||d.p_nombre||'').length>20?(d.nombre||d.p_nombre||'').slice(0,20)+'…':(d.nombre||d.p_nombre||'')),
    y:top.map(d=>d.ocs||0),
    marker:{color:PALETA[0],line:{color:'white',width:1.5}},
    text:top.map(d=>d.ocs||0),textposition:'outside',
    hovertemplate:'<b>%{x}</b><br>%{y} OCs<extra></extra>'
  }],{...LB,margin:{t:10,b:80,l:10,r:10},
    xaxis:{tickfont:{color:'#212529',size:10},showgrid:false,tickangle:-30},
    yaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.07)',zeroline:false}
  },CFG);
}

function renderFrecuenciaProv(lista) {
  if(!lista?.length) return;
  const badge={'Licitación / Directa (SE)':'badge-primary','Convenio Marco':'badge-success','Compra Ágil':'badge-warning','Trato Directo':'badge-danger'};
  const tbody=document.getElementById('tbody-proveedores');
  if(!tbody) return;
  tbody.innerHTML='';
  lista.forEach((p,i)=>{
    const bc=badge[p.origen_predominante]||'badge-gray';
    const tr=document.createElement('tr');
    tr.innerHTML='<td class="mono" style="color:#6C757D">'+String(i+1).padStart(2,'0')+'</td>'
      +'<td><strong>'+(p.nombre||p.p_nombre||'')+'</strong></td>'
      +'<td>'+(p.ocs||0)+'</td>'
      +'<td class="mono">'+fmt(p.monto||0)+'</td>'
      +'<td><span class="gob-badge '+bc+'">'+(p.origen_predominante||'—')+'</span></td>'
      +'<td><span class="gob-badge badge-success">'+(p.estado||p.estado_oc||'—')+'</span></td>';
    tbody.appendChild(tr);
  });
}

function poblarSelectores(anios,proveedores,unidades) {
  if(!anios?.length) return;
  // Guardar globalmente para que otros módulos (INF.EJEC, etc.) los usen
  window._anios     = anios.slice().sort();
  window._unidades  = unidades ? unidades.slice().sort() : [];
  const minA=Math.min(...anios),maxA=Math.max(...anios);
  ['sel-anio-desde','sel-anio-hasta'].forEach(id=>{
    const sel=document.getElementById(id); if(!sel) return;
    const isHasta=id.includes('hasta');
    sel.innerHTML='';
    anios.slice().sort().forEach(a=>{
      const o=new Option(a,a);
      if(isHasta&&a==maxA) o.selected=true;
      if(!isHasta&&a==minA) o.selected=true;
      sel.appendChild(o);
    });
  });
  const hp=document.getElementById('header-periodo');
  if(hp) hp.textContent=minA+' — '+maxA;
  const selUnidad=document.getElementById('sel-unidad');
  if(selUnidad&&unidades?.length){
    const valActual=selUnidad.value;
    selUnidad.innerHTML='<option value="">Todas las unidades</option>';
    unidades.slice().sort().forEach(u=>{const o=new Option(u,u);if(u===valActual)o.selected=true;selUnidad.appendChild(o);});
  }
  const selProv=document.getElementById('sel-prov-tend');
  if(selProv&&proveedores?.length){
    selProv.innerHTML='<option value="">— Seleccione proveedor —</option>';
    proveedores.forEach(p=>selProv.add(new Option(p,p)));
  }
}

async function buscarEquipos() {
  const input=document.getElementById('busq-equipo').value.trim();
  const charts=document.getElementById('equipo-charts');
  const empty=document.getElementById('equipo-empty');
  const kpisEl=document.getElementById('equipo-kpis');
  if(!input){charts.style.display='none';empty.style.display='block';return;}
  setEstado('⏳ Buscando...','#B45309');
  const p=new URLSearchParams({action:'equipos',...getFiltros(),terminos:input});
  try {
    const res=await fetch(API_URL+'?'+p);
    const d=await res.json();
    if(!d.matches?.length){empty.textContent='⚠️ Sin resultados para: '+input;empty.style.display='block';charts.style.display='none';setEstado('✔ Listo');return;}
    empty.style.display='none';charts.style.display='block';
    kpisEl.style.cssText='display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px;';
    kpisEl.innerHTML='<div class="mini-kpi"><div class="mlabel">Resultados</div><div class="mvalue">'+d.total+'</div></div>'
      +'<div class="mini-kpi"><div class="mlabel">Monto acumulado</div><div class="mvalue" style="font-size:1rem">'+d.montoFmt+'</div></div>'
      +'<div class="mini-kpi"><div class="mlabel">Proveedores</div><div class="mvalue">'+new Set(d.matches.map(x=>x.proveedor||x.p_nombre)).size+'</div></div>';
    const byP={};
    d.matches.forEach(x=>{const pv=x.proveedor||x.p_nombre||'—';byP[pv]=(byP[pv]||0)+1;});
    const po=Object.entries(byP).sort((a,b)=>b[1]-a[1]);
    Plotly.newPlot('chart-eq-prov',[{type:'bar',x:po.map(([n])=>n.length>22?n.slice(0,22)+'…':n),y:po.map(([,v])=>v),
      marker:{color:PALETA[0],line:{color:'white',width:1.5}},text:po.map(([,v])=>v),textposition:'outside',
      hovertemplate:'<b>%{x}</b><br>%{y} OCs<extra></extra>'
    }],{...LB,height:280,margin:{t:10,b:90,l:10,r:10},
      xaxis:{tickfont:{color:'#212529',size:10},showgrid:false,tickangle:-32},
      yaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.07)',zeroline:false}
    },CFG);
    const tbody=document.getElementById('tbody-equipo');
    tbody.innerHTML='';
    d.matches.forEach(x=>{
      const tr=document.createElement('tr');
      tr.innerHTML='<td class="mono" style="font-size:0.72rem">'+(x.codigo_oc||'')+'</td>'
        +'<td style="font-size:0.75rem;max-width:230px">'+(x.espec_comprador||x.espec||'')+'</td>'
        +'<td style="font-size:0.75rem">'+(x.proveedor||x.p_nombre||'')+'</td>'
        +'<td>'+(x.anio||'')+'</td>'
        +'<td class="mono">'+fmt(x.total_linea||x.monto||0)+'</td>';
      tbody.appendChild(tr);
    });
    setEstado('✔ Listo');
  } catch(err){console.error(err);setEstado('⚠ Error','#B91C1C');}
}

async function cargarTendencia() {
  const prov=document.getElementById('sel-prov-tend')?.value;
  const tendDiv=document.getElementById('tend-charts');
  const empty=document.getElementById('tend-empty');
  if(!prov){tendDiv.style.display='none';empty.style.display='block';return;}
  setEstado('⏳ Cargando tendencia...','#B45309');
  const p=new URLSearchParams({action:'tendencia',...getFiltros(),proveedor:prov});
  try {
    const res=await fetch(API_URL+'?'+p);
    const d=await res.json();
    if(!d.datos?.length){tendDiv.style.display='none';empty.style.display='block';setEstado('✔ Listo');return;}
    tendCache={};
    d.datos.forEach(x=>{tendCache[x.producto]=tendCache[x.producto]||[];tendCache[x.producto].push(x);});
    const selProd=document.getElementById('sel-prod-tend');
    selProd.innerHTML='';
    Object.keys(tendCache).sort().forEach(p=>{const o=document.createElement('option');o.value=o.textContent=p;o.selected=true;selProd.appendChild(o);});
    renderTendencia();
    setEstado('✔ Listo');
  } catch(err){console.error(err);setEstado('⚠ Error','#B91C1C');}
}

function renderTendencia() {
  const prodsAct=[...document.getElementById('sel-prod-tend').selectedOptions].map(o=>o.value);
  const tendDiv=document.getElementById('tend-charts');
  const empty=document.getElementById('tend-empty');
  if(!prodsAct.length||!Object.keys(tendCache).length){tendDiv.style.display='none';empty.style.display='block';return;}
  const datos=prodsAct.flatMap(p=>tendCache[p]||[]);
  if(!datos.length) return;
  tendDiv.style.display='block';empty.style.display='none';
  const aniosU=[...new Set(datos.map(d=>d.anio))].sort();
  const tC=prodsAct.map((prod,i)=>{
    const col=PALETA[i%PALETA.length];
    const byA=Object.fromEntries((tendCache[prod]||[]).map(d=>[d.anio,d.ocs]));
    return {type:'scatter',mode:'lines+markers',name:prod.length>45?prod.slice(0,45)+'…':prod,
      x:aniosU,y:aniosU.map(a=>byA[a]||0),line:{color:col,width:2.5},marker:{size:8,color:col},
      fill:'tozeroy',fillcolor:hexRgba(col,0.10)};
  });
  Plotly.newPlot('chart-tend-cantidad',tC,{...LB,height:320,hovermode:'x unified',
    margin:{t:10,b:40,l:50,r:10},
    xaxis:{tickfont:{color:'#6C757D'},title:{text:'Año',font:{color:'#6C757D',size:11}}},
    yaxis:{tickfont:{color:'#6C757D'},title:{text:'Órdenes',font:{color:'#6C757D',size:11}},zeroline:false},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:11}}
  },CFG);
  const tbody=document.getElementById('tbody-tend');
  tbody.innerHTML='';
  datos.sort((a,b)=>a.anio-b.anio).forEach(d=>{
    const tr=document.createElement('tr');
    tr.innerHTML='<td>'+d.anio+'</td><td>'+d.producto+'</td><td>'+d.ocs+'</td><td class="mono">'+fmt(d.monto||0)+'</td>';
    tbody.appendChild(tr);
  });
}

function togglePane(id,btn){
  const el=document.getElementById(id);
  el.classList.toggle('open');
  btn.textContent=el.classList.contains('open')?btn.textContent.replace('▾','▴'):btn.textContent.replace('▴','▾');
}

// ═══════════════════════════════════════
// INVERSIONES OC — Unidad 1057922
// ═══════════════════════════════════════
let invCargado=false;

async function cargarInversiones(){
  if(invCargado) return;
  setEstado('⏳ Cargando Inversiones...','#B45309');
  try{
    const res=await fetch(API_URL+'?action=inversiones');
    const d=await res.json();
    if(d.error) throw new Error(d.error);
    renderInvKPIs(d.kpis);
    renderInvHistograma(d.histograma);
    renderInvAGAnual(d.compraAgilAnual);
    renderInvAGComp(d.kpis.agComp);
    renderInvAGTendencia(d.tendenciaAG);
    renderInvAGMensual(d.compraAgilMensual, 'n');
    renderInvTopProv(d.topProveedores);
    renderInvAdj(d.indiceAdj);
    renderInvAdjModalidad(d.adjModalidad);
    renderPatronesProvProd(d.patronesProd);
    renderInvLitAnio(d.litPorAnio);
    renderInvProductos(d.topProductos);
    poblarInvProvs(d.proveedores);
    invCargado=true;
    setEstado('✔ Inversiones OC','#15803D');
  } catch(err){console.error(err);setEstado('⚠ '+err.message,'#B91C1C');}
}

function renderInvKPIs(k){
  const s=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
  s('inv-kpi-total', k?.inversionFmt||'—');
  s('inv-kpi-ocs',   k?.totalOcs||'—');
  s('inv-kpi-provs', k?.proveedores||'—');
  s('inv-kpi-prom',  k?.promedioOC||'—');
  s('inv-kpi-ag-n',  k?.agOcs||'—');
  s('inv-kpi-ag-m',  k?.agMontoFmt||'—');
}

function renderInvHistograma(hist){
  if(!hist||!Object.keys(hist).length) return;
  const anioStr=Object.keys(hist).sort();
  const anios=anioStr.map(Number);
  const maxAnio=Math.max(...anios);
  const origenes=[...new Set(anioStr.flatMap(a=>Object.keys(hist[a])))];
  const cOrig={'Licitación / Directa (SE)':'#0F69C4','Convenio Marco':'#15803D','Compra Ágil':'#B45309','Trato Directo':'#B91C1C','Otros Procesos':'#6B7280'};
  const traces=origenes.map(o=>({
    type:'bar',name:o,x:anios,
    y:anioStr.map(a=>((hist[a][o]?.monto||0)/1e6)),
    marker:{color:cOrig[o]||'#888',line:{color:'white',width:1}},
    hovertemplate:'<b>'+o+'</b><br>Año: %{x}<br>$%{y:.1f}M<extra></extra>'
  }));
  const totales=anioStr.map(a=>Object.values(hist[a]).reduce((s,v)=>s+(v.monto||0),0)/1e6);
  traces.push({type:'scatter',mode:'lines+markers',name:'Total',x:anios,y:totales,
    line:{color:'#1A1A2E',width:2.5,dash:'dot'},marker:{size:9,color:'#1A1A2E'},
    text:totales.map(v=>'$'+v.toFixed(0)+'M'),textposition:'top center',textfont:{size:10,color:'#1A1A2E'},
    hovertemplate:'<b>Total</b><br>Año: %{x}<br>$%{y:.1f}M<extra></extra>'
  });
  Plotly.newPlot('chart-inv-histograma',traces,{
    ...LB,barmode:'stack',
    margin:{t:10,b:55,l:65,r:10},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:false,tickmode:'array',
      tickvals:anios,ticktext:anios.map(a=>a===maxAnio?a+'*':String(a))},
    yaxis:{tickfont:{color:'#6C757D'},title:{text:'Monto ($M)',font:{color:'#6C757D',size:11}},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}},
    annotations:[{text:'* '+maxAnio+' = año en curso, datos parciales a la fecha de corte',
      showarrow:false,x:0,y:-0.15,xref:'paper',yref:'paper',
      font:{size:9,color:'#6B7280'},xanchor:'left'}]
  },CFG);
}

function renderInvAGAnual(data){
  if(!data?.length) return;
  const anios=data.map(d=>d.anio);
  const ns=data.map(d=>parseInt(d.n)||0);
  const montos=data.map(d=>(parseFloat(d.monto)||0)/1e6);
  Plotly.newPlot('chart-inv-ag-anual',[
    {type:'bar',name:'OCs Compra Ágil',x:anios,y:ns,
      marker:{color:anios.map(a=>parseInt(a)===new Date().getFullYear()?'#0F69C4':'#B45309'),line:{color:'white',width:1}},
      text:ns.map(v=>String(v)),textposition:'outside',cliponaxis:false,
      textfont:{color:'#374151',size:12,family:'Roboto,sans-serif'},
      constraintext:'none',
      hovertemplate:'<b>Compra Ágil %{x}</b><br>%{y} OCs<extra></extra>'},
    {type:'scatter',mode:'lines+markers+text',name:'Monto ($M)',x:anios,y:montos,
      yaxis:'y2',line:{color:'#15803D',width:2.5},marker:{size:8,color:'#15803D'},
      text:montos.map(v=>'$'+v.toFixed(1)+'M'),textposition:'top center',
      textfont:{color:'#15803D',size:9},cliponaxis:false,
      hovertemplate:'<b>Monto AG %{x}</b><br>$%{y:.2f}M<extra></extra>'}
  ],{
    ...LB,
    margin:{t:30,b:40,l:40,r:75},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:false,dtick:1,tickformat:'d'},
    yaxis:{tickfont:{color:'#B45309'},title:{text:'OCs',font:{color:'#B45309',size:11}},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false,autorange:true},
    yaxis2:{overlaying:'y',side:'right',tickfont:{color:'#15803D'},title:{text:'Monto ($M)',font:{color:'#15803D',size:11}},showgrid:false,zeroline:false},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}}
  },CFG);
}

function renderInvAGComp(comp){
  if(!comp) return;
  const varPct=comp.varPct;
  const varColor=varPct>=0?'#15803D':'#B91C1C';
  const varSym=varPct>=0?'▲':'▼';
  const periodo=comp.periodo||'mismo período';
  const compEl=document.getElementById('inv-ag-comp');
  if(compEl){
    compEl.innerHTML=`
      <div class="inv-comp-card" style="background:#FEF3C7;border:1px solid #FCD34D;">
        <div style="font-size:0.68rem;text-transform:uppercase;letter-spacing:.1em;color:#92400E;font-weight:700">AG ${comp.pasado.anio}</div>
        <div style="font-size:0.72rem;color:#92400E;margin-bottom:6px;font-style:italic">${periodo}</div>
        <div style="font-size:1.9rem;font-weight:700;color:#92400E;margin:4px 0;font-family:'Roboto Slab',serif">${comp.pasado.n} <small style="font-size:0.9rem;font-family:'Roboto',sans-serif">OCs</small></div>
        <div style="font-size:0.85rem;color:#78350F;font-weight:500">${comp.pasado.fmt}</div>
      </div>
      <div class="inv-comp-arrow">
        <div class="inv-comp-pct" style="color:${varColor}">${varSym} ${Math.abs(varPct)}%</div>
        <div class="inv-comp-note">variación<br>interanual</div>
      </div>
      <div class="inv-comp-card" style="background:#DCFCE7;border:1px solid #86EFAC;">
        <div style="font-size:0.68rem;text-transform:uppercase;letter-spacing:.1em;color:#166534;font-weight:700">AG ${comp.actual.anio} — Año Actual</div>
        <div style="font-size:0.72rem;color:#166534;margin-bottom:6px;font-style:italic">${periodo}</div>
        <div style="font-size:1.9rem;font-weight:700;color:#166534;margin:4px 0;font-family:'Roboto Slab',serif">${comp.actual.n} <small style="font-size:0.9rem;font-family:'Roboto',sans-serif">OCs</small></div>
        <div style="font-size:0.85rem;color:#14532D;font-weight:500">${comp.actual.fmt}</div>
      </div>`;
  }
  const notaEl=document.querySelector('.inv-nota');
  if(notaEl) notaEl.textContent='* Comparación sobre el mismo período: '+periodo+' de cada año.';
}

function renderInvAGTendencia(t){
  const el = document.getElementById('inv-ag-tendencia');
  if (!el || !t) return;

  const varOcsPos   = t.varOcs   >= 0;
  const varMontoPos = t.varMonto >= 0;
  const oSym  = v => v >= 0 ? '▲' : '▼';
  const oCol  = v => v >= 0 ? '#15803D' : '#B91C1C';

  // ── Proyección anual ─────────────────────────────────────
  const proyVsRef = t.proyAnual - t.refTotal;
  const proyCol   = proyVsRef >= 0 ? '#15803D' : '#B91C1C';
  const proySym   = proyVsRef >= 0 ? '▲' : '▼';

  el.innerHTML = `
    <!-- KPI 1: Proyección anual -->
    <div class="inv-tend-card inv-tend-proy">
      <div class="inv-tend-label">📈 Proyección Anual ${t.anioActual}</div>
      <div class="inv-tend-value" style="color:#0F69C4">~${t.proyAnual}
        <small style="font-size:0.85rem;font-weight:400;font-family:'Roboto',sans-serif"> OCs</small>
      </div>
      <div class="inv-tend-sub">Ritmo actual: <strong>${t.ritmo} OCs/mes</strong></div>
      <div class="inv-tend-sub2" style="color:${proyCol};font-weight:600">
        ${proySym} ${Math.abs(proyVsRef)} vs cierre ${t.anioPasado} (${t.refTotal} OCs)
      </div>
    </div>

    <!-- KPI 2: Mismo período año anterior -->
    <div class="inv-tend-card inv-tend-ytd">
      <div class="inv-tend-label">📊 ${t.periodo} ${t.anioActual} vs ${t.periodo} ${t.anioPasado}</div>
      <div class="inv-tend-value" style="color:${oCol(t.varOcs)}">
        ${oSym(t.varOcs)} ${Math.abs(t.varOcs)}%
        <small style="font-size:0.85rem;font-weight:400;font-family:'Roboto',sans-serif"> más OCs</small>
      </div>
      <div class="inv-tend-sub" style="display:flex;gap:12px;margin-top:8px;">
        <div style="text-align:center;flex:1;background:#EFF6FF;border-radius:6px;padding:5px 4px;">
          <div style="font-size:1.1rem;font-weight:700;color:#0F69C4">${t.ytdActual}</div>
          <div style="font-size:0.62rem;color:#6C757D;font-weight:600">${t.anioActual}</div>
        </div>
        <div style="text-align:center;flex:1;background:#FEF3C7;border-radius:6px;padding:5px 4px;">
          <div style="font-size:1.1rem;font-weight:700;color:#92400E">${t.ytdPasado}</div>
          <div style="font-size:0.62rem;color:#6C757D;font-weight:600">${t.anioPasado}</div>
        </div>
      </div>
      <div class="inv-tend-sub2" style="color:${oCol(t.varMonto)};font-weight:600;margin-top:5px;">
        ${oSym(t.varMonto)} ${Math.abs(t.varMonto)}% en monto &nbsp;·&nbsp; ${t.ytdMontoFmt}
      </div>
    </div>

    <!-- KPI 3: Próximo peak -->
    <div class="inv-tend-card inv-tend-peak">
      <div class="inv-tend-label">⚡ Próximo Peak</div>
      <div class="inv-tend-value" style="color:#D97706">${t.peakNombre}</div>
      <div class="inv-tend-sub">Prom. histórico: <strong>${t.peakProm} OCs</strong></div>
      <div class="inv-tend-sub2">Basado en 2021–${t.anioPasado}</div>
    </div>`;
}

let _invAgMensualData = [];

function renderInvAGMensual(data, metric){
  _invAgMensualData = data;
  if(!data?.length) return;

  const anios  = [...new Set(data.map(r=>+r.anio))].sort();
  const maxAnio = Math.max(...anios);
  const isMonto = metric === 'monto';

  // Colores: año anterior = ámbar, año actual = azul
  const COLORES = { [anios[0]]: '#B45309', [anios[1] ?? anios[0]]: '#0F69C4' };
  const OPACIDAD = { [anios[0]]: 0.75,    [anios[1] ?? anios[0]]: 1 };

  const traces = anios.map(anio => {
    const rowsByMes = {};
    data.filter(r=>+r.anio===anio).forEach(r=>{ rowsByMes[+r.mes]=r; });

    const yVals = MESES_ES.map((_,i) => {
      const row = rowsByMes[i+1];
      if(!row) return 0;
      return isMonto ? +(+row.monto/1e6).toFixed(2) : +row.n;
    });
    const textVals = yVals.map(v => v === 0 ? '' : isMonto ? '$'+v.toFixed(1)+'M' : String(v));
    const esCurso = anio === maxAnio;
    return {
      type:'bar',
      name: esCurso ? String(anio)+' ★ actual' : String(anio),
      x: MESES_ES,
      y: yVals,
      marker:{ color: COLORES[anio]||'#94A3B8', opacity: OPACIDAD[anio]||0.85 },
      text: textVals,
      textposition:'outside',
      cliponaxis:false,
      constraintext:'none',
      textfont:{ size:10, color: COLORES[anio]||'#555' },
      hovertemplate:`<b>${anio} — %{x}</b><br>${isMonto?'Monto':'OCs'}: %{y}<extra></extra>`
    };
  });

  // Anotación de variación % en los meses que ambos años tienen dato
  const annotations = [];
  if(anios.length === 2){
    const [a0, a1] = anios;
    MESES_ES.forEach((mes, i) => {
      const r0 = data.find(r=>+r.anio===a0&&+r.mes===i+1);
      const r1 = data.find(r=>+r.anio===a1&&+r.mes===i+1);
      if(!r0||!r1) return;
      const v0 = isMonto ? +r0.monto : +r0.n;
      const v1 = isMonto ? +r1.monto : +r1.n;
      if(!v0) return;
      const pct = Math.round((v1-v0)/v0*100);
      const color = pct >= 0 ? '#15803D' : '#B91C1C';
      const sym   = pct >= 0 ? '▲' : '▼';
      annotations.push({
        x: mes, y: Math.max(isMonto?v0/1e6:v0, isMonto?v1/1e6:v1),
        text: `<b style="color:${color}">${sym}${Math.abs(pct)}%</b>`,
        showarrow:false, yshift:14,
        font:{ size:9, color }
      });
    });
  }

  Plotly.react('chart-inv-ag-comp', traces, {
    ...LB, barmode:'group', bargap:0.22, bargroupgap:0.06,
    xaxis:{ tickfont:{size:11}, showgrid:false },
    yaxis:{ title: isMonto?'Monto ($M)':'N° OCs',
            gridcolor:'rgba(0,0,0,0.06)', zeroline:false,
            tickformat: isMonto?'$.1f,':',d' },
    legend:{ orientation:'h', y:1.08, x:0, font:{size:10} },
    annotations,
    margin:{ t:20, b:36, l:44, r:10 }
  }, CFG);
}

function invAgMensualToggle(btn, metric){
  document.querySelectorAll('[onclick*="invAgMensualToggle"]')
    .forEach(b=>b.classList.remove('ag-mes-btn-act'));
  btn.classList.add('ag-mes-btn-act');
  renderInvAGMensual(_invAgMensualData, metric);
}

function renderInvTopProv(data){
  if(!data?.length) return;
  const n=data.map(d=>(d.nombre||'').length>36?(d.nombre||'').slice(0,36)+'…':(d.nombre||'')).reverse();
  const m=data.map(d=>(parseFloat(d.monto)||0)/1e6).reverse();
  Plotly.newPlot('chart-inv-top-prov',[{
    type:'bar',orientation:'h',x:m,y:n,
    marker:{color:m,colorscale:[[0,'#93C5FD'],[1,'#0A4F96']],showscale:false,line:{color:'white',width:1}},
    text:m.map(v=>'$'+v.toFixed(1)+'M'),textposition:'outside',
    hovertemplate:'<b>%{y}</b><br>$%{x:.2f}M<extra></extra>'
  }],{
    ...LB,
    margin:{t:10,b:30,l:230,r:70},
    xaxis:{title:{text:'Monto ($M)',font:{color:'#6C757D',size:11}},tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:11}}
  },CFG);
}

function renderInvAdj(data){
  if(!data?.length) return;
  const top=data.slice(0,12);
  const n=top.map(d=>(d.nombre||'').length>36?(d.nombre||'').slice(0,36)+'…':(d.nombre||'')).reverse();
  const p=top.map(d=>parseInt(d.procesos)||0).reverse();
  Plotly.newPlot('chart-inv-adj',[{
    type:'bar',orientation:'h',x:p,y:n,
    marker:{color:'#15803D',line:{color:'white',width:1}},
    text:p,textposition:'outside',
    hovertemplate:'<b>%{y}</b><br>%{x} procesos adjudicados<extra></extra>'
  }],{
    ...LB,
    margin:{t:10,b:30,l:230,r:50},
    xaxis:{title:{text:'N° Procesos',font:{color:'#6C757D',size:11}},tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:11}}
  },CFG);
}

function renderInvLitAnio(data){
  if(!data?.length) return;
  const anios=[...new Set(data.map(d=>d.anio))].sort();
  const origenes=[...new Set(data.map(d=>d.origen_compra))].filter(Boolean);
  // Total monto por año para la línea
  const montoByAnio={};
  data.forEach(d=>{ montoByAnio[d.anio]=(montoByAnio[d.anio]||0)+(parseFloat(d.monto)||0); });
  // Traza apilada por modalidad
  const barTraces=origenes.map(o=>{
    const byAnio={};
    data.filter(d=>d.origen_compra===o).forEach(d=>{ byAnio[d.anio]=parseInt(d.n)||0; });
    return {type:'bar',name:o,x:anios,y:anios.map(a=>byAnio[a]||0),
      marker:{color:COLOR_ORIGEN[o]||'#888',line:{color:'white',width:1}},
      hovertemplate:`<b>${o}</b><br>Año %{x}: %{y} OCs<extra></extra>`};
  });
  // Línea monto total
  const montos=anios.map(a=>(montoByAnio[a]||0)/1e6);
  const maxAnioLit=Math.max(...anios);
  Plotly.newPlot('chart-inv-lit-anio',[...barTraces,
    {type:'scatter',mode:'lines+markers',name:'Monto Total ($M)',x:anios,y:montos,
      yaxis:'y2',line:{color:'#374151',width:2.5},marker:{size:8,color:'#374151'},
      hovertemplate:'<b>Total %{x}</b><br>$%{y:.1f}M<extra></extra>'}
  ],{
    ...LB,barmode:'stack',
    margin:{t:20,b:55,l:40,r:70},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:false,tickmode:'array',
      tickvals:anios,ticktext:anios.map(a=>a===maxAnioLit?a+'*':String(a))},
    yaxis:{tickfont:{color:'#0F69C4'},title:{text:'OCs',font:{color:'#0F69C4',size:11}},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis2:{overlaying:'y',side:'right',tickfont:{color:'#374151'},title:{text:'Monto ($M)',font:{color:'#374151',size:11}},showgrid:false,zeroline:false},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}},
    annotations:[{text:'* '+maxAnioLit+' = año en curso, datos parciales a la fecha de corte',
      showarrow:false,x:0,y:-0.15,xref:'paper',yref:'paper',
      font:{size:9,color:'#6B7280'},xanchor:'left'}]
  },CFG);
}

function renderInvProductos(items){
  if(!items?.length) return;
  const labels=items.map(d=>(d.espec||'').length>52?(d.espec||'').slice(0,52)+'…':(d.espec||'')).reverse();
  const veces=items.map(d=>parseInt(d.veces)||0).reverse();
  Plotly.newPlot('chart-inv-productos',[{
    type:'bar',orientation:'h',x:veces,y:labels,
    marker:{color:veces,colorscale:[[0,'#93C5FD'],[1,'#0A4F96']],showscale:false,line:{color:'white',width:1}},
    text:veces,textposition:'outside',textfont:{color:'#212529',size:10},
    hovertemplate:'<b>%{y}</b><br>%{x} veces comprado<extra></extra>'
  }],{
    ...LB,
    margin:{t:10,b:30,l:360,r:50},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:10}}
  },CFG);
  const tbody=document.getElementById('tbody-inv-prod');
  if(!tbody) return;
  tbody.innerHTML='';
  items.forEach((d,i)=>{
    const tr=document.createElement('tr');
    tr.innerHTML='<td class="mono" style="color:#6C757D">'+String(i+1).padStart(2,'0')+'</td>'
      +'<td style="font-size:0.75rem;max-width:280px">'+(d.espec||'')+'</td>'
      +'<td><strong>'+(d.veces||0)+'</strong></td>'
      +'<td class="mono">'+fmt(parseFloat(d.monto)||0)+'</td>'
      +'<td style="font-size:0.75rem;color:#374151">'+(d.proveedor||'—')+'</td>';
    tbody.appendChild(tr);
  });
}

function poblarInvProvs(provs){
  const sel=document.getElementById('inv-sel-prov');
  if(!sel||!provs?.length) return;
  sel.innerHTML='<option value="">— Seleccione proveedor —</option>';
  provs.forEach(p=>sel.add(new Option(p,p)));
}

// ── Modalidad de compra por proveedor ────────────────────
function renderInvAdjModalidad(data){
  if(!data?.length) return;
  // Totalizar por proveedor para ordenar el eje
  const totals={};
  data.forEach(d=>{if(!totals[d.nombre])totals[d.nombre]=0;totals[d.nombre]+=parseInt(d.procesos)||0;});
  // Top 15 por total de procesos (ya viene filtrado desde PHP)
  const provsSorted=Object.entries(totals).sort((a,b)=>b[1]-a[1]).map(x=>x[0]);
  const origenes=[...new Set(data.map(d=>d.origen))];
  const cOrig={'Licitación / Directa (SE)':'#0F69C4','Convenio Marco':'#15803D','Compra Ágil':'#B45309','Trato Directo':'#B91C1C','Otros Procesos':'#6B7280'};
  const traces=origenes.map(o=>{
    const yLabels=provsSorted.map(p=>p.length>38?p.slice(0,38)+'…':p).reverse();
    const xVals=provsSorted.map(p=>{
      const row=data.find(d=>d.nombre===p&&d.origen===o);
      return row?parseInt(row.procesos)||0:0;
    }).reverse();
    return{
      type:'bar',orientation:'h',name:o,x:xVals,y:yLabels,
      marker:{color:cOrig[o]||'#888',line:{color:'white',width:1}},
      hovertemplate:'<b>'+o+'</b><br>%{y}<br>%{x} procesos<extra></extra>'
    };
  });
  Plotly.newPlot('chart-inv-adj-modalidad',traces,{
    ...LB,barmode:'stack',
    margin:{t:10,b:30,l:240,r:20},
    xaxis:{title:{text:'Procesos adjudicados',font:{color:'#6C757D',size:11}},tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:11}},
    legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}}
  },CFG);
}

// ── Patrones proveedor × producto ────────────────────────
function renderPatronesProvProd(data){
  if(!data?.length) return;
  // KPI counters
  const monopolio=data.filter(d=>parseFloat(d.pct_concentracion)===100).length;
  const alta     =data.filter(d=>parseFloat(d.pct_concentracion)>70&&parseFloat(d.pct_concentracion)<100).length;
  const media    =data.filter(d=>parseFloat(d.pct_concentracion)>=50&&parseFloat(d.pct_concentracion)<=70).length;
  const diversif =data.filter(d=>parseFloat(d.pct_concentracion)<50).length;
  const s=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
  s('inv-pat-monopolio',monopolio);s('inv-pat-alta',alta);s('inv-pat-media',media);s('inv-pat-div',diversif);

  // Ordenar ascendente para la barra (los más bajos arriba → los más altos al fondo)
  const sorted=[...data].sort((a,b)=>parseFloat(a.pct_concentracion)-parseFloat(b.pct_concentracion));
  const labels=sorted.map(d=>(d.espec||'').length>54?(d.espec||'').slice(0,54)+'…':(d.espec||''));
  const pcts  =sorted.map(d=>parseFloat(d.pct_concentracion)||0);
  const colors=pcts.map(p=>p===100?'#B91C1C':p>70?'#D97706':p>=50?'#CA8A04':'#15803D');
  const texts =sorted.map(d=>`${d.pct_concentracion}% · ${(d.proveedor||'').slice(0,28)}`);

  Plotly.newPlot('chart-inv-patron',[{
    type:'bar',orientation:'h',x:pcts,y:labels,
    marker:{color:colors,line:{color:'white',width:0.5}},
    text:texts,textposition:'inside',insidetextanchor:'start',
    textfont:{size:10,color:'white'},cliponaxis:false,
    customdata:sorted.map(d=>[d.proveedor,d.origen,d.total_veces,d.n_proveedores,d.veces_top]),
    hovertemplate:'<b>%{y}</b><br>Proveedor: %{customdata[0]}<br>Concentración: <b>%{x}%</b><br>Modalidad: %{customdata[1]}<br>Veces dominante: %{customdata[4]} / %{customdata[2]}<br>Nº proveedores en competencia: %{customdata[3]}<extra></extra>'
  }],{
    ...LB,
    margin:{t:10,b:50,l:370,r:110},
    xaxis:{title:{text:'% del ítem adjudicado al proveedor dominante',font:{color:'#6C757D',size:11}},tickfont:{color:'#6C757D'},range:[0,108],showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
    yaxis:{tickfont:{color:'#212529',size:10}},
    shapes:[
      {type:'line',x0:50,x1:50,y0:-0.5,y1:labels.length-0.5,line:{color:'#CA8A04',width:1.5,dash:'dash'}},
      {type:'line',x0:70,x1:70,y0:-0.5,y1:labels.length-0.5,line:{color:'#D97706',width:2,dash:'dash'}},
      {type:'rect',x0:100,x1:108,y0:-0.5,y1:labels.length-0.5,fillcolor:'rgba(185,28,28,0.07)',line:{width:0}}
    ],
    annotations:[
      {x:50,y:labels.length,text:'50% competitivo',showarrow:false,font:{color:'#CA8A04',size:9},xanchor:'left',yanchor:'bottom'},
      {x:70,y:labels.length,text:'70% alta conc.',showarrow:false,font:{color:'#D97706',size:9},xanchor:'left',yanchor:'bottom'},
      {x:104,y:labels.length,text:'MONOP.',showarrow:false,font:{color:'#B91C1C',size:9},xanchor:'center',yanchor:'bottom'}
    ]
  },CFG);

  // Tabla
  const tbody=document.getElementById('tbody-inv-patron');
  if(!tbody) return;
  tbody.innerHTML='';
  [...data].sort((a,b)=>parseFloat(b.pct_concentracion)-parseFloat(a.pct_concentracion))
  .forEach((d,i)=>{
    const pct=parseFloat(d.pct_concentracion);
    const lvl=pct===100?['conc-monopolio','MONOPOLIO']:pct>70?['conc-alta','ALTA']:pct>=50?['conc-media','MEDIA']:['conc-comp','COMPETITIVO'];
    const tr=document.createElement('tr');
    tr.innerHTML=`<td class="mono" style="color:#6C757D">${String(i+1).padStart(2,'0')}</td>
      <td style="font-size:0.72rem;max-width:200px">${d.espec||''}</td>
      <td style="font-size:0.72rem">${d.proveedor||'—'}</td>
      <td style="font-size:0.72rem">${d.origen||'—'}</td>
      <td><span class="conc-badge ${lvl[0]}">${lvl[1]}</span> <strong>${pct}%</strong></td>
      <td class="mono">${d.veces_top||0} / ${d.total_veces||0}</td>
      <td class="mono" style="text-align:center">${d.n_proveedores||1}</td>`;
    tbody.appendChild(tr);
  });
}

async function cargarTendenciaInv(){
  const prov=document.getElementById('inv-sel-prov')?.value;
  const charts=document.getElementById('inv-tend-charts');
  const empty=document.getElementById('inv-tend-empty');
  if(!prov){charts.style.display='none';empty.style.display='block';return;}
  setEstado('⏳ Cargando tendencia...','#B45309');
  const p=new URLSearchParams({action:'inv_tend',proveedor:prov});
  try{
    const res=await fetch(API_URL+'?'+p);
    const d=await res.json();
    if(!d.datos?.length){charts.style.display='none';empty.textContent='Sin datos para: '+prov;empty.style.display='block';setEstado('✔ Listo');return;}
    const anios=d.datos.map(x=>x.anio);
    const montos=d.datos.map(x=>(parseFloat(x.monto)||0)/1e6);
    const ocs=d.datos.map(x=>parseInt(x.ocs)||0);
    Plotly.newPlot('chart-inv-tend',[
      {type:'scatter',mode:'lines+markers',name:'Monto ($M)',x:anios,y:montos,
        fill:'tozeroy',fillcolor:'rgba(15,105,196,0.10)',
        line:{color:'#0F69C4',width:2.5},marker:{size:9,color:'#0F69C4'},
        text:montos.map(v=>'$'+v.toFixed(1)+'M'),textposition:'top center',textfont:{size:10},
        hovertemplate:'Año %{x}<br>$%{y:.2f}M<extra></extra>'},
      {type:'bar',name:'OCs',x:anios,y:ocs,yaxis:'y2',
        marker:{color:'#B45309',opacity:0.5,line:{color:'white',width:1}},
        hovertemplate:'Año %{x}<br>%{y} OCs<extra></extra>'}
    ],{
      ...LB,hovermode:'x unified',
      margin:{t:10,b:40,l:60,r:60},
      xaxis:{tickfont:{color:'#6C757D'},title:{text:'Año',font:{color:'#6C757D',size:11}},dtick:1,tickformat:'d',tickmode:'array',tickvals:anios,range:[Math.min(...anios.map(Number))-0.5,Math.max(...anios.map(Number))+0.5]},
      yaxis:{tickfont:{color:'#0F69C4'},title:{text:'Monto ($M)',font:{color:'#0F69C4',size:11}},zeroline:false},
      yaxis2:{overlaying:'y',side:'right',tickfont:{color:'#B45309'},title:{text:'OCs',font:{color:'#B45309',size:11}},showgrid:false,zeroline:false},
      legend:{orientation:'h',yanchor:'bottom',y:1.02,xanchor:'right',x:1,font:{size:10}}
    },CFG);
    charts.style.display='block';
    empty.style.display='none';
    setEstado('✔ Listo','#15803D');
  } catch(err){console.error(err);setEstado('⚠ Error','#B91C1C');}
}

async function buscarEquiposInv() {
  const input  = document.getElementById('inv-busq-eq').value.trim();
  const results = document.getElementById('inv-eq-results');
  const empty   = document.getElementById('inv-eq-empty');
  const kpisEl  = document.getElementById('inv-eq-kpis');
  if (!input) { results.style.display='none'; empty.textContent='Ingresa el nombre de un equipo para ver qué proveedores lo adjudican con mayor frecuencia y su evolución a través del tiempo.'; empty.style.display='block'; return; }
  setEstado('⏳ Buscando equipos...','#B45309');
  const p = new URLSearchParams({action:'equipos', anioDesde:2018, anioHasta:new Date().getFullYear(), unidad:'Activos Fijos', origen:'', proveedor:'', busqueda:'', terminos:input});
  try {
    const res = await fetch(API_URL+'?'+p);
    const d   = await res.json();
    if (!d.matches?.length) {
      empty.textContent = '⚠️ Sin resultados para: "'+input+'" en Inversiones (unidad 1057922)';
      empty.style.display='block'; results.style.display='none'; setEstado('✔ Listo'); return;
    }
    empty.style.display='none'; results.style.display='block';

    // KPIs
    const numProv = new Set(d.matches.map(x=>x.proveedor||x.p_nombre||'—')).size;
    kpisEl.innerHTML =
      '<div class="mini-kpi"><div class="mlabel">Resultados</div><div class="mvalue">'+d.total+'</div></div>'+
      '<div class="mini-kpi"><div class="mlabel">Monto acumulado</div><div class="mvalue" style="font-size:1rem">'+d.montoFmt+'</div></div>'+
      '<div class="mini-kpi"><div class="mlabel">Proveedores</div><div class="mvalue">'+numProv+'</div></div>';

    // Agrupar por proveedor
    const byP = {};
    d.matches.forEach(x=>{
      const pv = x.proveedor||x.p_nombre||'—';
      if (!byP[pv]) byP[pv]={ocs:0, monto:0};
      byP[pv].ocs++; byP[pv].monto += parseFloat(x.total_linea||x.monto||0);
    });
    const top10 = Object.entries(byP).sort((a,b)=>b[1].ocs-a[1].ocs).slice(0,10);
    const top10Set = new Set(top10.map(x=>x[0]));

    // Gráfico horizontal — Top 10 por N° OCs
    const yn = top10.map(([n])=>n.length>38?n.slice(0,38)+'…':n).reverse();
    const xn = top10.map(([,v])=>v.ocs).reverse();
    Plotly.newPlot('chart-inv-eq-prov',[{
      type:'bar', orientation:'h', x:xn, y:yn,
      marker:{color:xn, colorscale:[[0,'#93C5FD'],[1,'#0A4F96']], showscale:false, line:{color:'white',width:1}},
      text:xn, textposition:'outside',
      hovertemplate:'<b>%{y}</b><br>%{x} OCs<extra></extra>'
    }],{
      ...LB, margin:{t:10,b:20,l:260,r:60},
      xaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.06)',zeroline:false},
      yaxis:{tickfont:{color:'#212529',size:10}}
    },CFG);

    // Tendencia anual — top 10 por año
    const byYr = {};
    d.matches.filter(x=>top10Set.has(x.proveedor||x.p_nombre||'—')).forEach(x=>{
      const yr = x.anio; const pv = x.proveedor||x.p_nombre||'—';
      if (!byYr[yr]) byYr[yr]={};
      byYr[yr][pv]=(byYr[yr][pv]||0)+1;
    });
    const yrs = Object.keys(byYr).map(Number).sort();
    const pal = ['#0F69C4','#15803D','#D97706','#B91C1C','#7C3AED','#0891B2','#EA580C','#16A34A','#9333EA','#64748B'];
    const traces = top10.map(([pv],i)=>({
      type:'scatter', mode:'lines+markers', name:pv.length>28?pv.slice(0,28)+'…':pv,
      x:yrs, y:yrs.map(yr=>(byYr[yr]||{})[pv]||0),
      line:{color:pal[i%10],width:2.5}, marker:{size:8,color:pal[i%10]},
      hovertemplate:'<b>'+pv.slice(0,50)+'</b><br>Año %{x}: %{y} OCs<extra></extra>'
    }));
    Plotly.newPlot('chart-inv-eq-tend', traces, {
      ...LB, hovermode:'x unified',
      margin:{t:10,b:75,l:50,r:10},
      xaxis:{tickfont:{color:'#6C757D'},dtick:1,tickformat:'d',tickmode:'array',tickvals:yrs},
      yaxis:{tickfont:{color:'#6C757D'},showgrid:true,gridcolor:'rgba(0,0,0,0.07)',zeroline:false,
             title:{text:'N° OCs',font:{color:'#6C757D',size:11}}},
      legend:{orientation:'h',yanchor:'top',y:-0.2,xanchor:'left',x:0,font:{size:9}}
    },CFG);

    // Tabla completa
    const tbody = document.getElementById('tbody-inv-eq');
    tbody.innerHTML='';
    d.matches.forEach(x=>{
      const tr=document.createElement('tr');
      tr.innerHTML='<td class="mono" style="font-size:0.72rem">'+(x.codigo_oc||'')+'</td>'
        +'<td style="font-size:0.75rem;max-width:230px">'+(x.espec||x.espec_comprador||'')+'</td>'
        +'<td style="font-size:0.75rem">'+(x.proveedor||x.p_nombre||'')+'</td>'
        +'<td>'+(x.anio||'')+'</td>'
        +'<td class="mono">'+fmt(parseFloat(x.total_linea||x.monto||0))+'</td>';
      tbody.appendChild(tr);
    });
    setEstado('✔ Listo','#15803D');
  } catch(err){console.error(err); setEstado('⚠ Error','#B91C1C');}
}

document.addEventListener('DOMContentLoaded',()=>{
  showTab('estadisticas');
  cargarDatos();
  document.getElementById('busq-equipo')?.addEventListener('keydown',e=>{if(e.key==='Enter')buscarEquipos();});
  document.getElementById('inv-busq-eq')?.addEventListener('keydown',e=>{if(e.key==='Enter')buscarEquiposInv();});
  document.getElementById('inp-busqueda')?.addEventListener('keydown',e=>{if(e.key==='Enter')cargarDatos();});
});

// ═══════════════════════════════════════
// OBRAS CIVILES
// ═══════════════════════════════════════
let obrasCargado = false;

async function cargarObras(){
  if(obrasCargado) return;
  setEstado('⏳ Cargando Obras Civiles...','#B45309');
  try {
    const res = await fetch(API_URL+'?action=obras');
    const d   = await res.json();
    if(d.error) throw new Error(d.error);
    renderObrasKPIs(d.kpis);
    renderObrasEvolucion(d.evolucion);
    renderObrasCategoria(d.distCategoria);
    renderObrasTopProv(d.topProv);
    renderObrasActivas(d.activas);
    obrasCargado = true;
    setEstado('✔ Obras Civiles','#15803D');
  } catch(err){ console.error(err); setEstado('⚠ '+err.message,'#B91C1C'); }
}

function renderObrasKPIs(k){
  const s=(id,v)=>{const e=document.getElementById(id);if(e)e.textContent=v;};
  s('ob-kpi-total',  k.total_inversion_fmt);
  s('ob-kpi-ejec',   k.obras_en_ejecucion + ' obras');
  s('ob-kpi-ejec-m', k.inversion_ejecucion);
  s('ob-kpi-term',   k.obras_terminadas + ' obras');
  s('ob-kpi-tasa',   '✔ ' + k.tasa_finalizacion + '% tasa finalización');
  s('ob-kpi-gore',   k.inversion_gore);
  s('ob-kpi-gore-pct', k.pct_gore + '% del total');
  s('ob-kpi-prom',   k.inversion_promedio_fmt);
  const topEl = document.getElementById('ob-kpi-top');
  if(topEl) topEl.textContent = (k.top_obra||'—').substring(0,50)+(k.top_obra?.length>50?'…':'');
  s('ob-kpi-top-m',  k.top_obra_monto_fmt);
}

function renderObrasEvolucion(data){
  if(!data?.length) return;
  const anios = [...new Set(data.map(r=>+r.anio))].sort();
  const unidades = [...new Set(data.map(r=>r.unidad))];
  const CLR = {'Obras (1057976)':'#0F69C4','GORE (1180747)':'#7C3AED'};
  const traces = unidades.map(u => {
    const vals = anios.map(a => {
      const row = data.find(r=>+r.anio===a && r.unidad===u);
      return row ? Math.round(+row.monto/1e6) : 0;
    });
    return {type:'bar',name:u,x:anios,y:vals,marker:{color:CLR[u]||'#94A3B8'},
      text:vals.map(v=>v>0?'$'+v+'M':''),textposition:'outside',cliponaxis:false,
      hovertemplate:'<b>'+u+'</b> %{x}<br>$%{y}M<extra></extra>'};
  });
  const layout={...LB,barmode:'group',bargap:0.2,
    xaxis:{dtick:1,tickformat:'d'},
    yaxis:{title:'Monto ($M)',gridcolor:'#F1F5F9'},
    legend:{orientation:'h',y:-0.18},margin:{t:30,r:20,b:70,l:60}};
  Plotly.newPlot('chart-obras-evolucion',traces,layout,CFG);
}

function renderObrasCategoria(data){
  if(!data?.length) return;

  // Paleta amplia para muchos proyectos
  const PALETA = [
    '#0F69C4','#15803D','#D97706','#7C3AED','#DC2626',
    '#0891B2','#65A30D','#EA580C','#9333EA','#0F766E',
    '#B45309','#1D4ED8','#16A34A','#C2410C','#6D28D9',
  ];
  const n = data.length;
  const colors = data.map((_,i) => i === n-1 && data[i].nombre === 'Otras obras'
    ? '#94A3B8' : PALETA[i % PALETA.length]);

  const labels = data.map(r => r.nombre);
  const values = data.map(r => Math.round(+r.monto/1e6));
  const texts  = data.map(r => r.n + ' OC' + (r.n!==1?'s':''));

  const trace = {
    type: 'pie',
    labels,
    values,
    customdata: texts,
    hole: 0.40,
    marker: { colors, line:{color:'#fff',width:1.5} },
    textinfo: 'percent',
    textfont: { size: 10.5, color:'#fff' },
    insidetextorientation: 'radial',
    hovertemplate: '<b>%{label}</b><br>$%{value}M · %{percent}<br>%{customdata}<extra></extra>',
    sort: false,
  };

  const layout = {
    ...LB,
    showlegend: true,
    legend: {
      orientation: 'v',
      x: 1.01, y: 1,
      font: { size: 9.5 },
      itemwidth: 20,
    },
    margin: { t: 10, b: 10, l: 10, r: 160 },
  };
  Plotly.newPlot('chart-obras-categoria', [trace], layout, CFG);
}

function renderObrasTopProv(data){
  if(!data?.length) return;
  const provs  = data.map(r=>r.p_nombre.length>45?r.p_nombre.substring(0,45)+'…':r.p_nombre).reverse();
  const montos = data.map(r=>Math.round(+r.monto/1e6)).reverse();
  const nOcs   = data.map(r=>+r.n_ocs).reverse();
  const max    = Math.max(...montos)||1;
  const colors = montos.map(v=>{
    const t=v/max;
    return `rgba(15,${Math.round(105+t*50)},196,${0.5+t*0.5})`;
  });
  const trace={type:'bar',orientation:'h',x:montos,y:provs,
    marker:{color:colors},
    text:montos.map(v=>'$'+v+'M'),textposition:'outside',cliponaxis:false,constraintext:'none',
    customdata:nOcs,
    hovertemplate:'<b>%{y}</b><br>Monto: $%{x}M<br>OCs/contratos: %{customdata}<extra></extra>'};
  const layout={...LB,xaxis:{title:'Monto total ($M)',gridcolor:'#F1F5F9'},
    yaxis:{automargin:true,tickfont:{size:10}},margin:{t:10,b:50,l:260,r:100}};
  Plotly.newPlot('chart-obras-top-prov',[trace],layout,CFG);
}

function renderObrasActivas(data){
  const tbody = document.getElementById('obras-activas-body');
  if(!tbody || !data?.length){
    if(tbody) tbody.innerHTML='<tr><td colspan="8" style="text-align:center;color:#6C757D;padding:24px">No hay obras en ejecución actualmente.</td></tr>';
    return;
  }

  // Colores y etiquetas según estado_plazo
  const estadoMap = {
    en_plazo:  { bar:'#22C55E', bg:'#DCFCE7', label:'En plazo',     icon:'✅' },
    proximo:   { bar:'#F59E0B', bg:'#FEF3C7', label:'Próx. venc.',  icon:'⚠️' },
    al_limite: { bar:'#EF4444', bg:'#FEF2F2', label:'Al límite',    icon:'🔴' },
    atrasado:  { bar:'#B91C1C', bg:'#FEF2F2', label:'Atrasado',     icon:'🚨' },
    sin_datos: { bar:'#94A3B8', bg:'#F8FAFC', label:'Sin ficha',    icon:'—'  },
  };

  tbody.innerHTML = data.map(r => {
    const dias   = +r.dias_activa || 0;
    const diasCol = dias > 730 ? '#B91C1C' : dias > 365 ? '#D97706' : '#15803D';
    const bipHtml = r.cod_bip
      ? `<a href="https://www.mercadopublico.cl/BIP/BusquedaBIP?codigoBip=${r.cod_bip}" target="_blank"
            style="font-family:monospace;font-size:0.71rem;background:#EFF6FF;color:#1D4ED8;padding:2px 5px;border-radius:4px;text-decoration:none">${r.cod_bip}</a>`
      : '<span style="color:#CBD5E1">—</span>';

    // Columna de plazo / progreso
    let plazoHtml;
    const est  = estadoMap[r.estado_plazo] || estadoMap.sin_datos;
    const pct  = r.pct_avance;

    if (pct !== null && pct !== undefined) {
      const barW   = Math.min(100, pct);
      const pctDisp = pct > 150 ? '>150' : pct;

      // Tooltip con detalles
      const tip = [
        `Plazo total: ${r.plazo_dias} días`,
        `Transcurridos: ${r.dias_transcurridos} días`,
        r.dias_restantes > 0 ? `Quedan: ${r.dias_restantes} días` : 'Plazo superado',
        `Término est.: ${r.fecha_termino_est || '—'}`,
      ].join(' | ');

      plazoHtml = `
        <div title="${tip}" style="min-width:140px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
            <span style="font-size:0.68rem;font-weight:700;color:${est.bar}">${est.icon} ${est.label}</span>
            <span style="font-size:0.7rem;font-weight:700;color:${pct>100?'#B91C1C':'#374151'}">${pctDisp}%</span>
          </div>
          <div style="height:7px;background:#E2E8F0;border-radius:4px;overflow:hidden">
            <div style="height:100%;width:${barW}%;background:${est.bar};border-radius:4px;transition:width .4s"></div>
          </div>
          <div style="font-size:0.63rem;color:#6B7280;margin-top:2px">
            ${r.dias_transcurridos}d / ${r.plazo_dias}d · fin est. ${r.fecha_termino_est ? r.fecha_termino_est.substring(0,7) : '—'}
          </div>
        </div>`;
    } else {
      plazoHtml = `<span style="font-size:0.72rem;color:#94A3B8">${est.icon} ${est.label}</span>`;
    }

    const nombre = (r.nombre_proyecto||r.nombre_oc||'—').substring(0,72);
    return `<tr>
      <td style="font-family:monospace;font-size:0.72rem;white-space:nowrap">${r.codigo_oc}</td>
      <td style="font-size:0.78rem;max-width:260px;white-space:normal;line-height:1.35">${nombre}</td>
      <td style="font-size:0.73rem">${(r.p_nombre||'—').substring(0,32)}</td>
      <td style="text-align:right;font-weight:700;font-size:0.82rem">${r.total_bruto_fmt}</td>
      <td style="font-size:0.73rem;white-space:nowrap">${(r.fecha_inicio||r.fecha_envio||'—').substring(0,10)}</td>
      <td style="text-align:right;font-weight:700;color:${diasCol}">${dias.toLocaleString('es-CL')}</td>
      <td style="padding:6px 8px">${plazoHtml}</td>
      <td>${bipHtml}</td>
    </tr>`;
  }).join('');
}

// ═══════════════════════════════════════
// COMPRA ÁGIL
// ═══════════════════════════════════════
let agCargado=false;

async function cargarCompraAgil(){
  if(agCargado) return;
  try {
    const d = await fetch(API_URL+'?action=compraagil').then(r=>r.json());
    renderAgKPIs(d.kpis);
    renderAgEvolucion(d.evolucion);
    renderAgMensual(d.evolucionMensual, 'total');
    renderAgDistEstado(d.distEstado);
    renderAgConvocatoria(d.convocatoria);
    renderAgMonto(d.montoAnual);
    renderAgCompetencia(d.competencia);
    renderAgTopProv(d.topProv);
    renderAgTopItems(d.topItems);
    renderAgEstadoOC(d.estadoOC);
    agCargado=true;
  } catch(err){ console.error('AG error:',err); }
}

function renderAgKPIs(k){
  const el=id=>document.getElementById(id);
  el('ag-k-total').textContent = k.total_cot.toLocaleString('es-CL');
  el('ag-k-oc').textContent    = k.oc_emitidas.toLocaleString('es-CL');
  el('ag-k-tasa').innerHTML    = `<span class="ag-tasa-pill">⚡ ${k.tasa_adj}% adjudicación</span>`;
  el('ag-k-des').textContent   = k.desiertas.toLocaleString('es-CL');
  el('ag-k-can').textContent   = k.canceladas.toLocaleString('es-CL');
  el('ag-k-ppto').textContent  = k.monto_ppto_fmt;
  el('ag-k-real').textContent  = k.monto_real_fmt;
  el('ag-k-comp').innerHTML    = k.avg_cot_recibidas + '<span style="font-size:0.8rem;font-weight:400"> cot/proc.</span>';
}

function renderAgEvolucion(data){
  const anios = data.map(r=>r.anio);
  const maxAnioAg=Math.max(...anios);
  const traces=[
    {type:'bar',name:'Total cot.',x:anios,y:data.map(r=>+r.total),marker:{color:'#94A3B8'},opacity:0.75,
     text:data.map(r=>r.total),textposition:'outside',cliponaxis:false,constraintext:'none',
     hovertemplate:'<b>%{x}</b><br>Total: %{y}<extra></extra>'},
    {type:'bar',name:'OC Emitidas',x:anios,y:data.map(r=>+r.oc_emitidas),marker:{color:'#15803D'},
     text:data.map(r=>r.oc_emitidas),textposition:'outside',cliponaxis:false,constraintext:'none',
     hovertemplate:'<b>%{x}</b><br>OC Emitidas: %{y}<extra></extra>'},
    {type:'bar',name:'Desiertas',x:anios,y:data.map(r=>+r.desiertas),marker:{color:'#B91C1C'},
     hovertemplate:'<b>%{x}</b><br>Desiertas: %{y}<extra></extra>'},
    {type:'bar',name:'Canceladas',x:anios,y:data.map(r=>+r.canceladas),marker:{color:'#6B7280'},
     hovertemplate:'<b>%{x}</b><br>Canceladas: %{y}<extra></extra>'},
    {type:'scatter',name:'Tasa adj.%',x:anios,y:data.map(r=>+r.tasa_adj),mode:'lines+markers+text',
     yaxis:'y2',line:{color:'#D97706',width:2.5,dash:'dash'},marker:{size:8,color:'#D97706'},
     text:data.map(r=>r.tasa_adj+'%'),textposition:'top center',textfont:{color:'#D97706',size:11},
     hovertemplate:'<b>%{x}</b><br>Tasa: %{y}%<extra></extra>'}
  ];
  const layout={...LB,barmode:'group',bargap:0.18,bargroupgap:0.06,
    xaxis:{tickmode:'array',tickvals:anios,
      ticktext:anios.map(a=>a===maxAnioAg?a+'*':String(a))},
    yaxis:{title:'Cotizaciones',gridcolor:'#F1F5F9'},
    yaxis2:{title:'Tasa adj. %',overlaying:'y',side:'right',range:[0,110],gridcolor:'rgba(0,0,0,0)'},
    legend:{orientation:'h',y:-0.18},margin:{t:36,r:60,b:80,l:50},
    annotations:[{text:'* '+maxAnioAg+' = año en curso, datos parciales a la fecha de corte',
      showarrow:false,x:0,y:-0.22,xref:'paper',yref:'paper',
      font:{size:9,color:'#6B7280'},xanchor:'left'}]};
  Plotly.newPlot('chart-ag-evolucion',traces,layout,CFG);
}

// ── Comparativa mensual interanual — Heatmap ──────────────────
let _agMensualData = [];
const MESES_ES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

// Paleta por métrica: de vacío → intensidad máxima
const AG_HM_SCALE = {
  total:      [[0,'#EFF6FF'],[0.25,'#93C5FD'],[0.6,'#2563EB'],[1,'#1E3A8A']],
  oc_emitidas:[[0,'#F0FDF4'],[0.25,'#86EFAC'],[0.6,'#16A34A'],[1,'#14532D']],
  desiertas:  [[0,'#FFF7ED'],[0.25,'#FCA5A5'],[0.6,'#DC2626'],[1,'#7F1D1D']],
  monto_ppto: [[0,'#FAF5FF'],[0.25,'#C4B5FD'],[0.6,'#7C3AED'],[1,'#3B0764']],
};
const AG_HM_LABEL = {
  total:'Total cotizaciones', oc_emitidas:'OCs emitidas',
  desiertas:'Desiertas',      monto_ppto:'Monto presupuestado (CLP)'
};

function renderAgMensual(data, metric){
  _agMensualData = data;
  const anios   = [...new Set(data.map(r=>+r.anio))].sort();
  const maxAnio = Math.max(...anios);
  const isMonto = metric === 'monto_ppto';

  // Construir matriz z[fila=anio][col=mes]
  const zMatrix = anios.map(anio =>
    MESES_ES.map((_,i) => {
      const row = data.find(r=>+r.anio===anio && +r.mes===i+1);
      return (row && row[metric] != null) ? +row[metric] : null;
    })
  );

  // Etiquetas de texto en cada celda
  const allVals = zMatrix.flat().filter(v=>v!=null);
  const maxVal  = Math.max(...allVals) || 1;
  const annotations = [];
  anios.forEach((anio, yi) => {
    MESES_ES.forEach((_, xi) => {
      const v = zMatrix[yi][xi];
      if (v === null) return;
      const ratio = v / maxVal;
      const txtColor = ratio > 0.55 ? '#FFFFFF' : '#1E293B';
      const label = isMonto
        ? (v >= 1e6 ? '$'+(v/1e6).toFixed(1)+'M' : '$'+(v/1e3).toFixed(0)+'K')
        : String(v);
      annotations.push({
        x: MESES_ES[xi], y: String(anio),
        text: label, showarrow:false,
        font:{ size:11, color:txtColor, family:'Roboto Mono,monospace' }
      });
    });
    // Marcar año en curso con sufijo *
    if (anio === maxAnio) {
      annotations.push({
        x: -0.5, y: String(anio),
        text:'*', showarrow:false, xref:'x', yref:'y',
        font:{ size:10, color:'#D97706' }
      });
    }
  });

  const yLabels = anios.map(a => a === maxAnio ? a+'*' : String(a));

  const trace = {
    type:'heatmap',
    x: MESES_ES,
    y: yLabels,
    z: zMatrix,
    colorscale: AG_HM_SCALE[metric] || AG_HM_SCALE.total,
    showscale: true,
    colorbar:{ thickness:14, len:0.9, tickformat: isMonto ? '$,.0f' : ',d',
               tickfont:{size:9}, outlinewidth:0 },
    xgap:4, ygap:4,
    hoverongaps:false,
    hovertemplate:'<b>%{y} — %{x}</b><br>'+AG_HM_LABEL[metric]+': %{z:,.0f}<extra></extra>',
    zauto:true,
  };

  const layout = {
    ...LB,
    xaxis:{ side:'bottom', tickfont:{size:12,family:'Inter,sans-serif'},
            fixedrange:true },
    yaxis:{ autorange:'reversed', tickfont:{size:12,family:'Inter,sans-serif'},
            fixedrange:true },
    annotations,
    margin:{ t:16, r:80, b:40, l:52 },
    plot_bgcolor:'#F8FAFC',
    paper_bgcolor:'#ffffff',
  };

  Plotly.react('chart-ag-mensual', [trace], layout,
    {...CFG, displayModeBar:false});
}

function agCambiarMetrica(btn, metric){
  document.querySelectorAll('.ag-mes-btn').forEach(b=>b.classList.remove('ag-mes-btn-act'));
  btn.classList.add('ag-mes-btn-act');
  renderAgMensual(_agMensualData, metric);
}

function renderAgDistEstado(data){
  const COLORES={'OC Emitida':'#15803D','Cancelada':'#B91C1C','Desierta':'#F59E0B',
                 'Guardada':'#94A3B8','Cerrada':'#6B7280','Publicada':'#0891B2'};
  const trace={type:'pie',labels:data.map(r=>r.estado),values:data.map(r=>+r.n),
    hole:0.45,marker:{colors:data.map(r=>COLORES[r.estado]||'#ccc')},
    textinfo:'percent+label',textfont:{size:11},
    hovertemplate:'<b>%{label}</b><br>%{value} cotizaciones<br>%{percent}<extra></extra>'};
  const layout={...LB,showlegend:true,legend:{orientation:'h',y:-0.12},margin:{t:20,b:50,l:10,r:10}};
  Plotly.newPlot('chart-ag-estado',[trace],layout,CFG);
}

function renderAgConvocatoria(data){
  const anios=[...new Set(data.map(r=>+r.anio))].sort();
  const llamados=[...new Set(data.map(r=>r.estado_convocatoria))].filter(Boolean);
  const CLR={'Primer llamado':'#0F69C4','Segundo llamado':'#B45309'};
  const traces=llamados.map(ll=>{
    const vals=anios.map(a=>{
      const row=data.find(r=>+r.anio===a&&r.estado_convocatoria===ll);
      return row?+row.n:0;
    });
    return {type:'bar',name:ll,x:anios,y:vals,marker:{color:CLR[ll]||'#94A3B8'},
      text:vals.map(v=>v||''),textposition:'inside',textfont:{color:'#fff',size:10},
      hovertemplate:`<b>${ll}</b> %{x}<br>%{y} cotizaciones<extra></extra>`};
  });
  const layout={...LB,barmode:'stack',xaxis:{dtick:1,tickformat:'d'},
    yaxis:{gridcolor:'#F1F5F9'},legend:{orientation:'h',y:-0.18},margin:{t:20,b:60,l:40,r:10}};
  Plotly.newPlot('chart-ag-convocatoria',traces,layout,CFG);
}

function renderAgMonto(data){
  const anios=data.map(r=>r.anio);
  const traces=[
    {type:'bar',name:'Presupuesto (monto disponible)',x:anios,y:data.map(r=>+r.monto_ppto),
     marker:{color:'#BFDBFE'},text:data.map(r=>fmt(r.monto_ppto)),
     textposition:'outside',cliponaxis:false,constraintext:'none',textfont:{size:9},
     hovertemplate:'<b>%{x}</b><br>Presupuesto: $%{y:,.0f}<extra></extra>'},
    {type:'bar',name:'Ejecutado real (oc_resumen)',x:anios,y:data.map(r=>+r.monto_real),
     marker:{color:'#1D4ED8'},text:data.map(r=>fmt(r.monto_real)),
     textposition:'outside',cliponaxis:false,constraintext:'none',textfont:{size:9,color:'#1D4ED8'},
     hovertemplate:'<b>%{x}</b><br>Ejecutado: $%{y:,.0f}<extra></extra>'},
    {type:'scatter',name:'Ahorro %',x:anios,y:data.map(r=>+r.ahorro),mode:'lines+markers',
     yaxis:'y2',line:{color:'#15803D',width:2.5},marker:{size:8,color:'#15803D'},
     text:data.map(r=>r.ahorro+'%'),textposition:'top center',textfont:{color:'#15803D',size:11},
     hovertemplate:'<b>%{x}</b><br>Ahorro: %{y}%<extra></extra>'}
  ];
  const layout={...LB,barmode:'group',bargap:0.2,
    xaxis:{dtick:1,tickformat:'d'},
    yaxis:{title:'CLP',gridcolor:'#F1F5F9',tickformat:'$,.0f'},
    yaxis2:{title:'Ahorro %',overlaying:'y',side:'right',range:[0,120],gridcolor:'rgba(0,0,0,0)'},
    legend:{orientation:'h',y:-0.2},margin:{t:36,r:60,b:80,l:80}};
  Plotly.newPlot('chart-ag-monto',traces,layout,CFG);
}

function renderAgCompetencia(data){
  const anios=data.map(r=>+r.anio);
  const traces=[
    {type:'bar',name:'Sin oferta',x:anios,y:data.map(r=>+r.sin_oferta),
     marker:{color:'#FCA5A5'},hovertemplate:'<b>%{x}</b><br>Sin oferta: %{y}<extra></extra>'},
    {type:'bar',name:'1 oferta',x:anios,y:data.map(r=>+r.una_oferta),
     marker:{color:'#FCD34D'},hovertemplate:'<b>%{x}</b><br>1 oferta: %{y}<extra></extra>'},
    {type:'bar',name:'2+ ofertas',x:anios,y:data.map(r=>+r.multi_oferta),
     marker:{color:'#86EFAC'},hovertemplate:'<b>%{x}</b><br>2+ ofertas: %{y}<extra></extra>'},
    {type:'scatter',name:'Promedio cot.',x:anios,y:data.map(r=>+r.avg_cotizaciones),
     mode:'lines+markers+text',yaxis:'y2',
     line:{color:'#0F69C4',width:2.5},marker:{size:8,color:'#0F69C4'},
     text:data.map(r=>r.avg_cotizaciones),textposition:'top center',textfont:{color:'#0F69C4',size:11},
     hovertemplate:'<b>%{x}</b><br>Prom. cot.: %{y}<extra></extra>'}
  ];
  const layout={...LB,barmode:'stack',xaxis:{dtick:1,tickformat:'d'},
    yaxis:{title:'N° de OCs emitidas',gridcolor:'#F1F5F9'},
    yaxis2:{title:'Promedio cotizaciones',overlaying:'y',side:'right',gridcolor:'rgba(0,0,0,0)'},
    legend:{orientation:'h',y:-0.22},margin:{t:30,r:60,b:80,l:50}};
  Plotly.newPlot('chart-ag-competencia',traces,layout,CFG);
}

function renderAgTopProv(data){
  if(!data||!data.length){document.getElementById('chart-ag-prov').innerHTML='<p style="padding:20px;color:#6C757D">Sin datos de proveedores en oc_resumen.</p>';return;}
  const provs = data.map(r=>r.proveedor).reverse();
  const montos= data.map(r=>+r.monto).reverse();
  const ocs   = data.map(r=>+r.ocs).reverse();
  const max   = Math.max(...montos)||1;
  const colors= montos.map(v=>{
    const t=v/max; const r=Math.round(15+(1-t)*220); const g=Math.round(105+t*20); const b=Math.round(196+t*10);
    return `rgb(${r},${g},${b})`;
  });
  const trace={type:'bar',orientation:'h',x:montos,y:provs,
    marker:{color:colors},
    text:montos.map(v=>fmt(v)),textposition:'outside',cliponaxis:false,constraintext:'none',
    customdata:ocs,
    hovertemplate:'<b>%{y}</b><br>Monto: $%{x:,.0f}<br>OCs: %{customdata}<extra></extra>'};
  const layout={...LB,xaxis:{tickformat:'$,.0f',gridcolor:'#F1F5F9'},
    yaxis:{automargin:true,tickfont:{size:10}},margin:{t:10,b:40,l:200,r:100}};
  Plotly.newPlot('chart-ag-prov',[trace],layout,CFG);
}

function renderAgTopItems(data){
  const items = data.map(r=>{
    const n=r.item||'';
    return n.length>50?n.substring(0,50)+'…':n;
  }).reverse();
  const veces = data.map(r=>+r.veces).reverse();
  const trace={type:'bar',orientation:'h',x:veces,y:items,
    marker:{color:'#0891B2'},
    text:veces,textposition:'outside',cliponaxis:false,constraintext:'none',
    hovertemplate:'<b>%{y}</b><br>Veces: %{x}<extra></extra>'};
  const layout={...LB,xaxis:{gridcolor:'#F1F5F9',dtick:1},
    yaxis:{automargin:true,tickfont:{size:9}},margin:{t:10,b:40,l:240,r:50}};
  Plotly.newPlot('chart-ag-items',[trace],layout,CFG);
}

function renderAgEstadoOC(data){
  const anios=[...new Set(data.map(r=>+r.anio))].sort();
  const estados=[...new Set(data.map(r=>r.estado_oc))].filter(Boolean);
  const CLR={'Recepción Conforme':'#15803D','Eliminada':'#B91C1C','Cancelada':'#D97706',
             'Aceptada':'#0891B2','No aceptado':'#6B7280','Guardada':'#94A3B8',
             'Enviada a Proveedor':'#7C3AED','Cancelación Solicitada':'#BE185D'};
  const traces=estados.map(e=>{
    const vals=anios.map(a=>{
      const row=data.find(r=>+r.anio===a&&r.estado_oc===e);
      return row?+row.n:0;
    });
    return {type:'bar',name:e,x:anios,y:vals,marker:{color:CLR[e]||'#94A3B8'},
      text:vals.map(v=>v||''),textposition:'inside',textfont:{color:'#fff',size:10},
      hovertemplate:`<b>${e}</b> %{x}<br>%{y} OCs<extra></extra>`};
  });
  const layout={...LB,barmode:'stack',xaxis:{dtick:1,tickformat:'d'},
    yaxis:{gridcolor:'#F1F5F9'},legend:{orientation:'h',y:-0.22},margin:{t:20,b:80,l:40,r:10}};
  Plotly.newPlot('chart-ag-estado-oc',traces,layout,CFG);
}

async function agEjecutarETL(){
  const btn=document.getElementById('ag-etl-btn');
  const msg=document.getElementById('ag-etl-msg');
  btn.disabled=true; btn.textContent='⏳ Importando...';
  msg.textContent='Procesando archivos Excel...';
  try {
    const d=await fetch('api/etl_compraagil.php?reset=1').then(r=>r.json());
    if(d.ok){
      msg.innerHTML='<span style="color:#15803D">✔ '+d.salida.split('\n').pop()+'</span>';
      agCargado=false;
      cargarCompraAgil();
    } else {
      msg.innerHTML='<span style="color:#B91C1C">⚠ Error (código '+d.codigo+')</span>';
      console.error(d.salida);
    }
  } catch(e){
    msg.innerHTML='<span style="color:#B91C1C">⚠ Error de red</span>';
  }
  btn.disabled=false; btn.textContent='🔄 Reimportar';
}

// ═══════════════════════════════════════
// TRAZABILIDAD
// ═══════════════════════════════════════
let trazCargado=false;
const ESTADO_COLOR={'Recepción Completa':'#15803D','En Trámite':'#D97706',
  'Recepción Parcial':'#0891B2','Anulada':'#6B7280','Cerrada':'#374151'};
const ESTADO_CSS={'Recepción Completa':'te-completa','En Trámite':'te-tramite',
  'Recepción Parcial':'te-parcial','Anulada':'te-anulada','Cerrada':'te-cerrada'};
const TIPO_NOMBRE={AG:'Compra Ágil',CM:'Conv. Marco',LE:'Licitación ≤1000',
  LP:'Licitación Pública',LQ:'Licitación 2',LR:'Licitación 3',TD:'Trato Directo',
  L1:'Licitación L1',R1:'Resolución',CC:'Convenio CC',Cenabast:'Cenabast',
  'Resolución Interna':'Resolución Int.'};

async function cargarTrazabilidad(){
  if(trazCargado) return;
  try {
    const d=await fetch(API_URL+'?action=trazabilidad').then(r=>r.json());
    renderTrazKPIs(d.kpis);
    renderTrazEstado(d.distEstado);
    renderTrazTipo(d.distTipo);
    renderTrazAlertas(d.alertas);
    renderTrazBodega(d.distBodega);
    renderTrazEvolucion(d.evolucion);
    trazCargado=true;
  } catch(err){ console.error('Trazabilidad error:',err); }
}

function renderTrazKPIs(k){
  const el=id=>document.getElementById(id);
  el('traz-k-total').textContent    = k.total.toLocaleString('es-CL');
  el('traz-k-completa').textContent = k.completa.toLocaleString('es-CL');
  el('traz-k-pct-completa').innerHTML =
    `<span class="traz-pct-pill traz-pct-ok">✔ ${k.pct_completa}% del total</span>`;
  el('traz-k-tramite').textContent  = k.en_tramite.toLocaleString('es-CL');
  el('traz-k-pct-tramite').innerHTML=
    `<span class="traz-pct-pill traz-pct-danger">⚠ ${k.pct_tramite}% pendiente</span>`;
  el('traz-k-parcial').textContent  = k.parcial.toLocaleString('es-CL');
  el('traz-k-monto-tramite').textContent = k.monto_tramite_fmt;
  el('traz-k-link').textContent     = k.ocs_trazables.toLocaleString('es-CL');
}

function renderTrazEstado(data){
  const trace={type:'pie',
    labels:data.map(r=>r.estado),values:data.map(r=>+r.n),
    marker:{colors:data.map(r=>ESTADO_COLOR[r.estado]||'#ccc')},
    hole:0.45,textinfo:'percent+label',textfont:{size:11},
    hovertemplate:'<b>%{label}</b><br>%{value} registros<br>%{percent}<extra></extra>'};
  const layout={...LB,showlegend:false,margin:{t:16,b:16,l:10,r:10}};
  Plotly.newPlot('chart-traz-estado',[trace],layout,CFG);
}

function renderTrazTipo(data){
  // Agrupar por tipo_oc → estado → n
  const tipos=[...new Set(data.map(r=>r.tipo_oc))];
  const estados=['Recepción Completa','En Trámite','Recepción Parcial','Anulada','Cerrada'];
  // Ordenar por total desc
  const totales={};
  data.forEach(r=>{ totales[r.tipo_oc]=(totales[r.tipo_oc]||0)+(+r.n); });
  const tiposOrd=tipos.sort((a,b)=>(totales[b]||0)-(totales[a]||0));

  const traces=estados.map(e=>({
    type:'bar',name:e,orientation:'h',
    x:tiposOrd.map(t=>{const row=data.find(r=>r.tipo_oc===t&&r.estado===e);return row?+row.n:0;}),
    y:tiposOrd.map(t=>TIPO_NOMBRE[t]||t),
    marker:{color:ESTADO_COLOR[e]||'#ccc'},
    hovertemplate:`<b>${e}</b><br>%{y}: %{x}<extra></extra>`
  }));

  const layout={...LB,barmode:'stack',
    xaxis:{gridcolor:'#F1F5F9'},yaxis:{automargin:true,tickfont:{size:10}},
    legend:{orientation:'h',y:-0.22},margin:{t:16,b:70,l:140,r:10}};
  Plotly.newPlot('chart-traz-tipo',traces,layout,CFG);
}

function renderTrazAlertas(data){
  // Solo mostrar tipos con en_tramite > 0, ordenados por en_tramite desc
  const filtered=data.filter(r=>+r.en_tramite>0);
  const labels=filtered.map(r=>TIPO_NOMBRE[r.tipo_oc]||r.tipo_oc).reverse();
  const completa=filtered.map(r=>+r.completa).reverse();
  const tramite=filtered.map(r=>+r.en_tramite).reverse();
  const parcial=filtered.map(r=>+r.parcial).reverse();

  const traces=[
    {type:'bar',name:'Recepción Completa',orientation:'h',x:completa,y:labels,
     marker:{color:'#15803D'},hovertemplate:'<b>Completa</b> %{y}: %{x}<extra></extra>'},
    {type:'bar',name:'En Trámite',orientation:'h',x:tramite,y:labels,
     marker:{color:'#D97706'},
     text:tramite.map(v=>v>0?v:''),textposition:'inside',textfont:{color:'#fff',size:10},
     hovertemplate:'<b>⚠ En Trámite</b> %{y}: %{x}<extra></extra>'},
    {type:'bar',name:'Recepción Parcial',orientation:'h',x:parcial,y:labels,
     marker:{color:'#0891B2'},hovertemplate:'<b>Parcial</b> %{y}: %{x}<extra></extra>'}
  ];
  const layout={...LB,barmode:'stack',
    xaxis:{gridcolor:'#F1F5F9'},yaxis:{automargin:true,tickfont:{size:11}},
    legend:{orientation:'h',y:-0.14},margin:{t:16,b:60,l:140,r:10}};
  Plotly.newPlot('chart-traz-alertas',traces,layout,CFG);
}

function renderTrazBodega(data){
  // Agrupar por bodega → monto total, luego estado
  const bodegas=[...new Set(data.map(r=>r.bodega))];
  const montoTot={};
  data.forEach(r=>{ montoTot[r.bodega]=(montoTot[r.bodega]||0)+(+r.monto); });
  const bodOrd=bodegas.sort((a,b)=>(montoTot[b]||0)-(montoTot[a]||0));
  const estados=['Recepción Completa','En Trámite','Recepción Parcial','Anulada','Cerrada'];

  const traces=estados.map(e=>({
    type:'bar',name:e,orientation:'h',
    x:bodOrd.map(b=>{const row=data.find(r=>r.bodega===b&&r.estado===e);return row?+row.monto:0;}),
    y:bodOrd.map(b=>b.replace(/^\s+/,'')).reverse().map(b=>b.length>28?b.substring(0,28)+'…':b),
    marker:{color:ESTADO_COLOR[e]||'#ccc'},
    hovertemplate:`<b>${e}</b><br>%{y}<br>$%{x:,.0f}<extra></extra>`
  })).map((t,i)=>({...t,y:[...t.y]}));

  // Reverse y arrays properly
  const revBod=bodOrd.slice().reverse();
  const tracesR=estados.map(e=>({
    type:'bar',name:e,orientation:'h',
    x:revBod.map(b=>{const row=data.find(r=>r.bodega===b&&r.estado===e);return row?+row.monto:0;}),
    y:revBod.map(b=>b.replace(/^\s+/,'').replace(/Bodega\s*/g,'').trim()).map(b=>b.length>32?b.substring(0,32)+'…':b),
    marker:{color:ESTADO_COLOR[e]||'#ccc'},
    hovertemplate:`<b>${e}</b><br>%{y}<br>$%{x:,.0f}<extra></extra>`
  }));

  const layout={...LB,barmode:'stack',
    xaxis:{tickformat:'$,.0f',gridcolor:'#F1F5F9'},
    yaxis:{automargin:true,tickfont:{size:10}},
    legend:{orientation:'h',y:-0.18},margin:{t:16,b:70,l:210,r:40}};
  Plotly.newPlot('chart-traz-bodega',tracesR,layout,CFG);
}

function renderTrazEvolucion(data){
  // Build month labels YYYY-MM and group
  const meses=[...new Set(data.map(r=>`${r.anio}-${String(r.mes).padStart(2,'0')}`))].sort();
  const estados=['Recepción Completa','En Trámite','Recepción Parcial','Anulada','Cerrada'];
  const map={};
  data.forEach(r=>{ const k=`${r.anio}-${String(r.mes).padStart(2,'0')}`; map[`${k}|${r.estado}`]=(+r.n||0); });
  const MESES_ES=['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  const labels=meses.map(m=>{const[y,mo]=m.split('-');return `${MESES_ES[+mo]} ${y}`;});
  const traces=estados.map(e=>({
    type:'bar',name:e,x:labels,
    y:meses.map(m=>map[`${m}|${e}`]||0),
    marker:{color:ESTADO_COLOR[e]||'#ccc'},
    hovertemplate:`<b>${e}</b><br>%{x}: %{y}<extra></extra>`
  }));
  const layout={...LB,barmode:'stack',
    xaxis:{gridcolor:'#F1F5F9',tickangle:-45},
    yaxis:{gridcolor:'#F1F5F9'},
    legend:{orientation:'h',y:-0.3},margin:{t:16,b:100,l:40,r:10}};
  Plotly.newPlot('chart-traz-evolucion',traces,layout,CFG);
}

// ── Búsqueda ──────────────────────────────────────────
async function trazBuscar(){
  const q=document.getElementById('traz-q').value.trim();
  if(q.length<2){alert('Ingresa al menos 2 caracteres');return;}
  const res=document.getElementById('traz-results');
  res.innerHTML='<p style="color:#6C757D;font-size:0.8rem">Buscando...</p>';
  try {
    const data=await fetch(`${API_URL}?action=trazabilidad_buscar&q=${encodeURIComponent(q)}`).then(r=>r.json());
    if(!data.length){res.innerHTML='<p style="color:#6C757D;font-size:0.8rem;margin-top:8px">Sin resultados para "<b>'+q+'</b>".</p>';return;}
    res.innerHTML=`<p style="font-size:0.72rem;color:#6C757D;margin-bottom:8px">${data.length} resultado(s) para "<b>${q}</b>"</p>`
      +data.map(r=>trazCardHTML(r)).join('');
  } catch(e){
    res.innerHTML='<p style="color:#B91C1C">Error en búsqueda</p>';
    console.error(e);
  }
}

function trazLimpiar(){
  document.getElementById('traz-q').value='';
  document.getElementById('traz-results').innerHTML='';
}

function trazCardHTML(r){
  const estadoCSS=ESTADO_CSS[r.estado]||'te-none';
  const prec=r.precio_total_fmt||'—';
  const mpBadge=r.en_mp
    ?`<span class="traz-badge-estado te-ok">🔗 En MP</span>`
    :`<span class="traz-badge-estado te-none">Sin match MP</span>`;

  const kimunFields=[
    ['Estado',`<span class="traz-badge-estado ${estadoCSS}">${r.estado||'—'}</span>`],
    ['Proveedor',r.proveedor||'—'],
    ['Bodega',r.bodega||'—'],
    ['Tipo OC',r.tipo_oc||'—'],
    ['Precio Kimun',prec],
    ['Fecha Ingreso',r.fecha_ingreso||'—'],
  ];
  const mpFields=r.en_mp?[
    ['Estado MP',`<span class="traz-badge-estado ${r.mp_estado==='Recepción Conforme'?'te-completa':'te-warn'}">${r.mp_estado||'—'}</span>`],
    ['Proveedor MP',r.mp_proveedor||'—'],
    ['Unidad',r.mp_unidad||'—'],
    ['Modalidad',r.mp_origen||'—'],
    ['Monto MP',r.mp_monto_fmt||'—'],
  ]:null;

  return `<div class="traz-oc-card">
    <div class="traz-oc-header">
      <span class="traz-oc-id">${r.codigo_oc||'Sin OC'}</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span class="traz-badge-estado ${estadoCSS}">${r.estado||'—'}</span>
        ${mpBadge}
        ${r.tipo_oc?`<span class="gob-badge badge-primary">${r.tipo_oc}</span>`:''}
      </div>
    </div>
    <div class="traz-oc-body">
      <div class="traz-oc-col col-kimun">
        <div class="traz-oc-col-title">Kimun (Bodega)</div>
        ${kimunFields.map(([l,v])=>`<div class="traz-field"><span class="traz-field-label">${l}</span><span class="traz-field-value">${v}</span></div>`).join('')}
      </div>
      <div class="traz-oc-col col-mp">
        <div class="traz-oc-col-title">Mercado Público</div>
        ${r.en_mp
          ? mpFields.map(([l,v])=>`<div class="traz-field"><span class="traz-field-label">${l}</span><span class="traz-field-value">${v}</span></div>`).join('')
          : '<div class="traz-no-mp">OC no encontrada en oc_resumen.<br>Puede ser de otra unidad o período no cargado.</div>'
        }
      </div>
    </div>
  </div>`;
}

document.getElementById('traz-q')?.addEventListener('keydown',e=>{if(e.key==='Enter')trazBuscar();});

async function trazEjecutarETL(){
  const btn=document.getElementById('traz-etl-btn');
  const msg=document.getElementById('traz-etl-msg');
  btn.disabled=true; btn.textContent='⏳ Importando...';
  msg.textContent='Procesando bdbodega.xlsx...';
  try {
    const d=await fetch('api/etl_bdbodega.php?reset=1').then(r=>r.json());
    if(d.ok){
      msg.innerHTML='<span style="color:#15803D">✔ '+d.salida+'</span>';
      trazCargado=false;
      cargarTrazabilidad();
    } else {
      msg.innerHTML='<span style="color:#B91C1C">⚠ Error (código '+d.codigo+')</span>';
      console.error(d.salida);
    }
  } catch(e){
    msg.innerHTML='<span style="color:#B91C1C">⚠ Error de red</span>';
  }
  btn.disabled=false; btn.textContent='🔄 Reimportar';
}

// ═══════════════════════════════════════════════════════
// LIBERACIÓN OC
// ═══════════════════════════════════════════════════════
let libCargado = false;
let libDatos   = { kpis: null, porAnio: [], porUnidad: [] };

const LIB_COLOR = {
  'En proceso'          : '#D97706',
  'Enviada a proveedor' : '#0891B2',
  'Cancelada'           : '#B91C1C',
};
const LIB_BADGE = {
  'En proceso'          : 'lib-b-proceso',
  'Enviada a proveedor' : 'lib-b-enviada',
  'Cancelada'           : 'lib-b-cancelada',
};
const LIB_ACCION = {
  'En proceso'          : ['lib-ac-naranja', '⚡ Verificar avance'],
  'Enviada a proveedor' : ['lib-ac-azul',    '📬 Confirmar recepción'],
  'Cancelada'           : ['lib-ac-rojo',    '🔓 Liberar presupuesto'],
};

async function cargarLiberacion() {
  if (libCargado) return;
  try {
    const d = await fetch(API_URL + '?action=liberacion').then(r => r.json());
    libDatos = d;
    renderLibKPIs(d.kpis);
    renderLibAnio(d.porAnio);
    renderLibUnidad(d.porUnidad);
    libCargado = true;
    // Carga el detalle inicial (todos los estados)
    libBuscar();
  } catch(err) { console.error('Liberación error:', err); }
}

function renderLibKPIs(k) {
  const g = id => document.getElementById(id);
  const byE = k.byEstado || {};
  g('lib-k-total').textContent        = (k.totalN || 0).toLocaleString('es-CL');
  g('lib-k-proceso').textContent      = (byE['En proceso']?.n || 0).toLocaleString('es-CL');
  g('lib-k-proceso-fmt').textContent  = byE['En proceso']?.fmt  || '$ 0';
  g('lib-k-enviada').textContent      = (byE['Enviada a proveedor']?.n || 0).toLocaleString('es-CL');
  g('lib-k-enviada-fmt').textContent  = byE['Enviada a proveedor']?.fmt || '$ 0';
  g('lib-k-cancelada').textContent    = (byE['Cancelada']?.n || 0).toLocaleString('es-CL');
  g('lib-k-cancelada-fmt').textContent= byE['Cancelada']?.fmt   || '$ 0';
  g('lib-k-monto').textContent        = k.totalFmt || '$ 0';
}

function renderLibAnio(data) {
  if (!data || !data.length) return;
  const anios    = [...new Set(data.map(r => r.anio))].sort();
  const estados  = ['En proceso','Enviada a proveedor','Cancelada'];
  const traces   = estados.map(e => ({
    type: 'bar', name: e,
    x: anios,
    y: anios.map(a => { const row = data.find(r => r.anio === a && r.estado_oc === e); return row ? +row.n : 0; }),
    marker: { color: LIB_COLOR[e] || '#888', line: { color: '#fff', width: 1 } },
    hovertemplate: `<b>${e}</b><br>Año %{x}: %{y} OCs<extra></extra>`,
  }));
  Plotly.newPlot('chart-lib-anio', traces, {
    ...LB, barmode: 'stack',
    margin: { t: 16, b: 40, l: 40, r: 10 },
    xaxis: { tickmode: 'array', tickvals: anios, ticktext: anios.map(String), showgrid: false },
    yaxis: { gridcolor: '#F1F5F9' },
    legend: { orientation: 'h', y: -0.18 },
  }, CFG);
}

function renderLibUnidad(data) {
  if (!data || !data.length) return;
  // Agrupar por unidad → sumar monto total
  const montoUnidad = {};
  data.forEach(r => { montoUnidad[r.unidad] = (montoUnidad[r.unidad] || 0) + parseFloat(r.monto || 0); });
  // Top 10 por monto
  const top10 = Object.entries(montoUnidad)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10)
    .map(e => e[0]);

  const estados = ['En proceso','Enviada a proveedor','Cancelada'];
  const traces  = estados.map(e => ({
    type: 'bar', name: e, orientation: 'h',
    x: top10.map(u => { const row = data.find(r => r.unidad === u && r.estado_oc === e); return row ? parseFloat(row.monto || 0) / 1e6 : 0; }),
    y: top10.map(u => u && u.length > 30 ? u.substring(0, 30) + '…' : (u || '—')),
    marker: { color: LIB_COLOR[e] || '#888' },
    hovertemplate: `<b>${e}</b><br>%{y}<br>$%{x:.1f}M<extra></extra>`,
  }));
  Plotly.newPlot('chart-lib-unidad', traces, {
    ...LB, barmode: 'stack',
    margin: { t: 16, b: 40, l: 180, r: 40 },
    xaxis: { ticksuffix: 'M', gridcolor: '#F1F5F9' },
    yaxis: { automargin: true, tickfont: { size: 10 } },
    legend: { orientation: 'h', y: -0.18 },
  }, CFG);
}

async function libBuscar() {
  const estado   = document.getElementById('lib-filtro-estado')?.value    || '';
  const busqueda = document.getElementById('lib-filtro-busqueda')?.value?.trim() || '';
  const tbody    = document.getElementById('lib-tabla-body');
  const info     = document.getElementById('lib-tabla-info');
  if (tbody) tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:#6C757D;padding:20px">Cargando...</td></tr>';

  try {
    const params = new URLSearchParams({ action: 'liberacion_detalle' });
    if (estado)   params.set('estado',   estado);
    if (busqueda) params.set('busqueda', busqueda);
    const d = await fetch(API_URL + '?' + params.toString()).then(r => r.json());
    const rows = d.rows || [];

    if (info) info.textContent = rows.length
      ? `${rows.length} OC(s) encontrada(s)` + (rows.length >= 500 ? ' (límite 500 — use filtros para acotar)' : '')
      : 'Sin resultados.';

    if (!rows.length) {
      if (tbody) tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;color:#6C757D;padding:24px">Sin resultados para los filtros aplicados.</td></tr>';
      return;
    }

    const hoy = new Date(); hoy.setHours(0,0,0,0);

    if (tbody) tbody.innerHTML = rows.map(r => {
      const badgeClass  = LIB_BADGE[r.estado_oc]  || '';
      const [acClase, acLabel] = LIB_ACCION[r.estado_oc] || ['','—'];

      // ── Cálculo de tiempo transcurrido ──────────────────
      let diasNum = '—', antHtml = '—';
      if (r.fecha_envio && r.fecha_envio !== '—') {
        const fechaEnv = new Date(r.fecha_envio); fechaEnv.setHours(0,0,0,0);
        const diffMs   = hoy - fechaEnv;
        if (!isNaN(diffMs) && diffMs >= 0) {
          diasNum = Math.floor(diffMs / 86400000);
          // Años y meses completos para la etiqueta legible
          const anios = Math.floor(diasNum / 365);
          const meses = Math.floor((diasNum % 365) / 30);
          let label, cls;
          if (diasNum > 365) {
            label = anios > 0
              ? `${anios} año${anios>1?'s':''} ${meses>0?meses+' mes'+(meses>1?'es':''):''}`.trim()
              : `${meses} mes${meses>1?'es':''}`;
            cls = 'lib-ant-critico';
          } else if (diasNum > 180) {
            label = meses > 0 ? `${meses} mes${meses>1?'es':''}` : `${diasNum} días`;
            cls = 'lib-ant-alto';
          } else if (diasNum > 90) {
            label = meses > 0 ? `${meses} mes${meses>1?'es':''}` : `${diasNum} días`;
            cls = 'lib-ant-medio';
          } else {
            label = diasNum === 1 ? '1 día' : `${diasNum} días`;
            cls = 'lib-ant-reciente';
          }
          antHtml = `<span class="lib-ant-badge ${cls}">${label}</span>`;
        }
      }

      return `<tr>
        <td><span class="lib-badge ${badgeClass}">${r.estado_oc || '—'}</span></td>
        <td style="font-family:'Roboto Mono',monospace;font-size:0.78rem">${r.codigo_oc || '—'}</td>
        <td style="max-width:220px;white-space:normal;font-size:0.78rem">${(r.nombre_oc || '—').substring(0,80)}${r.nombre_oc && r.nombre_oc.length > 80 ? '…' : ''}</td>
        <td style="font-size:0.78rem">${(r.p_nombre || '—').substring(0,35)}${r.p_nombre && r.p_nombre.length > 35 ? '…' : ''}</td>
        <td style="font-size:0.75rem">${r.c_unidad || '—'}</td>
        <td style="font-size:0.75rem">${r.origen_compra || '—'}</td>
        <td style="text-align:center">${r.anio || '—'}</td>
        <td style="font-size:0.75rem;white-space:nowrap">${r.fecha_envio || '—'}</td>
        <td style="text-align:right;font-weight:700;font-size:0.82rem;color:#374151">${typeof diasNum==='number'?diasNum.toLocaleString('es-CL'):'—'}</td>
        <td style="white-space:nowrap">${antHtml}</td>
        <td style="text-align:right;font-weight:600;font-size:0.82rem">${r.total_fmt || '$ 0'}</td>
        <td><span class="lib-accion ${acClase}">${acLabel}</span></td>
      </tr>`;
    }).join('');
  } catch(e) {
    console.error('libBuscar error:', e);
    if (tbody) tbody.innerHTML = '<tr><td colspan="10" style="color:#B91C1C;padding:16px">Error al cargar datos.</td></tr>';
  }
}

function libLimpiar() {
  const sel = document.getElementById('lib-filtro-estado');
  const inp = document.getElementById('lib-filtro-busqueda');
  if (sel) sel.value = '';
  if (inp) inp.value = '';
  libBuscar();
}

// ══════════════════════════════════════════════════════════════
// TAB INF.EJEC — Indicadores de Ejecución y Riesgo
// ══════════════════════════════════════════════════════════════
let ejCargado = false;

function ejPoblarFiltros() {
  const selU = document.getElementById('ej-unidad');
  const selD = document.getElementById('ej-desde');
  const selH = document.getElementById('ej-hasta');
  if (!selU || selU.options.length > 1) return; // ya poblado

  // Unidades desde datos globales cargados en la tab Estadísticas
  const unidades = window._unidades || [];
  unidades.forEach(u => {
    const opt = document.createElement('option');
    opt.value = u; opt.textContent = u;
    selU.appendChild(opt);
  });

  // Años
  const anios = window._anios || [];
  const allAnios = anios.length ? anios : Array.from({length:8},(_,i)=>2018+i);
  allAnios.forEach(a => {
    const od = document.createElement('option'); od.value=a; od.textContent=a;
    const oh = document.createElement('option'); oh.value=a; oh.textContent=a;
    selD.appendChild(od); selH.appendChild(oh);
  });
  if (selD.options.length) selD.value = allAnios[0];
  if (selH.options.length) selH.value = allAnios[allAnios.length-1];
}

async function cargarInfEjec(force) {
  if (ejCargado && !force) return;
  ejPoblarFiltros();

  const unidad  = document.getElementById('ej-unidad')?.value  || '';
  const desde   = document.getElementById('ej-desde')?.value   || 2018;
  const hasta   = document.getElementById('ej-hasta')?.value   || new Date().getFullYear();

  setEstado('⏳ Cargando INF.EJEC...','#7C3AED');
  try {
    const p = new URLSearchParams({
      action:'infejec', anioDesde:desde, anioHasta:hasta,
      unidad, origen:'', proveedor:'', busqueda:''
    });
    const d = await fetch(API_URL+'?'+p).then(r=>r.json());
    renderEjSemaforo(d.semaforo   || {});
    renderEjConcentracion(d.concentracion  || {});
    renderEjTratoDirecto(d.tratoDirecto    || {});
    renderEjVariacionPrecios(d.variacionPrecios || []);
    renderEjSinCompra(d.sinCompra || []);
    ejCargado = true;
    setEstado('✓ INF.EJEC actualizado','#15803D');
  } catch(err) {
    console.error('cargarInfEjec error:', err);
    setEstado('⚠ Error cargando INF.EJEC','#B91C1C');
  }
}

function renderEjSemaforo(s) {
  const el = document.getElementById('ej-semaforo');
  if (!el) return;
  // Explicaciones estáticas por indicador
  const explicaciones = {
    'Concentración HHI':
      'Mide si el mercado de proveedores es competitivo o está controlado por pocos actores. ' +
      'HHI < 1.500 = competencia sana · 1.500–2.500 = moderado · > 2.500 = riesgo de dependencia.',
    'Trato Directo':
      'Porcentaje del gasto adjudicado sin proceso competitivo. ' +
      'Incluye TD formal (FTD, desde 2025) y OCs bajo SE que mencionan "trato directo" en la especificación (antes de 2025 no existía la FTD en Mercado Público). ' +
      'Umbral de alerta: > 15% amarillo · > 30% rojo.',
    'OCs Canceladas':
      'Proporción de órdenes de compra que no llegaron a término. ' +
      'Indica ineficiencias operativas, problemas con proveedores o errores en la planificación. ' +
      'Umbral: > 5% amarillo · > 15% rojo.',
    'Variación Precios':
      'Cantidad de ítems cuyo precio unitario promedio subió más del 20% respecto al año anterior. ' +
      'Señala posibles alzas abusivas, cambios de especificación o inflación sectorial. ' +
      'Umbral: > 20% de ítems amarillo · > 40% rojo.',
  };
  const items = [
    s.concentracion     || { nivel:'—', valor:'—', label:'Concentración HHI',   desc:'Sin datos' },
    s.trato_directo     || { nivel:'—', valor:'—', label:'Trato Directo',        desc:'Sin datos' },
    s.canceladas        || { nivel:'—', valor:'—', label:'OCs Canceladas',       desc:'Sin datos' },
    s.variacion_precios || { nivel:'—', valor:'—', label:'Variación Precios',    desc:'Sin datos' },
  ];
  const colores = { verde:'ej-sem-verde', amarillo:'ej-sem-amarillo', rojo:'ej-sem-rojo' };
  const iconos  = { verde:'🟢', amarillo:'🟡', rojo:'🔴' };
  el.innerHTML = items.map(item => {
    const cls  = colores[item.nivel] || '';
    const ico  = iconos[item.nivel]  || '⚪';
    const expl = explicaciones[item.label] || '';
    return `<div class="ej-semaforo-card ${cls}">
      <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#6C757D;margin-bottom:8px">${item.label}</div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
        <span style="font-size:1.5rem">${ico}</span>
        <span class="ej-sem-value" style="font-family:'Roboto Slab',serif;font-size:1.65rem;font-weight:700;color:#1E293B">${item.valor}</span>
      </div>
      <div style="font-size:0.74rem;font-weight:600;color:#374151;margin-bottom:6px">${item.desc}</div>
      <div style="font-size:0.68rem;color:#6C757D;line-height:1.5;border-top:1px solid #F1F5F9;padding-top:6px;margin-top:2px">${expl}</div>
      <div style="display:inline-block;margin-top:8px;padding:2px 8px;border-radius:999px;font-size:0.65rem;font-weight:700;
        background:${item.nivel==='verde'?'#DCFCE7':item.nivel==='amarillo'?'#FEF9C3':'#FEE2E2'};
        color:${item.nivel==='verde'?'#15803D':item.nivel==='amarillo'?'#92400E':'#991B1B'}">
        ${(item.nivel||'—').toUpperCase()}
      </div>
    </div>`;
  }).join('');
}

function renderEjConcentracion(c) {
  const kpisEl = document.getElementById('ej-conc-kpis');
  if (kpisEl) {
    const fmt = v => typeof v === 'number' ? v.toLocaleString('es-CL', {maximumFractionDigits:0}) : (v || '—');
    const pct  = v => typeof v === 'number' ? v.toFixed(1)+'%' : '—';
    const colHHI = c.nivel==='verde'?'#15803D':c.nivel==='amarillo'?'#D97706':'#B91C1C';
    kpisEl.innerHTML = [
      { v: fmt(c.hhi ? Math.round(c.hhi) : null), l: 'Índice HHI', color: colHHI,
        d: 'Herfindahl-Hirschman Index. Escala 0–10.000. Mide cuánto se concentra el gasto en pocos proveedores. Bajo = mercado competitivo.' },
      { v: pct(c.c1), l: 'Share mayor proveedor', color:'#374151',
        d: 'Porcentaje del gasto total que recibe el proveedor con mayor adjudicación. Un valor alto indica dependencia de un solo actor.' },
      { v: pct(c.c5), l: 'Top 5 acaparan', color:'#374151',
        d: 'Participación acumulada de los 5 proveedores más grandes. Si supera el 70% el mercado tiene baja competencia.' },
      { v: fmt(c.n_proveedores), l: 'N° proveedores activos', color:'#374151',
        d: 'Cantidad de proveedores distintos que recibieron al menos una OC en el período. Mayor diversidad = menor riesgo de dependencia.' },
    ].map(k=>`<div class="ej-conc-kpi">
      <div class="ej-conc-kpi-val" style="color:${k.color}">${k.v}</div>
      <div class="ej-conc-kpi-lbl">${k.l}</div>
      <div style="font-size:0.65rem;color:#94A3B8;margin-top:5px;line-height:1.45">${k.d}</div>
    </div>`).join('');
  }

  // Donut top5 + Otros
  const top10 = c.top10 || [];
  if (top10.length) {
    const totalMonto = top10.reduce((s,r)=>s+parseFloat(r.monto||0),0);
    const top5  = top10.slice(0,5);
    const otros = top10.slice(5).reduce((s,r)=>s+parseFloat(r.monto||0),0);
    const labels = [...top5.map(r=>r.proveedor && r.proveedor.length>30?r.proveedor.substring(0,30)+'…':r.proveedor), 'Otros'];
    const values = [...top5.map(r=>parseFloat(r.monto||0)), otros];
    Plotly.newPlot('chart-ej-conc-donut', [{
      type:'pie', labels, values,
      hole:0.48,
      marker:{ colors:['#6D28D9','#8B5CF6','#A78BFA','#C4B5FD','#DDD6FE','#CBD5E1'] },
      textinfo:'percent', hovertemplate:'<b>%{label}</b><br>$%{value:,.0f}<br>%{percent}<extra></extra>',
    }], { ...LB, margin:{t:20,b:20,l:10,r:10}, showlegend:true, legend:{font:{size:10}} }, CFG);

    // Horizontal bar top10
    const sorted = [...top10].sort((a,b)=>parseFloat(b.monto)-parseFloat(a.monto));
    const prov10  = sorted.map(r=>r.proveedor && r.proveedor.length>35?r.proveedor.substring(0,35)+'…':r.proveedor);
    const monto10 = sorted.map(r=>parseFloat(r.monto||0)/1e6);
    const pcts    = sorted.map(r=>((parseFloat(r.monto||0)/totalMonto)*100).toFixed(1)+'%');
    Plotly.newPlot('chart-ej-conc-bar', [{
      type:'bar', orientation:'h',
      y: prov10, x: monto10,
      text: pcts, textposition:'outside', cliponaxis:false,
      marker:{ color:'#7C3AED', opacity:0.85 },
      hovertemplate:'<b>%{y}</b><br>$%{x:.2f}M<extra></extra>',
    }], {
      ...LB,
      margin:{t:16,b:40,l:200,r:60},
      xaxis:{ ticksuffix:'M', gridcolor:'#F1F5F9', title:'Monto (MM$)' },
      yaxis:{ automargin:true, tickfont:{size:10} },
    }, CFG);
  }
}

function renderEjTratoDirecto(td) {
  const kpisEl = document.getElementById('ej-td-kpis');
  if (kpisEl) {
    const pct = v => typeof v==='number' ? v.toFixed(1)+'%' : '—';
    const col = td.nivel==='verde'?'#15803D':td.nivel==='amarillo'?'#D97706':'#B91C1C';
    const fmtN = v => typeof v==='number' ? v.toLocaleString('es-CL') : '—';
    kpisEl.innerHTML = [
      { v: pct(td.pct_td_monto), l: 'TD Ampliado (% monto)', color: col,
        d: 'Incluye TD formal (FTD desde 2025) + OCs bajo SE que mencionan "Trato Directo" en la especificación. Refleja la exposición real al gasto sin competencia.' },
      { v: pct(td.pct_td_ocs), l: 'TD Ampliado (% OCs)', color: col,
        d: 'Fracción total de OCs que corresponden a Trato Directo, considerando ambas fuentes de detección.' },
      { v: fmtN(td.td_amp_ocs), l: 'OCs Trato Directo (total)', color: col,
        d: `${fmtN(td.td_ocs)} OCs con FTD formal + ${fmtN(td.td_espec_ocs)} detectadas en especificación (SE). Antes de 2025 no existía la FTD en Mercado Público.` },
      { v: td.total_fmt || '—', l: 'Gasto total del período', color:'#374151',
        d: 'Monto bruto total de todas las OCs en el período y unidad seleccionados. Es la base sobre la que se calculan los porcentajes.' },
    ].map(k=>`<div class="ej-conc-kpi">
      <div class="ej-conc-kpi-val" style="color:${k.color}">${k.v}</div>
      <div class="ej-conc-kpi-lbl">${k.l}</div>
      <div style="font-size:0.65rem;color:#94A3B8;margin-top:5px;line-height:1.45">${k.d}</div>
    </div>`).join('');
  }

  // Pie de modalidades
  const mods = td.modalidades || [];
  if (mods.length) {
    const paleta = { 'Trato Directo':'#B91C1C','Licitación / Directa (SE)':'#1D4ED8','Compra Ágil':'#059669','Convenio Marco':'#D97706','Otros Procesos':'#6B7280' };
    Plotly.newPlot('chart-ej-td-pie', [{
      type:'pie',
      labels: mods.map(m=>m.modalidad),
      values: mods.map(m=>parseFloat(m.monto||0)),
      marker:{ colors: mods.map(m=>paleta[m.modalidad]||'#94A3B8') },
      hole:0.35,
      textinfo:'percent+label',
      hovertemplate:'<b>%{label}</b><br>$%{value:,.0f}<br>%{percent}<extra></extra>',
    }], { ...LB, margin:{t:20,b:20,l:10,r:10}, showlegend:false }, CFG);
  }

  // Evolución trato directo por año
  const evol = td.evolucion || [];
  if (evol.length) {
    Plotly.newPlot('chart-ej-td-evol', [{
      type:'scatter', mode:'lines+markers',
      x: evol.map(r=>r.anio),
      y: evol.map(r=>parseFloat(r.pct_td||0)),
      line:{ color:'#B91C1C', width:2 },
      marker:{ size:7, color:'#B91C1C' },
      fill:'tozeroy', fillcolor:'rgba(185,28,28,0.07)',
      hovertemplate:'Año %{x}<br>Trato Directo: %{y:.1f}%<extra></extra>',
    }], {
      ...LB,
      margin:{t:16,b:40,l:50,r:20},
      xaxis:{ tickmode:'array', tickvals:evol.map(r=>r.anio), showgrid:false },
      yaxis:{ ticksuffix:'%', gridcolor:'#F1F5F9' },
      shapes:[
        { type:'line',x0:evol[0]?.anio,x1:evol[evol.length-1]?.anio,y0:30,y1:30, line:{color:'#B91C1C',width:1.5,dash:'dash'} },
        { type:'line',x0:evol[0]?.anio,x1:evol[evol.length-1]?.anio,y0:15,y1:15, line:{color:'#D97706',width:1.5,dash:'dot'} },
      ],
    }, CFG);

    // Barras: cantidad de TD (formal + detectado) vs Total OCs por año
    const anios       = evol.map(r => r.anio);
    const tdFormal    = evol.map(r => parseInt(r.td_formal_ocs || 0));
    const tdEspec     = evol.map(r => parseInt(r.td_espec_ocs  || 0));
    const tdTotal     = evol.map(r => parseInt(r.td_ocs || 0));
    const totCount    = evol.map(r => parseInt(r.total_ocs || 0));
    const otrosCount  = evol.map((r,i) => totCount[i] - tdTotal[i]);
    Plotly.newPlot('chart-ej-td-cant', [
      {
        type:'bar', name:'TD formal (FTD)',
        x: anios, y: tdFormal,
        marker:{ color:'#B91C1C', opacity:0.9 },
        hovertemplate:'Año %{x}<br>TD formal (FTD): %{y} OCs<extra></extra>',
      },
      {
        type:'bar', name:'TD detectado en espec (SE)',
        x: anios, y: tdEspec,
        marker:{ color:'#F87171', opacity:0.75 },
        hovertemplate:'Año %{x}<br>TD detectado en espec: %{y} OCs<extra></extra>',
      },
      {
        type:'bar', name:'Otras modalidades',
        x: anios, y: otrosCount,
        marker:{ color:'#93C5FD', opacity:0.6 },
        hovertemplate:'Año %{x}<br>Otras: %{y} OCs<extra></extra>',
      }
    ], {
      ...LB,
      barmode:'stack',
      margin:{t:16,b:40,l:50,r:20},
      xaxis:{ tickmode:'array', tickvals:anios, showgrid:false, title:'Año' },
      yaxis:{ gridcolor:'#F1F5F9', title:'N° de OCs' },
      legend:{ orientation:'h', y:1.15, x:0.5, xanchor:'center', font:{size:10} },
      annotations: anios.map((a,i) => ({
        x: a, y: totCount[i],
        text: tdTotal[i] > 0 ? tdTotal[i]+' TD' : '',
        showarrow:false, font:{size:10, color:'#B91C1C', weight:'bold'}, yshift:10,
      })),
    }, CFG);
  }
}

function renderEjVariacionPrecios(data) {
  if (!data || !data.length) {
    const el = document.getElementById('chart-ej-precios');
    if (el) el.innerHTML = '<div style="color:#6C757D;padding:24px;text-align:center">Sin datos de variación de precios para el período seleccionado.</div>';
    return;
  }

  // Actualizar encabezados de años
  const anioAnt = data[0]?.anio_ant || '—';
  const anioAct = data[0]?.anio_act || '—';
  const elAnt = document.getElementById('ej-anio-ant');
  const elAct = document.getElementById('ej-anio-act');
  if (elAnt) elAnt.textContent = anioAnt;
  if (elAct) elAct.textContent = anioAct;

  const sorted = [...data].sort((a,b)=>parseFloat(b.var_pct||0)-parseFloat(a.var_pct||0));
  const labels = sorted.map(r=>r.espec && r.espec.length>45?r.espec.substring(0,45)+'…':r.espec);
  const values = sorted.map(r=>parseFloat(r.var_pct||0));
  const colors = values.map(v=>v>=0?'rgba(185,28,28,0.8)':'rgba(21,128,61,0.8)');

  Plotly.newPlot('chart-ej-precios', [{
    type:'bar', orientation:'h',
    y: labels, x: values,
    marker:{ color: colors },
    text: values.map(v=>(v>=0?'+':'')+v.toFixed(1)+'%'),
    textposition:'outside', cliponaxis:false,
    hovertemplate:'<b>%{y}</b><br>Variación: %{x:.1f}%<extra></extra>',
  }], {
    ...LB,
    margin:{ t:20, b:40, l:320, r:80 },
    xaxis:{ ticksuffix:'%', gridcolor:'#F1F5F9', zeroline:true, zerolinecolor:'#94A3B8', zerolinewidth:2 },
    yaxis:{ automargin:true, tickfont:{size:9} },
    shapes:[
      { type:'line',x0:20,x1:20,y0:-0.5,y1:sorted.length-0.5, line:{color:'#D97706',width:1.5,dash:'dash'} },
      { type:'line',x0:-20,x1:-20,y0:-0.5,y1:sorted.length-0.5, line:{color:'#059669',width:1.5,dash:'dash'} },
    ],
  }, CFG);

  // Tabla
  const tbody = document.getElementById('tbody-ej-precios');
  if (tbody) tbody.innerHTML = sorted.map(r => {
    const v    = parseFloat(r.var_pct||0);
    const cls  = v>40?'color:#B91C1C;font-weight:700':v>20?'color:#D97706;font-weight:600':v<-20?'color:#15803D;font-weight:600':'';
    const fmt  = n => typeof n==='number' ? n.toLocaleString('es-CL',{maximumFractionDigits:0}) : '—';
    return `<tr>
      <td style="font-size:0.8rem;max-width:280px;white-space:normal">${r.espec||'—'}</td>
      <td style="text-align:right">$${fmt(r.precio_ant)}</td>
      <td style="text-align:right">$${fmt(r.precio_act)}</td>
      <td style="text-align:right;${cls}">${v>=0?'+':''}${v.toFixed(1)}%</td>
      <td style="text-align:center">${r.n_act||'—'}</td>
    </tr>`;
  }).join('');
}

function renderEjSinCompra(data) {
  const empty = document.getElementById('ej-sincmp-empty');
  const tbl   = document.getElementById('tbl-ej-sincmp');
  const tbody = document.getElementById('tbody-ej-sincmp');
  if (!data || !data.length) {
    if (empty) { empty.textContent = 'No se encontraron ítems sin compra reciente. ✓'; empty.style.display='block'; }
    if (tbl)   tbl.style.display = 'none';
    return;
  }
  if (empty) empty.style.display = 'none';
  if (tbl)   tbl.style.display   = '';
  if (tbody) tbody.innerHTML = data.map((r,i) => `<tr>
    <td style="text-align:center;color:#6C757D">${i+1}</td>
    <td style="font-size:0.8rem;max-width:320px;white-space:normal">${r.espec||'—'}</td>
    <td style="text-align:center">${r.ultimo_anio||'—'}</td>
    <td style="text-align:center">${(r.n_ocs||0).toLocaleString('es-CL')}</td>
    <td style="text-align:right;font-weight:600">$${typeof r.monto_hist==='number'?r.monto_hist.toLocaleString('es-CL',{maximumFractionDigits:0}):parseFloat(r.monto_hist||0).toLocaleString('es-CL',{maximumFractionDigits:0})}</td>
  </tr>`).join('');
}

// ══════════════════════════════════════════════════════════════════
// MÓDULO GARANTÍAS DE SERIEDAD
// ══════════════════════════════════════════════════════════════════

let _garCargado = false;

async function cargarGarantias(force = false) {
  if (_garCargado && !force) return;
  const estado = document.getElementById('gar-filtro-estado')?.value || 'pendiente';
  setEstado('⏳ Cargando garantías...', '#7C3AED');
  try {
    const p = new URLSearchParams({ action:'garantias_listar', estado, unidad:'' });
    const d = await fetch(API_URL + '?' + p).then(r => r.json());
    renderGarKpis(d.kpis || {});
    renderGarTabla(d.garantias || []);
    _garCargado = true;
    setEstado('✓ Garantías actualizadas', '#15803D');
  } catch(e) {
    console.error('cargarGarantias:', e);
    setEstado('⚠ Error cargando garantías', '#B91C1C');
  }
}

function renderGarKpis(k) {
  const el = document.getElementById('gar-kpis');
  if (!el) return;
  const fmtM = v => typeof v==='number'||typeof v==='string'
    ? parseFloat(v||0).toLocaleString('es-CL',{style:'currency',currency:'CLP',maximumFractionDigits:0})
    : '—';
  const kpis = [
    { v: k.pendientes||0, l:'Pendientes de devolución', color:'#B45309', bg:'#FEF9C3' },
    { v: k.criticas||0,   l:'Críticas (≤5 días)',       color:'#B91C1C', bg:'#FEE2E2' },
    { v: k.alertas||0,    l:'En alerta (6–15 días)',    color:'#D97706', bg:'#FFF7ED' },
    { v: k.vencidas||0,   l:'Vencidas sin devolver',    color:'#1E293B', bg:'#F1F5F9' },
    { v: k.monto_pendiente_fmt||'$0', l:'Monto pendiente de devolución', color:'#1D4ED8', bg:'#EFF6FF', big:true },
  ];
  el.innerHTML = kpis.map(p => `
    <div class="gar-kpi-card" style="border-top:4px solid ${p.color}">
      <div class="gar-kpi-val" style="color:${p.color};font-size:${p.big?'1.3rem':'1.9rem'}">${p.v}</div>
      <div class="gar-kpi-lbl">${p.l}</div>
    </div>`).join('');
}

function renderGarTabla(rows) {
  const tbody = document.getElementById('gar-tbody');
  const sd    = document.getElementById('gar-sin-datos');
  if (!tbody) return;
  if (!rows.length) {
    tbody.innerHTML = '';
    if (sd) sd.style.display = '';
    return;
  }
  if (sd) sd.style.display = 'none';

  const badgeCls = {
    vigente:'gar-b-vigente', alerta:'gar-b-alerta', critico:'gar-b-critico',
    vencida:'gar-b-vencida', plazo_legal:'gar-b-plazo_legal', devuelta:'gar-b-devuelta',
  };
  const badgeIcon = {
    vigente:'✅', alerta:'⚠️', critico:'🔴', vencida:'⛔', plazo_legal:'⏰', devuelta:'✔',
  };

  tbody.innerHTML = rows.map(r => {
    const sem   = r.semaforo || 'vigente';
    const cls   = badgeCls[sem]  || '';
    const ico   = badgeIcon[sem] || '';
    const diasTxt = r.estado === 'devuelta'
      ? `<span style="color:#15803D;font-size:0.75rem">Dev. ${r.fecha_devolucion||''}</span>`
      : (parseInt(r.dias_rest) < 0
          ? `<span style="color:#B91C1C;font-weight:700">VENCIDA</span>`
          : `<span style="${parseInt(r.dias_rest)<=5?'color:#B91C1C;font-weight:700':''}">
              ${r.dias_rest} día${parseInt(r.dias_rest)!==1?'s':''}
            </span>`);
    const accion = r.estado !== 'devuelta'
      ? `<button onclick="abrirModalDev(${r.id},'${(r.empresa||'').replace(/'/g,"\\'")}','${r.cod_licitacion||''}')"
           style="background:#15803D;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:0.72rem;cursor:pointer;white-space:nowrap">
           ✔ Registrar devolución</button>`
      : `<span style="font-size:0.72rem;color:#6C757D">${r.devuelta_a||'—'}</span>`;

    return `<tr style="${r.estado==='devuelta'?'opacity:0.65':''}">
      <td><span class="gar-badge ${cls}">${ico} ${r.sem_label}</span></td>
      <td style="font-size:0.78rem">
        <div style="font-weight:600">${r.cod_licitacion}</div>
        <div style="color:#6C757D;font-size:0.72rem;max-width:200px;white-space:normal">${r.nombre_licitacion||''}</div>
      </td>
      <td style="font-size:0.78rem">
        <div style="font-weight:600">${r.empresa}</div>
        <div style="color:#6C757D;font-size:0.72rem">${r.rut_empresa||''}</div>
      </td>
      <td style="font-size:0.78rem">
        <div>${r.tipo_documento}</div>
        <div style="color:#6C757D">${r.n_documento||'—'}</div>
      </td>
      <td style="font-size:0.78rem">${r.banco||'—'}</td>
      <td style="text-align:right;font-weight:600;font-size:0.82rem">${r.monto_fmt}</td>
      <td style="text-align:center;font-size:0.8rem">${r.fecha_vencimiento||'—'}</td>
      <td style="text-align:center">${diasTxt}</td>
      <td style="text-align:center;font-size:0.75rem;color:#6C757D">${r.fecha_adjudicacion||'—'}</td>
      <td>${accion}</td>
    </tr>`;
  }).join('');
}

// Busca licitación en la BD para pre-poblar el formulario
async function buscarLicitGar() {
  const cod = document.getElementById('gar-cod-licit')?.value.trim();
  if (!cod) return;
  const infoEl = document.getElementById('gar-licit-info');
  infoEl.style.display = '';
  infoEl.textContent   = '⏳ Buscando...';
  try {
    const d = await fetch(API_URL + '?' + new URLSearchParams({ action:'garantias_buscar_licit', cod }))
                     .then(r => r.json());
    if (!d || !d.cod_licitacion) {
      infoEl.textContent = '⚠ Licitación no encontrada en la base de datos. Puedes igualmente registrar la garantía manualmente.';
      infoEl.style.color = '#B45309';
      return;
    }
    // Pre-poblar campos
    if (d.nombre_licitacion) infoEl.innerHTML =
      `📋 <b>${d.nombre_licitacion}</b><br>
       🏢 Unidad: ${d.unidad_compra||'—'} &nbsp;|&nbsp;
       🏆 Adjudicado a: <b>${d.adjudicado_a||'—'}</b> &nbsp;|&nbsp;
       📅 Adj.: ${d.fecha_adjudicacion||'—'}`;
    infoEl.style.color = '#1D4ED8';
    // Rellenar fecha adjudicación si viene
    if (d.fecha_adjudicacion) {
      const fa = document.getElementById('gar-adj');
      if (fa && !fa.value) fa.value = d.fecha_adjudicacion.substring(0,10);
    }
  } catch(e) {
    infoEl.textContent = '⚠ Error al buscar licitación.';
    infoEl.style.color = '#B91C1C';
  }
}

// Registra una garantía vía POST
async function registrarGarantia() {
  const cod     = document.getElementById('gar-cod-licit')?.value.trim();
  const empresa = document.getElementById('gar-empresa')?.value.trim();
  const venc    = document.getElementById('gar-vencimiento')?.value;
  const msgEl   = document.getElementById('gar-msg');

  if (!cod || !empresa || !venc) {
    if (msgEl) { msgEl.textContent = '⚠ Completa los campos obligatorios (licitación, empresa, vencimiento)'; msgEl.style.color='#B91C1C'; }
    return;
  }
  if (msgEl) { msgEl.textContent = '⏳ Guardando...'; msgEl.style.color='#374151'; }

  const body = {
    cod_licitacion:     cod,
    nombre_licitacion:  document.getElementById('gar-licit-info')?.dataset.nombre || '',
    empresa,
    rut_empresa:        document.getElementById('gar-rut')?.value.trim(),
    tipo_documento:     document.getElementById('gar-tipo-doc')?.value,
    n_documento:        document.getElementById('gar-ndoc')?.value.trim(),
    banco:              document.getElementById('gar-banco')?.value.trim(),
    monto:              parseFloat(document.getElementById('gar-monto')?.value || 0) || null,
    fecha_vencimiento:  venc,
    fecha_adjudicacion: document.getElementById('gar-adj')?.value || null,
    observacion:        document.getElementById('gar-obs')?.value.trim(),
    registrado_por:     document.getElementById('gar-registrador')?.value.trim(),
  };

  try {
    const r = await fetch(API_URL + '?action=garantias_registrar', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)
    }).then(r => r.json());

    if (r.ok) {
      if (msgEl) { msgEl.textContent = '✅ Garantía registrada correctamente'; msgEl.style.color='#15803D'; }
      limpiarFormGar();
      _garCargado = false;
      cargarGarantias(true);
    } else {
      if (msgEl) { msgEl.textContent = '⚠ ' + (r.msg||'Error'); msgEl.style.color='#B91C1C'; }
    }
  } catch(e) {
    if (msgEl) { msgEl.textContent = '⚠ Error de conexión'; msgEl.style.color='#B91C1C'; }
  }
}

function limpiarFormGar() {
  ['gar-cod-licit','gar-empresa','gar-rut','gar-ndoc','gar-banco',
   'gar-monto','gar-vencimiento','gar-adj','gar-registrador','gar-obs'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  const td = document.getElementById('gar-tipo-doc');
  if (td) td.selectedIndex = 0;
  const info = document.getElementById('gar-licit-info');
  if (info) { info.style.display='none'; info.textContent=''; }
  const msg = document.getElementById('gar-msg');
  if (msg) msg.textContent = '';
}

function abrirModalDev(id, empresa, cod) {
  document.getElementById('gar-modal-id').value    = id;
  document.getElementById('gar-modal-fecha').value = new Date().toISOString().substring(0,10);
  document.getElementById('gar-modal-devuelta-a').value = '';
  document.getElementById('gar-modal-obs').value        = '';
  document.getElementById('gar-modal-info').innerHTML   =
    `<b>Garantía #${id}</b> — <b>${empresa}</b><br>Licitación: ${cod}`;
  document.getElementById('gar-modal').style.display = 'flex';
}

async function confirmarDevolucion() {
  const id = parseInt(document.getElementById('gar-modal-id').value);
  const fd = document.getElementById('gar-modal-fecha').value;
  const da = document.getElementById('gar-modal-devuelta-a').value.trim();
  const ob = document.getElementById('gar-modal-obs').value.trim();
  if (!fd) { alert('Ingresa la fecha de devolución'); return; }

  try {
    const r = await fetch(API_URL + '?action=garantias_devolver', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id, fecha_devolucion:fd, devuelta_a:da, observacion:ob })
    }).then(r => r.json());

    document.getElementById('gar-modal').style.display = 'none';
    if (r.ok) {
      setEstado('✅ Devolución registrada', '#15803D');
      _garCargado = false;
      cargarGarantias(true);
    } else {
      alert('Error: ' + (r.msg||'Desconocido'));
    }
  } catch(e) {
    alert('Error de conexión');
  }
}

// Enter en el campo de código de licitación
// ══════════════════════════════════════════════════════════════════
// COMPRA ÁGIL — CANCELADAS / DESIERTAS
// ══════════════════════════════════════════════════════════════════

let _agCanCargado = false;

async function cargarAgCanceladas(force = false) {
  if (_agCanCargado && !force) return;
  const anio = document.getElementById('ag-can-anio')?.value || '0';
  try {
    const p = new URLSearchParams({ action:'ag_canceladas', anio });
    const d = await fetch(API_URL + '?' + p).then(r => r.json());
    renderAgCanKpis(d.kpis || {});
    renderAgCanTabla(d.lista || []);
    _agCanCargado = true;
  } catch(e) { console.error('cargarAgCanceladas:', e); }
}

function renderAgCanKpis(k) {
  const el = document.getElementById('ag-can-kpis');
  if (!el) return;
  const kpis = [
    { v: k.total       || 0, l:'Total procesos',           color:'#1E293B' },
    { v: k.desiertas   || 0, l:'Desiertas',                color:'#B45309' },
    { v: k.canceladas  || 0, l:'Canceladas',               color:'#B91C1C' },
    { v: k.sin_ofertas || 0, l:'Sin ninguna oferta',       color:'#6B7280' },
    { v: k.reiterativas|| 0, l:'2° llamado fallido',       color:'#C2410C' },
    { v: k.monto_fmt   ||'$0', l:'Presupuesto no ejecutado', color:'#1D4ED8', big:true },
  ];
  el.innerHTML = kpis.map(p => `
    <div class="ag-can-kpi" style="border-top:3px solid ${p.color}">
      <div class="ag-can-kpi-val" style="color:${p.color};font-size:${p.big?'1.1rem':'1.6rem'}">${p.v}</div>
      <div class="ag-can-kpi-lbl">${p.l}</div>
    </div>`).join('');
}

function renderAgCanTabla(rows) {
  const tbody = document.getElementById('ag-can-tbody');
  if (!tbody) return;
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#94A3B8;padding:24px">Sin registros para el período seleccionado.</td></tr>';
    return;
  }
  const catColor = {
    guardada:              'background:#F8FAFC;color:#6C757D',
    sin_ofertas:           'background:#EFF6FF;color:#1D4ED8',
    desierta_con_ofertas:  'background:#FEF3C7;color:#92400E',
    cancelada_2do_llamado: 'background:#FFF7ED;color:#C2410C',
    cancelada_sin_ofertas: 'background:#FEE2E2;color:#991B1B',
    cancelada_con_ofertas: 'background:#FEF2F2;color:#B91C1C',
  };
  tbody.innerHTML = rows.map(r => {
    const stl = catColor[r.motivo_categoria] || '';
    const yaGuardado = r.motivo_manual
      ? `<div style="font-size:0.68rem;color:#6C757D;font-style:italic;margin-top:2px">
           Registrado: ${r.actualizado_en?.substring(0,10)||''} — ${r.registrado_por||''}
         </div>`
      : '';
    return `<tr>
      <td style="text-align:center;font-weight:600">${r.anio}</td>
      <td style="font-size:0.75rem;color:#1D4ED8;white-space:nowrap">${r.id_cotizacion}</td>
      <td style="font-size:0.78rem;max-width:240px;white-space:normal;line-height:1.35">${r.nombre||'—'}</td>
      <td><span style="padding:3px 8px;border-radius:12px;font-size:0.7rem;font-weight:700;
          ${r.estado==='Desierta'?'background:#FEF3C7;color:#92400E':
            r.estado==='Cancelada'?'background:#FEE2E2;color:#991B1B':
            'background:#F1F5F9;color:#6C757D'}">${r.estado}</span></td>
      <td style="font-size:0.75rem;text-align:center">${r.estado_convocatoria||'—'}</td>
      <td style="text-align:center;font-weight:600">${r.cotizaciones_recibidas??'—'}</td>
      <td style="text-align:right;font-size:0.78rem">${r.monto_fmt}</td>
      <td style="font-size:0.72rem;max-width:200px;white-space:normal;line-height:1.35">
        <span style="padding:2px 7px;border-radius:10px;${stl}">${r.motivo_icono} ${r.motivo_inferido}</span>
      </td>
      <td>
        <textarea class="ag-can-motivo-input" rows="2"
          id="mot-${r.id_cotizacion}"
          placeholder="Ingresa el motivo real..."
          onInput="agMotivoChanged('${r.id_cotizacion}')">${r.motivo_manual||''}</textarea>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px">
          <button class="ag-can-save-btn" id="btn-mot-${r.id_cotizacion}"
            onclick="agGuardarMotivo('${r.id_cotizacion}')">💾 Guardar</button>
          <span class="ag-can-saved" id="saved-mot-${r.id_cotizacion}">✓ Guardado</span>
        </div>
        ${yaGuardado}
      </td>
    </tr>`;
  }).join('');
}

function agMotivoChanged(id) {
  const btn   = document.getElementById('btn-mot-'   + id);
  const saved = document.getElementById('saved-mot-' + id);
  if (btn)   btn.style.display   = 'inline-block';
  if (saved) saved.style.display = 'none';
}

async function agGuardarMotivo(id) {
  const ta  = document.getElementById('mot-' + id);
  const btn = document.getElementById('btn-mot-' + id);
  const ok  = document.getElementById('saved-mot-' + id);
  if (!ta) return;
  try {
    const r = await fetch(API_URL + '?action=ag_guardar_motivo', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ id_cotizacion: id, motivo: ta.value.trim(), registrado_por: '' })
    }).then(r => r.json());
    if (r.ok) {
      if (btn) btn.style.display = 'none';
      if (ok)  { ok.style.display = 'inline'; setTimeout(() => { if (ok) ok.style.display = 'none'; }, 3000); }
    }
  } catch(e) { console.error(e); }
}

function agCanPoblarAnios() {
  const sel = document.getElementById('ag-can-anio');
  if (!sel || sel.options.length > 1) return;
  const anios = (window._anios || []).filter(a => a >= 2020);
  anios.slice().reverse().forEach(a => {
    const opt = document.createElement('option'); opt.value = a; opt.textContent = a; sel.appendChild(opt);
  });
}

document.getElementById('gar-cod-licit')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') buscarLicitGar();
});

</script>
<div style="position:fixed;bottom:6px;right:10px;font-size:0.58rem;color:rgba(150,150,160,0.45);
     letter-spacing:.04em;user-select:none;pointer-events:none;z-index:1">
  🐇 Elaborado por Bastián Miranda Coronado
</div>
</body>
</html>
