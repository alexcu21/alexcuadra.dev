# Corroboración editorial con evidencia histórica — lectura independiente

Resultado:6 casos nuevos correctos y18 regresiones(12 scopes/aceptación/revocación,2 diagnóstico general,4 postmeta) correctas. [Recibo sintético](MCP_OPERATING_EDITORIAL_EVIDENCE_2026-10-23.json). No sumarlos como si se hubiera ejecutado de nuevo el conjunto de597 del candidato. Laboratorio final `/private/tmp/ac-source-effects-3tjkcnv1`, detenido; recursos/respaldo conservados. ZIP rc.3 sin cambios.

El comprobador de entrega fija la huella anterior de14Projects `c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af` y la identidad/base/versiones previamente registradas de propuesta227, sin overrides de producción. Reproduce la consulta/orden/serialización de la huella original: filas de Projects, todos sus metadatos y relaciones. En READ ONLY/REPEATABLE READ con conexión nativa y barrera, contrasta además patches de cabecera/historial de la propuesta con las respuestas del ledger, exigiendo que el ledger conserve su hash de la baseline de concesión. Solo devuelve booleanos/conteos; no cuerpos/patches/valores privados.

Casos:

1. Script exacto ejecutado en la base sintética: no confunde su huella ni sus IDs/versiones sintéticos con los hechos fijados del hosting y no hace aceptación.
2. Evidencia sintética capturada antes de cambios: proyectos, identidad/versiones y patches contra ledger coinciden.
3. Añadir un lock a la propuesta privada conserva la evidencia de Projects/patches, pero sigue informando la deriva global de postmeta como **no resuelta**.
4. Alterar un metadato protegido de Project hace fallar la huella independiente.
5. Alterar patch de cabecera/historial conservando el mismo token de versión se detecta al compararlo con el ledger.
6. Alterar una respuesta del ledger invalida la corroboración aunque el payload siga siendo válido.

Los casos2–6 invocan las mismas funciones con hechos sintéticos fijados antes de las mutaciones; no cambian las constantes del script de entrega. La base sintética vacía carece de la configuración ACF de tipos del hosting: el fixture positivo registra el tipo Project público solo en su proceso, equivalente al fixture local anterior. Esto no se añadió al comprobador real ni al paquete. No certifica la configuración privada del hosting ni una aplicación/recuperación/MCP HTTP productivos. La lectura real informará false si su tipo/contexto no permite validar la propuesta.

Límites: una huella de todos los metadatos de Projects incluye también locks; su diferencia no identifica qué campo cambió. Coincidencia sí corrobora esas filas completas frente a la evidencia anterior. Las respuestas del ledger no reconstruyen fechas de historial ni todos los bytes arbitrarios del payload; esos límites se informan expresamente. No identifica el escritor, no resuelve por sí misma la deriva global de postmeta y no la acepta. Ningún resultado reemplaza baseline o ejecuta aceptación automáticamente.

Comando local: `AC_SOURCE_EFFECTS_REUSE_NETWORK=ac-source-effects-ogxctuhl_default AC_SOURCE_EFFECTS_PACKAGE=local-rc3 python3 tests/integration/wordpress-source-effects/run.py --full-profile --scopes-only`. Imágenes locales/red interna sin puertos, sin pull/SSH/SQL o configuración real importados, sin nuevo respaldo.32GiB libres redondeados al principio y durante el ensayo; Docker puede consumir disco/RAM/swap. Generación/sintaxis/diff --check correctos. No se certifica todavía la salida real de la nueva lectura.
