# Candidato de propuestas privadas — 22 octubre 2026

**0.1.0-rc.1 preparado para revisión; instalación en HOLD.** Seis abilities de propuestas existentes, sin ejecutar aplicación/recuperación de fuentes. No es la versión0.5-lab con un guard retirado: namespace, CPT, meta, tablas y opción propios, únicamente dos PHP. El laboratorio y Editorial Read se conservan sin modificar.

Actualización posterior22oct: [aceptación CLI/SQL y restauración sintética completadas](qa/MCP_PROPOSALS_SYNTHETIC_ACCEPTANCE_2026-10-22.md), **165 comprobaciones correctas** con Polylang3.9.1 confirmado por Alex. Caché pública verificada, sin CMS; ZIP candidato intacto. HTTP/GraphQL/carreras/perfil completo y backup real restaurado/retención siguen pendientes antes de instalar. La entrada inferior sobre bloqueo404 describe el intento previo, superado para el nuevo perfil3.9.1; no reescribe la evidencia3.9.0.

Actualización22oct: [aceptación aislada preparada e intentada](qa/MCP_PROPOSALS_ACCEPTANCE_PREPARATION_2026-10-22.md), detenida antes de instalar por seed tmpfs vacío y artefacto público Polylang3.9.0 no disponible (404). Código de archivo/restauración sintéticos listo, no ejecutado. Sigue HOLD; candidato/hash sin cambios. Alex confirmará metadata instalada mediante el comando del QA; no reemplazar silenciosamente versiones.

## Alcance revisable

Fuentes en `wordpress/plugins/alexcuadra-editorial-proposals`; contrato operativo y límites en su README. Artefactos `artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.1.zip` y manifiesto adjunto con SHA256 de ZIP y cada entrada. Se genera con `python3 scripts/package-editorial-proposals.py`, sin dependencias, red, CMS ni Docker; el ZIP solo contiene dos PHP y README de una allowlist fija. Sin credenciales/configuración/medios/dumps. Es candidato de revisión, no permiso de instalación.

Guard exacto y opt-in manual en configuración privada; sin scope automático ni creación de tablas al cargar. No se habilita el opt-in aquí. Scope mínimo nuevo eventual: alexcuadra_prepare_project_changes sobre la cuenta aprobada, conservando read y alexcuadra_read_public_projects. Sin Editor/administrador global ni scopes apply/approve/recover. No proporcionar un comando de concesión/instalación hasta disponer del resultado de respaldo/restauración y aceptación del paquete.

Las propuestas usan almacenamiento nuevo aditivo; no importan filas de0.3/0.4/0.5 ni reescriben recibos anteriores. Crear sus tablas es paso CLI explícito, con operador manage_options, distinto de las capacidades necesarias para usar las abilities. Las transacciones existentes se mantienen para propuestas/recibos; no garantizan aislamiento de efectos externos de WordPress. Crear una propuesta privada todavía invoca wp_insert_post para su propio CPT: probar hooks y transporte para ese CPT antes de certificar hosting.

## Archivo privado, respaldo y restauración antes de instalación

1. Elegir destino privado fuera del webroot/repositorio y responsable con acceso limitado. No enviar dumps o patches al chat. Definir con Alex plazo de conservación y mecanismo de eliminación; por ahora el candidato no borra nada y se detiene al llegar a cuota. No heredar30 días de contacto.
2. Preservar respaldo coherente de DB, archivos plugins/MU/temas y configuración ACF/CPT, manifiesto de versiones/fecha/hashes, con configuración/secretos separados y protegidos. Verificar respaldo del proveedor si se usa; existencia/tamaño de tablas no equivale a respaldo. Guardar manifiestos públicos solo sin datos privados.
3. Restaurar en destino aislado aprobado, con correo/webhooks/trabajos externos bloqueados antes del primer bootstrap y sin conexión de aplicación al CMS compartido. Revisar cómo se aplica el guard exacto sin cambiar el destino real ni crear bypass en el paquete; una prueba local de guards con stubs no equivale a esa aceptación. No clonar secretos de producción innecesarios.
4. Para el archivo editorial preservar juntos filas CPT/meta, owners/requests y opción de esquema; guardar orden/IDs, payloads, recibos exactos, relaciones y hashes. Usar snapshot consistente de DB y ensayo de restauración; no ofrecer CSV de patches como sustituto. No podar recibos para recuperar cuota.
5. Comparar versiones/esquema exacto/huellas y replays después de restaurar; aceptar dueño/otro/admin sin scope, REST/GraphQL por ID/colección, seis abilities, idempotencia, cuotas, carreras y fallos SQL. Verificar que Projects146/196 y sus idiomas/meta/relaciones permanecen intactos. El nuevo CPT no hereda automáticamente la evidencia de privacidad del CPT del laboratorio.

Este incremento entrega el procedimiento revisable; **no implementa un exportador ni demuestra backup/restauración**. Aún no se ha elegido destino, responsable o plazo. No ejecutar una restauración en el CMS compartido para ensayar. La decisión concreta se pide después de tener artefacto y resultados de aceptación; no solicitar una aprobación genérica anticipada.

## Reversión

Si no se ha instalado, no hay acción remota. Si se instala en el futuro: parar admisiones retirando opt-in o desactivando solo Proposals, preservar datos y retirar únicamente prepare de la cuenta si fue concedido. Verificar que Read continúa y que no aparece edición genérica en MCP/REST/GraphQL. No desinstalar/borrar tablas, ni restaurar toda la DB compartida para retirar propuestas. El paquete no escribe fuentes, por lo que no se ofrece apply/recover como rollback. Una restauración completa necesitaría ventana coordinada para conservar ediciones ajenas.

## Validación y almacenamiento

95 comprobaciones de frontera en9 procesos PHP con stubs: guards, seis registros, flags privados, denegaciones de scope/almacenamiento, rechazo de apply/recover y campos no permitidos, DTO draft y migración denegada fuera de CLI/operador. Lint de ambos PHP y sintaxis del script de almacenamiento. No pruebas de DB real, migración completa, APIs WordPress, concurrencia o transporte;531 pruebas anteriores son evidencia del laboratorio, no se atribuyen a este candidato.

ZIP determinista verificado en memoria por entradas/CRC/hashes; manifiesto y fuentes sin archivos privados. Tamaños exactos en el manifiesto. Impacto de este incremento: texto y ZIP pequeños; sin instalación de dependencias, build, imágenes/volúmenes Docker ni respaldo descargado. Captura de22oct:29GiB locales libres; CMS medido por Alex≈690,5MiB archivos+DB antes de copias auxiliares. No implica uso final exacto de una restauración.

Pendiente antes de instalar: ensayo aislado con WordPress/perfil real y esquema exacto, privacidad/transporte/SQL/fallos/carreras, respaldo+archivo restaurados, política de retención y aprobación concreta de ZIP/hash/scope. Apply/recover reales siguen dependiendo del adaptador de efectos y diff aprobado, fuera de este paquete. Contacto, Workers, QA visual/429 y la etapa final Astro–WordPress conservan su estado.
