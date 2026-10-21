# Historial y recuperación de propuestas — 20 octubre 2026

Actualización posterior: [idempotencia y cuotas0.3-lab completadas](MCP_EDITORIAL_IDEMPOTENCY_2026-10-20.md),236 comprobaciones combinadas y86 HTTP. Este informe/JSON conservan evidencia histórica0.2 de181. La admisión nueva serializa por propietario y mueve la barrera HTTP antes del bloqueo; ya no se instrumenta el CAS dentro de una transacción.

## Resultado

Editorial Changes **0.2.0-lab**, exclusivamente Docker. `npm run test:mcp-changes` exit0: **181 comprobaciones correctas** (67 framework existentes+44 historial+68 HTTP+2 guards). [Resultado saneado](MCP_EDITORIAL_HISTORY_RESULT_2026-10-20.json), [contrato actual](../WORDPRESS_MCP_PROJECT_CHANGES.md), [prototipo](../../wordpress/plugins/alexcuadra-editorial-changes/alexcuadra-editorial-changes.php).

El historial y la recuperación de propuestas propias están implementados y verificados en laboratorio. No hay aplicación/publicación sobre proyectos, instalación en el CMS compartido ni permisos reales nuevos. No hace falta crear un Editor. Editorial Read0.1.0 instalado permanece intacto: fuente y ZIP coinciden con sus SHA256 anteriores.

## Comportamiento

Tres abilities nuevas, seis en total en el laboratorio: listar metadatos paginados, consultar el snapshot de una revisión y recuperarlo como una versión nueva. Schemas cerrados; mismos scopes/dueño/idioma que create/get/update. Nunca devuelven el cuerpo/ACF privado de la fuente. La lista no devuelve patches; consultar una revisión devuelve únicamente su snapshot de propuesta.

Cada escritura conserva un snapshot normalizado, token propio, padre, operación, fecha UTC y referencia de recuperación. Cabeza e historial viven en un solo payload y cambian en el mismo CAS condicionado por versión/valor/propietario/tipo/estado. Un conflicto o fallo DB no deja una revisión huérfana. Las abilities no borran ni reescriben entradas anteriores.

Recuperar reemplaza el patch completo por el de la revisión elegida: si aquel snapshot no proponía cuerpo, se elimina esa propuesta de cuerpo añadida después. Genera un token nuevo y conserva las versiones previas. Exige la versión actual y la fuente todavía pública/sin cambios; no rebase ni restauración del proyecto publicado.

Paginación1–10 entradas, orden de creación, token actual obligatorio para detectar cambio entre páginas.32 snapshots/2MiB de JSON codificado son topes técnicos del journal: se rechaza la escritura que exceda cualquiera, sin podar versiones. No constituyen todavía cuotas por usuario ni protección de solicitudes repetidas.

Los payloads0.1 existentes se leen como una sola `baseline` con fecha null: no se inventa su pasado. Lectura sin escrituras; primer update guarda baseline y nuevo snapshot juntos. Un payload con cadena rota, tokens duplicados, contenido no normalizado o cabeza incoherente se rechaza. No se ofrece downgrade del formato0.2 a0.1.

## Evidencia

WordPress7.1.3/PHP8.2.34/Polylang3.9.0/MCP Adapter0.7.1, imágenes fijadas por digest, proyecto `alexcuadra-editorial-change-lab`, sin puertos host. Fixtures y credenciales sintéticos. Sin navegador ni SSH/MCP del sitio real.

- **44 checks de historial:** creación/update/restore, snapshot exacto con Unicode/comillas/barras, eliminación de campos posteriores, versiones y padres, metadatos paginados, conflicto de token, revisión inexistente/de otra propuesta, otro usuario/idioma/scope revocado y campos arbitrarios, parámetros de página inválidos. Fallo SQL inducido del CAS deja fila intacta; cadenas/cabezas alteradas fallan; baseline legacy no escribe al leer y migra solo con update. Topes de cantidad y bytes dejan fila intacta. Hash completo de fuentes antes de los cambios intencionales de fixture coincide.
- **68 checks HTTP:** se conservan las48 comprobaciones previas y se añaden20 de descubrimiento/schemas/historial/recuperación/aislamiento. Persistencia de historial completo tras desactivar/reactivar el plugin y nuevas peticiones PHP. Revocación de capacidad/credencial y cierre de sesión siguen verificados; edición REST convencional del proyecto sigue denegada403.
- **Dos carreras HTTP:** dos sesiones del dueño y dos procesos Apache distintos se sincronizan justo antes de su CAS. Primera update/update; segunda update/restore. Cada ronda verifica solapamiento, exactamente un ganador/un conflicto específico `proposal_changed`, cabeza igual al ganador y exactamente una revisión nueva con el padre esperado. En esta ejecución ganó update en ambas rondas; la recuperación ganadora se comprueba además en HTTP secuencial y en framework. No se afirma haber observado ambos órdenes de victoria bajo HTTP paralelo.
- **Conservación:** hashes HTTP de posts/postmeta/relaciones fuente iguales antes/después. El harness modifica/restaura una fuente deliberadamente para comprobar drift; el plugin no lo hace. Capacidades y Application Passwords sintéticas retiradas y verificadas; solicitudes de cierre de sesiones enviadas; plugin local restaurado y filas de barrera retiradas.67 checks originales y2 guards siguen pasando.

PHP lint, shell y `git diff --check` correctos. No se repiten build/SSR del frontend ni91 pruebas del plugin de lectura: no cambiaron esas superficies. Los avisos conocidos de las pruebas negativas/bootstrap se conservan.

```sh
npm run test:mcp-changes
npm run test:mcp-changes-http # subconjunto HTTP; incluye bootstrap
docker compose -f tests/integration/wordpress-mcp-read/compose.yaml \
  -f tests/integration/wordpress-mcp-read/compose.site-stack.yaml \
  -f tests/integration/wordpress-mcp-changes/compose.yaml stop
```

Solo se detuvo este laboratorio al terminar. Sin CMS/credenciales/permisos reales, Workers/contacto/envíos ni navegador alterados; cambios locales y dev4325 preservados.

## Límites y continuación

Persistencia en DB entre peticiones y reactivación verificada. Los volúmenes del laboratorio son tmpfs: no se ensaya pérdida del volumen, recuperación ante caída del host o backups. El journal no es un registro externo resistente a modificaciones administrativas; no hay export/retención/borrado de propuestas todavía. Tampoco TLS/hosting real, WPGraphQL instalado, prueba de carga, apply/publicación/creación de traducciones ni paquete apto para el CMS compartido.

**Siguiente incremento: idempotencia y cuotas por propietario en laboratorio**, con concurrencia y fallos comprobados. Después integración ACF/Polylang/WPGraphQL y diseño separado de aplicación/recuperación de fuentes. TOC visual y429 siguen abiertos; contacto permanece desactivado. Este resultado no amplía permisos reales.
