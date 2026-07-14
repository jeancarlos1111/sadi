-- Migración: factura
-- Generada: 2026-07-13 01:39:34

-- RETENCIONES E IMPUESTOS
CREATE TABLE IF NOT EXISTS factura (

    id_factura SERIAL PRIMARY KEY,
    id_proveedor INTEGER,
    numero_factura TEXT,
    fecha_factura TEXT,
    monto_base REAL DEFAULT 0,
    monto_impuesto REAL DEFAULT 0,
    monto_total REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE SET NULL
);
