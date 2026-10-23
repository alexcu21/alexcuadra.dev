# Validación de registros operativos — 23 octubre

111 controles del contrato y132 regresiones pasan (243 integraciones;9 casos nuevos). Perfil de código completo, rc.2 y datos sintéticos con WordPress/MariaDB/ACF/Polylang/LiteSpeed reales.

Aprobaciones: documento acotado, claves cerradas, IDs enteros positivos, hash, revisión/executor/actor ligados a la fila y al documento revisado, actor independiente, fecha dentro de vigencia y consumed_event nulo o hex64. Se comprueba también al repetir una aprobación existente.

Recibos de prepare/prepareRecovery/apply/recover: tamaño/profundidad, conjunto de campos y formato por operación, IDs, idioma y vínculo con los IDs de entrada, hashes, estado de contenido y efecto congelado pending. Se validan antes de devolver un recibo almacenado. No se alteran los DTO existentes.

Aplicaciones: columnas exactas, enteros SQL canónicos sin desbordamiento, IDs/hashes y coherencia entre revisión/evento de recuperación y reservas (573440/1/2 inicialmente,12416/0/1 tras preparar recuperación,8192/0/0 tras recuperarla). Admisión al leer aplicación para recuperar o consultar estado. La consulta agregada de cuotas no sustituye una auditoría completa de filas.

Nueve casos: aprobación con campo extra, revisión incorrecta o actor de tipo incorrecto; comprobación de fuente intacta; recibo con campo extra, idioma incorrecto o ID de tipo incorrecto; reserva de aplicación inconsistente; repetición idéntica de recibo válido. Se restauran las filas sintéticas tras cada inyección.

Límites: valida estructura y vínculos indicados, no autentica una DB manipulada por un actor privilegiado. Faltan decoders finales de journal/efectos y auditoría de relaciones entre tablas, migración/admisión finales, transporte/schema MCP, export privado, paquete/manifiesto/preflight/reversión y aceptación autorizada. No hay candidato instalable nuevo.

Una ejecución completa en imágenes cacheadas, red interna propia reutilizada y WP/DB tmpfs; sin puertos, pulls, SSH, SQL/config real, grants, cambios CMS, Workers/contacto o respaldos nuevos. Contenedores detenidos; recursos y respaldo anteriores retenidos.32GiB libres al inicio; Docker/swap pueden consumir disco.
