# Tema, MU y drop-ins — 22 octubre 2026

Resultado: **303 comprobaciones de integración y41 tests locales correctos** con rc.2,
los plugins del respaldo, los MU originales y el tema activo. No se instaló en el CMS real.
SHA256 rc.2 sin cambios:
`c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07`.

## Identidad y procedencia

Se verificaron contra el manifiesto privado los4044 archivos de temas (46865466B), dos MU
(41044B) y los9590 archivos de plugins del ensayo anterior. Un contenedor SQL nuevo importó
el dump ya verificado exclusivamente para consultar stylesheet/template. Devuelve solo
identidad/versión: **twentytwentyfour1.6**, sin child theme. El contenedor no ejecuta PHP,
no tiene red/puertos/binds del host y se detiene al terminar la consulta. La base de aplicación
se crea con datos sintéticos; no recibe el dump ni la configuración original.

MU originales: Hostinger Smart Auto Updates1.0.8 y Hostinger Preview Domain1.4.0. Hashes en
el resultado. No hay drop-ins originales de DB/caché/errores/mantenimiento en el manifiesto.
Solo existe index.php en la raíz de wp-content. No hay drop-ins omitidos deliberadamente.

## Efectos revisados y comprobados

- El MU de updates desactiva actualizaciones y redirige API/descargas de WordPress a proxies
  de Hostinger y localhost. El guard del ensayo y la red interna mantienen esas rutas offline,
  incluido localhost:7777. Se comprueban registro del callback y filtros con los argumentos
  que utiliza WordPress. No se ejercen descargas ni servicios reales del proveedor.
- El MU de preview se inicia con X-Preview-Indicator y puede reescribir URL, contenido,
  opciones, metadatos y SQL si el dominio cambia. En HTTP interno normal conserva el contexto
  canónico. Con header de preview, home/site pasan a https://wordpress y el guard del candidato
  impide registrar sus abilities. Las filas editoriales permanecen iguales y la siguiente
  petición normal recupera las abilities. No se usa preview para escribir propuestas.
- El tema activo y los dos MU deben aparecer entre archivos incluidos y conservar sus hashes,
  versión/estado antes/después. Los18 plugins originales/13 activos permanecen como se observaron.
- 288 checks repetidos justificadamente bajo esta nueva combinación:67 framework,44 historial,
  37 cuotas/fallos,86 HTTP/MCP y54 GraphQL/ACF. Las seis carreras tienen ahora tanto resúmenes
  de edición como los cuatro de admisión, todos con workers independientes/solapamiento.
- 15 checks adicionales de carga, actualizaciones/proxy, preview, invariancia editorial,
  recuperación del contexto y ausencia de drop-ins. No sumar288 anteriores a303 como cobertura
  distinta: son la misma regresión ejercida con componentes adicionales.
- 41 tests locales pasan; siete nuevos cubren identidad segura de tema, paths/nulls/enlaces,
  hash de SQL antes de Docker y aislamiento/parada de la base de metadatos, incluso con error.

Primer intento: prefijo ambiguo detectado antes de iniciar la base; se precisó identificación
mediante las cinco tablas básicas. Segundo intento pasó la regresión pero el fixture llamó
auto_update_plugin con un solo argumento; WPGraphQL requiere el segundo, como WordPress real.
Se corrigió el fixture, no los plugins. Ejecución final completa exitosa y sin fallos.

## Aislamiento, almacenamiento y límites

Sin SSH, CMS, nuevos scopes reales, frontend, Workers, contacto ni descargas. Paquete intacto.
Metadata DB: network_mode:none, skip-networking, cero mounts host; estado exited confirmado.
Aplicación: Docker internal:true confirmado, cero puertos, wordpress/db exited. HTTP externo,
correo, cron y updates bloqueados antes del bootstrap del ensayo. Todos los logs privados
quedan fuera del repo; solo se guarda metadatos/resultados sintéticos.

Los archivos adicionales se copian a tmpfs existente, aproximadamente44,7MiB lógicos. No se
crean nuevos backups del host. DB temporal512MiB/8MiB de socket, secuencial y detenida antes
de la aplicación. RAM/swap/capas Docker añaden consumo; libre final aproximadamente35,7GiB.
La carpeta privada exitosa ocupa40960B de metadatos/logs. Intentos previos se conservan,
sin borrados programados. La DB temporal no es una copia durable de recuperación.

No se afirma replicar LiteSpeed/servicios/proxies/TLS públicos ni todas las opciones del
servidor. Se usan core e imágenes fijadas, datos/opciones sintéticos y HTTPS simulado dentro
de HTTP. La aceptación de código/restauración local está completada dentro de este alcance;
estos límites no justifican añadir indefinidamente laboratorios antes de presentar el cambio.

Siguiente: preflight final de deriva de versiones/archivos, colisiones de esquema y cuenta2;
preparar procedimiento de instalación/reversión y paquete/hash/scope mínimo para decisión
concreta. Alex ejecutará los comandos remotos. El smoke posterior autorizado verificará
comportamiento real, scopes y fuentes. La instalación sigue HOLD hasta esa decisión.

[Resultado exacto](MCP_PROPOSALS_HOSTING_COMPONENTS_RESULT_2026-10-22.json).
