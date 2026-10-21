# Preparación operativa del MCP editorial — 21 octubre 2026

**Decisión: no instalar escritura todavía.** La lectura real0.1.0 sigue operativa. La base0.5.0-lab pasa sus531 comprobaciones previas, pero no es un paquete para hosting. Este incremento revisó el CMS real por SSH sin instalar archivos ni ejecutar guardados; no abre otra fase de funcionalidades del laboratorio.

[Resultado saneado](qa/MCP_EDITORIAL_OPERATIONS_RESULT_2026-10-21.json) · [manifiesto de la base revisada](wordpress/editorial-operational-candidate-review.json) · [QA y límites](qa/MCP_EDITORIAL_OPERATIONS_2026-10-21.md).

## Qué quedó comprobado

En el contexto CLI del21oct: WP7.1.3/PHP8.2.34,13 plugins activos y2 MU; usuario2 subscriber con lectura específica y sin los scopes de preparación/aplicación/recuperación/aprobación ni capacidades globales comprobadas de edición/administración. Projects146EN↔196ES públicos, vinculados, una fila de tagline y referencia ACF original. Grupo `group_676d40f7bb646`, textarea `project_tagline`, key `field_676dcb2aec8d4`. Cinco tablas fuente InnoDB. Huellas de post/meta/relaciones iguales antes/después en ambas muestras.

El tipo project declara title/editor/thumbnail/custom-fields/excerpt/autosave; no declara revisions en esta captura. No usar revisiones nativas como respaldo supuesto. Solo se inspeccionaron dos proyectos y capacidades seleccionadas; no equivale a una auditoría de todo el CMS ni certifica otras sesiones o contextos HTTP.

## Efectos encontrados en el código instalado

Se inventariaron84 registros de callbacks en hooks seleccionados. Su registro no significa que todos se ejecuten con cada campo. Se leyeron15 métodos concretos por reflexión, sin invocarlos. Los archivos, líneas y hashes están en el resultado.

| Componente | Comportamiento observado en código | Consecuencia para la entrega |
| --- | --- | --- |
| Hostinger Hooks::handle_transition_post_status, línea26 | Cuando el estado anterior **o** nuevo es publish, despacha el evento del trabajo NotifyMcpJob; también cubre publish→publish | La actualización de cuerpo puede despachar trabajo antes de confirmar la transacción. No se trazó el receptor del trabajo: no se afirma haber enviado una notificación ni conocer su transporte |
| LiteSpeed Purge::purge_publish, línea130; _purge_post, línea1102 | En la misma condición de publicación calcula etiquetas, las agrega si existen y dispara litespeed_purged_post | La transacción SQL no certifica recuperación de efectos de caché. Falta seguir la emisión efectiva/consumo de esas etiquetas |
| Polylang PLL_Sync_Metas::update_meta, línea234; get_metas_to_copy, línea129 | Puede actualizar metadata de traducciones si los filtros de claves a copiar incluyen la clave; can_synchronize_metadata puede denegar cambios | No se evaluó la configuración ni filtros finales para tagline: la propagación EN/ES es una posibilidad condicionada, no un efecto demostrado. El contrato de un solo proyecto exige resolverla antes de aplicar |
| Hostinger LlmsTxtGenerator::on_post_status_change, línea86; generate, línea131 | Filtra por tipos soportados y ajustes; solo después puede crear archivo y despachar trabajos | No se inspeccionó la lista constante de tipos ni los ajustes. No afirmar que project genera llms.txt; aplicabilidad pendiente |
| WPGraphQLIDE Access::cap_document_title_length, línea195 | Retorna sin cambios salvo tipo graphql_document | Este método concreto no transforma project |
| WPGraphQLIDE Telemetry::mirror_request, línea75 | Solo refleja solicitudes Appsero con URL y hash del plugin coincidentes | No evidencia que guardar project lo invoque. No ejecutar telemetría para comprobarlo |

Estas observaciones explican por qué un rollback de filas SQL, aunque funcione en las pruebas aisladas, no basta como recuperación del hosting completo. No desactivar plugins/hooks ni sustituir las APIs por SQL para ocultar esos efectos.

## Candidato y puertas de salida

El manifiesto fija los cuatro PHP y README de0.5.0-lab, los contratos, la evidencia531 y la lectura0.1.0. **Es una base revisada con decisión HOLD, no un ZIP instalable ni una nueva versión.** Se conserva el guard local/http://wordpress en todos los archivos; no cambiar home_url/environment para saltarlo. El diagnóstico operativo tampoco es un plugin o ability.

Antes de presentar instalación deben cerrarse estas puertas concretas:

1. **Integración operativa:** trazar el receptor NotifyMcpJob y el consumo de purgas, filtros efectivos Polylang para ambas claves del tagline y contextos MCP/HTTP/edición/autosave. Resolver efectos fuera de la transacción mediante diseño y reconciliación explícitos; demostrarlo en una copia aislada autorizada del perfil real, con salidas externas bloqueadas. No editar el sitio compartido para ensayar ni modificar ajustes de plugins por iniciativa propia.
2. **Recuperación de datos:** verificar un respaldo privado de DB, plugins/MU y configuración ACF/CPT, fecha/huella y restauración en destino separado. No se inspeccionaron backups del proveedor: estado desconocido, no «no hay backups». No copiar credenciales/configuración privada al repositorio ni publicar un dump.
3. **Paquete operativo:** versión/destino explícitos, guard cerrado para el sitio aprobado, registro privado y migración aditiva verificables, ZIP/checksum, scopes mínimos por cuenta y aceptación sin scope. No habilitar apply/recover por instalar. Designar aprobador distinto del ejecutor y presentar diff/efecto público antes de cualquier aplicación.
4. **Archivo/retención:** exportación privada verificable y restaurable de propuestas, historial, revisiones, aprobaciones, eventos y recibos con versiones/hashes y relaciones; definir responsables y plazo con Alex. El prototipo conserva todo, sin TTL ni borrado. No reutilizar los30 días del formulario de contacto como política editorial. Al llegar a cuota se rechaza trabajo nuevo; no purgar registros para continuar.

**Siguiente incremento acotado:** cerrar la primera puerta con una matriz por callback/campo/contexto y una propuesta de tratamiento de efectos; a continuación preparar el candidato operativo y ensayo de respaldo/restauración. Sin repetir las531 pruebas salvo cambios de código/perfil que lo justifiquen. La instalación se solicita cuando exista el paquete revisable y se cumplan las puertas, no mediante una aprobación genérica anticipada.

## Procedimiento de reversión que debe acompañar al paquete

Este es el procedimiento previsto, **no un ensayo realizado en el CMS**:

1. Detener admisiones nuevas. Registrar versiones, IDs, hashes y recibos en almacenamiento privado; preservar las tablas y sus relaciones. Si hay incidente de seguridad, revocar solo los scopes nuevos de la entrega a sus cuentas, sin tocar Editorial Read ni convertir roles globales. Identificar trabajos en curso antes de parar ejecutores.
2. Si nunca se aplicó contenido: desactivar únicamente el módulo de escritura y volver al artefacto conocido; no ejecutar uninstall ni borrar tablas, propuestas o recibos. Verificar que el usuario2 conserva la lectura aprobada y que REST/GraphQL/MCP no conceden escritura genérica.
3. Si hubo apply: desactivar el plugin no restaura contenido. Comparar la huella posterior del evento con la fuente actual. Solo con igualdad y revisión/aprobación independientes vigentes ejecutar recuperación condicional con nueva clave; conservar el evento original y el de recuperación. Ante conflicto, caducidad o cuota insuficiente para renovar revisión, detenerse y resolver con el editor; nunca forzar snapshots antiguos sobre una edición posterior.
4. Reconciliar efectos externos identificados (caché, trabajos y posibles archivos) mediante las APIs soportadas, con acciones y estado final registrados. Un correo/webhook ya emitido no se deshace restaurando MySQL. No afirmar recuperación completa solo porque coinciden las filas.
5. Una restauración de respaldo es último recurso y requiere ventana coordinada por ser CMS compartido; revisar cambios posteriores para no perder ediciones ajenas. Verificar DB, ACF/idiomas, lectura autenticada y frontend después. El rollback de Worker es independiente y no restaura WordPress.

No se despliega staging por esta revisión de PHP/operación: TOC/anchors ya están en1b12660e. QA visual/zoom y429 siguen abiertos. Contacto continúa desactivado y la mejora del conector Astro–WordPress permanece al final del plan.
