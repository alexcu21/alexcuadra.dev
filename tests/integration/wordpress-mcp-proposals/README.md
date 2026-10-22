# Aceptación CLI y archivo sintéticos del candidato

**Preparado; ejecución intentada y detenida antes de instalar WordPress el22oct.** Alex abrió Docker; el seed tmpfs anterior estaba vacío. No pasó la aceptación ni el ensayo de archivo. No instalar en el hosting basándose en este arnés. El ensayo es independiente de WordPress compartido y de los laboratorios anteriores. No usa SSH ni descarga el CMS. No crea un nuevo bypass en el candidato.

Intento de recuperación de dependencias públicas: `cache-plugins.py` fija las seis versiones y limita48MiB descargados/256MiB extraídos en `/private/tmp/alexcuadra-proposals-public-plugins-v1`, TLS verificado con CA del sistema. **La primera URL, Polylang3.9.0, respondió404; no se descargó ningún plugin.** No se eligieron versiones sustitutas ni se desactivó TLS. Esa caché auxiliar no está conectada al Compose ni se considera un seed verificado; falta obtener los artefactos exactos revisados o resolver su procedencia antes de cambiar el modo de seed. No repetir la descarga sin resolver la versión/origen.

El intento dejó wordpress/db de este proyecto detenidos; sin core install, scopes ni migraciones. Se conservaron contenedores/volúmenes, no se hizo down -v ni poda. La nueva comprobación de contenido de seed ocurre antes de asignar otro proyecto. No confundir la existencia de un volumen tmpfs detenido con respaldo recuperable; el fallo lo demostró.

## Prerrequisitos y almacenamiento

Docker Desktop en ejecución, imágenes fijadas ya locales y volumen retenido de un laboratorio sintético con Polylang3.9.0, MCP Adapter0.7.1, WPGraphQL2.24.2, ACF6.8.10, WPGraphQL ACF3.0.0 y WPGraphQL Polylang0.7.1. El runner rechaza imágenes ausentes en lugar de descargarlas. Requiere8GiB libres como umbral de trabajo, no estimación del consumo exacto. Última captura local31GiB libres. No iniciar Desktop automáticamente sin conocer la configuración del usuario.

Seed permitido por nombre y etiqueta Compose: `alexcuadra-editorial-application-lab_wp-data` (por defecto) o `alexcuadra-editorial-graphql-lab_wp-data`. El volumen se monta solo lectura en un contenedor sin red; solo se copian seis directorios fijos de plugins. No copia wp-config, usuarios, DB ni datos de fuentes. Un volumen tmpfs retenido puede estar vacío después de detener su último contenedor: si faltan plugins, el ensayo se detiene, no descarga ni reconstruye silenciosamente el laboratorio anterior.

Proyecto nuevo `alexcuadra-editorial-proposals-acceptance`; DB tmpfs512MiB para DB original+restaurada, archivos WordPress tmpfs768MiB, archivo SQL tmpfs64MiB. Máximo configurado de esos mounts1344MiB; RAM/swap, capas de contenedor, logs e infraestructura Docker añaden consumo, y los límites de mounts no son reserva de disco ni cota total. No obtiene copias del CMS de≈690,5MiB. No poda/borra volúmenes antiguos. `stop` no es un respaldo y un tmpfs puede perder sus datos al desmontarse.

Rechaza contextos Docker remotos, puertos de host, seeds desconocidos y proyecto con contenedores/volúmenes existentes. Usa solo socket local e imágenes cacheadas (`--pull never`). No vuelve a ejecutar sobre un proyecto previo ni hace reset/down -v. Si queda una ejecución parcial se inspecciona antes de decidir conservación o limpieza explícita.

## Aislamiento y guard exacto

Red Compose `internal:true`, seed `network_mode:none`, sin puertos publicados. WP_HOME/WP_SITEURL y opt-in tienen el origen exacto del guard dentro de esta copia sintética. Eso no crea un vínculo con DNS/hosting: el ensayo CLI no realiza solicitudes a ese origen y la red no tiene salida externa. Fixture MU bloquea APIs HTTP de WordPress y correo antes de activar plugins; cron/actualizaciones/file mods deshabilitados. No es una prueba de todos los efectos de Hostinger/LiteSpeed ni una clonación de su perfil; esos plugins no se copian en este arnés. No se afirma que los filtros PHP bloqueen todos los transports, por eso se añade aislamiento de red.

El runner compara ZIP, manifiesto y fuentes byte por byte antes de copiar los tres archivos al volumen nuevo. El candidato, su guard, versión y ZIP no se transforman. Instala solo en DB sintética un administrador y usuarios ficticios con credenciales ficticias. Migra dos tablas con operador CLI y concede scopes únicamente a fixtures sintéticos.

## Ejecución prevista

Desde la raíz del proyecto, con Docker listo:

```sh
python3 tests/integration/wordpress-mcp-proposals/run.py
```

El agente debe obtener acceso al socket mediante el mecanismo normal del sandbox si es necesario. No se requiere SSH. No ejecutarlo en el servidor real ni sustituir el seed por un volumen del CMS. El runner detiene wordpress/db de su propio proyecto en finally; no altera los demás proyectos. El resultado final `PROPOSALS_ACCEPTANCE_RESULT` solo existe después de las aserciones, no se considera exit0 ni arranque de Docker como prueba suficiente.

## Qué comprobará

- Tres suites existentes `check/history/quotas`, adaptadas **solo en tests temporales** para namespace/origen/tabla del candidato; conservan originales y datos sintéticos. Cubren framework, versiones, historial, recibos/cuotas y fallos SQL seleccionados. No transportes HTTP ni carreras entre procesos en este incremento.
- Fixture de archivo: crear propuesta, actualizar y guardar digest de filas fuente/propuestas/meta/relaciones/owners/recibos, DDL de tablas privadas y versión de esquema.
- Dump SQL consistente de DB sintética con mariadb-dump dentro del contenedor, SHA256/tamaño, restauración en `wordpress_restore` separada. El archivo permanece en tmpfs del contenedor, no en el repositorio o el equipo como dump descargado.
- En proceso CLI nuevo contra DB restaurada, igualdad de digest, replay exacto de recibos create/update, cuota sin cambio y cabeza actual conservada; fuentes originales del fixture intactas. Revocar scopes del dueño de archivo en ambas bases al terminar con éxito. Las suites previas incluyen cambios deliberados sobre sus fuentes sintéticas para comprobar invalidación: no afirmar que todos los fixtures quedaron inmutables.

Pendiente incluso si pasa: aceptación HTTP/MCP/GraphQL de este CPT, carreras, esquema exhaustivo frente a tablas incompatibles, perfil de hooks real, respaldo privado real restaurado, destino/responsable/retención y aprobación concreta de instalación/scope. El dump sintético solo valida el mecanismo de ensayo; no certifica un respaldo del hosting ni implementa un exportador editorial selectivo.
