# Paquete MCP del blog — 26 octubre 2026

**Resultado:** paquete 0.1.0-rc.1 reproducible y diagnóstico de destino preparados. Sin instalación, activación ni scope nuevos en el CMS real; sin deploy frontend ni envío de correo.

[Recibo verificable](editorial-blog-read-package-2026-10-26.json), [paquete y procedimiento](../WORDPRESS_MCP_BLOG_READ_PACKAGE.md).

## Evidencia

- ZIP de tres archivos, 4.649 bytes, SHA256 `d8f8fcb1dcc83962036a70c3fb0e3a57e44dbeb96b5556e8abe1dcb7fbd205e6`. Dos generaciones idénticas. El empaquetador rechaza archivos adicionales y enlaces simbólicos.
- reader.php conserva byte por byte el cuerpo funcional del candidato anterior desde `const CAPABILITY`. El bootstrap añade guard exacto de sitio único/home/site y opt-in explícito; sin asignaciones de capacidades ni activation hooks.
- 75 comprobaciones de contrato contra el entrypoint empaquetado y nueve escenarios del guard correctos: cerrado, destino correcto, flag/home/site incorrectos, multisite, laboratorio correcto, flag de laboratorio en producción y laboratorio con URL errónea.
- Instalación del ZIP mediante el instalador normal de WordPress, inicialmente inactivo. Cuatro fases de preflight correctas: before, after-install, after-enable y after-scope. Quinto recibo after-install correcto tras retirar el scope y desactivar, con guard apagado.
- Con el paquete instalado: **34 pruebas funcionales y 37 pruebas HTTP/MCP reales**, cero fallos. Autenticación, acceso acotado, privacidad, traducciones, taxonomías, paginación, revocación, sesiones y desactivación verificadas. Credenciales y scopes sintéticos retirados.
- WordPress7.1.3/PHP8.5.11/Polylang3.9.1/Adapter0.7.1/MariaDB11.8.9; mismas imágenes fijadas del laboratorio anterior. Proyecto propio `alexcuadra-blog-read-package-lab-20261026`, red internal, sin puertos publicados; contenedores WordPress y BD confirmados detenidos. Sin importación de datos del hosting ni poda de recursos ajenos.

El laboratorio conserva avisos esperados de abilities ausentes/rechazadas, comprobaciones externas bloqueadas y deprecación de Colors.php de WP-CLI en PHP8.5. No hubo errores funcionales en los recibos; no se presenta el log como libre de avisos.

## Reproducción

```sh
python3 scripts/package-editorial-blog-read.py
python3 scripts/prepare-editorial-blog-read-preflight.py
sh tests/integration/wordpress-blog-read/release-run.sh
```

El runner exige un proyecto fresco; para una repetición justificada usar un nombre nuevo con prefijo `alexcuadra-blog-read-package-lab-` mediante BLOG_PACKAGE_LAB_PROJECT. Detiene exclusivamente ese proyecto al salir. No borrar volúmenes o repetirlo por continuidad.

## Límites

El diagnóstico real todavía no se ha ejecutado. Los cuatro estados válidos y la retirada se probaron en WordPress aislado; no constituyen una prueba de todos los estados inválidos del preflight o de compatibilidad con plugins adicionales del hosting. Los nueve escenarios negativos/correctos citados corresponden al bootstrap del paquete.

Se verifican huellas editoriales durante lecturas del ensayo, no conservación global de toda la BD real. No se modifica la propuesta227, la revisión1, sus ZIP sellados ni la incidencia histórica de postmeta. El catálogo MCP real sigue pendiente de incorporar estas dos herramientas. Siguiente paso: recibo de solo lectura `before` del destino, antes de revisar instalación y primer scope.
