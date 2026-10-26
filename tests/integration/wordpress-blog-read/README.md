# Integración real del lector MCP del blog

Desde la raíz del repositorio:

```sh
sh tests/integration/wordpress-blog-read/run.sh
```

Requiere Docker, Node22 y Python3. Usa un proyecto Compose propio, sin puertos del host ni montajes de configuración real. WordPress/PHP/MariaDB se fijan por digest. Los ZIP de Polylang3.9.1 y MCP Adapter0.7.1 se descargan previamente desde WordPress.org y se comprueban por SHA256; quedan en la caché ignorada de node_modules. El laboratorio se ejecuta en una red internal y bloquea llamadas WordPress a hosts externos. La obtención inicial de imágenes/ZIP sí requiere red del host.

La BD y WordPress tienen volúmenes tmpfs limitados a256MiB cada uno. Solo se montan el candidato, los scripts del laboratorio y esos ZIP, en lectura. El fixture evita correo y usa cuentas, contraseñas, contenido y términos ficticios. Ninguna cuenta/credencial del CMS se carga en el contenedor.

El runner detiene únicamente este proyecto al terminar o fallar. No poda Docker, elimina imágenes ni toca otros proyectos. Exige un resultado JSON completo de cada fase: falta de marcador, fallo o limpieza incompleta se rechazan aunque WP-CLI devuelva exit0.

- `check.php`: Abilities API/WP_Query/Polylang reales, idiomas, metadatos, taxonomías, límites, permisos y huellas editoriales antes/después.
- `http.php`: Apache real y MCP Adapter por HTTP, sesiones y Application Passwords sintéticas; descubrimiento, ejecución, rechazo, revocación, desactivación/reactivación y limpieza de credenciales/scopes.
- `verify-report.py`: validación de los recibos del runner. No convierte logs crudos en evidencia de éxito si faltan marcadores.

Esta prueba no instala nada en el hosting ni reproduce todos sus plugins, MU plugins, cachés y restricciones de red. El candidato conserva el guard de entorno local; la preparación del paquete y el preflight de destino son pasos posteriores.
