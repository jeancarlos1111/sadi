-- Migración: orden_de_compra
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS orden_de_compra (

    id_orden_de_compra SERIAL PRIMARY KEY,
    fecha_odc TEXT,
    concepto_odc TEXT,
    id_proveedor INTEGER,
    porcentaje_iva_odc REAL,
    monto_base_odc REAL,
    monto_iva_odc REAL,
    monto_total_odc REAL,
    contabilizada BOOLEAN DEFAULT false,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE SET NULL
);
