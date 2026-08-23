# Informe base de avance — Cote France

**Fecha de corte:** 23 de agosto de 2026  
**Rama analizada:** `fracturacion`  
**Commit analizado:** `c4e2317`  
**Informe completo:** [Informe_avance_Cote_France_2026-08-23.docx](./Informe_avance_Cote_France_2026-08-23.docx)

## Objetivo de este archivo

Este documento conserva la línea base del informe de avance para que las siguientes revisiones puedan comparar cambios de alcance, implementación y evidencia. Los porcentajes deben recalcularse a partir del backlog vigente y validarse contra el código, las migraciones y la base de datos más reciente.

## Resumen de la línea base

| Indicador | Resultado al corte | Criterio |
|---|---:|---|
| Avance del roadmap completo | 26 de 167 tareas (15,6 %) | Conteo de tareas implementadas frente al backlog completo |
| Avance del MVP | 26 de 121 tareas (21,5 %) | Conteo de tareas implementadas frente al alcance MVP |
| Avance ponderado por esfuerzo | Aproximadamente 15 % | Entre 55 y 77 horas evidenciadas sobre 415–485 horas estimadas |
| Épicas completadas en código | 3 | Épicas 1, 2 y 3 |
| Migraciones en código | 52 | Conteo en la rama analizada |
| Migraciones aplicadas en el respaldo | 49 | Respaldo de base de datos del 14 de agosto de 2026 |
| Inscripciones históricas observadas | 368 | Evidencia contenida en el respaldo analizado |

## Avances confirmados

- La base funcional de las épicas 1 a 3 se encuentra implementada en código.
- Existe estructura para inscripción, configuración relacionada y persistencia de datos necesaria para continuar con facturación.
- El proyecto cuenta con historial operativo en la base de datos, incluyendo 368 inscripciones observadas.
- La arquitectura existente permite iniciar la épica 4 sin rehacer las funcionalidades ya completadas.

## Dificultades y riesgos abiertos

| Tema | Situación al corte | Impacto |
|---|---|---|
| Desfase de migraciones | El respaldo tiene 49 migraciones aplicadas y el código contiene 52 | El entorno analizado no representa exactamente el estado de la rama |
| Facturación operativa | No se evidenciaron cargos ni pagos operativos | Las épicas iniciales están listas, pero todavía no existe un flujo completo de cobro |
| Validación integral | La estimación se apoya en evidencia estática de código, backlog y respaldo | Hace falta validar el flujo en un entorno ejecutable con pruebas funcionales |
| Alcance restante | La mayor parte del roadmap y del MVP sigue pendiente | Se requiere priorización estricta para evitar dispersión |

## Próximo hito recomendado

Iniciar la **épica 4** y convertir la base existente en un flujo verificable de facturación. Antes de medir un nuevo incremento de avance:

1. Alinear las migraciones del entorno con las 52 migraciones presentes en código.
2. Definir criterios de aceptación para generación de cargos, registro de pagos y trazabilidad.
3. Ejecutar pruebas funcionales sobre datos representativos.
4. Actualizar el backlog con estado, evidencia y esfuerzo real por tarea.

## Fuentes de la línea base

- Propuesta de facturación de Cote France.
- Backlog de desarrollo propuesto.
- Rama `fracturacion` del repositorio, commit `c4e2317`.
- Respaldo SQL con fecha de referencia 14 de agosto de 2026.

## Procedimiento para el siguiente informe

1. Crear un nuevo archivo con el formato `AAAA-MM-DD-informe-avance-base.md`; no sobrescribir esta línea base.
2. Registrar la rama, el commit y la fecha exacta de cada fuente analizada.
3. Comparar el backlog anterior y el vigente; documentar tareas agregadas, eliminadas o redefinidas.
4. Verificar cada tarea declarada como completa contra código, migraciones y pruebas.
5. Recalcular por separado:
   - avance del roadmap por conteo de tareas;
   - avance del MVP por conteo de tareas;
   - avance ponderado por esfuerzo.
6. Explicar cualquier diferencia respecto a los porcentajes de este corte.
7. Registrar dificultades nuevas, riesgos cerrados y evidencias pendientes.
8. Generar el informe formal y enlazarlo desde el nuevo archivo base.

## Plantilla de comparación

| Indicador | Corte anterior | Corte actual | Variación | Evidencia |
|---|---:|---:|---:|---|
| Roadmap completo | 15,6 % | — | — | — |
| MVP | 21,5 % | — | — | — |
| Esfuerzo ponderado | ~15 % | — | — | — |
| Tareas completadas | 26 | — | — | — |
| Migraciones en código | 52 | — | — | — |
| Migraciones aplicadas | 49 | — | — | — |

> Nota: un aumento del número total de tareas puede reducir el porcentaje aun cuando se haya completado trabajo adicional. Por eso, cada informe debe mostrar tanto cantidades absolutas como porcentajes.
