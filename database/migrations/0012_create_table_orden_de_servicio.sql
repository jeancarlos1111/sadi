-- Migración: orden_de_servicio
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS orden_de_servicio (

    id_orden_de_servicio SERIAL PRIMARY KEY,
    fecha_os DATE,
    concepto_os TEXT,
    id_proveedor INTEGER,
    porcentaje_iva_os REAL,
    monto_base_os REAL,
    monto_iva_os REAL,
    monto_total_os REAL,
    contabilizada BOOLEAN DEFAULT false,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE SET NULL
);
