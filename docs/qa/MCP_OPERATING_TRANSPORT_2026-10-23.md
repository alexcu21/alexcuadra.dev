# Transporte MCP operativo — aceptación local

68 comprobaciones nuevas por MCP HTTP+50 migración/aceptación+144 contrato+132 regresiones:394 integraciones correctas. No sumar ejecuciones repetidas. Perfil completo de código original/rc.2, adaptador oficial MCP0.7.1, WP7.1.3/PHP8.5 y datos sintéticos.

operating-abilities.php registra explícitamente seis abilities, únicamente cuando el fixture local invoca register_operating_abilities: prepare-project-application, inspect-project-review, apply-project-review, prepare-project-recovery, recover-project-review y get-project-effect-state. No instala hooks al cargarlo. Todas usan alexcuadra/ y categoría propia. No hay aprobación, aceptación, auditoría, migración, export ni consumidor de purga por MCP.

Input/output JSON Schema cerrados por operación, IDs/huellas/idioma/clave obligatorios, límites y recibos versionados. Inspección admite documentos apply/recover mediante oneOf; devuelve el diff solo al dueño autorizado. Metadatos MCP públicos para descubrimiento autenticado; public=false/show_in_rest=false para API nativa. Las acciones de fuente se anotan destructive=true, lectura readonly=true e idempotencia declarada para clave exacta. No se conceden scopes.

Permission callback exige rc.2 read/public-read/prepare más apply o recover según operación; inspect/estado aceptan cualquiera de estos dos. Admin sin scope y prepare-only se rechazan. Factory valida dependencias/perfil. Los errores conocidos tienen código/estado controlados; excepciones desconocidas devuelven mensaje genérico sin SQL/rutas. No se interpreta una respuesta perdida como permiso para otra clave.

Ensayo real JSON-RPC initialize/notifications-initialized/tools-call contra mcp-adapter-default-server en HTTP interno: Application Passwords sintéticas; descubre6 e inputs cerrados; no descubre acciones de operador; anonymous, admin/prepare-only, input extra, dueño ajeno, sesión ajena y scope revocado rechazados. Propuesta rc.2 por MCP→review→inspección→apply denegado sin aprobación→aprobación CLI independiente→apply/replay→recovery/inspección→denegada sin aprobación nueva→CLI→recover/replay→último efecto pending. Valores EN/ES originales recuperados. No se consume purga inline.

Se comprueba ocultación en listado REST nativo y fallo interno sintético en factory oculto también en HTTP; el encabezado para inyectarlo existe solo en fixture del laboratorio, no en módulo de abilities. La primera pasada61 checks se amplió con inicialización completa, sesión/revocación/fallo HTTP; evidencia aceptada segunda ejecución68. No se verifica MCP remoto del CMS ni TLS real del hosting: HTTP interno simula contexto HTTPS, red sin puertos/acceso externo.

Pendiente: export privado, paquete/manifiesto con admisión y guardas para destino, preflight/reversión, aceptación con pausa externa declarada y aprobación de instalación/scopes/diff. El registro permanece en fixture con guardas local; no hay ZIP instalable nuevo, cambios de plugin instalado ni permisos CMS reales.

Imágenes cacheadas, red interna propia reutilizada, WP/DB tmpfs; sin SSH/pulls/SQL o config real/Workers/contacto/backups nuevos. Contenedores detenidos y recursos previos retenidos.32GiB libres al inicio; Docker/swap pueden consumir disco.
