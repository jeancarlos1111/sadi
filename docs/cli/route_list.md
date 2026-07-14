# Comando: `route:list`

Herramienta de diagnóstico que permite visualizar rápidamente todos los "Endpoints" o rutas web configuradas en la aplicación.

SADI utiliza un enrutador básico en `public/index.php`. El comando analiza este archivo y detecta el patrón `?route=modulo/accion` y la instrucción `switch/case` o el mapeo de arreglos, renderizando una tabla en consola.

## Ejemplos de Uso

### 1. Listar rutas del sistema
```bash
php sadi route:list
```

**Ejemplo de salida:**
```text
Rutas detectadas (switch/case)
──────────────────────────────
┌───────────────────────┬──────┐
│ Ruta (?route=)        │ Tipo │
┼───────────────────────┼──────┼
│ home/index            │ case │
│ presupuesto/index     │ case │
│ cuentas_por_pagar/... │ case │
└───────────────────────┴──────┘
```

[⬅ Volver al Índice](README.md)
