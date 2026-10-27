# Persistencia local de clasificación — 27 octubre 2026

Repositorio separado en `wordpress/experimental/editorial-project-classification-proposals/repository.php`. Deriva del patrón transaccional del laboratorio de blog, sin editar aquel componente o paquetes sellados. Añade tres tablas SQLite de laboratorio, revisiones inmutables, lectura/listado solo del propietario, versión esperada y recibos por propietario/request_id. No escribe fuentes o taxonomías y no registra herramientas MCP.

**99 comprobaciones de almacenamiento y62 regresiones del planificador correctas.** PHP8.5.11/SQLite3.53.4, archivos temporales de uso exclusivo con permisos0600/directorio0700, sin red/WordPress/credenciales reales. [Recibo completo](project-classification-proposal-storage-2026-10-27.json). Lint de los cuatro PHP nuevos y git diff --check correctos.

- Reapertura devuelve revisiones y recibos exactos. Reintento devuelve la revisión original sin consultar la nueva fuente; reutilizar clave con payload/operación/versión diferente se rechaza. Orden de mapas y conjuntos no produce duplicados.
- Otro propietario no puede leer, actualizar o descubrir documentos; su inventario es independiente. Scopes ausentes/revocados y actor anónimo bloquean lectura/listado/replay. Son proyecciones sintéticas, no sesiones WordPress reales.
- Cambio explícito de base genera revisión nueva, mantiene proyecto/idioma y reemplaza el patch completo. No reescribe historia ni hereda campos omitidos del patch previo.
- Dos procesos con la misma solicitud obtienen exactamente una revisión/recibo. Dos escritores con la misma versión esperada producen un ganador y un conflicto, sin sobrescritura perdida.
- Fallo SQL al insertar recibo revierte head/revisión/recibo. SIGKILL antes del recibo deja cero registros; después del commit conserva los tres. El reintento genera o devuelve una única revisión. Lock ocupado produce storage_busy; se puede reintentar tras liberarlo.
- Se prueban las seis cuotas y su rollback, replay al alcanzar cuota y separación entre propietarios. No hay TTL, poda o API de borrado.
- Copia aislada VACUUM INTO y reapertura preservan head/historia/replay, restricciones por propietario e integridad SQLite. No se certifica respaldo/restauración del CMS o herramientas de hosting.

Durante la adaptación de pruebas, un subprocess de guard conservaba el namespace del laboratorio anterior y falló; se corrigió su referencia. La suite final pasa completa. Los experimentos previos se conservan intactos.

Límites: SQLite es un laboratorio, no el backend del sitio. No WordPress/Polylang/InnoDB, transporte HTTP/MCP, admisión de esquema preexistente, cifrado o protección frente al administrador de DB. No inyección de pérdida de conexión/acuse de commit. El llamador debe aportar actor/clasificación/catálogos confiables y completos; la copia sintética no define retención operativa.

Sin llamadas al CMS, instalación, grants, nuevas propuestas reales, cambios al piloto blog1/v1 o despliegue. Siguiente incremento: adaptador de contexto WordPress/Polylang y persistencia nativa separada, con pruebas reales aisladas antes de empaquetar o conectar herramientas.
