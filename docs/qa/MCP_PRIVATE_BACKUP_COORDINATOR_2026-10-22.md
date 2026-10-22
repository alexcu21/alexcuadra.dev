# Coordinador de respaldo privado preparado — 22 octubre 2026

**Preparación verificada; ningún respaldo real creado por el agente.** [Procedimiento/comando para Alex](../WORDPRESS_MCP_PRIVATE_BACKUP_PLAN.md). La decisión previa de destino local privado/disco externo y conservación hasta validar sigue vigente; no se programó borrado.

## Preflight real aportado por Alex

[Perfil saneado](MCP_BACKUP_PROFILE_2026-10-22.json): WordPress7.1.3, PHP8.5.11, MariaDB11.8.9,37 tablas BASE TABLE/InnoDB, cliente mysqldump disponible y herramientas bash/tar/gzip/mktemp. Triggers/rutinas/eventos visibles0; no se infiere privilegio completo ni ausencia de objetos ocultos. Preflight no creó backup ni exportó datos/credenciales.

Deriva frente a captura21oct: PHP8.2.34→8.5.11, Hostinger3.0.79→3.0.80, Polylang3.9.0→3.9.1, WPGraphQL2.24.2→2.25.0 y IDE5.6.0→5.7.0. No modificar evidencia antigua para fingir esa aceptación.165 CLI y140 transporte siguen históricos sobre su perfil. Nueva aceptación de runtime/plugins/efectos sigue pendiente, incluso después de tener backup.

## Implementación revisable

Python local construye script remoto desde helpers PHP/bash y perfil registrado, sin secretos incorporados al repositorio. Modo dry-run no usa SSH ni crea carpetas. Solo Alex ejecutará modo de creación: SSH estricto, exportación transaccional por cliente shell, manifiesto/hash y archivo privado con wp-config/DB. Credenciales del hosting solo se usarán dentro de archivo temporal privado remoto; el agente no las leyó ni imprimió.

La transferencia tiene límites, permisos privados, control de incompletos y verificación sin extracción. PHP crea inventario y detecta deriva de archivos/esquema/perfil. Temporal remoto con cleanup acotado; no modifica el sitio para obtener consistencia global ni elimina archivos de fuente. Ver el plan para exclusiones, falta de atomicidad global, objetos SQL invisibles, SIGKILL y límites del filesystem.

## Verificación realizada

`PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/backup -q`: **21 tests correctos**. Transporte Popen sustituido por fixture; **no se ejecutó SSH**. Casos: hashes de archivos/SQL alterados, rutas absolutas/traversal, duplicados, enlaces, miembros extra, bits especiales, gzip truncado, límites de expansión, destino público/repo/symlink, permisos0700/0600, fallos de transporte/marcador y conservación incompleta. PHP real con credenciales/WordPress/DB sintéticos comprueba escape de opciones, configuración temporal privada, colección SQL e inventario con deriva/symlink. No se prueba todavía el parser del cliente real ni mysqldump completo con este coordinador.

Lint del helper PHP, plantilla bash y **payload renderizado** con bash-n; AST Python. Dry-run del coordinador: destino /Users/Alex/PrivateBackups/alexcuadra.dev, SSH=false, filesCreated=false. No se creó esa carpeta real. Scratch de tests solo en /private/tmp y eliminado por TemporaryDirectory.42GiB locales libres; no imágenes/volúmenes Docker ni descargas del CMS en este incremento. ZIP candidato y fuentes de Read/lab no se tocaron.

## Pendiente y siguiente paso

Alex ejecutará el comando de creación en su terminal y compartirá solo EDITORIAL_PRIVATE_BACKUP_RESULT. Un resultado positivo certificará integridad de transferencia, no restauración. En fallo, mantener incompleto/log privados y revisar etapa sin pegar secretos. Después: restauración real aislada con egress/cron/mail/webhooks bloqueados antes de bootstrap, comparar datos/esquema/perfil, aceptar runtime actualizado y esquema de tablas preexistentes; instalación/scopes reales requieren decisión concreta posterior.
