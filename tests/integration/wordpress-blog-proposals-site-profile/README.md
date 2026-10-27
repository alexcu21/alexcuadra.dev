# Arranque del perfil ampliado

Preparar código local con scripts/prepare-blog-proposal-site-code.py y --archive apuntando al respaldo privado ya existente. El script admite únicamente su tamaño/SHA conocidos, verifica cada archivo seleccionado y excluye backup/ y wp-config. No invoca SSH o PHP del respaldo. Código/procedencia quedan en caché privada ignorada.

Después ejecutar `BLOG_SITE_PROFILE_PROJECT=alexcuadra-blog-site-profile-20261026-r6 python3 tests/integration/wordpress-blog-proposals-site-profile/run.py` solo para una repetición justificada, con un nombre nuevo si hace falta. Requiere la red interna propia del laboratorio de paquete indicada en el runner, vacía y con etiquetas verificadas; no reutiliza redes ajenas ni publica puertos. Usa base sintética, código copiado a tmpfs768MiB, BD256MiB y un guard de HTTP/correo. Configuración y overlays sintéticos van en caché compartida con Docker, sin permisos globales nuevos.

46casos de arranque/inclusión/versiones/procedencia y reproducción del bloqueo del preflight. No instala el candidato ni concede el scope nuevo; no prueba CRUD, aprobación o publicación. Al finalizar detiene solo su proyecto y conserva recursos sin prune. [Evidencia y límites](../../../docs/qa/MCP_BLOG_PROPOSAL_DESTINATION_PROFILE_2026-10-26.md).


## Funcional ampliado

`BLOG_SITE_PROFILE_PROJECT=alexcuadra-blog-site-profile-functional-20261026-r3 python3 tests/integration/wordpress-blog-proposals-site-profile/run.py --functional` prepara un candidato solo local desde rc.1 sellado. No reutilizar un proyecto en ejecución. El candidato requiere flag de experimento/local/db/URL exactos; no es distribuible. Prueba72operaciones+34fallos+43HTTP, con perfiles/main hashes/MU/tema del inventario. No modifica rc.1 o su admisión.

Workers usan archivos privados en vez de pipes para diagnósticos, marcadores con plazo y vaciado de buffers. `php process-check.php` prueba siete situaciones con mucho stdout/stderr y errores. El debug log se conserva privado, no se desactiva error_reporting. [Evidencia ampliada](../../../docs/qa/MCP_BLOG_PROPOSAL_SITE_FUNCTIONAL_2026-10-26.md).

El modo de arranque también prueba el nuevo diagnóstico autocontenido de huellas y escribe un recibo distinto, sin sustituir el recibo histórico de arranque. La prueba compara entradas, no árboles completos del servidor; installationApproved=false incluso cuando coinciden.

## Candidato rc.2

`scripts/package-editorial-blog-proposals-site.py` deriva un candidato distribuible separado del ZIP rc.1, conservando el original. `--release functional` verifica149casos de operaciones/fallos/HTTP más29contratos y10casos de admisión real simulada. `--release lifecycle` instala el ZIP con WP-CLI y prueba nueve preflights/30checks, respaldo privado sintético y restauración a prefijo separado. No copiar los fixtures al hosting. Usar un proyecto propio nuevo; runner exige red interna vacía y detiene solo su proyecto. [Procedimiento](../../../docs/WORDPRESS_MCP_BLOG_PROPOSAL_RC2.md).

## Recuperación operativa

`--release recovery` instala el ZIP rc.2, crea tablas/propietario sintéticos y monta un volumen privado tmpfs64MiB fuera del document root. Prueba39casos de la biblioteca y5recorridos del CLI (export/verify/restore exitoso/destino ocupado/pausa ausente); guarda recibo separado sin artefactos privados. Los prefijos aislados los establece solo el harness, no una opción del helper desplegable. Red interna vacía requerida, sin puertos/correo/egreso; al terminar detiene solo su proyecto, sin prune. [Procedimiento](../../../docs/WORDPRESS_MCP_BLOG_PROPOSAL_RECOVERY.md).
