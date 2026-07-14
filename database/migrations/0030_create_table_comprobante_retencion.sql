-- Migración: comprobante_retencion
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS comprobante_retencion (

    id_comprobante_retencion SERIAL PRIMARY KEY,
    id_factura INTEGER,
    tipo_retencion TEXT, -- IVA, ISLR, 1X1000
    numero_comprobante TEXT,
    porcentaje REAL DEFAULT 0,
    monto_retenido REAL DEFAULT 0,
    fecha_emision TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_factura) REFERENCES factura(id_factura) ON DELETE SET NULL
);
