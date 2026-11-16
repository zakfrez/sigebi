# Refactorización: cálculo de días vencidos

## Problema (antes)
La misma fórmula estaba **copiada en 3 lugares**:

| Archivo | Código |
|---|---|
| `src/Circulacion/DevolucionAction.php` | `round((strtotime($hoy) - strtotime($fechaVencimiento)) / 86400)` (método privado) |
| `src/Circulacion/RenovacionAction.php` | `round((strtotime($hoy) - strtotime($prestamo['fecha_venc'])) / 86400)` (dentro de `renovar()`) |
| `public/index.php` | `round((strtotime($hasta) - strtotime($desde)) / 86400)` (función `diasEntre`) |

Si había que corregir la fórmula, había que acordarse de cambiarla en 3 sitios.
Además, al ser un método privado, **no se podía probar por separado**.

## Técnica aplicada
**Extract Class**: nueva clase `src/Multa/DiasVencidos.php` con un solo método
`DiasVencidos::entre($fechaVencimiento, $hoy)`. Los 3 lugares ahora la llaman.
El número mágico `86400` pasa a ser la constante `SEGUNDOS_POR_DIA`.

## ¿Se conservó el comportamiento?
- Las **49 pruebas existentes pasan sin modificar ninguna**.
- La equivalencia con el código original (240 combinaciones) da el mismo resultado: 235 idénticas, 5 multas negativas corregidas, 0 otras diferencias.

## Cobertura de pruebas (PHPUnit 9.6 + PCOV, carpeta `src/`)

| Métrica | Antes | Después | Análisis |
|---|---|---|---|
| Cobertura de líneas | 99,10 % (110/111) | 99,10 % (110/111) | Se mantiene: ya era alta. La única línea sin cubrir es `VistaCirculacion::urlPagoMulta()`, ajena a este cambio. |
| Cobertura de métodos | 97,83 % (45/46) | 97,83 % (45/46) | Igual. |
| Clases | 10 | 11 | Se agrega `DiasVencidos`, cubierta al 100 %. |
| Pruebas totales | 49 | 56 | +7 pruebas nuevas. |
| Aserciones | 1.324 | 1.421 | +97 verificaciones. |
| Pruebas **directas** del cálculo de días | 0 (solo indirectas) | 7 | ✅ Se fortalece la validación: atraso, vence hoy, no vence, cambio de mes, cambio de año, año bisiesto y equivalencia con la fórmula anterior. |
| Copias de la fórmula | 3 | 1 | ✅ Un solo lugar para mantener. |

Reportes completos: `antes.txt` y `despues.txt` en esta carpeta.

## Cómo reproducirlo
```
phpunit                                         # 56 pruebas
phpunit --filter DiasVencidosTest --testdox     # las 7 pruebas nuevas
phpunit --testsuite refactor --coverage-text    # cobertura (requiere PCOV o Xdebug)
```
