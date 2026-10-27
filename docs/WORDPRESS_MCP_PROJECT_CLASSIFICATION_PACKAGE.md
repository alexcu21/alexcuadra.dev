# Paquete coordinado de clasificación de proyectos

**27 octubre — instalación del lector detenida por carpeta existente:** SHA del ZIP48d0734c…a4dfe2d correcto; WP-CLI rechaza destino ya existente sin --force y la cadena no ejecuta preflight. [Observación](qa/project-classification-install-existing-directory-2026-10-27.json). No se conocen aún integridad/activación de esa carpeta ni su origen; no asumir instalación correcta o parcial. Siguiente solo classifier-installed de lectura con historySHA75a0aa5f…60c7835a, manteniendo scope pausado. No reintentar, forzar, borrar, activar o restaurar permisos. Se observó mensaje de purga de cachés; no se afirma ausencia global de efectos del instalador/plugins.

**27 octubre — propuestas rc.3 activas y conservadas en el hosting:** Alex aporta proposal-upgraded ready=true/19checks, ZIP exacto SHA581dc9a0…cb00df. HistorySHA75a0aa5f…60c7835a/documentoSHA54945c84…7f6b3d/conteos1/1/1/1 intactos; ocho capacidades previas y scope privado pausado. [Recibo](qa/project-classification-upgrade-destination-proposal-upgraded-2026-10-27.json). Siguiente: instalar inactivo solo lector rc.1 SHA48d0734c…a4dfe2d, sin --force/activación, y classifier-installed con SHA de historia original. No repetir actualización de propuestas, DDL, enroll o backup.

**27 octubre — respaldo actualizado real verificado:** Alex aporta verify ready=true/integrityVerified=true y conteos1/1/1/1 del artefacto deb1593d…ab76110. [Recibo](qa/project-classification-upgrade-backup-verify-2026-10-27.json). Mantener scope privado pausado. Siguiente: revisar/subir ZIP exacto de propuestas rc.3, comprobar SHA581dc9a0…cb00df, desactivar únicamente propuestas rc.2, sustituir código mediante actualización explícita de esa versión conocida, reactivar y proposal-upgraded con historySHA75a0aa5f…60c7835a. Aún sin lector nuevo, DDL, enrolamientos o restauración de permisos. Actualización real pendiente de Alex.

**27 octubre — respaldo actualizado real recibido:** export ready=true de Alex, backup-20261027T200039Z-1e211a74ec3e7502, manifestSHAdeb1593d…ab76110;1propietario/1propuesta/1revisión/1recibo, sin escrituras de BD. [Recibo](qa/project-classification-upgrade-backup-export-2026-10-27.json). El helper exige scope privado pausado y ocho capacidades previas, sin grant a roles/otros usuarios. Siguiente verify del mismo artefacto/SHA con helper rc.2; mantener pausa y no instalar paquetes todavía. Conservar historySHA75a0aa5f…60c7835a, sin recapturar before; no restaurar el CMS vivo para demostrar respaldo.

**27 octubre — before real del paquete coordinado correcto:**19checks, rc.2 exacto/activo, lector nuevo ausente y nueve capacidades originales. [Recibo](qa/project-classification-upgrade-destination-before-2026-10-27.json). Documento SHA54945c84…7f6b3d coincide con pilotoMCP; historySha75a0aa5f…60c7835a también coincide con el derivado de su petición/recibo originales.1propietario/1propuesta/1revisión/1recibo. Conservar este SHA en todas las fases; no recapturarlo. Siguiente: Alex retira solo alexcuadra_prepare_post_changes y exporta un respaldo nuevo con los helpers rc.2 existentes; sin instalar ZIPs o cambiar otros permisos todavía. Pausa/exportación real aún pendientes.

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

## Paso actual: diagnosticar la carpeta existente, sin reinstalar

Alex aportó un [rechazo por carpeta existente](qa/project-classification-install-existing-directory-2026-10-27.json). El archivo ZIP sí coincidió con el SHA esperado. La instalación no informó éxito y, por la cadena &&, no se ejecutó classifier-installed. No se conoce todavía el estado/integridad de los tres archivos o su activación; no se deduce instalación parcial, éxito previo o causa concreta.

Ejecutar únicamente el diagnóstico ya disponible:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/preflight.php --use-include classifier-installed 75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a
```

Compartir PROJECT_CLASSIFICATION_UPGRADE_RESULT incluso si ready=false. Comprueba conjunto exacto de archivos/hashes, inactividad del lector y ausencia de sus herramientas, rc.3/perfil, permisos pausados e historia original. No modifica la carpeta o sus asignaciones. Mantener el scope privado ausente; no usar --force, borrar/renombrar archivos, reintentar instalación, activar o restaurar permisos por continuidad. El instalador mostró una purga de cachés: no se certifica ausencia global de efectos por ese error.

## Histórico: instalación inactiva solicitada, pendiente de conciliación

El [proposal-upgraded real de Alex](qa/project-classification-upgrade-destination-proposal-upgraded-2026-10-27.json) pasa19checks: rc.3 exacto/activo y misma historia/documento/conteos. El usuario2 mantiene las ocho capacidades previas con scope privado ausente; lector nuevo todavía ausente. No repetir sustitución de propuestas o restaurar permisos.

Subir solo el ZIP del lector0.1.0-rc.1 de la tabla a private-tools/project-classification-upgrade. Comprobar primero:

```sh
sha256sum /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/alexcuadra-editorial-project-classification-0.1.0-rc.1.zip
```

Debe devolver exactamente `48d0734c5fcb0876171c3fd7ad8d291d7310b624ebb45a18a52b9dc32a4dfe2d`. Solo entonces instalarlo inactivo y verificar la fase:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin install /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/alexcuadra-editorial-project-classification-0.1.0-rc.1.zip &&
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/preflight.php --use-include classifier-installed 75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a
```

Sin --force ni --activate. Si ya existe o falla, conservar pausa y compartir el error, sin sustituir una versión desconocida. Compartir PROJECT_CLASSIFICATION_UPGRADE_RESULT antes de configurar el opt-in o activar. La fase exige los tres archivos exactos del lector, inactivo/sin sus herramientas, rc.3 operativo, ocho capacidades y la historia original. Instalación del lector aún pendiente de ejecución por Alex.

## Histórico: sustitución de propuestas rc.2 por rc.3 ya verificada

[Verify actualizado de Alex](qa/project-classification-upgrade-backup-verify-2026-10-27.json): integrityVerified=true y1propietario/1propuesta/1revisión/1recibo. Mantener alexcuadra_prepare_post_changes ausente. No volver a exportar, enrolar, crear tablas o restaurar el sitio.

Subir solo el ZIP de propuestas0.1.0-rc.3 de la tabla superior a private-tools/project-classification-upgrade. Revisar su SHA antes de desactivar código:

```sh
sha256sum /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/alexcuadra-editorial-blog-proposals-0.1.0-rc.3.zip
```

Debe coincidir exactamente con `581dc9a006e49d0add30209bf33e31f5f8ba2c97bc575630dc262ebf73cb00df`. Solo entonces, Alex ejecuta la secuencia siguiente. El uso acotado de --force sustituye el rc.2 cuyos ocho archivos fueron verificados en before y por el helper de backup; no autoriza sobrescribir otro paquete o versión desconocida. Esta actualización exacta se ensayó en laboratorio conservando historia no vacía y rollback.

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin deactivate alexcuadra-editorial-blog-proposals &&
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin install /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/alexcuadra-editorial-blog-proposals-0.1.0-rc.3.zip --force &&
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content plugin activate alexcuadra-editorial-blog-proposals &&
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/project-classification-upgrade/preflight.php --use-include proposal-upgraded 75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a
```

Las condiciones && detienen los pasos posteriores si un comando falla. Compartir PROJECT_CLASSIFICATION_UPGRADE_RESULT o el error antes de avanzar. No reintentar sustitución, activar el lector, restaurar permisos o ejecutar DDL por continuidad. Si falla después de desactivar, mantener la pausa y compartir la salida; no improvisar recuperación de código/tablas.

Se esperan19checks correctos, rc.3 exacto/activo y cinco herramientas privadas, lector nuevo ausente, los ocho permisos anteriores y los mismos hashes/conteos de historia. El sitio publicado no requiere migración de fuentes; el cambio de paquete no implementa publicación o asignación de términos. La sustitución real sigue pendiente de ejecución por Alex; el manifiesto sellado no se modifica para atribuirle ejecución anticipada.

## Histórico: verificación del respaldo actualizado ya recibida

[Export real de Alex](qa/project-classification-upgrade-backup-export-2026-10-27.json): ready=true,1propietario/1propuesta/1revisión/1recibo y ninguna escritura de BD. El artefacto nuevo incluye el piloto; no sustituye al respaldo owner-only anterior. El helper verifica scope pausado/permisos exactos y valida internamente el archivo antes de devolver éxito. Falta su verificación en otra invocación con el SHA del recibo.

Mantener el scope privado ausente y ejecutar únicamente:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-backup.php verify /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-backups/backup-20261027T200039Z-1e211a74ec3e7502 deb1593d8340ea7a68c917fc24c370d9384da6beddde79e6ac3dc6903ab76110
```

Compartir BLOG_PROPOSAL_BACKUP_RESULT; se esperan integrityVerified=true y los cuatro conteos1. Si falla, conservar artefacto/pausa y compartir el código, sin reexportar o sustituir SHA para hacer pasar el control. restorationVerified=false es correcto porque no se restauró el hosting. No adjuntar artefactos, instalar ZIPs o restaurar permisos todavía.

El historySha de las siguientes fases sigue siendo `75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a`; no usar en su lugar el SHA del manifiesto de backup. Son comprobaciones distintas. La verificación real separada permanece pendiente.

## Histórico: pausa y exportación ya realizadas por Alex

El [before real de Alex](qa/project-classification-upgrade-destination-before-2026-10-27.json) pasa19checks. La huella del documento coincide con el piloto leído por MCP; el historySha coincide también con el calculado desde su petición original y metadatos conocidos. Lector nuevo ausente y sin actualización de código todavía. Los checks de presencia/activación de clasificación en before significan ausencia/inactividad esperadas, no instalación del lector.

SHA original de historia que se conservará en todas las fases: `75a0aa5fdaab48206bf72e8e70eb274dd75c5b12af81d8b04963aff160c7835a`. No volver a capturar before para aceptar un cambio inesperado. La forma validada es1propietario/1propuesta/1revisión/1recibo.

Alex pausa únicamente el scope privado del usuario2; el lector público de proyectos y las otras ocho capacidades se conservan:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 user remove-cap 2 alexcuadra_prepare_post_changes
```

Solo si termina correctamente, y sin otros escritores de estas cuatro tablas, exportar mediante el helper rc.2 ya instalado:

```sh
wp --path=/home/u776991923/domains/alexcuadra.dev/public_html/content --user=1 eval-file /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-rc2/blog-proposal-backup.php export /home/u776991923/domains/alexcuadra.dev/private-tools/blog-proposal-backups writers-stopped
```

No crear otra carpeta o reemplazar el respaldo inicial; el helper usa un nombre nuevo exclusivo. Debe devolver ready=true, owners/proposals/revisions/requests=1, sin escrituras de BD. Compartir únicamente BLOG_PROPOSAL_BACKUP_RESULT; no subir los artefactos privados al chat o repositorio. Si falla, conservar la pausa y compartir el error; no restaurar el scope o reintentar por continuidad.

Después se verificará ese artefacto con su SHA recibido antes de revisar el reemplazo de paquetes. No instalar los ZIP, crear planes o modificar roles/otros permisos todavía. La retirada y exportación real permanecen pendientes de ejecución por Alex. No repetir before, instalación inicial, DDL o enroll.

## Histórico: comprobación real de solo lectura ya recibida

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
