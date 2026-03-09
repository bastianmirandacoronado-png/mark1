from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from database import get_connection

app = FastAPI(title="Mark1 API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.get("/")
def root():
    return {"mensaje": "Mark1 API funcionando ✅"}

@app.get("/oc/resumen")
def get_oc_resumen():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM oc_resumen").fetchall()
    return [dict(row) for row in rows]

@app.get("/oc/detalles")
def get_oc_detalles():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM oc_detalles").fetchall()
    return [dict(row) for row in rows]

@app.get("/etl/control")
def get_etl_control():
    conn = get_connection()
    rows = conn.execute("SELECT * FROM etl_control").fetchall()
    return [dict(row) for row in rows]