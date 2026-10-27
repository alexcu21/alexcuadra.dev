# Propuestas privadas: candidato 0.1.0-rc.2

**27 octubre — propuestas privadas rc.2 operativas por MCP:** Alex aporta after-scope ready=true/15checks; usuario2 conserva subscriber y ocho capacidades previas más solo alexcuadra_prepare_post_changes, sin grant a roles/otros usuarios y16permisos amplios denegados. [Recibo CLI](qa/blog-proposal-rc2-destination-after-scope-2026-10-27.json). Verificación independiente del conector: catálogo18abilities, cinco nuevas con DTOs cerrados y anotaciones correctas; listado privado items=[]/next_after=null. Siete llamadas de lectura/metadatos, sin crear planes o leer cuerpos. [Cierre real/límites](qa/MCP_BLOG_PROPOSAL_LIVE_2026-10-27.md). Instalación/grant/backup inicial ya cerrados; no repetir comandos. Siguiente piloto privado con fuente/diff explícitos antes de cualquier creación, o incremento de taxonomías propias de proyectos; edición/publicación de posts sigue sin implementarse.

**27 octubre — respaldo inicial real verificado:** Alex aporta verify ready=true/integrityVerified=true;1propietario y0propuestas/revisiones/recibos, sin escrituras de BD, mismo artefacto/SHA del export. [Recibo](qa/blog-proposal-backup-destination-verify-2026-10-27.json). Herramientas de recuperación ya ensayadas en entorno aislado; no se restauró sobre el CMS vivo. Siguiente concesión directa únicamente alexcuadra_prepare_post_changes al usuario2 por Alex y after-scope, conservando ocho capacidades previas/rol subscriber y16denegaciones amplias. No grant a roles/otros usuarios ni creación de propuestas todavía. Verificación MCP real de solo lectura tras recibir after-scope.

**27 octubre — primera exportación real recibida:** Alex aporta export ready=true, respaldo backup-20261027T160054Z-d1f849d991c44fdb y manifestSha256 39022b62…a5b0c76; cuenta1propietario y0propuestas/revisiones/recibos, sin escrituras de BD. [Recibo](qa/blog-proposal-backup-destination-export-2026-10-27.json). Siguiente verify sobre ese mismo directorio/SHA con scope todavía ausente; no repetir exportación, modificar artefactos, restaurar sobre el CMS vivo ni conceder permiso todavía. restorationVerified=false es correcto: no se ejecutó restauración del hosting.

**27 octubre — respaldo/recuperación operativos preparados y ensayados:** dos PHP externos al plugin;39pruebas de recuperación y5recorridos CLI correctos, incluida restauración exitosa en prefijo aislado y get/list/replay originales. Exportación nativa READ ONLY/REPEATABLE READ, archivos privados/manifiesto/hashes, destino ocupado rechazado, rollback de filas ante fallo sin reintento de DDL. [Procedimiento vigente](WORDPRESS_MCP_BLOG_PROPOSAL_RECOVERY.md), [QA/límites](qa/MCP_BLOG_PROPOSAL_RECOVERY_2026-10-27.md). ZIP rc.2 intacto; laboratorios propios detenidos/red interna vacía. Siguiente: Alex sube solo blog-proposal-backup.php y recovery.php a private-tools/blog-proposal-rc2, crea carpeta0700 y ejecuta primera exportación con scope ausente. No se ha hecho backup/restauración/grant real desde el agente; revisar recibo antes del nuevo permiso.

**27 octubre — propietario real de rc.2 verificado:** Alex aporta after-owner ready=true, 15 comprobaciones principales y 11 del perfil correctas. El check de esta fase exige únicamente propietario2; propuestas/revisiones/recibos siguen vacíos y las ocho capacidades anteriores se conservan. [Recibo](qa/blog-proposal-rc2-destination-after-owner-2026-10-27.json). Siguiente: preparar y probar exportación/restauración operativas limitadas a las cuatro tablas antes de grant. No repetir enroll/install ni conceder alexcuadra_prepare_post_changes todavía. El respaldo genérico histórico está fijado a otro perfil y no se reutiliza para este módulo. Ningún comando adicional requerido ahora a Alex.

**27 octubre — almacenamiento real de rc.2 verificado:** Alex aporta after-storage ready=true, 15 comprobaciones principales/11 del perfil correctas. Cuatro tablas completas InnoDB/utf8mb4_bin con esquema exacto, sin propietarios ni propuestas/revisiones/recibos. Ocho capacidades previas conservadas y scope nuevo ausente. [Recibo](qa/blog-proposal-rc2-destination-after-storage-2026-10-27.json). El rechazo de install no requiere reparar/recrear; no se conoce el momento ni la ejecución que creó las tablas. Siguiente: enroll(2) mediante operador1 existente y after-owner, sin grant. Respaldo operativo antes de habilitar escritura de propuestas sigue pendiente.

**27 octubre — creación de almacenamiento detenida:** Alex aporta blog_proposal_storage_not_empty en wordpress-repository.php:37. Esa línea está en la comprobación de ausencia, antes de todos los CREATE: esta invocación no llegó al DDL. Se observó al menos una tabla propia existente; se desconocen cuáles, sus filas y el origen del cambio desde after-enable (present=0). [Observación](qa/blog-proposal-rc2-storage-install-rejected-2026-10-27.json). Siguiente únicamente preflight rc.2 `2 after-storage` de solo lectura. No reintentar install, borrar/alterar tablas, inscribir propietario ni conceder scope. No atribuir éxito de almacenamiento o causa sin el diagnóstico.

Candidato separado de rc.1, derivado del ZIP sellado y del diagnóstico de huellas aportado por Alex. No instala ni cambia el CMS por generar estos archivos. Conserva las cinco herramientas privadas, los DTOs cerrados y la lógica de repositorio; cambia versión, admisión del perfil y README. No incluye escritor de posts, publicación, grants automáticos, migración automática ni borrado.

- [ZIP rc.2](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip):17.693bytes; SHA256 `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`.
- [Manifiesto de ocho archivos](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.manifest.json), [generador reproducible](../scripts/package-editorial-blog-proposals-site.py).
- [Pruebas y límites](qa/MCP_BLOG_PROPOSAL_RC2_2026-10-27.md).

## Perfil y límites

Admisión exacta de16plugins activos por versión y huella de archivo principal, dosMU por huella, Twenty Twenty-Four1.6 por entradas conocidas, núcleo/wpdb fijado, sin drop-ins/caché externa ni callbacks query desconocidos. Se permite adicionalmente el propio candidato rc.2 por versión/main hash. El guard real exige sitio único, home/site `https://content.alexcuadra.dev` y la constante específica. No se activa esa constante por copiar o instalar el ZIP.

La excepción de pruebas exige entorno local, DB_HOST=db, URL exacta y marca de laboratorio; solo allí admite el MU de seguridad conocido y las inyecciones de fallos. Diez pruebas separadas ejercitan también la rama sin esa excepción dentro del entorno sintético.

Las huellas principales coinciden con el servidor; no verifican árboles completos. El laboratorio usa PHP8.5.11/MariaDB11.8.9 y configuración/datos sintéticos; servidor PHP8.5.12. No certifica opciones reales de hosting, preview, cachés externas, restauración ante pérdida del servidor ni escritura pública.

## Estado actual: instalación y acceso MCP inicial verificados

Alex completó after-scope con15checks correctos; nueve capacidades efectivas (ocho previas más alexcuadra_prepare_post_changes), subscriber conservado y16permisos amplios denegados. [Recibo CLI](qa/blog-proposal-rc2-destination-after-scope-2026-10-27.json). El conector independiente confirma18abilities, cinco nuevas y listado privado vacío. [Cierre real](qa/MCP_BLOG_PROPOSAL_LIVE_2026-10-27.md).

No repetir instalación, grant, enroll o diagnósticos iniciales. No se ha creado una propuesta real ni leído cuerpos para probar acceso. Siguiente piloto privado requiere fuente y cambio concretos revisables; no se prepara un diff arbitrario ni una publicación. Actualizar un plan privado no modifica el artículo o sus traducciones. El escritor público/aprobación/aplicación/recuperación de posts sigue fuera de este paquete.

El respaldo inicial y su SHA se conservan; futuras exportaciones requieren pausar escritores y retirar solo este scope. No repetir after-scope inicial cuando ya existan planes: exige almacenamiento inicialmente vacío; no borrar historia para satisfacerlo.

## Histórico: permiso directo y after-scope ya completados

El after-owner real y el respaldo inicial exportado/verificado son correctos. [Verify de Alex](qa/blog-proposal-backup-destination-verify-2026-10-27.json): ready=true/integrityVerified=true,1propietario y otras tres tablas vacías. Se conservan artefacto/SHA fuera del árbol público; no se restauró sobre el sitio vivo. Recuperación y replay se ensayaron con las herramientas exactas en datos sintéticos.

Cambio de permiso concreto: añadir únicamente alexcuadra_prepare_post_changes al usuario2 existente. Permite contexto de posts publicados y creación/actualización/lectura/listado de sus planes privados; no edita/publica fuentes, crea posts/traducciones reales ni concede administración. Se conservan rol subscriber y las ocho capacidades anteriores; ningún grant al rol o a otra cuenta. El grant real sigue pendiente de ejecución por Alex.

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 user add-cap 2 alexcuadra_prepare_post_changes
```

Si termina correctamente, ejecutar inmediatamente:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 after-scope
```

Compartir BLOG_PROPOSAL_PREFLIGHT_RESULT antes de usar las herramientas. Se esperan15checks correctos, exactamente nueve capacidades para usuario2 (ocho previas más una), ningún grant al rol/otra cuenta,16permisos amplios denegados y almacenamiento de propuestas vacío con propietario2. Si falla un comando, compartir el error antes de avanzar. No crear una propuesta para probar permisos ni repetir enroll/install/backup.

Tras revisar after-scope: catálogo y listado privado vacío mediante conector MCP, sin creación, credenciales nuevas o publicación. Una propuesta real requiere especificar y revisar su fuente/diff por separado. Para futuros respaldos hay que pausar los escritores y retirar solo el scope nuevo; la herramienta de exportación rechaza escritores habilitados. No sobrescribir ni borrar el respaldo inicial.

## Histórico: primera exportación real antes de grant ya completada

El procedimiento específico y sus dos PHP ya están preparados y probados: [respaldo/recuperación](WORDPRESS_MCP_BLOG_PROPOSAL_RECOVERY.md), [39pruebas y5recorridos CLI](qa/MCP_BLOG_PROPOSAL_RECOVERY_2026-10-27.md). La primera exportación real debe conservar únicamente propietario2 y las otras tres tablas vacías. Subir los dos nuevos PHP, crear carpeta privada y ejecutar export según ese procedimiento; compartir BLOG_PROPOSAL_BACKUP_RESULT. No conceder todavía el scope ni restaurar sobre el CMS vivo. No hay que repetir enroll, install, guard o activación.

## Histórico: preparación operativa que motivó este incremento

El [after-owner aportado por Alex](qa/blog-proposal-rc2-destination-after-owner-2026-10-27.json) pasa las 15 comprobaciones principales y las 11 del perfil. expectedOwners en esta fase exige exactamente propietario2; las otras tres tablas permanecen vacías. Se conservan las ocho capacidades previas y sigue ausente alexcuadra_prepare_post_changes. No repetir enroll, install, activación o diagnósticos iniciales.

El siguiente incremento prepara un procedimiento operativo de respaldo/recuperación limitado a las cuatro tablas privadas del módulo. El ensayo sintético ya realizado demuestra esquema/filas/hashes en prefijo aislado; no es una herramienta de exportación y restauración lista para usar en el hosting. El helper genérico editorial-private-backup.php está fijado a otro perfil/conjunto de37tablas y exporta fuentes/configuración: no corresponde reutilizarlo ni ampliar su perfil por continuidad.

Criterios pendientes antes de conceder el scope:

- Exportación consistente y acotada de solo owners/proposals/revisions/requests, con escritores pausados, sitio/paquete/esquema/propietario verificados, sin credenciales o cuerpos en stdout.
- Artefacto privado fuera del árbol público, acceso restringido, manifiesto/hashes, conteos y comprobación de integridad; sin sobreescribir un respaldo anterior o podar historia.
- Restauración del artefacto exacto en destino aislado con rechazo de destino ocupado, verificación de propietario/versiones/documentos/recibos y pruebas de lectura/replay sobre el estado restaurado. No restaurar posts/postmeta/roles/opciones/capacidades.
- Ensayar errores de exportación/integridad/restauración y documentar la recuperación del destino real como una operación revisada separada, sin DROP o sustitución automática en producción.

No se ha realizado un respaldo nuevo del hosting ni una restauración real. No hay comandos adicionales para Alex en este estado; no conceder el nuevo scope ni crear propuestas de prueba hasta cerrar esta preparación y revisar el cambio de permiso concreto. Sin cambios de correo/Worker/CMS realizados por el agente.

## Histórico: propietario2 y after-owner ya completados

El [after-storage real](qa/blog-proposal-rc2-destination-after-storage-2026-10-27.json) pasa las 15 comprobaciones: cuatro tablas con esquema exacto, InnoDB/utf8mb4_bin, vacías y sin propietarios, conservando las ocho capacidades anteriores. El estado actual queda verificado; no se conoce qué ejecución creó las tablas. El rechazo anterior de install ocurrió antes del DDL y no justifica repetir creación, borrar o reparar tablas.

Alex ejecuta por SSH como el operador administrador1 existente:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval '(new \AlexCuadra\EditorialBlogProposals\WordPressRepository($GLOBALS["wpdb"]))->enroll(2); WP_CLI::line("BLOG_PROPOSAL_OWNER_ENROLLED");'
```

Si termina correctamente, comprobar:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 after-owner
```

Compartir BLOG_PROPOSAL_PREFLIGHT_RESULT. enroll(2) comprueba sitio/perfil/esquema, contexto CLI, manage_options y existencia del usuario2; solo inserta su fila de propietario si falta. No concede permisos, cambia roles, crea propuestas o edita contenido. after-owner exige exactamente propietario2, las otras tres tablas vacías y las ocho capacidades previas sin nuevo scope. Si falla, compartir el error antes de avanzar.

El grant sigue pendiente: antes de habilitar escritura de propuestas reales debe quedar revisado el procedimiento operativo de respaldo/recuperación del hosting. No usar el fixture sintético en el servidor.

## Histórico: creación detenida, almacenamiento ya reconciliado por diagnóstico

El [after-enable real](qa/blog-proposal-rc2-destination-after-enable-2026-10-27.json) pasa las 15 comprobaciones: guard/plugin activos, cinco herramientas registradas, archivos exactos y perfil compatible. Las cuatro tablas propias aún no existen; no hay nuevo scope y las ocho capacidades anteriores se conservan. No repetir instalación o activación.

Alex ejecuta por SSH con el operador CLI1 administrador existente, ya identificado en el procedimiento operativo anterior; no se asignan capacidades al usuario2. El método install comprueba nuevamente sitio/perfil, contexto CLI, manage_options, ausencia de transacción y ausencia de las cuatro tablas antes del primer CREATE. Solo crea las cuatro tablas ac_blog_proposal_ del prefijo de esta instalación y valida su esquema; no inscribe propietarios ni concede permisos.

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval '(new \AlexCuadra\EditorialBlogProposals\WordPressRepository($GLOBALS["wpdb"]))->install(); WP_CLI::line("BLOG_PROPOSAL_STORAGE_CREATED");'
```

Si termina correctamente, comprobar:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 after-storage
```

Compartir BLOG_PROPOSAL_PREFLIGHT_RESULT antes de continuar. after-storage exige cuatro tablas InnoDB/utf8mb4_bin con columnas/índices/FK/triggers exactos, sin filas ni propietario y con los permisos anteriores intactos. El schema.ready=false de las fases previas era la ausencia esperada; aquí debe ser true.

Si el primer comando falla, detenerse y compartir el error; no repetir DDL ni usar DROP/ALTER para reparar. El DDL de MariaDB no es transaccional y una interrupción puede dejar tablas parciales. Si el usuario1 ya no tiene manage_options, el método rechaza sin crear tablas; revisar el operador antes de cambiar identidades o permisos. No hay creación real de almacenamiento hasta que Alex ejecute este paso. Propietario y primer scope se revisarán después; el respaldo operativo del hosting sigue pendiente antes de escribir propuestas privadas reales.

## Histórico: guard, activación y after-enable completados

El [after-install aportado por Alex](qa/blog-proposal-rc2-destination-after-install-2026-10-27.json) pasa las 15 comprobaciones. Archivos exactos, perfil compatible y plugin inactivo. No se han creado tablas propias ni concedido el scope nuevo; las ocho capacidades anteriores permanecen. No repetir instalación, before o fingerprint.

Alex ejecuta por SSH, deteniéndose si algún comando falla:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content config set AC_EDITORIAL_BLOG_PROPOSALS_SITE 'https://content.alexcuadra.dev' --type=constant
```

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin activate alexcuadra-editorial-blog-proposals
```

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 after-enable
```

Compartir BLOG_PROPOSAL_PREFLIGHT_RESULT. La constante es una cadena de URL, sin --raw. No configurar la excepción de laboratorio. after-enable exige plugin/guard activos y cinco herramientas registradas, archivos exactos, tablas todavía ausentes y las ocho capacidades anteriores sin nuevo scope. Activar este paquete no instala almacenamiento, inscribe propietarios ni concede permisos. No llamar herramientas de creación para probar activación.

Si falla la activación o el diagnóstico, compartir la salida antes de avanzar; no modificar perfil, plugins, tema o permisos para forzar aceptación. La activación real sigue pendiente de ejecución por Alex. DDL/propietario/grant son fases posteriores; falta procedimiento operativo de respaldo/recuperación del hosting antes de escritura privada real.

## Histórico: instalación inactiva y after-install ya completados

Alex aportó el [before real](qa/blog-proposal-rc2-destination-before-2026-10-27.json): ready=true, las 15 comprobaciones principales y las 11 de perfil son correctas. El SHA coincide con este ZIP. El paquete, guard, tablas y nuevo scope todavía están ausentes. schema.ready=false es lo esperado en before porque aún no existen las cuatro tablas; no indica un fallo de esta fase. Se conservan las ocho capacidades previas.

Alex realiza la instalación por SSH. Subir únicamente el ZIP rc.2 a `/home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/`, donde ya están los dos diagnósticos. Verificar primero:

```sh
sha256sum /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip
```

Debe devolver exactamente `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`. Solo si coincide, instalar inactivo:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin install /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip
```

Sin --activate ni --force. Si indica que el plugin ya existe o falla, detenerse y compartir el error; no sustituir archivos. Tras instalación correcta:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 after-install
```

Compartir BLOG_PROPOSAL_PREFLIGHT_RESULT antes de continuar. Esta fase exige archivos exactos del paquete y que siga inactivo, sin guard, tablas, propietario ni nuevo scope. La ejecución real de instalación permanece pendiente; no se ha instalado nada desde el agente ni se cambió el manifiesto sellado para atribuir autorización o ejecución a este recibo. Activación y posteriores fases se revisan por separado. Falta procedimiento operativo de respaldo/recuperación del hosting antes de habilitar escritura privada real.

## Histórico: preflight rc.2 de solo lectura ya completado

Los diagnósticos rc.1 anteriores quedan como históricos. Para este ZIP se preparó una pareja nueva, probada junto al ZIP exacto. Alex puede crear `/home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/` y subir **solo**:

1. [blog-proposal-admission.php rc.2](../wordpress/operations/blog-proposal-rc2/blog-proposal-admission.php).
2. [blog-proposal-preflight.php rc.2](../wordpress/operations/blog-proposal-rc2/blog-proposal-preflight.php).

Después, por SSH:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 before
```

Compartir `BLOG_PROPOSAL_PREFLIGHT_RESULT`. Consulta perfil, ausencia de las tablas propias, estado del candidato y capacidades exactas; no lee cuerpos/credenciales, instala, activa o concede permisos. No repetir el diagnóstico de huellas que ya coincidió ni cambiar componentes para forzar admisión. El arranque ordinario de WordPress y sus plugins queda fuera de la garantía del observador.

El manifiesto conserva installationApproved=false y capabilityGrantApproved=false. Antes de instalar: revisar este ZIP exacto y el nuevo before. La instalación inicial será inactiva y seguida de after-install, sin `--force`. Activación, tablas, propietario y primer scope son fases posteriores, con su recibo propio; no ejecutar por continuidad de este documento. No instalar rc.1 ni el experimento local.

## Respaldo, restauración y retirada

Ensayo realizado únicamente con datos sintéticos: crear dos revisiones/recibos, retirar el scope del único escritor, capturar las cuatro tablas propias y sus DDL en un archivo privado0600, restaurar en cuatro tablas con prefijo separado, validar esquema, filas exactas, propietario, cabeza de versión y hashes de documentos. Las tablas originales privadas y editoriales se conservan. No se restauran tablas públicas ni permisos anteriores.

El ensayo no es una herramienta de respaldo lista para el hosting. Antes de habilitar escritura privada real falta un procedimiento operativo revisado de exportación/restauración consistente, con manifest/hashes, acceso privado, pausa coordinada y validación aislada para ese destino. No copiar al servidor el fixture de pruebas ni su mecanismo de restauración. La API de replay funciona antes del respaldo; después de restaurar se verificaron filas y recibos, no una reapertura de la API sobre el espacio restaurado.

Retirada ensayada: retirar solo el scope nuevo, desactivar el candidato y deshabilitar su guard. Permanecen propietario, propuesta, ambas revisiones/recibos, las ocho capacidades anteriores y el lector del blog activo. No hay DROP, poda ni uninstall automático.
