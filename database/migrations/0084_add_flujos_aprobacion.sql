-- Migración: add_flujos_aprobacion
-- Agrega campos de estado de aprobación a documentos críticos y crea tabla de historial

ALTER TABLE orden_de_compra ADD COLUMN IF NOT EXISTS estado_aprobacion TEXT DEFAULT 'ELABORACION';
ALTER TABLE solicitud_pago ADD COLUMN IF NOT EXISTS estado_aprobacion TEXT DEFAULT 'ELABORACION';
ALTER TABLE planilla_nomina ADD COLUMN IF NOT EXISTS estado_aprobacion TEXT DEFAULT 'ELABORACION';

CREATE TABLE IF NOT EXISTS historial_aprobacion (
    id_historial SERIAL PRIMARY KEY,
    tipo_documento TEXT NOT NULL, -- ej: 'ORDEN_COMPRA', 'SOLICITUD_PAGO', 'NOMINA'
    id_documento INTEGER NOT NULL,
    estado_anterior TEXT NOT NULL,
    estado_nuevo TEXT NOT NULL,
    id_usuario INTEGER NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    comentarios TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_historial_aprobacion_doc ON historial_aprobacion (tipo_documento, id_documento);
