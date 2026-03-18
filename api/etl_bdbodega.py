#!/usr/bin/env python3
"""
etl_bdbodega.py — Mark 1
ETL: Lee bdbodega.xlsx (export Kimun) e importa en bodega_recepciones (SQLite).
Uso: python etl_bdbodega.py [--reset] [--file ruta_alternativa.xlsx]
"""
import sys, sqlite3, os
import pandas as pd

BASE_DIR  = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DB_PATH   = os.path.join(BASE_DIR, 'db', 'mark1.sqlite')
EXCEL_DEF = os.path.join(BASE_DIR, 'bdbodega.xlsx')

# Mapeo normalizado de estados
ESTADO_NORM = {
    'recepcion completa': 'Recepción Completa',
    'en tramite':         'En Trámite',
    'recepcion parcial':  'Recepción Parcial',
    'anulada':            'Anulada',
    'cerrada':            'Cerrada',
}

def norm_str(val):
    """Decodifica posible mojibake latin-1 y retorna string limpio."""
    if val is None or (isinstance(val, float) and str(val) == 'nan'):
        return ''
    s = str(val).strip()
    try:
        return s.encode('latin-1').decode('utf-8')
    except Exception:
        return s

def norm_estado(raw):
    key = norm_str(raw).lower()
    # Busca coincidencia parcial
    for k, v in ESTADO_NORM.items():
        if k in key:
            return v
    return norm_str(raw)

def crear_esquema(cur):
    cur.executescript("""
        CREATE TABLE IF NOT EXISTS bodega_recepciones (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            anio             INTEGER,
            numero           INTEGER,
            rut_proveedor    TEXT,
            proveedor        TEXT,
            fecha_ingreso    TEXT,
            estado           TEXT,
            cod_licitacion   TEXT,
            codigo_oc        TEXT,
            precio_total     REAL DEFAULT 0,
            bodega           TEXT,
            usuario          TEXT,
            tipo_oc          TEXT,
            anio_consumo     INTEGER,
            mes_consumo      INTEGER,
            cargado_en       TEXT DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_bod_oc     ON bodega_recepciones(codigo_oc);
        CREATE INDEX IF NOT EXISTS idx_bod_estado ON bodega_recepciones(estado);
        CREATE INDEX IF NOT EXISTS idx_bod_tipo   ON bodega_recepciones(tipo_oc);
        CREATE INDEX IF NOT EXISTS idx_bod_anio   ON bodega_recepciones(anio_consumo);
        CREATE INDEX IF NOT EXISTS idx_bod_bodega ON bodega_recepciones(bodega);
    """)

def importar(cur, excel_path):
    df = pd.read_excel(excel_path, dtype=str)
    total = 0
    for _, row in df.iterrows():
        cols = row.tolist()
        if len(cols) < 9:
            continue

        anio    = int(cols[0]) if cols[0] not in (None,'nan','') else None
        numero  = int(float(cols[1])) if cols[1] not in (None,'nan','') else None
        rut     = norm_str(cols[2])
        prov    = norm_str(cols[3])
        fecha   = norm_str(cols[4])
        estado  = norm_estado(cols[5])
        cod_lic = norm_str(cols[6]) if cols[6] not in (None,'nan','') else None
        cod_oc  = norm_str(cols[7]) if cols[7] not in (None,'nan','') else None
        if cod_oc == '':
            cod_oc = None
        try:
            precio = float(str(cols[8]).replace(',','').strip()) if cols[8] not in (None,'nan','') else 0.0
        except ValueError:
            precio = 0.0
        bodega   = norm_str(cols[9]).strip() if len(cols) > 9 else ''
        usuario  = norm_str(cols[10]) if len(cols) > 10 else ''
        tipo_oc  = norm_str(cols[11]) if len(cols) > 11 else ''
        try:
            anio_c = int(float(cols[12])) if len(cols) > 12 and cols[12] not in (None,'nan','') else None
        except (ValueError, TypeError):
            anio_c = None
        try:
            mes_c  = int(float(cols[13])) if len(cols) > 13 and cols[13] not in (None,'nan','') else None
        except (ValueError, TypeError):
            mes_c  = None

        cur.execute("""
            INSERT INTO bodega_recepciones
                (anio, numero, rut_proveedor, proveedor, fecha_ingreso, estado,
                 cod_licitacion, codigo_oc, precio_total, bodega, usuario,
                 tipo_oc, anio_consumo, mes_consumo)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        """, (anio, numero, rut, prov, fecha, estado, cod_lic, cod_oc,
              precio, bodega, usuario, tipo_oc, anio_c, mes_c))
        total += 1
    return total

def main():
    reset = '--reset' in sys.argv
    excel_path = EXCEL_DEF
    if '--file' in sys.argv:
        idx = sys.argv.index('--file')
        if idx + 1 < len(sys.argv):
            excel_path = sys.argv[idx + 1]

    if not os.path.exists(excel_path):
        print(f'ERROR: No se encontró {excel_path}', flush=True)
        return 1

    conn = sqlite3.connect(DB_PATH)
    cur  = conn.cursor()

    if reset:
        cur.execute('DROP TABLE IF EXISTS bodega_recepciones')
        print('Tabla bodega_recepciones eliminada.', flush=True)

    crear_esquema(cur)
    conn.commit()

    n = importar(cur, excel_path)
    conn.commit()
    conn.close()

    print(f'ETL Bodega finalizado: {n} registros importados desde {os.path.basename(excel_path)}', flush=True)
    return 0

if __name__ == '__main__':
    sys.exit(main())
