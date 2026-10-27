# Paquete coordinado de clasificación de proyectos

Preparado y probado en laboratorio con el perfil completo de código del sitio. **Instalación real pendiente**; el CMS conserva propuestas rc.2, catálogo18 y el piloto privado1/v1. No hay nuevos grants o migraciones de tablas.

| Componente | Paquete | Bytes | SHA256 |
| --- | --- | --- | --- |
| Lector de clasificación | [0.1.0-rc.1](../artifacts/wordpress/alexcuadra-editorial-project-classification-0.1.0-rc.1.zip) | 4385 | 48d0734c5fcb0876171c3fd7ad8d291d7310b624ebb45a18a52b9dc32a4dfe2d |
| Perfil de propuestas | [0.1.0-rc.3](../artifacts/wordpress/alexcuadra-editorial-blog-proposals-0.1.0-rc.3.zip) | 18023 | 581dc9a006e49d0add30209bf33e31f5f8ba2c97bc575630dc262ebf73cb00df |

[Manifiesto coordinado](../artifacts/wordpress/project-classification-bundle-0.1.0-rc.1.manifest.json), [generador](../scripts/package-project-classification-bundle.py), [QA y límites](qa/MCP_PROJECT_CLASSIFICATION_BUNDLE_2026-10-27.md).

## Cambio concreto

El lector añade get-public-project-classification y list-public-project-terms, reutilizando alexcuadra_read_public_projects. Guard por opt-in de sitio exacto; no registra taxonomías, asigna términos, cambia roles, crea propuestas o publica fuentes.

Propuestas rc.3 cambia solo versión/main, admisión del lector exacto y README. Planner, proveedor de fuente, repositorio, barrera y DTOs de abilities son idénticos a rc.2; mismo namespace y cuatro tablas. El perfil admite la base medida con o sin el lector nuevo para permitir una transición/reversión ordenadas. Si está activo, exige su versión y los tres archivos exactos, además del perfil previo; no permite otros plugins desconocidos ni relaja hashes/versiones/MU/tema/caché.

Los dos PHP de recuperación nuevos, en project-classification-upgrade/, validan los archivos de rc.3 y aceptan únicamente artefactos con SHA de paquete rc.2 o rc.3: el esquema/DTO de historia no cambió. Se probó recuperar un artefacto creado por rc.2 y leer/repetir su plan desde rc.3. Se conservan las herramientas/ZIP anteriores para rollback; no se sobrescriben sus fuentes o manifiestos.

## Paso actual: una comprobación real de solo lectura

Subir únicamente [preflight.php](../wordpress/operations/project-classification-upgrade/preflight.php) a una carpeta nueva:

`/home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/`

No necesita los ZIP o los helpers de recuperación para esta comprobación. Alex ejecuta:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/preflight.php --use-include before
```

**--use-include es obligatorio para este archivo.** Se reprodujo que el reescritor de constantes de eval-file devuelve null con PCRE JIT stack limit exhausted al procesar la política embebida. El modo include, admitido por WP-CLI, evita esa reescritura sin cambiar PCRE/PHP o los controles del diagnóstico. No interpretar salida vacía/código0 como éxito: debe existir PROJECT_CLASSIFICATION_UPGRADE_RESULT ready=true.

Compartir ese resultado. Verifica rc.2 exacto y activo, lector nuevo ausente, perfil/taxonomías propias públicas traducidas, cuatro tablas válidas, propietario2/una propuesta1v1/una revisión/un recibo y nueve capacidades esperadas. No exige propuestas vacías ni crea una baseline de fuentes o del incidente histórico de postmeta.

Devuelve historySha256 sobre metadatos ordenados de las cuatro tablas y pilotDocumentSha256. Antes de aprobar otro paso se debe contrastar este último con el piloto MCP ya leído: `54945c849ee3fea44d56c640a63f335961f68577d2ca1fb41b5d7248337f6b3d`. Conservar el historySha original y usarlo sin reemplazar en todas las fases siguientes. No se leen cuerpos de documentos, posts, credenciales o backups. El arranque ordinario de otros plugins queda fuera de la garantía del observador; no es una certificación global de la BD ni un snapshot de toda la fuente.

No instalar los ZIP, tocar scopes o volver a exportar todavía. El manifiesto conserva installationApproved=false. El preflight real de actualización sigue pendiente.

## Secuencia posterior, todavía sin ejecutar en el hosting

1. Revisar before real y el SHA del piloto. Pausar únicamente alexcuadra_prepare_post_changes, conservando las ocho capacidades anteriores y los otros escritores de estas tablas detenidos. Exportar/verificar un respaldo nuevo mediante los helpers rc.2 ya instalados, sin sobrescribir el owner-only inicial. Debe incluir1propietario/1propuesta/1revisión/1recibo.
2. Presentar/aprobar los ZIP exactos antes de reemplazar código. Desactivar solo propuestas, instalar rc.3 sobre el rc.2 exacto revisado y reactivarlo. La sustitución necesita el modo de actualización de WP-CLI, no una instalación inicial forzada sobre una versión desconocida. proposal-upgraded exige historia original, ocho capacidades y lector ausente; no hay DDL/enroll.
3. Instalar el lector inactivo; classifier-installed con el mismo historySha. Después, opt-in AC_EDITORIAL_PROJECT_CLASSIFICATION_SITE con valor cadena https://content.alexcuadra.dev y activación del lector; classifier-enabled. Deben coexistir cinco herramientas privadas, dos de clasificación y lectores anteriores; ningún nuevo grant.
4. Subir los dos helpers de recuperación rc.3 a la carpeta nueva; verificar también el artefacto rc.2 antes de abrir escritores. No sustituir el SHA de un archivo cambiado ni restaurar sobre el CMS vivo para demostrar instalación.
5. Restaurar solo el scope privado anterior al usuario2; after-scope exige las nueve capacidades originales y la misma historia. Después verificar por MCP catálogo20, clasificación EN/ES de un proyecto público y get/replay exacto del plan1 existente. No crear otra propuesta por demostrar actualización.

Las fases del nuevo preflight, excepto before, requieren como segundo argumento el historySha recibido originalmente. No usar los preflights iniciales rc.2 que exigían tablas vacías, ni borrar filas para hacer pasar una fase. Si aparece otra historia, deriva de paquete o fallo, detenerse y revisar; no normalizar/capturar un SHA nuevo por continuidad.

## Reversión probada

Con escritor privado pausado, desactivar el lector, quitar su opt-in y recuperar el ZIP rc.2 exacto conservado; activar propuestas rc.2, restaurar solo el scope previo tras revisar el estado y comprobar rolled-back con el historySha original. La fase rolled-back preparada espera las nueve capacidades restauradas y el lector inactivo, todavía instalado con sus archivos exactos. No desinstalarlo o eliminarlo para cumplir ese control.

No se elimina historia, recrean tablas, restauran fuentes públicas o cambian roles. El ensayo vuelve a leer y repetir el plan original tras rollback con documento idéntico y una sola versión. No hay restauración automática en caso de error ni comandos de recuperación del sitio real autorizados por esta prueba.

## Límites

Código de16plugins anteriores más propuestas y lector (18activos al final), dosMU y Twenty Twenty-Four contrastados; opciones/taxonomías/cuentas/contenido nuevos sintéticos. PHP8.5.11/MariaDB11.8.9 del laboratorio frente a PHP8.5.12 reportado por el hosting. No certifica opciones/cachés/OPcache del servidor, árboles completos o escrituras concurrentes externas. Las pruebas no importan el piloto real: reproducen su forma1/v1 con datos sintéticos.

El catálogo público sigue limitado a términos con testigo público; no amplía el lector a términos editoriales nunca publicados. Propuestas/aplicación de asignaciones y escritor público de posts siguen pendientes. CMS429, contacto/producción y aceptación integral conservan su estado anterior.
