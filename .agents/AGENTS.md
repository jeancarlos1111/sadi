
# SADI Buenas Prácticas y Reglas de Desarrollo
Debes cumplir ESTRICTAMENTE las siguientes buenas prácticas en todo el código PHP y vistas PHTML:
- **Tipado estricto:** Incluir `declare(strict_types=1);` al inicio de TODOS los archivos PHP.
- **DTOs Inmutables:** Usar clases `readonly` para los Modelos/DTOs. No incluir lógica de negocio en ellos (separación de responsabilidades).
- **Serialización en DTOs:** Implementar siempre métodos `fromArray()` y `toArray()` en los modelos/DTOs.
- **Seguridad en Vistas:** Usar siempre `htmlspecialchars()` al imprimir variables en las vistas (`.phtml`) para prevenir XSS.
- **Autocompletado IDE:** Incluir bloques de documentación `@var` al inicio de las vistas `.phtml` para tipar las variables inyectadas.
