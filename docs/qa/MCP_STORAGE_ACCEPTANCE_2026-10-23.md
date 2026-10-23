# Aceptación con snapshot global y barrera operativa — 23 octubre

50 comprobaciones de migración/aceptación inicial,144 del contrato y132 regresiones:326 integraciones correctas,9 casos añadidos. Perfil completo y rc.2 con datos sintéticos y WordPress/MariaDB/ACF/Polylang/LiteSpeed reales.

acceptStorage solo CLI manage_options+scope aprobación. Requiere externalWritersStopped===true y un máximo de1–100 propietarios. Comprueba ready/esquema y ejecuta auditoría completa de los propietarios en una transacción READ ONLY/REPEATABLE READ/WITH CONSISTENT SNAPSHOT. Sus consultas no usan FOR UPDATE. Una cantidad superior al máximo se rechaza; no se trunca ni se presenta un lote como aceptación global. Devuelve conteos y aceptación del snapshot; no persiste una habilitación ni modifica fuentes o recibos.

Barrera GET_LOCK por DB/prefijo en las transacciones coordinadas, migración y consumo de efectos. Mientras se acepta, las escrituras que usan este protocolo no entran. Los consumidores conservan not_claimed si la barrera está ocupada; no se ejecuta la purga ni cambia su lease. El modo mysqli, lock y filtro transaccional se restauran al salir. Los cuatro SIGKILL y las desconexiones siguen pasando como regresiones.

La pausa externa es una declaración del operador: rc.2, wp-admin, SQL directo y otros plugins no usan esta barrera. No se afirma bloquearlos ni certificar que estén detenidos. El snapshot ofrece coherencia de lectura InnoDB; la aceptación de destino exige coordinar esos escritores, y una aceptación local no habilita cambios públicos. No es una barrera global de todo WordPress ni un certificado permanente ante escrituras posteriores.

Nueve casos nuevos: rechazo sin declaración externa; aceptación vacía; aceptación/migración rechazadas con lock de otra sesión; UPDATE rechazado realmente por MariaDB1792 en READ ONLY; huérfanos rechazan aceptación; cadena con1 propietario/1 evento/2 requests aceptada; repetición idéntica y fuente intacta; consumidor concurrente no reclama ni purga. El fixture retira temporalmente/restaura sus huérfanos inyectados para probar el conjunto válido; el aceptador no repara ni borra.

La primera pasada detectó una regresión de respuesta del consumidor concurrente; la evidencia aceptada es la segunda ejecución completa tras restaurar not_claimed. No sumar runners repetidos. Pendiente: schema/transporte MCP operativo, export privado, empaquetado/admisión para destino, manifiesto/preflight/reversión y aprobación de instalación/scopes/diff. Se conservan las guardas locales; no hay candidato instalable todavía.

Imágenes cacheadas, red interna reutilizada y WP/DB tmpfs; sin SSH/pulls/puertos/SQL o config real/grants/CMS/Workers/contacto/backups nuevos. Contenedores detenidos; recursos anteriores retenidos.32GiB libres al inicio; Docker/swap pueden consumir disco.
