# Restauración privada real — 22 octubre 2026

Resultado: restauración básica verificada. No constituye aceptación funcional del perfil
completo de plugins ni autorización de instalación en el CMS compartido.

Alex ejecutó el respaldo. Archivo de 247734036 bytes, SHA256
`13f20d924a5d161f28444fabf24adbcba40f1c7bbb8951a57c1e4a00f9541d62`.
El agente no ejecutó SSH. El archivo original se conserva sin cambios.

## Evidencia

- Verificación completa de gzip, tar, manifiesto, archivos y SQL; copia privada y hashes
  de los archivos extraídos comprobados de nuevo. Ningún contenido privado en Git/chat.
- 37 tablas sanas. Todos los esquemas coinciden con los hashes del manifiesto del origen,
  normalizando solamente el contador AUTO_INCREMENT como en la exportación.
- Importación SQL real en MariaDB 11.8.9, seguida de exportación e importación en otra
  base local. Esquemas, número de filas y CHECKSUM TABLE EXTENDED coinciden en cada tabla.
  No se capturaron checksums de filas del servidor original: no se afirma esa comparación.
- WordPress 7.1.3 de referencia arranca con PHP 8.5.11. Configuración nueva y credenciales
  exclusivamente locales; archivos/configuración/plugins/temas/MU/drop-ins del respaldo
  no ejecutados. Base restaurada sin cambios después de este arranque.
- Todos los contenedores sin red, sin puertos publicados. Socket Unix para SQL y servidor
  con skip-networking. Guard local comprueba rechazo de HTTP y correo; cron desactivado.
- Contenedores detenidos y estados comprobados: DB exit0; CLI exit137 al terminar el
  proceso sleep de mantenimiento con el plazo de parada. No indica fallo de las pruebas.
- 27 tests locales pasan, incluidos rechazo de traversal/enlaces, prohibición de
  sobrescritura, prefijo seguro, configuración local y aislamiento de los servicios.

## Incidencias del ensayo y almacenamiento

Primera preparación detenida porque Docker no resolvía la referencia local tag+digest;
se corrigió a repositorio@digest sin descargar imágenes. El primer arranque encontró el
núcleo vacío: tmpfs perdía el contenido al salir seed antes de montar CLI. Se corrigió el
orden, manteniendo CLI montado durante seed. Hubo otro intento incompleto sin resultado
final; el diagnóstico inicial ocultaba la causa y se mejoró para mostrar únicamente códigos
de error seguros. No se atribuye una causa a ese intento sin evidencia. La ejecución final
completa pasa y verifica además que el arranque no modifica la DB.

Los intentos se conservan en carpetas privadas /private/tmp/ac-wp-private-restore-* fuera
del repositorio, permisos0700/0600. Ocupan juntos 2245865472 bytes adicionales (2.09GiB).
Quedan 42528088064 bytes libres (39.6GiB). No se descargaron imágenes. DB y núcleo usan
tmpfs; al detener los contenedores no constituyen una copia persistente de recuperación.
Los archivos privados extraídos y snapshots permanecen, sin borrados programados.

## Pendiente

Aceptar el perfil de plugins actualizado en otro ensayo aislado y revisar el esquema
exhaustivo del candidato. No instalar todavía ni ampliar permisos reales. Mantener el
respaldo hasta validar instalación/restauración y después decidir su retención con Alex.

Resultado público limitado a metadatos:
[MCP_PRIVATE_RESTORATION_RESULT_2026-10-22.json](MCP_PRIVATE_RESTORATION_RESULT_2026-10-22.json).
