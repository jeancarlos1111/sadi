-- Migración: documento
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS documento (

    id_documento SERIAL PRIMARY KEY,
    id_orden_de_compra INTEGER, 
    id_orden_de_servicio INTEGER,
    nro_documento_d TEXT,
    nro_control_d TEXT,
    fecha_emision_d TEXT,
    fecha_vencimiento_d TEXT,
    id_proveedor INTEGER,
    id_tipo_documento INTEGER,
    monto_base_d REAL,
    monto_impuesto_d REAL,
    monto_total_d REAL,
    observacion_d TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_orden_de_compra) REFERENCES orden_de_compra(id_orden_de_compra) ON DELETE SET NULL,
    FOREIGN KEY (id_orden_de_servicio) REFERENCES orden_de_servicio(id_orden_de_servicio) ON DELETE SET NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE SET NULL,
    FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento(id_tipo_documento) ON DELETE SET NULL
);
