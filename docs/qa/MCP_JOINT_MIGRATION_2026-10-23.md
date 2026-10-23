# Preparación conjunta desde cero — 23 octubre

45 comprobaciones de migración,140 de contrato y132 regresiones correctas:317 integraciones. Las pruebas de arranque/interrupción antes incluidas en el contrato se trasladaron al runner de migración; no sumar143+45 ni ejecuciones repetidas. Perfil completo original/rc.2 y datos sintéticos con WP/MariaDB/ACF/Polylang/LiteSpeed reales.

El preparador incluye source_events/source_effects y las cuatro tablas operativas. Un único conjunto de definiciones DDL y una huella abarcan las seis tablas. operating-storage-v2 sustituye al marcador local anterior: versiones previas se rechazan, sin conversión/adopción automática. No hay ese marcador experimental en producción.

Cada tabla existente se valida antes de crear las faltantes; el journal admite validación individual para un esquema parcial. Ready se escribe solo después de validar las seis tablas y los requisitos transaccionales del núcleo. Se mantienen lock de migración, SQL sobre mysqli original, preparación reanudable, bloqueo de operaciones operativas sin ready y rechazo de datos no admitidos. No ALTER/DROP de migración ni grants automáticos. El instalador genérico de journal conserva tablas completas compatibles y rechaza conjuntos parciales: el preparador conjunto es el que los reanuda.

El ensayo verifica que ninguna de las seis tablas existe al comenzar; interrumpe mediante excepción tras cada DDL, comprueba tablas conservadas e indisponibilidad, y reanuda. En el primer esquema parcial añade una columna incompatible: se rechaza antes de crear source_effects. Se restaura solo esa alteración de fixture y continúa. Ready/repetición completa pasan. No se inyecta SIGKILL nuevo en DDL; las cuatro muertes de worker de fuente siguen como regresiones independientes.

La primera ejecución encontró un contador de informe desconectado del ámbito de WP-CLI (reportaba0); se corrigió y el runner exige mínimo45 comprobaciones. La evidencia aceptada corresponde a la segunda ejecución completa. El contador correcto es45, sin sumar ambas ejecuciones.

Pendiente: aceptación global con escritores detenidos, schema/transporte MCP operativo, export privado, empaquetado con admisión para destino, manifiesto/preflight/reversión y autorización de instalación/permisos/diff. Ready certifica almacenamiento, no aceptación editorial ni entrega de caché hosting. El código conserva guardas de laboratorio: no es aún un plugin instalable en destino.

Imágenes cacheadas, red interna propia reutilizada y WP/DB tmpfs; sin SSH, pulls, puertos, SQL/config real, grants, CMS/Workers/contacto/backups nuevos. Contenedores detenidos y recursos previos retenidos.32GiB libres al inicio; Docker/swap pueden consumir disco.
