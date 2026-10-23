# Exportación privada de registros operativos — 23 octubre

155 controles del contrato+50 migración/aceptación+68 MCP HTTP+132 regresiones:405 integraciones,11 casos nuevos. Perfil completo original/rc.2, WordPress/MariaDB/ACF/Polylang/LiteSpeed reales y únicamente datos sintéticos.

exportOwner solo CLI manage_options+scope aprobación, propietario positivo y pausa externa declarada. Usa barrera y snapshot READ ONLY, comprueba ready/esquemas/presupuesto y audita la cadena del propietario. Exporta sus reviews/requests/apps/approvals y eventos/efectos enlazados. No incluye tablas fuente, wp-config, credenciales, SQL completo, usuarios ni payloads/historia rc.2; no es respaldo independiente ni importador/restauración.

Destino absoluto/canónico, padre existente0700 fuera de ABSPATH, nombre acotado .ndjson y sin archivo/symlink existente. Temporal aleatorio exclusivo0600 con umask077; NDJSON por filas, encabezado con dueño/huella de esquema/conteos, pie con conteo/bytes/SHA256 de datos. Límite32MiB/archivo,2MiB/línea,128 registros. Escritura comprueba bytes, fflush/fsync y verificación completa antes de publicar mediante hardlink que no sobrescribe. Solo retira el enlace temporal tras publicar; datos y archivo final se conservan. Fallos pueden dejar un parcial para decisión manual, sin limpieza automática.

verify_operating_export se ejecuta aparte: modo0600/archivo regular/no symlink/inode, framing, formatos, límites, conteos, pie y digest. Devuelve solo metadatos, SHA256 y restoration_verified=false; nunca cuerpos ni ruta privada en el recibo. La verificación es de integridad/framing, no firma de autenticidad, auditoría de cada fila sin DB o prueba de restauración. El archivo contiene datos privados en texto, sin cifrado. fsync de archivo no certifica persistencia de la entrada de directorio ante corte eléctrico. Si una respuesta falla después de publicar, verificar el archivo retenido; no sobrescribirlo.

Once casos: usuario sin autoridad; falta pausa; export de7 registros exactos; verificación independiente idéntica;0600; archivo existente; truncamiento; datos modificados; padre0755; symlink; fuente/ledger/cuotas conservados. Archivos solo sintéticos en carpeta privada /tmp del contenedor, fuera del webroot. Se retienen en el laboratorio detenido; no se copiaron al repo/chat/equipo como export real.

No hay ability/REST/GraphQL de export ni cron de retención/poda; no cambia archivo privado real de respaldo. Pendiente paquete/manifiesto con guardas/admisión para destino, preflight/reversión y autorización de instalación/permisos/diff. Guarda local intacta: no es aún instalable en el CMS real.

Una ejecución completa con imágenes cacheadas/red interna reutilizada y WP/DB tmpfs. Sin SSH/pulls/puertos/SQL o config real/grants/CMS/Workers/contacto/export o backup real nuevo.32GiB libres al inicio; Docker/swap y archivos sintéticos pueden consumir disco. Contenedores detenidos; recursos previos retenidos.
