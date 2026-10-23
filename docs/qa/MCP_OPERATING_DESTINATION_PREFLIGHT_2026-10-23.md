# Revisión del preflight real y publicación privada compatible — 23 octubre

Alex aportó el preflight completo observado a las23:02:36UTC. [Metadata y comparación](MCP_OPERATING_DESTINATION_PREFLIGHT_2026-10-23.json). No se ejecutó SSH desde el agente.

Coinciden las versiones, activación y SHA256 de los archivos principales de los19 plugins con el código verificado del respaldo y Proposals rc.2; también las huellas de los2 MU plugins. Esta comparación no certifica todos los archivos, opciones privadas ni callbacks dinámicos del hosting. WordPress7.1.3/PHP8.5.11/MariaDB11.8.9, tema Twenty Twenty-Four1.6, cache nativa sin drop-ins, tablas inspeccionadas InnoDB, Polylang sync vacío y usuario2 con read/public-read/prepare sin apply/recover/approve/edit/admin. Las seis tablas operativas y su marker no existen.

El servidor deshabilita PHP link(). El rc.1 local sellado dependía de esa función para publicar el export privado. Se conserva rc.1 intacto; el cambio está en el código experimental para la próxima versión. No se ha implementado ni habilitado la admisión productiva.

La publicación nueva crea el destino exclusivamente mediante fopen xb con umask077, copia como máximo32MiB+1byte, comprueba longitud, fflush/fsync, verifica independientemente destino completo y exige igualdad de metadatos/digest con el temporal verificado. Un archivo o symlink existente no se sobrescribe. El temporal solo se retira tras éxito; ante error se retienen las rutas para revisión manual. Ninguna operación de datos/quota/retención cambia.

La copia es visible mientras se escribe: no se afirma publicación atómica. Consumidores deben esperar el resultado exitoso y verificar framing/conteos/pie/digest, sin inferir éxito por existencia. Un fallo puede dejar destino incompleto; no se reintenta sobre esa ruta. El fsync del archivo no certifica durabilidad del directorio. No es restauración, cifrado ni respaldo independiente.

Almacenamiento: máximo transitorio aproximado64MiB para temporal+destino por export de32MiB; al finalizar queda un archivo si se retiró el staging. En caso de error ambos pueden quedar retenidos. Este incremento solo usa archivos sintéticos del laboratorio, imágenes cacheadas y red interna sin puertos; el ensayo puede consumir RAM/swap/capas de Docker y pequeños registros locales. No crea ni descarga otro respaldo real, ni borra el existente o laboratorios anteriores.

Pendiente: admisión y opt-in explícitos de producción, nueva versión sellada con guardas probadas, y posteriormente revisión/autorización de instalación, permisos y aceptación real. Una edición pública necesita su diff aprobado. No hay comando remoto nuevo para Alex en esta corrección.

Ensayo completo correcto:162 contrato (incluidos7 casos nuevos con link deshabilitado)+50 migración/aceptación+68 MCP+58 CLI+50 HTTP/proceso+24 bilingües=412 comprobaciones. Se verificaron export sin link, copia idéntica, preservación de entrada, colisión y destino symlink sin modificarlo. Versiones/perfil antes y después iguales. No se repite el ciclo del paquete rc.1 porque su contenido sellado no incluye esta corrección;439 sigue siendo la evidencia histórica del paquete anterior. Resultado privado en /private/tmp/ac-source-effects-az8zhdes/result.json; resumen sin contenidos/credenciales incluido en JSON QA.32GiB libres antes y después, medición redondeada del volumen completo.
