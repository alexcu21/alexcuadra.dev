# Esquema estricto y candidato rc.2 — 22 octubre 2026

Resultado: migración endurecida y regresión sintética correcta. Instalación real HOLD.
Nuevo ZIP0.1.0-rc.2, SHA256
`c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07`.
rc.1 conserva sus bytes/hash y su evidencia histórica; no es el candidato vigente.

## Cambio y motivo

rc.1 solo comprobaba nombres de columnas y motores; un índice o tipo incompatible podía
invalidar bloqueo por dueño, idempotencia o persistencia de recibos. rc.2 compara tipos
normalizando únicamente el ancho visual de BIGINT, unsigned, null/default/extra/comment,
orden de columnas, collation de respuesta, PK completa/orden/no prefijos, ausencia de otros
índices, constraints, triggers visibles, particiones, motor y tipo de tabla. Respuesta LONGTEXT
usa utf8mb4_bin explícito. No ALTER, reparación automática ni eliminación de tablas/datos.

La comprobación ocurre antes de DDL y después de CREATE IF NOT EXISTS. Se revalida aunque
exista la opción0.1. Un fallo retira el marcador de disponibilidad. Una transacción externa
se rechaza antes de DDL para conservarla abierta. Una creación fallida puede dejar tablas
vacías parciales; no se borran automáticamente. Esto no valida el contenido semántico de
recibos preexistentes ni certifica visibilidad de triggers sin privilegios; tampoco vigila
continuamente cambios posteriores hechos por administradores de DB.

## Pruebas ejecutadas

- **208 comprobaciones SQL reales**, WP7.1.3/PHP8.5.11/MariaDB11.8.9. Rechazan28 variantes:
  enteros firmados/estrechos/autoincrementales, columnas extra/omitidas/reordenadas,
  defaults/null/comments, binarios cortos/textuales, LONGTEXT/collation incorrectos,
  PK invertida/incompleta/con prefijo/ausente, índices/unique/check/FK adicionales,
  MyISAM en cada tabla, particiones y trigger. Cada mutación se ejecuta correctamente
  antes de exigir rechazo; después se comprueba que tablas/filas permanezcan idénticas.
  También seis errores SQL de introspección, marcador obsoleto, preparación repetida,
  tabla parcial incompatible, transacción externa y operador anónimo.
- **95 fronteras en9 procesos PHP con stubs** pasan con el nuevo código. Se mantienen
  seis abilities privadas, guards, denegación sin scopes y ausencia de apply/recover.
- **165 funcionales CLI/SQL/restauración sintética** pasan con rc.2 y PHP8.5.11:
  67 framework,44 historial,37 cuotas/idempotencia/fallos SQL,7 archivo y10 restauración.
  ZIP/manifiesto/fuentes coinciden byte por byte. Es el subconjunto público en caché,
  con WPGraphQL2.24.2; no certifica WPGraphQL2.25.0 ni Hostinger/LiteSpeed del hosting.
- **27 tests locales de respaldo/aislamiento** pasan. La espera de MariaDB exige el PID1
  final para evitar aceptar el servidor temporal de inicialización. El ensayo inicial
  detectó esa carrera; los fixtures posteriores corrigieron variables globales requeridas
  por WP-CLI eval-file. Solo se atribuyen resultados a las ejecuciones finales completas.

La suite de esquema usa network_mode:none y socket Unix. La regresión CLI usa red Docker
internal sin puertos host, fixture que bloquea correo/HTTP externo y cron desactivado.
Imágenes y plugins ya en caché; sin descargas, SSH, backup privado adicional o CMS real.
Los contenedores de ambos proyectos se detuvieron; recursos anteriores conservados.
Tmpfs configura DB768MiB+runtime512MiB en esquema y DB512MiB+WP768MiB+SQL64MiB en CLI;
RAM/swap/capas/logs de Docker añaden consumo. Espacio libre final registrado en resultado.

## Pendientes

Aceptación funcional del perfil completo actualizado y transporte HTTP/MCP/GraphQL/carreras
de rc.2. Los140 checks HTTP del paquete rc.1 no se atribuyen a rc.2. Respaldo real básico
restaurado ya validado en el incremento anterior; no repetir exportación sin necesidad.
Después de cerrar puertas, presentar paquete/hash/scope para decisión concreta de instalación.
Read, laboratorio, permisos reales, contacto, frontend y Workers permanecen intactos.

Evidencia: [esquema](MCP_PROPOSALS_SCHEMA_RESULT_2026-10-22.json) y
[regresión CLI](MCP_PROPOSALS_RC2_CLI_RESULT_2026-10-22.json).
