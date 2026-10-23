# Adaptador: HTTP y SIGKILL sobre MariaDB — 23 octubre

**46 comprobaciones HTTP correctas +58 de regresión CLI** en WP7.1.3/MariaDB11.8.9/ACF6.8.10/LiteSpeed7.9.2. Son dos suites, no repeticiones sumadas como cobertura nueva. Mismo runner/cache de imágenes, código de los dos plugins verificado contra el respaldo; ninguna base/config original importada.

Las requests usan REST/Apache real con Application Passwords de usuarios sintéticos. Se rechaza anónimo/subscriber sin crear intención. Escritura normal conserva tags/cabeceras durante la transacción, confirma efecto pending y no emite cabecera de purga. El consumidor posterior sí devuelve X-LiteSpeed-Purge nativa. Un aborto HTTP409 revierte fuente completa y no emite purga.

Cuatro cortes SIGKILL reales: antes de COMMIT, después de COMMIT antes de responder, consumidor antes de API, y después de API antes de guardar acknowledgment. Un marcador por otra conexión y el cierre del transporte prueban llegada al punto de corte. Antes de COMMIT, contenido/meta/relaciones/timestamps y contador transaccional vuelven al estado original y no hay intención. Después, fuente/recibo/intención sobreviven; replay conserva versión y contador1. Consumidores muertos dejan lease running que bloquea otra ejecución hasta expirar. Se expira el lease de fixture por SQL para evitar esperas y se retoma; API contada1 o2 según el corte, source writer permanece con una sola ejecución. No se promete exactamente una vez.

Se factorizó support.php para que CLI/HTTP ejerzan los mismos snapshots/helpers. No se modificó el ejecutor experimental ni los plugins instalados. No hay endpoints nuevos en paquetes: todas las rutas y modos de corte son fixtures del laboratorio, con opt-in/env/Host y permisos del usuario sintético. Las credenciales permanecen en memoria del ensayo, no en informes/Git. Sin SSH, llamadas al CMS compartido, nuevos grants reales, Workers o contacto.

Limitaciones: siguen pendientes el perfil completo (Polylang, otros plugins, MU/tema/opciones), lector bilingüe y autorizador final/cuotas/contrato operativo. HTTP interno no es TLS público ni prueba de entrega de invalidación del hosting. Se añaden estas dos pruebas a la cobertura de CLI, no se convierte esta capa en candidato instalable.

Almacenamiento:36GiB libres al inicio; sin pulls ni respaldo nuevo. Volúmenes tmpfs512+768MiB, uso RAM/swap posible; contenedores del nuevo proyecto detenidos al finalizar. Pequeños scripts/logs/informes locales, respaldos y laboratorios previos conservados.

Pendiente y estimación concreta en [plan de cierre](../WORDPRESS_MCP_SOURCE_REMAINING.md):1–2 iteraciones para cerrar el adaptador;4–6 en total para contrato/candidato e instalación autorizada, condicionado a los resultados y decisión de edición pública. No añadir fases sin explicar qué fallo/condición nueva las requiere.
