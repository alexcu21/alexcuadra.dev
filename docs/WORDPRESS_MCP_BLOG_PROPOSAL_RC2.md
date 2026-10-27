# Propuestas privadas: candidato 0.1.0-rc.2

Candidato separado de rc.1, derivado del ZIP sellado y del diagnóstico de huellas aportado por Alex. No instala ni cambia el CMS por generar estos archivos. Conserva las cinco herramientas privadas, los DTOs cerrados y la lógica de repositorio; cambia versión, admisión del perfil y README. No incluye escritor de posts, publicación, grants automáticos, migración automática ni borrado.

- [ZIP rc.2](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.zip):17.693bytes; SHA256 `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`.
- [Manifiesto de ocho archivos](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.2.manifest.json), [generador reproducible](../scripts/package-editorial-blog-proposals-site.py).
- [Pruebas y límites](qa/MCP_BLOG_PROPOSAL_RC2_2026-10-27.md).

## Perfil y límites

Admisión exacta de16plugins activos por versión y huella de archivo principal, dosMU por huella, Twenty Twenty-Four1.6 por entradas conocidas, núcleo/wpdb fijado, sin drop-ins/caché externa ni callbacks query desconocidos. Se permite adicionalmente el propio candidato rc.2 por versión/main hash. El guard real exige sitio único, home/site `https://content.alexcuadra.dev` y la constante específica. No se activa esa constante por copiar o instalar el ZIP.

La excepción de pruebas exige entorno local, DB_HOST=db, URL exacta y marca de laboratorio; solo allí admite el MU de seguridad conocido y las inyecciones de fallos. Diez pruebas separadas ejercitan también la rama sin esa excepción dentro del entorno sintético.

Las huellas principales coinciden con el servidor; no verifican árboles completos. El laboratorio usa PHP8.5.11/MariaDB11.8.9 y configuración/datos sintéticos; servidor PHP8.5.12. No certifica opciones reales de hosting, preview, cachés externas, restauración ante pérdida del servidor ni escritura pública.

## Siguiente paso real: preflight rc.2 de solo lectura

Los diagnósticos rc.1 anteriores quedan como históricos. Para este ZIP se preparó una pareja nueva, probada junto al ZIP exacto. Alex puede crear `/home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/` y subir **solo**:

1. [blog-proposal-admission.php rc.2](../wordpress/operations/blog-proposal-rc2/blog-proposal-admission.php).
2. [blog-proposal-preflight.php rc.2](../wordpress/operations/blog-proposal-rc2/blog-proposal-preflight.php).

Después, por SSH:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-preflight.php 2 before
```

Compartir `BLOG_PROPOSAL_PREFLIGHT_RESULT`. Consulta perfil, ausencia de las tablas propias, estado del candidato y capacidades exactas; no lee cuerpos/credenciales, instala, activa o concede permisos. No repetir el diagnóstico de huellas que ya coincidió ni cambiar componentes para forzar admisión. El arranque ordinario de WordPress y sus plugins queda fuera de la garantía del observador.

El manifiesto conserva installationApproved=false y capabilityGrantApproved=false. Antes de instalar: revisar este ZIP exacto y el nuevo before. La instalación inicial será inactiva y seguida de after-install, sin `--force`. Activación, tablas, propietario y primer scope son fases posteriores, con su recibo propio; no ejecutar por continuidad de este documento. No instalar rc.1 ni el experimento local.

## Respaldo, restauración y retirada

Ensayo realizado únicamente con datos sintéticos: crear dos revisiones/recibos, retirar el scope del único escritor, capturar las cuatro tablas propias y sus DDL en un archivo privado0600, restaurar en cuatro tablas con prefijo separado, validar esquema, filas exactas, propietario, cabeza de versión y hashes de documentos. Las tablas originales privadas y editoriales se conservan. No se restauran tablas públicas ni permisos anteriores.

El ensayo no es una herramienta de respaldo lista para el hosting. Antes de habilitar escritura privada real falta un procedimiento operativo revisado de exportación/restauración consistente, con manifest/hashes, acceso privado, pausa coordinada y validación aislada para ese destino. No copiar al servidor el fixture de pruebas ni su mecanismo de restauración. La API de replay funciona antes del respaldo; después de restaurar se verificaron filas y recibos, no una reapertura de la API sobre el espacio restaurado.

Retirada ensayada: retirar solo el scope nuevo, desactivar el candidato y deshabilitar su guard. Permanecen propietario, propuesta, ambas revisiones/recibos, las ocho capacidades anteriores y el lector del blog activo. No hay DROP, poda ni uninstall automático.
