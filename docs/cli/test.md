# Comando: `test`

Envoltura (Wrapper) para la ejecución de la suite de pruebas del proyecto SADI utilizando el framework Pest PHP. 

Invocar `test` a través de CLI es equivalente a ejecutar manualmente `vendor/bin/pest`, pero de forma más cómoda e integrada.

## Ejemplos de Uso

### 1. Correr toda la suite de pruebas
```bash
php sadi test
```

### 2. Filtrar y correr una prueba específica
```bash
php sadi test --filter=EjecucionTest
```

### 3. Activar procesamiento en paralelo
```bash
php sadi test --parallel
```

### 4. Generar reporte de cobertura de código (Coverage)
```bash
php sadi test --coverage
```

[⬅ Volver al Índice](README.md)
