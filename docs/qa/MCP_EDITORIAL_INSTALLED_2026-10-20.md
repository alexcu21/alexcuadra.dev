# MCP editorial de lectura instalado — 20 octubre 2026

## Resultado

Alex autorizó expresamente «ok instala y continua». Instalado y activado **Editorial Read 0.1.0** en el CMS compartido. Asignada únicamente `alexcuadra_read_public_projects` al **usuario 2**, manteniendo subscriber y sin modificar el rol global. [Resultado verificable](MCP_EDITORIAL_INSTALL_RESULT_2026-10-20.json), [procedimiento y reversión](../WORDPRESS_MCP_READ_INSTALL.md).

El conector nativo descubre ahora cinco abilities: las tres core y `alexcuadra/list-public-projects` / `alexcuadra/get-public-project`. Se usan a través de los tres meta-tools existentes; no se añadieron cinco herramientas nativas independientes al chat.

## Instalación y verificación

Preflight real `before` correcto: WP7.1.3/PHP8.2.34/Polylang3.9.0/Adapter0.7.1, usuario2/subscriber/read sin capacidad editorial, sin edición/admin comprobados ni concesiones almacenadas inesperadas. Misma combinación que pasó las91 pruebas aisladas y cinco escenarios de diagnóstico en el incremento anterior; no se repitieron esas suites al no cambiar el paquete.

Verificados ZIP local/fuente/archivos incluidos y ZIP subido por SHA256. Copia temporal privada fuera del directorio público. Instalación sin `--force`, verificación de huellas de PHP/README antes de activar, y activación exclusiva del plugin propio. El instalador informó también una purga de caché por hooks del sitio; no se lanzó una purga adicional.

**Antes de conceder la capacidad**, la definición ya se descubría pero la lectura real146EN devolvió `Scoped project read permission is required.` Confirmada así la ausencia de permiso automático al activar. Después de la asignación individual, preflight `after` correcto: capacidad presente, rol subscriber conservado y permisos comprobados de edición/administración aún false. El campo `installationAuthorized=false` del diagnóstico es una constante que evita que el script otorgue autorización; la aprobación humana está registrada por separado.

Archivos instalados coinciden con el paquete probado:

- ZIP: `72f1fa195c04ff1da690d2788e287451d52cc210d69d67eb453c869b8922f302`.
- PHP: `9e4def28919b5dc896037fa13dddf62b4f63e4a725095fe12b3e0d8419f22619`.
- README: `53e02012f3edc4d7a968d4ced9bda51298a49d824e83f181662d0e4c10586f77`.

ZIP temporal y carpeta vacía retirados; paquete local conservado. Los diagnósticos se transmitieron por stdin, sin instalarlos en WordPress. No se leyeron ni modificaron Application Passwords.

## Aceptación mediante el MCP real

Solicitudes espaciadas, sin reintentos ni cambios de protecciones. Schemas reales inspeccionados: entradas acotadas, propiedades adicionales prohibidas y anotaciones readonly/destructive=false.

- Paginación completa con3 elementos por página: seis proyectos EN (`42,43,50,137,144,146`) y seis ES (`195,196,197,198,199,200`), sin duplicados, idioma exacto y terminación explícita.
- Fichas146EN↔196ES con relación recíproca. Salida exacta ID/título/slug/locale/traducciones; sin cuerpos/ACF/meta privados.
- Búsqueda por título `christianitytoday` devuelve únicamente146 y ninguna siguiente página.
- Idioma incorrecto y borrador143 rechazados con el mismo error de proyecto público ausente; campos arbitrarios y `per_page=9999` rechazados por validación. Se verificaron los mensajes específicos, no solo un error genérico de transporte.

## Conservación y límites

[Diagnóstico de huellas](../../wordpress/operations/editorial-read-state.php) antes/después: coinciden14 registros de proyectos y hash de sus posts/postmeta/relaciones, hash de los demás permisos directos del usuario2, capacidades del rol subscriber y lista de otros plugins activos. Solo el plugin propio y la capacidad aprobada cambian en esas comparaciones. No es un snapshot/auditoría de todas las tablas ni garantiza ausencia de actividad concurrente fuera de ese alcance.

Sin edición de contenido, publicación, habilitación de ACF/cuerpos/escritura, cambio de credenciales, despliegue de Workers, activación de contacto ni envíos. No se ejecutaron despliegues en producción/staging del frontend. Navegador no usado ni eludido; cambios locales y dev4325 preservados. No se ejecutó una revocación de credencial real ni se retiró después la capacidad: denegación previa a concesión verificada aquí, revocaciones completas probadas con datos sintéticos en laboratorio.

El ZIP/README/manifest conservan su estado histórico de preparación; no se reescribió el paquete0.1.0 para cambiar esos textos. Este informe y el handoff son el estado vigente de instalación. Sintaxis operativa ejercitada en el servidor y `git diff --check` correcto; sin build/SSR repetidos porque no cambió frontend.

## Continuidad

Lectura mínima completada y disponible. Siguiente incremento de esta etapa: contrato local de edición acotada de proyectos, con claves permitidas, revisión/concurrencia y borradores, seguido de pruebas aisladas. Ese trabajo no amplía permisos ni instala escritura en el CMS real. La autorización de esta instalación cubre lectura0.1.0; no repetir su aprobación. TOC visual y429 del hosting siguen abiertos e independientes.
