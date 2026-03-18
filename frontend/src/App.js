import React, { useEffect, useState } from 'react';
import axios from 'axios';

function App() {
  const [cotizaciones, setCotizaciones] = useState([]);

  useEffect(() => {
    // Llamamos a tu API de Django
    axios.get('http://127.0.0.1:8000/api/cotizaciones/')
      .then(response => {
        setCotizaciones(response.data);
      })
      .catch(error => {
        console.error("Error al conectar con el Backend:", error);
      });
  }, []);

  return (
    <div style={{ padding: '20px', fontFamily: 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif' }}>
      <header style={{ marginBottom: '30px', borderBottom: '2px solid #005c97', paddingBottom: '10px' }}>
        <h1 style={{ color: '#005c97' }}>Mark 1: Control Interno SSO</h1>
        <p>Seguimiento de Cotizaciones - Mercado Público</p>
      </header>

      <table style={{ width: '100%', borderCollapse: 'collapse', boxShadow: '0 2px 5px rgba(0,0,0,0.1)' }}>
        <thead>
          <tr style={{ backgroundColor: '#005c97', color: 'white', textAlign: 'left' }}>
            <th style={{ padding: '12px' }}>ID Mercado Público</th>
            <th style={{ padding: '12px' }}>Nombre Licitación</th>
            <th style={{ padding: '12px' }}>Estado</th>
            <th style={{ padding: '12px' }}>Monto Disponible</th>
          </tr>
        </thead>
        <tbody>
          {cotizaciones.map((item, index) => (
            <tr key={index} style={{ borderBottom: '1px solid #ddd' }}>
              <td style={{ padding: '12px' }}>{item.id_cotizacion}</td>
              <td style={{ padding: '12px' }}>{item.nombre}</td>
              <td style={{ padding: '12px' }}>
                <span style={{
                  backgroundColor: item.estado === 'Publicada' ? '#e1f5fe' : '#fff3e0',
                  padding: '4px 8px', borderRadius: '4px', fontSize: '0.9em'
                }}>
                  {item.estado}
                </span>
              </td>
              <td style={{ padding: '12px' }}>${item.monto_disponible}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

export default App;