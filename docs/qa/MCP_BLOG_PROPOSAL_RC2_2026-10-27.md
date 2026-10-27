# Candidato de propuestas rc.2 — 27 octubre 2026

Nuevo paquete reproducible de ocho archivos/17.693bytes, SHA256 `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`. rc.1, su directorio de fuentes, ZIP, manifiesto y diagnósticos originales se conservan. Los cuatro archivos de negocio y abilities.php son idénticos a rc.1; cambios en main/version, admission y README. [Procedimiento y límites operativos](../WORDPRESS_MCP_BLOG_PROPOSAL_RC2.md).

## Verificación

- [Recibo funcional](blog-proposal-rc2-functional-2026-10-27.json):72operaciones WordPress/MariaDB/Polylang,34fallos de conexión/proceso y43MCP/HTTP correctas. Además29contratos/DTOs y10pruebas de admisión sin excepción local. Se verificó cada entrada del ZIP antes de instalar sus bytes mediante extracción del harness.
- [Recibo de ciclo del ZIP instalado con WP-CLI](blog-proposal-rc2-lifecycle-2026-10-27.json):nueve preflights; ocho ready=true y uno ready=false esperado tras añadir un índice no admitido. Treinta checks adicionales de perfil, almacenamiento, propietario, scope, respaldo/restauración y conservación. Arranque46checks antes de cada ejecución; no sumarlos como casos funcionales nuevos.
- La admisión bloquea plugin activo desconocido, cambios de huella principal, MU desconocido, deriva del tema y esquema no admitido. La rama sin excepción local rechaza falta de opt-in, home/site ajenos, callback query desconocido y deriva del main propio; vuelve a admitir el perfil tras retirar las alteraciones sintéticas.
- Respaldo privado0600 de cuatro tablas propias con escritor pausado; restauración a un prefijo aislado. Conserva1propietario/1propuesta/2revisiones/2recibos, esquema, hashes y cabeza. Las filas privadas originales y las tablas editoriales no cambian durante restauración. No se probó API/replay contra el espacio restaurado, ni recuperación real del hosting.
- Tras retirada, se conservan los ocho permisos anteriores y el lector activo. No se concedió scope ni se instalaron componentes en el CMS real. Las credenciales y scopes de las pruebas HTTP/funcionales se retiraron según el recibo.

El ZIP se generó dos veces con SHA idéntico. Fuentes y diagnósticos PHP revisados con lint. No hay nueva compilación frontend porque no cambió Astro.

## Límites y siguiente paso

Se usa código de perfil previamente verificado, con configuración y datos nuevos sintéticos; sin SQL/wp-config/cuentas reales. PHP8.5.11 del laboratorio difiere del8.5.12 reportado por el servidor. Las huellas principales reales coinciden; no son una auditoría de árboles completos ni de opciones/cachés del hosting.

No hay aprobación de instalación, grant, escritor público o publicación; la nueva versión permanece candidata. Siguiente: preflight rc.2 real de solo lectura con su pareja de PHP separada; revisar antes de instalación inactiva. El procedimiento de respaldo/recuperación específico del hosting sigue pendiente antes de conceder escritura privada. Sin SSH del agente, deploy, correo real, auditorías históricas repetidas ni operación sobre propuesta227/revisión1.

## Recibo before real revisado

Alex aportó el [before rc.2](blog-proposal-rc2-destination-before-2026-10-27.json): ready=true, 15 comprobaciones principales y 11 del perfil correctas; SHA del ZIP coincide. Ausencia esperada de paquete/tablas/guard/scope, ocho capacidades previas conservadas y 16 permisos amplios denegados. No se hizo una nueva consulta remota independiente ni instalación. Siguiente: Alex instala el ZIP exacto inactivo y comparte after-install; no repetir before o fingerprint.

## Instalación inactiva real verificada

[After-install aportado por Alex](blog-proposal-rc2-destination-after-install-2026-10-27.json): ready=true, 15 comprobaciones principales/11 del perfil correctas y archivos exactos del ZIP. Continúan inactivo el plugin y el guard, sin tablas ni scope nuevo, con ocho capacidades previas. No hubo nueva consulta remota independiente del agente. Siguiente guard del sitio + activación + after-enable; no repetir instalación o diagnósticos anteriores.

## Activación real verificada

[After-enable de Alex](blog-proposal-rc2-destination-after-enable-2026-10-27.json): ready=true, 15 comprobaciones principales y 11 del perfil correctas. Plugin/guard activos y cinco herramientas registradas; sin tablas ni nuevo scope, con ocho capacidades anteriores. Siguiente creación explícita de cuatro tablas vacías mediante install() como operador1 existente y after-storage. El método exacto ya se ejercitó en el ciclo rc.2 de laboratorio. El agente no ejecuta DDL remoto; no hubo nueva inspección independiente del servidor.

## Almacenamiento real reconciliado

[After-storage de Alex](blog-proposal-rc2-destination-after-storage-2026-10-27.json): ready=true, cuatro tablas con esquema completo y esperado, sin propietarios o filas de propuestas/revisiones/recibos; ocho capacidades previas intactas. Esto verifica el estado actual, no identifica cuándo ni quién creó las tablas. No repetir install ni reparar/borrar. Siguiente enroll(2) por operador1 y after-owner, sin permiso nuevo. Método enroll ya ensayado en el ciclo rc.2; sin nueva ejecución remota del agente.
