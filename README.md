# SIGEBI - Actividad 3: Transformación progresiva del código

Refactorización de los fragmentos F1 a F4 del caso (Biblioteca Universitaria Villa Verde).

## Estructura
- `original/`  Código ORIGINAL del caso (F1 a F4), congelado y sin modificar.
- `src/`       Código refactorizado (namespace `Sigebi\`), compatible con PHP 7.4+.
- `tests/Original/`  Pruebas de CARACTERIZACIÓN: fijan el comportamiento del código original.
- `tests/Refactor/`  Pruebas del código refactorizado y de equivalencia con el original.
- `evidencias/`  Salida de pruebas, métricas, equivalencia (240 combinaciones) e historial de Git.

## Cómo ejecutar las pruebas
Requisitos: PHP 7.4 o superior con `pdo_sqlite`, y PHPUnit 9.
```
phpunit                       # todas las pruebas (49)
phpunit --testsuite original  # solo el código original (19)
phpunit --testsuite refactor  # solo el código refactorizado (30)
phpunit --testdox             # lista legible de pruebas
git log --oneline             # historial de cambios (6 commits)
```
Nota: las funciones `mysql_*` del código original no existen desde PHP 7. `tests/FakeMysql.php`
las simula sobre SQLite en memoria para poder ejecutar el original sin modificarlo.
