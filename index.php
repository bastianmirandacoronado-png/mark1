<?php /* Dashboard DSSO Mark 1 — Diseño Gobierno Digital Chile */ ?>
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
  </style>
</head>
<body>

<div class="gob-topstrip"></div>

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
    </div>
  </div>
</header>

<nav class="gob-subnav">
  <div class="gob-subnav-inner">
    <a href="#" onclick="return showTab('estadisticas')" id="tab-estadisticas" class="active">📊 Estadísticas Históricas</a>
    <a href="#" onclick="return showTab('inversiones')"  id="tab-inversiones">📈 Inversiones OC</a>
    <a href="#" onclick="return showTab('compra-agil')"  id="tab-compra-agil">🛒 Compra Ágil</a>
    <a href="#" onclick="return showTab('trazabilidad')" id="tab-trazabilidad">🔍 Trazabilidad</a>
    <a href="#" onclick="return showTab('exportar')"     id="tab-exportar">📥 Exportar</a>
  </div>
</nav>

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
    <div class="kpi-card"><div class="kpi-label">INVERSIÓN TOTAL</div><div class="kpi-value" id="kpi-total">$ —</div><div class="kpi-sub">Suma TotalBruto todas las OCs</div><div class="kpi-icon">💰</div></div>
    <div class="kpi-card kpi-c2"><div class="kpi-label">PROVEEDORES ÚNICOS</div><div class="kpi-value" id="kpi-prov">—</div><div class="kpi-sub">Empresas adjudicadas en el período</div><div class="kpi-icon">🏢</div></div>
    <div class="kpi-card kpi-c3"><div class="kpi-label">TOTAL REGISTROS</div><div class="kpi-value" id="kpi-ocs">—</div><div class="kpi-sub">Órdenes de compra</div><div class="kpi-icon">📋</div></div>
  </div>

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

<!-- TAB 2: INVERSIONES -->
<div id="view-inversiones" class="tab-view">
  <div style="padding:40px;text-align:center;color:#6C757D">
    <div style="font-size:3rem;margin-bottom:16px">📈</div>
    <h2 style="color:#0A4F96">Inversiones OC — Próximamente</h2>
    <p>Módulo en construcción. Detalle completo de inversiones por unidad.</p>
  </div>
</div>

<!-- TAB 3: COMPRA ÁGIL -->
<div id="view-compra-agil" class="tab-view">
  <div style="padding:40px;text-align:center;color:#6C757D">
    <div style="font-size:3rem;margin-bottom:16px">🛒</div>
    <h2 style="color:#0A4F96">Compra Ágil — Próximamente</h2>
    <p>Módulo en construcción. Análisis de adquisiciones bajo modalidad Compra Ágil.</p>
  </div>
</div>

<!-- TAB 4: TRAZABILIDAD -->
<div id="view-trazabilidad" class="tab-view">
  <div style="padding:40px;text-align:center;color:#6C757D">
    <div style="font-size:3rem;margin-bottom:16px">🔍</div>
    <h2 style="color:#0A4F96">Trazabilidad — Próximamente</h2>
    <p>Módulo en construcción. Historial completo por producto o equipo.</p>
  </div>
</div>

<!-- TAB 5: EXPORTAR -->
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
    'compra-agil':['Compra Ágil','Compra Ágil','Órdenes bajo modalidad Compra Ágil.'],
    'trazabilidad':['Trazabilidad','Trazabilidad','Historial completo por producto o equipo.'],
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
  const anios=Object.keys(evolucion).sort();
  const origenes=[...new Set(anios.flatMap(a=>Object.keys(evolucion[a])))];
  const traces=origenes.map(o=>({
    type:'bar',name:o,x:anios,y:anios.map(a=>evolucion[a][o]||0),
    marker:{color:COLOR_ORIGEN[o]||'#888',line:{color:'white',width:1}},
    hovertemplate:'<b>'+o+'</b><br>Año: %{x}<br>OCs: %{y}<extra></extra>'
  }));
  const totales=anios.map(a=>Object.values(evolucion[a]).reduce((s,v)=>s+v,0));
  traces.push({type:'scatter',mode:'lines+markers',name:'Total',x:anios,y:totales,
    line:{color:'#1A1A2E',width:2,dash:'dot'},marker:{size:8,color:'#1A1A2E'}});
  Plotly.newPlot('chart-evolucion',traces,{...LB,barmode:'group',
    margin:{t:10,b:40,l:40,r:10},
    xaxis:{tickfont:{color:'#6C757D'},showgrid:false},
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

document.addEventListener('DOMContentLoaded',()=>{
  showTab('estadisticas');
  cargarDatos();
  document.getElementById('busq-equipo')?.addEventListener('keydown',e=>{if(e.key==='Enter')buscarEquipos();});
  document.getElementById('inp-busqueda')?.addEventListener('keydown',e=>{if(e.key==='Enter')cargarDatos();});
});
</script>
</body>
</html>
