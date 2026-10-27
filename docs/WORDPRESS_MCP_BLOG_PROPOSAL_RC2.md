# Propuestas privadas: candidato 0.1.0-rc.2

**27 octubre — creación de almacenamiento detenida:** Alex aporta blog_proposal_storage_not_empty en wordpress-repository.php:37. Esa línea está en la comprobación de ausencia, antes de todos los CREATE: esta invocación no llegó al DDL. Se observó al menos una tabla propia existente; se desconocen cuáles, sus filas y el origen del cambio desde after-enable (present=0). [Observación](qa/blog-proposal-rc2-storage-install-rejected-2026-10-27.json). Siguiente únicamente preflight rc.2 `2 after-storage` de solo lectura. No reintentar install, borrar/alterar tablas, inscribir propietario ni conceder scope. No atribuir éxito de almacenamiento o causa sin el diagnóstico.

Candidato separado de rc.1, derivado del ZIP sellado y del diagnóstico de huellas aportado por Alex. No instala ni cambia el CMS por generar estos archivos. Conserva las cinco herramientas privadas, los DTOs cerrados y la lógica de repositorio; cambia versión, admisión del perfil y README. No incluye escritor de posts, publicación, grants automáticos, migración automática ni borrado.

- [ZIP rc.2](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip):17.693bytes; SHA256 `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`.
- [Manifiesto de ocho archivos](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.manifest.json), [generador reproducible](../scripts/package-editorial-blog-proposals-site.py).
- [Pruebas y límites](qa/MCP_BLOG_PROPOSAL_RC2_2026-10-27.md).

## Perfil y límites

Admisión exacta de16plugins activos por versión y huella de archivo principal, dosMU por huella, Twenty Twenty-Four1.6 por entradas conocidas, núcleo/wpdb fijado, sin drop-ins/caché externa ni callbacks query desconocidos. Se permite adicionalmente el propio candidato rc.2 por versión/main hash. El guard real exige sitio único, home/site `https://content.alexcuadra.dev` y la constante específica. No se activa esa constante por copiar o instalar el ZIP.

La excepción de pruebas exige entorno local, DB_HOST=db, URL exacta y marca de laboratorio; solo allí admite el MU de seguridad conocido y las inyecciones de fallos. Diez pruebas separadas ejercitan también la rama sin esa excepción dentro del entorno sintético.

Las huellas principales coinciden con el servidor; no verifican árboles completos. El laboratorio usa PHP8.5.11/MariaDB11.8.9 y configuración/datos sintéticos; servidor PHP8.5.12. No certifica opciones reales de hosting, preview, cachés externas, restauración ante pérdida del servidor ni escritura pública.

## Fase pausada: no ejecutar de nuevo la creación de tablas

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
