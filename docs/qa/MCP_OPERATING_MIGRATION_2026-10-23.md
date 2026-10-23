# Preparación reanudable de tablas operativas — 23 octubre

143 controles del contrato y132 regresiones pasan:275 integraciones,12 casos nuevos. Perfil completo de código original y rc.2, datos sintéticos, WordPress/MariaDB/ACF/Polylang/LiteSpeed reales.

CLI manage_options, conexión wpdb core y entorno local admitido. Cuatro definiciones DDL únicas; huella de definiciones, formato operating-storage-v1 y fase preparing/ready en una opción no autoload. La admisión de operaciones requiere ready exacto y comprueba además el esquema. No se activan abilities ni conceden scopes.

Se adquiere GET_LOCK ligado a DB/prefijo. Se comprueban todas las tablas existentes antes de escribir el marcador o crear tablas; no ALTER/DROP/conversión automática. Esquema desconocido, engine distinto, marcador de otra versión y datos sin ready previo se rechazan. Una instalación ready compatible es idempotente incluso con registros retenidos. El lock se libera en finally y se restaura el modo del driver.

DDL usa el mysqli original y modo estricto, sin repetición automática de wpdb; antes de cada SQL se comprueba que wpdb conserva ese handle. No hay transacción envolvente: DDL confirma por separado. Una interrupción conserva preparing y las tablas creadas; repetir valida lo existente y crea únicamente lo que falta. Solo tras verificar todas las tablas y el journal se escribe ready mediante CAS. Se limpian cachés de la opción. Una transacción ajena se rechaza sin confirmarla/revertirla.

Doce controles añadidos: marcador ausente bloquea; interrupción controlada tras primer DDL; preparing bloquea; reanudación válida; repetición ready idéntica; deriva de esquema rechazada; versión desconocida rechazada por migración y admisión; fuentes/marcador conservados; lock ocupado desde segunda sesión; transacción externa; preparing con datos no adoptados. La interrupción DDL es un checkpoint con excepción, no un SIGKILL nuevo. Los cuatro SIGKILL de fuente del adaptador siguen como evidencia aparte.

Alcance: preparador local de las cuatro tablas operativas, con las dos tablas de journal ya existentes/validadas como requisito; no instala desde cero las seis tablas ni convierte esquemas experimentales anteriores. Ready certifica admisión de almacenamiento, no aceptación editorial global. Candidato pendiente: preparación conjunta/reanudable del journal, aceptación global en reposo, schema/transporte MCP, export privado, paquete/manifiesto/preflight/reversión y autorización de instalación/permisos/diff. No ejecutar esto en destino como paquete final.

Una ejecución completa, imágenes cacheadas, red interna propia reutilizada y WP/DB tmpfs. Sin SSH/pulls/puertos/SQL o config real/grants/CMS/Workers/contacto/backups nuevos. Contenedores detenidos, recursos previos retenidos.32GiB libres al inicio; Docker/swap pueden consumir disco.
