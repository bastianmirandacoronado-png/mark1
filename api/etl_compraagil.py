#!/usr/bin/env python3
"""
etl_compraagil.py — Mark 1
ETL: Lee archivos Excel de data_compraagil/ e importa en ag_cotizaciones (SQLite).
Uso: python etl_compraagil.py [--reset]
"""
import sys, sqlite3, os
import pandas as pd

# Rutas
BASE_DIR  = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH   = os.path.join(BASE_DIR, 'db', 'mark1.sqlite')
DATA_DIR  = os.path.join(BASE_DIR, 'data_compraagil')

ARCHIVOS = [
    ('AG20', 'AG20.xls'),
    ('AG21', 'AG21.xls'),
    ('AG22', 'AG22.xls'),
    ('AG23', 'AG23.xls'),
    ('AG24', 'AG24.xls'),
    ('AG25', 'AG25.xlsx'),
    ('AG26', 'AG26.xls'),
]

def normalizar_estado(estado):
    """Unifica nomenclatura entre años distintos de Mercado Público."""
    if pd.isna(estado):
        return ''
    e = str(estado).strip()
    # Antes de 2025 usaba 'Proveedor seleccionado' para OC exitosa
    if e == 'Proveedor seleccionado':
        return 'OC Emitida'
    return e

def normalizar_str(val):
    if pd.isna(val):
        return ''
    s = str(val).strip()
    # Fix latin-1 mojibake when pandas reads without encoding detection
    try:
        return s.encode('latin-1').decode('utf-8')
    except Exception:
        return s

def crear_esquema(cur):
    cur.executescript("""
        CREATE TABLE IF NOT EXISTS ag_cotizaciones (
            id                   INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cotizacion        TEXT UNIQUE NOT NULL,
            nombre               TEXT,
            unidad_compra        TEXT,
            fecha_publicacion    TEXT,
            fecha_cierre         TEXT,
            estado               TEXT,
            estado_orig          TEXT,
            estado_convocatoria  TEXT,
            monto_disponible     REAL DEFAULT 0,
            moneda               TEXT DEFAULT 'CLP',
            tipo_presupuesto     TEXT,
            cotizaciones_recibidas INTEGER DEFAULT 0,
            codigo_oc            TEXT,
            estado_oc            TEXT,
            anio                 INTEGER,
            cargado_en           TEXT DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_ag_anio   ON ag_cotizaciones(anio);
        CREATE INDEX IF NOT EXISTS idx_ag_estado ON ag_cotizaciones(estado);
        CREATE INDEX IF NOT EXISTS idx_ag_oc     ON ag_cotizaciones(codigo_oc);
    """)

def importar_archivo(cur, folder, fname):
    path = os.path.join(DATA_DIR, folder, fname)
    if not os.path.exists(path):
        print(f"  {folder}: archivo no encontrado ({path})", flush=True)
        return 0

    anio = int('20' + folder[2:])
    df   = pd.read_excel(path, dtype=str)   # leer todo como texto primero

    insertados = 0
    for _, row in df.iterrows():
        cols = row.tolist()
        if len(cols) < 13:
            continue

        id_cot       = str(cols[0]).strip() if pd.notna(cols[0]) else ''
        if not id_cot or id_cot == 'nan':
            continue

        nombre       = normalizar_str(cols[1])
        unidad       = normalizar_str(cols[2])
        fecha_pub    = str(cols[3]).strip()  if pd.notna(cols[3]) else None
        fecha_cie    = str(cols[4]).strip()  if pd.notna(cols[4]) else None
        estado_orig  = normalizar_str(cols[5])
        estado       = normalizar_estado(cols[5])
        estado_conv  = normalizar_str(cols[7])
        try:
            monto    = float(str(cols[8]).replace(',', '').strip()) if pd.notna(cols[8]) else 0.0
        except ValueError:
            monto    = 0.0
        moneda       = normalizar_str(cols[9]) or 'CLP'
        tipo_ppto    = normalizar_str(cols[10])
        try:
            cot_rec  = int(float(str(cols[11]).strip())) if pd.notna(cols[11]) else 0
        except (ValueError, TypeError):
            cot_rec  = 0
        codigo_oc    = normalizar_str(cols[12]) if pd.notna(cols[12]) else None
        if codigo_oc == 'nan' or codigo_oc == '':
            codigo_oc = None
        estado_oc    = normalizar_str(cols[13]) if len(cols) > 13 and pd.notna(cols[13]) else None
        if estado_oc == 'nan' or estado_oc == '':
            estado_oc = None

        cur.execute("""
            INSERT OR REPLACE INTO ag_cotizaciones
                (id_cotizacion, nombre, unidad_compra, fecha_publicacion, fecha_cierre,
                 estado, estado_orig, estado_convocatoria, monto_disponible, moneda,
                 tipo_presupuesto, cotizaciones_recibidas, codigo_oc, estado_oc, anio)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        """, (id_cot, nombre, unidad, fecha_pub, fecha_cie,
              estado, estado_orig, estado_conv, monto, moneda,
              tipo_ppto, cot_rec, codigo_oc, estado_oc, anio))
        insertados += 1

    return insertados

def main():
    reset = '--reset' in sys.argv

    conn = sqlite3.connect(DB_PATH)
    cur  = conn.cursor()

    if reset:
        cur.execute("DROP TABLE IF EXISTS ag_cotizaciones")
        print("Tabla ag_cotizaciones eliminada.", flush=True)

    crear_esquema(cur)
    conn.commit()

    total = 0
    for folder, fname in ARCHIVOS:
        n = importar_archivo(cur, folder, fname)
        conn.commit()
        print(f"  {folder}: {n} filas importadas", flush=True)
        total += n

    conn.close()
    print(f"ETL Compra Ágil finalizado: {total} cotizaciones en DB", flush=True)
    return 0

if __name__ == '__main__':
    sys.exit(main())
