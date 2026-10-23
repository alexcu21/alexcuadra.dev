# Admisión estricta del almacenamiento operativo — 23 octubre

102 controles del contrato y132 regresiones pasan (234 integraciones,8 casos nuevos). Perfil completo original y rc.2, WordPress/MariaDB/ACF/Polylang/LiteSpeed reales y datos sintéticos. No se suman ejecuciones repetidas ni las pruebas de frontera anteriores.

Las cuatro tablas operativas se comprueban contra columnas en orden, tipo, nulabilidad, default, auto_increment e índices exactos con orden/unicidad/tipo/prefijo. Documentos y respuestas LONGTEXT se crean con utf8mb4_bin explícito. Se rechazan triggers y constraints CHECK/FK; InnoDB sigue obligatorio. Inspección y estado verifican también el esquema, además de las rutas transaccionales. Fallos SQL cierran la admisión.

Las revisiones guardadas se decodifican con límites de bytes/profundidad y conjunto cerrado de claves por operación. Exigen IDs enteros positivos, huellas hex64, idioma admitido, TTL600s exacto, campos válidos y mismo conjunto antes/después; se mantiene hash del documento y vínculo dueño/propuesta de la fila. La validación de hash sola no sustituye validar estructura.

Ocho casos añadidos: columna, índice, CHECK y collation inesperados; clave desconocida, ID con tipo incorrecto, campos antes/después distintos y duración alterada. Los documentos inválidos se prueban con hash recalculado para verificar que se rechaza su estructura y no solo un hash diferente. El resto de102 controles conserva apply/recover, reservas, aprobación, desconexiones y recibos anteriores.

Alcance local: se instala en tablas nuevas del ensayo. No hay migración final/reanudable de esquemas previos ni conversión automática; esquemas experimentales anteriores con collation distinta se rechazan. Faltan decoders finales de aprobaciones/recibos/app/effect y migración/admisión del candidato, schemas/transporte MCP operativo, export privado, ZIP/manifiesto/preflight/reversión y aceptación autorizada. No se presenta un paquete instalable.

Una ejecución completa con imágenes cacheadas y red interna propia reutilizada; sin puertos, pulls, SSH, SQL/config real, grants, CMS o Workers/contacto. WP/DB tmpfs768+512MiB, contenedores detenidos y recursos anteriores retenidos.32GiB libres al inicio; Docker/swap pueden usar disco. No nuevos respaldos ni borrados automáticos. [Contrato](../WORDPRESS_MCP_OPERATING_CONTRACT.md).
