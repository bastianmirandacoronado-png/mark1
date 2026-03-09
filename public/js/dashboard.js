// ═══════════════════════════════════════════════════
// DSSO Dashboard — dashboard.js
// ═══════════════════════════════════════════════════

const CFG = { responsive: true, displayModeBar: false };
const LB = {
  paper_bgcolor: 'rgba(0,0,0,0)',
  plot_bgcolor:  'rgba(0,0,0,0)',
  font: { family: "'DM Sans', sans-serif", color: '#1A1A2E', size: 12 },
  legend: { font: { color: '#1A1A2E', size: 11 }, bgcolor: 'rgba(255,255,255,0.85)', bordercolor: '#D0D7E2', borderwidth: 1 }
};
const PALETA = ['#0F69C4','#15803D','#B45309','#B91C1C','#7C3AED','#0891B2','#BE185D','#D97706'];
const COLOR_ORIGEN = {
  'Licitación / Directa (SE)': '#0F69C4',
  'Convenio Marco':            '#15803D',
  'Compra Ágil':               '#B45309',
  'Trato Directo':             '#B91C1C',
  'Otros Procesos':            '#6B7280'
};

const API_URL = 'api/data.php';
const fmt = v => '$ ' + Math.round(v||0).toLocaleString('es-CL');
const hexRgba = (hex, a=0.10) => {
  const r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
  return `rgba(${r},${g},${b},${a})`;
};

let tendCache = {};

// ── FILTROS ──────────────────────────────────────────
function getFiltros(extras={}) {
  return {
    anioDesde: document.getElementById('sel-anio-desde')?.value || 2020,
    anioHasta: document.getElementById('sel-anio-hasta')?.value || 2025,
    busqueda:  document.getElementById('inp-busqueda')?.value   || '',
    ...extras
  };
}

function setEstado(msg, color='#28a745') {
  const el = document.getElementById('topbar-estado');
  if (el) { el.textContent = msg; el.style.background = color; }
}

// ── CARGA PRINCIPAL ───────────────────────────────────
async function cargarDatos() {
  setEstado('⏳ Cargando...', '#f59e0b');
  try {
    const p   = new URLSearchParams({ action: 'stats', ...getFiltros() });
    const res = await fetch(API_URL + '?' + p);
    const d   = await res.json();
    if (d.error) throw new Error(d.error);

    renderKPIs(d.kpis);
    renderTopProveedores(d.topProv);
    renderEstado(d.distEstado);
    renderEvolucion(d.evolucion);
    renderOrigen(d.distOrigen);
    renderTopItems(d.topItems);
    renderFrecuenciaProv(d.frecProv);
    poblarSelectores(d.anios, d.proveedores);

    const rango = `${getFiltros().anioDesde} → ${getFiltros().anioHasta}`;
    const el = document.getElementById('topbar-rango');
    if (el) el.textContent = rango;
    setEstado('✔ ' + (d.meta?.total || '—') + ' OCs', '#28a745');
  } catch(err) {
    console.error(err);
    setEstado('⚠ ' + err.message, '#dc3545');
  }
}

// ── KPIs ──────────────────────────────────────────────
function renderKPIs(k) {
  const set = (id, v) => { const el=document.getElementById(id); if(el) el.innerHTML=v; };
  set('kpi-total', k?.inversionFormato || '—');
  set('kpi-prov',  k?.proveedores      || '—');
  set('kpi-ocs',   k?.registros        || '—');
}

// ── TOP PROVEEDORES ───────────────────────────────────
function renderTopProveedores(data) {
  if (!data?.length) return;
  const n = data.map(d => d.nombre.length>32 ? d.nombre.slice(0,32)+'…' : d.nombre).reverse();
  const m = data.map(d => (d.monto||0)/1e6).reverse();
  Plotly.newPlot('chart-top-prov', [{
    type:'bar', orientation:'h', x:m, y:n,
    marker:{ color:m, colorscale:[[0,'#93C5FD'],[1,'#0A4F96']], showscale:false, line:{color:'white',width:1} },
    text: m.map(v=>'$'+v.toFixed(1)+'M'), textposition:'outside',
    hovertemplate: '<b>%{y}</b><br>$%{x:.2f}M<extra></extra>'
  }], { ...LB, margin:{t:10,b:30,l:220,r:70},
    xaxis:{ title:{text:'Monto (M$)',font:{color:'#6C757D',size:11}}, tickfont:{color:'#6C757D'}, showgrid:true, gridcolor:'rgba(0,0,0,0.06)', zeroline:false },
    yaxis:{ tickfont:{color:'#1A1A2E',size:11} }
  }, CFG);
}

// ── ESTADO OC ─────────────────────────────────────────
function renderEstado(dist) {
  if (!dist || !Object.keys(dist).length) return;
  Plotly.newPlot('chart-estado', [{
    type:'pie', hole:0.42,
    labels: Object.keys(dist), values: Object.values(dist),
    marker:{ colors:['#15803D','#0F69C4','#0A4F96','#B91C1C','#B45309'], line:{color:'white',width:2} },
    textposition:'inside', textinfo:'percent+label', textfont:{color:'#fff',size:11},
    hovertemplate: '<b>%{label}</b><br>%{value} OCs<extra></extra>'
  }], { ...LB, showlegend:false, margin:{t:10,b:10,l:10,r:10} }, CFG);
}

// ── EVOLUCIÓN ─────────────────────────────────────────
function renderEvolucion(evolucion) {
  if (!evolucion || !Object.keys(evolucion).length) return;
  const anios    = Object.keys(evolucion).sort();
  const origenes = [...new Set(anios.flatMap(a => Object.keys(evolucion[a])))];
  const traces   = origenes.map(o => ({
    type:'bar', name:o, x:anios, y:anios.map(a => evolucion[a][o]||0),
    marker:{ color:COLOR_ORIGEN[o]||'#888', line:{color:'white',width:1} },
    hovertemplate: '<b>'+o+'</b><br>Año: %{x}<br>OCs: %{y}<extra></extra>'
  }));
  const totales = anios.map(a => Object.values(evolucion[a]).reduce((s,v)=>s+v,0));
  traces.push({ type:'scatter', mode:'lines+markers', name:'Total', x:anios, y:totales,
    line:{color:'#1A1A2E',width:2,dash:'dot'}, marker:{size:8,color:'#1A1A2E'} });
  Plotly.newPlot('chart-evolucion', traces, { ...LB, barmode:'group',
    margin:{t:10,b:40,l:40,r:10},
    xaxis:{ tickfont:{color:'#6C757D'}, showgrid:false },
    yaxis:{ tickfont:{color:'#6C757D'}, showgrid:true, gridcolor:'rgba(0,0,0,0.06)', zeroline:false },
    legend:{ orientation:'h', yanchor:'bottom', y:1.02, xanchor:'right', x:1, font:{size:10} }
  }, CFG);
}

// ── ORIGEN ────────────────────────────────────────────
function renderOrigen(dist) {
  if (!dist || !Object.keys(dist).length) return;
  Plotly.newPlot('chart-origen', [{
    type:'pie', hole:0.42,
    labels: Object.keys(dist), values: Object.values(dist),
    marker:{ colors:Object.keys(dist).map(k=>COLOR_ORIGEN[k]||'#888'), line:{color:'white',width:2} },
    textposition:'inside', textinfo:'percent+label', textfont:{color:'#fff',size:11},
    hovertemplate: '<b>%{label}</b><br>%{percent}<extra></extra>'
  }], { ...LB, showlegend:false, margin:{t:10,b:10,l:10,r:10} }, CFG);
}

// ── TOP ITEMS ─────────────────────────────────────────
function renderTopItems(items) {
  if (!items?.length) return;
  const labels = items.map(d => (d.espec||'').length>46 ? (d.espec||'').slice(0,46)+'…' : (d.espec||'')).reverse();
  const veces  = items.map(d => d.veces||0).reverse();
  Plotly.newPlot('chart-items-espec', [{
    type:'bar', orientation:'h', x:veces, y:labels,
    marker:{ color:veces, colorscale:[[0,'#93C5FD'],[1,'#0A4F96']], showscale:false, line:{color:'white',width:1} },
    text:veces, textposition:'outside', textfont:{color:'#1A1A2E',size:10},
    hovertemplate: '<b>%{y}</b><br>Veces: %{x}<extra></extra>'
  }], { ...LB, margin:{t:10,b:30,l:330,r:55},
    xaxis:{ tickfont:{color:'#6C757D'}, showgrid:true, gridcolor:'rgba(0,0,0,0.06)', zeroline:false },
    yaxis:{ tickfont:{color:'#1A1A2E',size:10}, autorange:'reversed' }
  }, CFG);

  const tbody = document.getElementById('tbody-items-espec');
  if (!tbody) return;
  tbody.innerHTML = '';
  items.forEach((d, i) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="mono" style="color:#6C757D">${String(i+1).padStart(2,'0')}</td>
      <td style="font-size:0.75rem">${d.espec||''}</td>
      <td><strong>${d.veces||0}</strong></td>
      <td class="mono">${fmt(d.monto||0)}</td>`;
    tbody.appendChild(tr);
  });
}

// ── FRECUENCIA PROVEEDORES ────────────────────────────
function renderFrecuenciaProv(lista) {
  if (!lista?.length) return;
  const badge = {
    'Licitación / Directa (SE)':'pill pill-blue',
    'Convenio Marco':'pill pill-green',
    'Compra Ágil':'pill pill-yellow',
    'Trato Directo':'pill pill-red'
  };
  const tbody = document.getElementById('tbody-proveedores');
  if (!tbody) return;
  tbody.innerHTML = '';
  lista.forEach((p, i) => {
    const bc = badge[p.origen_predominante] || 'pill pill-gray';
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="mono" style="color:#6C757D">${String(i+1).padStart(2,'0')}</td>
      <td><strong>${p.nombre||p.p_nombre||''}</strong></td>
      <td>${p.ocs||0}</td>
      <td class="mono">${fmt(p.monto||0)}</td>
      <td><span class="${bc}">${p.origen_predominante||'—'}</span></td>
      <td><span class="pill pill-green">${p.estado||p.estado_oc||'—'}</span></td>`;
    tbody.appendChild(tr);
  });
}

// ── POBLAR SELECTORES ─────────────────────────────────
function poblarSelectores(anios, proveedores) {
  if (!anios?.length) return;
  const minA = Math.min(...anios), maxA = Math.max(...anios);
  ['sel-anio-desde','sel-anio-hasta'].forEach(id => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const isHasta = id.includes('hasta');
    sel.innerHTML = '';
    anios.slice().sort().forEach(a => {
      const o = new Option(a, a);
      if (isHasta  && a == maxA) o.selected = true;
      if (!isHasta && a == minA) o.selected = true;
      sel.appendChild(o);
    });
  });
  const selProv = document.getElementById('sel-prov-tend');
  if (selProv && proveedores?.length) {
    selProv.innerHTML = '<option value="">— Seleccione proveedor —</option>';
    proveedores.forEach(p => selProv.add(new Option(p, p)));
  }
}

// ── BÚSQUEDA EQUIPOS ──────────────────────────────────
async function buscarEquipos() {
  const input   = document.getElementById('busq-equipo').value.trim();
  const charts  = document.getElementById('equipo-charts');
  const empty   = document.getElementById('equipo-empty');
  const kpisEl  = document.getElementById('equipo-kpis');
  if (!input) { charts.style.display='none'; empty.style.display='block'; return; }

  setEstado('⏳ Buscando...', '#f59e0b');
  const p = new URLSearchParams({ action:'equipos', ...getFiltros(), terminos:input });
  try {
    const res = await fetch(API_URL + '?' + p);
    const d   = await res.json();
    if (!d.matches?.length) {
      empty.textContent = '⚠️ Sin resultados para: ' + input;
      empty.style.display = 'block'; charts.style.display = 'none';
      setEstado('✔ Listo'); return;
    }
    empty.style.display = 'none'; charts.style.display = 'block';

    kpisEl.style.cssText = 'display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px;';
    kpisEl.innerHTML = `
      <div class="kpi-card c1"><div class="kpi-label">Resultados</div><div class="kpi-value">${d.total}</div></div>
      <div class="kpi-card c2"><div class="kpi-label">Monto acumulado</div><div class="kpi-value" style="font-size:1rem">${d.montoFmt}</div></div>
      <div class="kpi-card c3"><div class="kpi-label">Proveedores</div><div class="kpi-value">${new Set(d.matches.map(x=>x.proveedor||x.p_nombre)).size}</div></div>`;

    // Gráfico por proveedor
    const byP = {};
    d.matches.forEach(x => { const pv=x.proveedor||x.p_nombre||'—'; byP[pv]=(byP[pv]||0)+1; });
    const po = Object.entries(byP).sort((a,b)=>b[1]-a[1]);
    Plotly.newPlot('chart-eq-prov', [{
      type:'bar', x:po.map(([n])=>n.length>22?n.slice(0,22)+'…':n), y:po.map(([,v])=>v),
      marker:{ color:PALETA[0], line:{color:'white',width:1.5} },
      text:po.map(([,v])=>v), textposition:'outside',
      hovertemplate: '<b>%{x}</b><br>%{y} OCs<extra></extra>'
    }], { ...LB, height:340, margin:{t:10,b:90,l:10,r:10},
      xaxis:{ tickfont:{color:'#1A1A2E',size:10}, showgrid:false, tickangle:-35 },
      yaxis:{ tickfont:{color:'#6C757D'}, showgrid:true, gridcolor:'rgba(0,0,0,0.07)', zeroline:false }
    }, CFG);

    // Gráfico por término
    const byT = {};
    d.matches.forEach(x => { const t=x.termino||'—'; byT[t]=(byT[t]||0)+1; });
    const to = Object.entries(byT).sort((a,b)=>b[1]-a[1]);
    Plotly.newPlot('chart-eq-termino', [{
      type:'bar', x:to.map(([n])=>n), y:to.map(([,v])=>v),
      marker:{ color:PALETA[2], line:{color:'white',width:1.5} },
      text:to.map(([,v])=>v), textposition:'outside',
      hovertemplate: '<b>%{x}</b><br>%{y} OCs<extra></extra>'
    }], { ...LB, height:340, margin:{t:10,b:90,l:10,r:10},
      xaxis:{ tickfont:{color:'#1A1A2E',size:10}, showgrid:false, tickangle:-35 },
      yaxis:{ tickfont:{color:'#6C757D'}, showgrid:true, gridcolor:'rgba(0,0,0,0.07)', zeroline:false }
    }, CFG);

    // Evolución anual
    const byAnio = {};
    d.matches.forEach(x => { byAnio[x.anio]=(byAnio[x.anio]||0)+1; });
    const anios = Object.keys(byAnio).sort();
    Plotly.newPlot('chart-eq-evolucion', [{
      type:'scatter', mode:'lines+markers',
      x:anios, y:anios.map(a=>byAnio[a]),
      fill:'tozeroy', fillcolor:hexRgba(PALETA[0],0.10),
      line:{color:PALETA[0],width:2.5}, marker:{size:8,color:PALETA[0]},
      hovertemplate: 'Año %{x}<br>%{y} OCs<extra></extra>'
    }], { ...LB, height:300, margin:{t:10,b:40,l:40,r:10},
      xaxis:{ tickfont:{color:'#6C757D'} },
      yaxis:{ tickfont:{color:'#6C757D'}, zeroline:false }
    }, CFG);

    // Tabla
    const tbody = document.getElementById('tbody-equipo');
    tbody.innerHTML = '';
    d.matches.forEach(x => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td class="mono" style="font-size:0.72rem">${x.codigo_oc||''}</td>
        <td style="font-size:0.75rem">${x.termino||''}</td>
        <td style="font-size:0.75rem;max-width:220px">${x.espec_comprador||x.espec||''}</td>
        <td style="font-size:0.75rem">${x.proveedor||x.p_nombre||''}</td>
        <td>${x.anio||''}</td>
        <td class="mono">${fmt(x.total_linea||x.monto||0)}</td>`;
      tbody.appendChild(tr);
    });
    setEstado('✔ Listo');
  } catch(err) { console.error(err); setEstado('⚠ Error', '#dc3545'); }
}

// ── TENDENCIA PROVEEDOR ───────────────────────────────
async function cargarTendencia() {
  const prov    = document.getElementById('sel-prov-tend')?.value;
  const tendDiv = document.getElementById('tend-charts');
  const empty   = document.getElementById('tend-empty');
  if (!prov) { tendDiv.style.display='none'; empty.style.display='block'; return; }

  setEstado('⏳ Cargando tendencia...', '#f59e0b');
  const p = new URLSearchParams({ action:'tendencia', ...getFiltros(), proveedor:prov });
  try {
    const res = await fetch(API_URL + '?' + p);
    const d   = await res.json();
    if (!d.datos?.length) { tendDiv.style.display='none'; empty.style.display='block'; setEstado('✔ Listo'); return; }

    tendCache = {};
    d.datos.forEach(x => { tendCache[x.producto]=tendCache[x.producto]||[]; tendCache[x.producto].push(x); });

    const selProd = document.getElementById('sel-prod-tend');
    selProd.innerHTML = '';
    Object.keys(tendCache).sort().forEach(p => {
      const o = document.createElement('option');
      o.value = o.textContent = p; o.selected = true;
      selProd.appendChild(o);
    });
    renderTendencia();
    setEstado('✔ Listo');
  } catch(err) { console.error(err); setEstado('⚠ Error', '#dc3545'); }
}

function renderTendencia() {
  const prodsAct = [...document.getElementById('sel-prod-tend').selectedOptions].map(o=>o.value);
  const tendDiv  = document.getElementById('tend-charts');
  const empty    = document.getElementById('tend-empty');
  if (!prodsAct.length || !Object.keys(tendCache).length) { tendDiv.style.display='none'; empty.style.display='block'; return; }

  const datos  = prodsAct.flatMap(p => tendCache[p]||[]);
  if (!datos.length) return;
  tendDiv.style.display = 'block'; empty.style.display = 'none';

  const aniosU = [...new Set(datos.map(d=>d.anio))].sort();
  const tracesC = prodsAct.map((prod, i) => {
    const col = PALETA[i % PALETA.length];
    const byA = Object.fromEntries((tendCache[prod]||[]).map(d=>[d.anio,d.ocs]));
    return { type:'scatter', mode:'lines+markers', name:prod.length>45?prod.slice(0,45)+'…':prod,
      x:aniosU, y:aniosU.map(a=>byA[a]||0),
      line:{color:col,width:2.5}, marker:{size:8,color:col},
      fill:'tozeroy', fillcolor:hexRgba(col,0.08) };
  });
  Plotly.newPlot('chart-tend-cantidad', tracesC, { ...LB, height:380, hovermode:'x unified',
    margin:{t:10,b:40,l:50,r:10},
    xaxis:{ tickfont:{color:'#6C757D'}, title:{text:'Año',font:{color:'#6C757D',size:11}} },
    yaxis:{ tickfont:{color:'#6C757D'}, title:{text:'Órdenes',font:{color:'#6C757D',size:11}}, zeroline:false },
    legend:{ orientation:'h', yanchor:'bottom', y:1.02, xanchor:'right', x:1, font:{size:11} }
  }, CFG);

  const tracesM = prodsAct.map((prod, i) => {
    const col = PALETA[i % PALETA.length];
    const byA = Object.fromEntries((tendCache[prod]||[]).map(d=>[d.anio,(d.monto||0)/1e6]));
    return { type:'bar', name:prod.length>45?prod.slice(0,45)+'…':prod,
      x:aniosU, y:aniosU.map(a=>byA[a]||0),
      marker:{color:col, line:{color:'white',width:1}} };
  });
  Plotly.newPlot('chart-tend-monto', tracesM, { ...LB, height:300, barmode:'group',
    margin:{t:10,b:40,l:55,r:10},
    xaxis:{ tickfont:{color:'#6C757D'} },
    yaxis:{ tickfont:{color:'#6C757D'}, title:{text:'M$',font:{color:'#6C757D',size:11}}, zeroline:false }
  }, CFG);

  const tbody = document.getElementById('tbody-tend');
  tbody.innerHTML = '';
  datos.sort((a,b)=>a.anio-b.anio).forEach(d => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${d.anio}</td><td>${d.producto}</td><td>${d.ocs}</td><td class="mono">${fmt(d.monto||0)}</td>`;
    tbody.appendChild(tr);
  });
}

// ── HELPERS ───────────────────────────────────────────
function toggleEl(id, btn, labelShow, labelHide) {
  const el = document.getElementById(id);
  const shown = el.style.display !== 'none';
  el.style.display = shown ? 'none' : 'block';
  btn.textContent = shown ? labelShow + ' ▾' : labelHide + ' ▴';
}

// ── INIT ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  cargarDatos();
  document.getElementById('busq-equipo')?.addEventListener('keydown', e => { if(e.key==='Enter') buscarEquipos(); });
  document.getElementById('inp-busqueda')?.addEventListener('keydown', e => { if(e.key==='Enter') cargarDatos(); });
});
