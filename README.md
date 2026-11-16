# SIGEBI - Actividad 3: Transformación progresiva del código

Refactorización de los fragmentos F1 a F4 del caso (Biblioteca Universitaria Villa Verde).

## Estructura
- `original/`  Código ORIGINAL del caso (F1 a F4), congelado y sin modificar.
- `src/`       Código refactorizado (namespace `Sigebi\`), compatible con PHP 7.4+.
- `tests/Original/`  Pruebas de CARACTERIZACIÓN: fijan el comportamiento del código original.
- `tests/Refactor/`  Pruebas del código refactorizado y de equivalencia con el original.
- `evidencias/`  Salida de pruebas, métricas, equivalencia (240 combinaciones) e historial de Git.

## Aplicación web (el software funcionando)
Pantallas para registrar préstamos, devolver (con multa), renovar y ver usuarios, ejemplares y configuración.
Toda la lógica la hacen las clases refactorizadas de `src/`; `public/index.php` solo las llama y muestra el resultado.
La base de datos es un archivo SQLite (`datos/sigebi.sqlite`) que se crea solo con datos de ejemplo.
```
php -S localhost:8000 -t public      # luego abrir http://localhost:8000
```
En Windows también basta con doble clic en `iniciar.bat`.
- **Fecha de operación** (arriba a la derecha): simula otro día para ver cómo crecen las multas.
- **Configuración → Reiniciar datos**: vuelve al estado inicial.
- `app/` contiene la conexión a la base de datos y los listados; no modifica nada de `src/`.

## Demostración en vivo (Actividad 4)
Ejecuta el código original y el refactorizado con los mismos datos y muestra el resultado lado a lado.
No necesita MySQL ni PHPUnit, solo PHP 7.4+ con `pdo_sqlite`.
```
php demo.php          # las 5 escenas seguidas
php demo.php pausa    # espera ENTER entre escenas (para presentar)
php demo.php 4        # solo una escena (1 a 5)
```
1. Multa duplicada: devolver() y renovar() cobraban distinto; ahora cobran lo mismo.
2. Los 7 casos del enunciado: iguales salvo las dos multas negativas corregidas.
3. Equivalencia en 240 combinaciones (235 idénticas, 5 negativas corregidas, 0 otras).
4. Inyección SQL en el registro de préstamos: antes pasaba, ahora se rechaza.
5. Configuración con nombres en lugar de dato1…dato70.

La salida de referencia está en `evidencias/demo_salida.txt`.

## Cómo ejecutar las pruebas
Requisitos: PHP 7.4 o superior con `pdo_sqlite`, y PHPUnit 9.
```
phpunit                       # todas las pruebas (56)
phpunit --testsuite original  # solo el código original (19)
phpunit --testsuite refactor  # solo el código refactorizado (37)
phpunit --testdox             # lista legible de pruebas
phpunit --testsuite refactor --coverage-text   # cobertura (requiere PCOV o Xdebug)
git log --oneline             # historial de cambios
```
Nota: las funciones `mysql_*` del código original no existen desde PHP 7. `tests/FakeMysql.php`
las simula sobre SQLite en memoria para poder ejecutar el original sin modificarlo.

## Refactorización del cálculo de días vencidos (Taller final)
La fórmula de días vencidos estaba copiada en 3 lugares; ahora vive solo en `src/Multa/DiasVencidos.php` (Extract Class).
Las 49 pruebas anteriores pasan sin cambios y se agregaron 7 pruebas nuevas.
Comparación de cobertura antes/después: `evidencias/cobertura/COMPARACION.md`.
