-- Migración: vinculacion_puc_contable
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS vinculacion_puc_contable (

            id_vinculacion SERIAL PRIMARY KEY,
            id_codigo_plan_unico INTEGER NOT NULL REFERENCES plan_unico_cuentas(id_codigo_plan_unico),
            id_cuenta_contable INTEGER NOT NULL REFERENCES cuenta_contable(id_cuenta_contable),
            tipo_operacion VARCHAR(50) NOT NULL, -- Ej: CAUSADO, PAGADO
            descripcion TEXT,
            eliminado BOOLEAN DEFAULT false,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_codigo_plan_unico) REFERENCES plan_unico_cuentas(id_codigo_plan_unico) ON DELETE SET NULL,
    FOREIGN KEY (id_cuenta_contable) REFERENCES cuenta_contable(id_cuenta_contable) ON DELETE SET NULL
);
