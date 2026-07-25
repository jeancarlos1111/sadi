-- Migración: auditoria_log
-- Generada: 2026-07-18

CREATE TABLE IF NOT EXISTS auditoria_log (
    id_log BIGSERIAL PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    accion VARCHAR(10) NOT NULL, -- INSERT, UPDATE, DELETE
    id_registro INTEGER,
    datos_antes JSONB,
    datos_despues JSONB,
    id_usuario INTEGER,
    usuario_nombre VARCHAR(100),
    ip_address INET,
    fecha_hora TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_auditoria_tabla_registro ON auditoria_log (tabla, id_registro);
CREATE INDEX IF NOT EXISTS idx_auditoria_usuario_fecha ON auditoria_log (id_usuario, fecha_hora);
