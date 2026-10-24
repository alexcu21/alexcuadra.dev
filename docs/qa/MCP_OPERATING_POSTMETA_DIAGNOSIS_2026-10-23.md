# Diagnóstico acotado de postmeta — ensayo local

Resultado:4 casos nuevos correctos, además del circuito previo de12 escenarios y2 del diagnóstico general. [Recibo sintético](MCP_OPERATING_POSTMETA_DIAGNOSIS_2026-10-23.json). No sumar ejecuciones repetidas como casos nuevos del candidato. Laboratorio `/private/tmp/ac-source-effects-ff3vnnhc`, detenido al finalizar; recursos previos/respaldo conservados.

Se ensayó el script exacto con el perfil completo verificado y rc.3 sellado instalado/activo, env production simulado, Projects/propuesta/cuentas sintéticos. No SQL/configuración privada importados, SSH, CMS real, descargas ni respaldo nuevo. No se modificó el ZIP `d0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30`.

1. Tabla idéntica: dos recibos con baselinePostmetaMatches=true y observaciones coincidentes.
2. Adición sintética de `_edit_lock`: detecta deriva solo en postmeta, identifica la fila/categoría/formato sin valor y reproduce exactamente el hash original al excluirla únicamente en memoria.
3. Segunda fila añadida con nombre/valor sintético sensible: reproduce el hash al excluir el sufijo de dos filas; no exporta ese nombre/valor y no acepta la diferencia ni borra nada.
4. Actualización de un valor ACF existente: detecta deriva solo en postmeta y declara que no pudo reconstruir la baseline con su búsqueda de adiciones. No convierte una actualización en una adición admisible.

En todos los casos, capacidades esperadas conservadas, dos observaciones consecutivas coincidentes, ninguna escritura CMS/aceptación/baseline reemplazada, recibo final obligatorio. El fixture verifica que los bytes de la baseline original no cambian. Las mutaciones y limpieza que permiten montar los casos son exclusivamente del fixture sintético; no están en el diagnóstico.

Límites: búsqueda de una fila o sufijos entre las32 filas más recientes por meta_id, máximo5000 filas/16MiB de valores. Solo devuelve categorías fijas e IDs para candidatos; no valores ni claves arbitrarias. Coincidencia de hash identifica un conjunto de filas añadidas compatible con la baseline; no el autor ni el motivo. Sin coincidencia no permite recuperar valores viejos. La estabilidad de dos snapshots no certifica pausa de escritores externos. No se ha reproducido aún la causa de la diferencia real de Alex ni certificado su resolución.

Comando: `AC_SOURCE_EFFECTS_REUSE_NETWORK=ac-source-effects-ogxctuhl_default AC_SOURCE_EFFECTS_PACKAGE=local-rc3 python3 tests/integration/wordpress-source-effects/run.py --full-profile --scopes-only`. Red interna existente sin puertos públicos, imágenes locales sin pull;32GiB libres redondeados antes/después. Sintaxis shell y diff --check correctos. Nuevos scripts/helpers/observaciones pequeños; Docker puede usar disco/RAM/swap. No se podan recursos ni se toca el respaldo privado.
