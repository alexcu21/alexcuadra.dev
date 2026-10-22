# Aceptación CLI/SQL y archivo sintético — 22 octubre 2026

**165 comprobaciones correctas, exit0. Instalación real sigue HOLD.** Candidato0.1.0-rc.1 sin modificación, ZIP SHA256 `cd990139d7915567d76012e53060def452e8f2b43796918415d5b1f82da38e14`. [Resumen estructurado](MCP_PROPOSALS_SYNTHETIC_ACCEPTANCE_RESULT_2026-10-22.json).

## Perfil y procedencia

Alex proporcionó metadata actual del CMS: Polylang3.9.1 activo. Perfil anterior3.9.0 sigue siendo histórico; no se sustituyeron sus informes ni se certifican sus hashes para el plugin actualizado. Esto resuelve el bloqueo de dependencias anterior, no demuestra que todos los callbacks/sync/filtros del hosting estén iguales; cualquier futura aplicación de fuentes requiere revisar la deriva.

Fuentes públicas obtenidas con TLS verificado: Polylang3.9.1, Adapter0.7.1, WPGraphQL2.24.2, ACF6.8.10, WPGraphQL ACF3.0.0 y WPGraphQL Polylang0.7.1. Las cinco versiones adicionales son fijadas para el ensayo según evidencia previa; Alex confirmó ahora solo Polylang. Se registraron URL/SHA256/tamaño por ZIP en el resultado. No implica que todos esos bytes coincidan con el hosting.

12,1MiB descargados y38,0MiB extraídos, por debajo de límites48/256MiB. Caché privada temporal `/private/tmp/alexcuadra-proposals-public-plugins-v2`, sin dumps/datos del sitio. Antes de usarla se compararon ZIPs y archivos extraídos completos, sin extras, contra el manifiesto. El código del candidato se comparó byte por byte con su ZIP. No modificar guard ni fuentes del paquete para ensayar.

## Ejecución y cobertura

Runner `tests/integration/wordpress-mcp-proposals/run.py`, overlay público y proyecto nuevo `alexcuadra-editorial-proposals-acceptance-v2`. WP7.1.3/PHP8.2.34/MariaDB11.8, imágenes ya locales. Red interna, ningún puerto host, seed sin red; WordPress HTTP/mail bloqueados por fixture, cron/actualizaciones desactivados. WP_HOME/WP_SITEURL/opt-in exactos solo en esta DB sintética; no se conectó al CMS ni se ejecutó SSH.

| Grupo | Comprobaciones correctas |
| --- | ---: |
| Framework, permisos, idioma, patch, CAS y adaptador oficial en proceso | 67 |
| Historial, snapshots, versiones y restauración de patch | 44 |
| Idempotencia, cuotas, transacciones y fallos SQL seleccionados | 37 |
| Preparación de archivo sintético | 7 |
| Restauración separada y replays | 10 |
| Total | 165 |

Las primeras tres suites se adaptaron solo en tests temporales para namespace/origen/tabla nuevos. Framework real/DB real, no stubs; no sumarlas a las95 comprobaciones de frontera ni reutilizar531 del laboratorio como cobertura nueva. Los casos del adaptador oficial se invocan en proceso; no cubren transporte HTTP. Pruebas incluyen alteraciones deliberadas de fuentes sintéticas para comprobar invalidación; los fixtures de preservación comparan sus huellas, no afirmar que ningún dato sintético cambió.

Tablas nuevas creadas explícitamente por operador CLI en DB sintética; activación sin scopes automáticos. Usuarios sintéticos owner/otro/editor/admin ejercitan permisos, y las suites retiran sus scopes. Guard de sitio/namespace/almacenamiento distintos del lab siguen intactos. No se prueba aún rechazo exhaustivo de tipos/índices incompatibles preexistentes.

## Ensayo de archivo/restauración

Fixture crea fuente pública sintética y propuesta, la actualiza y conserva recibos create/update. Digest cubre filas posts/postmeta/relaciones/owners/requests, DDL de las dos tablas privadas y versión de esquema. `mariadb-dump --single-transaction --hex-blob` produce archivo interno de12845477B, SHA256 `e37976c2e9cd24f746b93d7074eda806162fd3f4de190b76678d26cc6851659a`.

Se restaura en `wordpress_restore` distinta de `wordpress`; nuevo proceso WP-CLI verifica digest exacto, replay idéntico create/update, cuota y filas sin cambio tras replay, cabeza actual y fuente del fixture intactas. El dueño restaurado queda sin scopes y se comprueba denegación; se invoca también retirada de scopes del dueño en la DB original. El archivo solo existió en tmpfs del contenedor: valida consistencia/restauración inmediata, **no durabilidad, retención ni respaldo del hosting**. No es un exportador selectivo editorial.

## Almacenamiento y cierre

Antes33166946304B libres; tras aserciones33167093760B libres (~30,9GiB). Captura df posterior redondea31GiB. Son observaciones globales, no se atribuye la pequeña variación al ensayo. Cache de plugins permanece fuera del repo; no se descargó CMS ni imágenes nuevas. Mounts limitados a1344MiB más RAM/swap/logs/capas Docker. Al terminar finally detuvo únicamente wordpress/db de v2; conserva recursos del primer intento y anteriores, sin prune/down-v. No usar tmpfs detenido como backup. Verificar recursos antes de reanudar: el runner rechaza v2 previo y no limpia datos para repetir.

Pendiente antes de instalación: privacidad/transporte HTTP/MCP/GraphQL de este CPT, carreras entre procesos, esquema exhaustivo/perfil de hooks real actualizado, respaldo privado real restaurado y archivo durable, destino/responsable/plazo acordados y aprobación concreta ZIP+scope. Este avance no altera CMS/roles/permisos reales/Workers/contacto. Próximo incremento acotado: aceptación HTTP/GraphQL y carreras del candidato, sin ampliar funcionalidades.
