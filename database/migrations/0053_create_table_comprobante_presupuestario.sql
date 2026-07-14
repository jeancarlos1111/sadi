-- Migración: comprobante_presupuestario
-- Generada: 2026-07-13 01:39:34

-- PROCESOS PRESUPUESTARIOS: COMPROBANTES Y MOVIMIENTOS
CREATE TABLE IF NOT EXISTS comprobante_presupuestario (
    id_comprobante SERIAL PRIMARY KEY,
    acronimo_c TEXT NOT NULL,          -- Ej: 'AAP' (Apertura), 'TR' (Traspaso), 'CA' (Crédito Adicional)
    numero_c TEXT NOT NULL,            -- Correlativo string
    fecha_c TEXT NOT NULL,
    denominacion_c TEXT NOT NULL,
    referencia_c TEXT,                 -- Reemplazo de referenci interna SIGAFS
    beneficiario_cedula TEXT,          -- Relacionado a pagos
    estado TEXT DEFAULT 'APROBADO',
    eliminado BOOLEAN DEFAULT false
);
