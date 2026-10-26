# Propuestas privadas: WordPress/MariaDB/Polylang

```sh
sh tests/integration/wordpress-blog-proposals/run.sh
```

Solo laboratorio fresco, sin importar datos/configuración/backups del hosting. Usa las imágenes por digest y los ZIP públicos verificados del laboratorio de lectura, una red internal y ningún puerto publicado. Los mounts de código y pruebas son readonly. Fixture de correo bloqueado, cron y HTTP externo desactivados. Instala Polylang/Adapter; no instala el lector ni registra nuevas abilities o rutas.

El runner exige una instalación vacía y detiene únicamente su proyecto al salir. Para repetir tras un cambio justificado, indicar BLOG_PROPOSAL_LAB_PROJECT con un nombre nuevo que empiece por alexcuadra-blog-proposals-lab-. No borrar/podar recursos ajenos. WordPress y BD usan tmpfs limitado a256MiB cada uno.

72casos reales en la pasada final, incluidos cuatro procesos WP-CLI secundarios para dos carreras. Los usuarios son subscriber con un scope sintético de preparación, eliminado al terminar. Son usuarios reales de WordPress seleccionados por el fixture mediante wp_set_current_user, **no autenticación HTTP probada**. Captura recibo único y exige conteo completo; una salida0 de WP-CLI sin recibo válido no basta.

[Evidencia, errores corregidos y límites](../../../docs/qa/MCP_BLOG_PROPOSAL_WORDPRESS_2026-10-26.md). Las pruebas de SIGKILL anteriores son SQLite: no atribuirlas a MariaDB. El siguiente incremento debe cubrir transporte/autenticación y pérdida de conexión/proceso en la misma conexión wpdb antes de cualquier paquete.
