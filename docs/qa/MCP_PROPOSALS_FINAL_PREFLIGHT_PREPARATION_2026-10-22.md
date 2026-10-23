# Preflight final preparado — 22 octubre 2026

Listo para que Alex ejecute el comando de solo lectura del
[procedimiento revisable](../WORDPRESS_MCP_PROPOSALS_INSTALL.md). No se ejecutó SSH ni
se consultó/modificó el CMS. Aprobación de instalación pendiente después de recibir salida.

Entregables: plantilla PHP de inspección, generador sin SSH y wrapper sh autocontenido de
10786B con baseline público codificado. Incluye solo metadata de versiones/activación,
cuatro huellas agregadas de árboles de archivos y tema activo; ningún dump, contenido,
credencial, nombre de DB o configuración privada. Baseline derivado del respaldo restaurado
y de los perfiles ya aceptados; no se vuelve a exportar ni copiar el respaldo.

Comprueba versiones WP/PHP/DB, plugins, archivos de plugins/MU/temas/raíz de wp-content,
tema activo, ausencia de Proposals/opt-in/tablas/marker, motores InnoDB de posts/postmeta,
cuenta2/subscriber/read/scope público sin permisos de edición/admin/prepare ni grants globales,
Application Passwords, CPT público/traducible y abilities API. Enumera IDs de administradores
con manage_options para elegir después el operador de migración, sin conceder permisos.

Huella de árbol: ordenar rutas relativas por bytes, concatenar ruta+NUL+SHA256hex+LF y SHA256
final; informar número de archivos/bytes. Rechaza enlaces, archivos especiales y presupuestos
excedidos. Raíz de wp-content ignora debug.log/directorios; dentro de cada árbol se incluyen
todos los archivos regulares. La lectura puede detectar cambios concurrentes como deriva;
no impone locks ni modifica archivos para conseguir coincidencia.

Validación local:

- 46 tests correctos. Cinco métodos nuevos cubren17 escenarios de stubs PHP: combinación
  correcta, roles/scopes excesivos/ausentes/globales, edición/admin efectivos, versiones,
  colisión de tablas/marker/opt-in, MyISAM/fallo SQL, contexto preview y archivos alterados/
  extra/enlaces. La instancia DB simulada rechaza comandos que no empiecen por SELECT.
  Se exige restaurar el usuario actual en memoria y nunca autorizar instalación en el resultado.
- Lint PHP y sh correctos; generación local sin red/SSH ni Docker nuevo.
- La función PHP de huella se ejecutó sobre los cuatro árboles reales restaurados, sin cargar
  WordPress ni ningún archivo del respaldo como PHP. Coincide con el baseline calculado
  desde el manifiesto privado en Python para los cuatro componentes.
- No se atribuyen los stubs a aceptación de la cuenta/servidor actuales: sigue pendiente
  el resultado de Alex. No se ejercen privilegios DDL en el preflight.

El operador no escribe opciones, archivos, grants, contenido ni esquema. WP-CLI arranca el
WordPress normal del servidor y carga sus hooks; el script no promete impedir escrituras
internas de caché del proveedor. Salida limitada a checks/IDs/versiones de candidato.

Impacto: lectura de aproximadamente198MiB existentes, sin scratch ni exportación remota;
localmente solo texto/JSON. Retener backups y laboratorios anteriores sin borrados/prune.
El plan especifica rc.2/hash, scope único en usuario2, opt-in/tablas, smoke de una propuesta
privada y reversión conservando Read y datos. Comandos de mutación se concretan con IDs/rutas
observados después del preflight, antes de pedir aprobación específica.

[Registro de preparación](MCP_PROPOSALS_FINAL_PREFLIGHT_PREPARATION_RESULT_2026-10-22.json).
