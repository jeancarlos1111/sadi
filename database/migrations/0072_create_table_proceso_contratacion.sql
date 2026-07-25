-- Migración: create_table_proceso_contratacion
-- Fase 2: Módulo LCP

CREATE TABLE IF NOT EXISTS proceso_contratacion (
    id_proceso SERIAL PRIMARY KEY,
    numero_expediente TEXT UNIQUE NOT NULL,
    descripcion TEXT NOT NULL,
    modalidad TEXT NOT NULL,
      -- CONSULTA_DE_PRECIOS | CONCURSO_CERRADO | CONCURSO_ABIERTO | CONTRATACION_DIRECTA
    monto_estimado NUMERIC(15,2) NOT NULL DEFAULT 0,
    id_orden_de_compra INTEGER REFERENCES orden_de_compra(id_orden_de_compra),
    justificacion_legal TEXT,       -- Obligatorio para CONTRATACION_DIRECTA
    crs_aplicable BOOLEAN DEFAULT false,
    numero_crs TEXT,                -- Compromiso de Responsabilidad Social
    estatus TEXT DEFAULT 'ABIERTO', -- ABIERTO | EVALUACION | ADJUDICADO | DESIERTO | ANULADO
    fecha_apertura DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_cierre DATE,
    id_usuario_creador INTEGER REFERENCES usuario(id_usuario),
    eliminado BOOLEAN DEFAULT false,
    creado_el TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
