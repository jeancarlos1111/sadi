-- Migración: add_cuenta_to_bancos
-- Propósito: Relacionar la cuenta bancaria física con la cuenta contable de activo (Ej: 1.1.1.02.01 Bancos) para permitir asientos automáticos de pago.

ALTER TABLE cta_bancaria
ADD COLUMN IF NOT EXISTS id_cuenta_contable INTEGER REFERENCES cuenta_contable(id_cuenta_contable) ON DELETE SET NULL;
