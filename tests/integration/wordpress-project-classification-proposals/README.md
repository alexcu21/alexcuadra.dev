# Clasificación: WordPress/Polylang/InnoDB nativos

Ejecutar desde la raíz: `python3 tests/integration/wordpress-project-classification-proposals/run.py` con Docker local y las dependencias ya cacheadas. Imágenes fijadas por digest en el compose base del laboratorio de blog; ZIPs Polylang3.9.1 y MCP Adapter0.7.1 verificados por SHA256. No descarga plugins, SQL, cuentas o configuración del CMS. Base nueva sintética, red interna y sin puertos publicados.

El ejecutor monta código de solo lectura y carga el fixture del lector experimental de clasificación; registra proyectos/taxonomías traducibles y bloquea correo/salidas HTTP externas. El nuevo repositorio se carga solo desde CLI. La prueba instala explícitamente sus cuatro tablas propias y crea usuarios/scopes sintéticos, retirados al final. No registra nuevas herramientas, cron, formularios o rutas.

Guard propio: flag exacto, entorno local, DB_HOST=db, home/site=http://wordpress y single site. Además exige el guard del lector local. No es un plugin distribuible o admisión para el hosting. El ensayo usa el wpdb global/core7.1.3 y mysqli; no copiar BEGIN/locking de SQLite.

70 comprobaciones nativas: motores/collation, instalación/enrollment separados, contexto público cerrado, categorías/tecnologías/idioma/reciprocidad, términos no disponibles, EN/ES, fuente protegida/privada/draft/tipo incorrecto, estado real frente a WP_Post cacheado, taxonomías compartidas rechazadas, propietario/permisos/revocación, replay, versiones, concurrencia de dos procesos, fallo SQL al guardar recibo y rollback, y huellas de tablas editoriales conservadas. PHP8.5.11/WP7.1.3/MariaDB11.8.9/Polylang3.9.1; el parche PHP del hosting es distinto.

Informe verificable con casos únicos y mínimo60; un informe vacío falla. Se corregió un contador con ámbito local de eval-file y se repitió en un proyecto nuevo. Solo se acepta el segundo ensayo con70casos. [Recibo y hashes](../../../docs/qa/project-classification-proposal-native-2026-10-27.json).

El `finally` detiene únicamente el proyecto del ensayo; no prune o eliminación de otros proyectos. Los volúmenes son tmpfs: detener los contenedores descarta el estado sintético. Para repetir, usar un identificador propio `PROJECT_CLASSIFICATION_LAB_PROJECT=alexcuadra-classification-proposals-...`; no reutilizar una base en ejecución. Las redes internas de ambos ensayos quedaron sin conexiones.

Pendientes: pérdidas/sustituciones de conexión y SIGKILL en esta nueva implementación nativa, seis cuotas completas en MariaDB (cubiertas en SQLite), transporte HTTP/MCP y privacidad en REST/GraphQL, backup/restauración de tablas nativas, esquema/admisión y coexistencia con el perfil completo del CMS. Ninguna prueba SQLite o del módulo anterior sustituye esos ensayos del nuevo módulo.
