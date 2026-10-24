# Ensayo de permisos operativos y aceptación inicial

Resultado:12 escenarios correctos con los scripts generados exactos de concesión/aceptación/revocación. [Recibo sintético completo](MCP_OPERATING_SCOPES_2026-10-23.json). Laboratorio `/private/tmp/ac-source-effects-8r_plkfm`, detenido al terminar; conserva recursos/evidencia. rc.3 SHA256 `d0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30` sin cambios. No se suman estos escenarios a las597 comprobaciones anteriores como si se hubieran vuelto a ejecutar.

Se usó el perfil completo verificado y env production simulado, instalación/activación real por WP-CLI del ZIP, dos Projects EN/ES y una propuesta privada sintéticos. Código de plugins/MU/tema procedente del respaldo verificado; no se importó su SQL ni configuración privada. Imágenes locales, red interna reutilizada sin puertos públicos ni descargas, sin SSH ni cambios en el CMS real.

1. Concesión sin declaración de pausa rechazada.
2. Aceptación sin declaración de pausa rechazada.
3. Archivo extra en paquete bloquea la concesión.
4. Clave directa previa false bloquea la concesión sin sobrescribirla.
5. Scope heredado de rol bloquea la concesión.
6. Excepción inyectada después de escribir la primera capacidad revierte las capacidades y preserva la baseline completa.
7. Concesión exacta de tres claves, roles/fuentes/propuestas/configuración/otros permisos y almacenamiento conservados;6 permisos permitidos/12 denegados.
8. Concesión repetida rechazada.
9. Aceptación inicial accepted=true, snapshot single-read-only-repeatable-read, operator-attested, cero owners/reviews/approvals/requests/applications/events y ninguna escritura editorial.
10. Identificador de revocación fuera del directorio permitido rechazado.
11. Revocación y repetición verificadas con recibos explícitos.
12. Tras revocar se conservan una edición de fuente ajena, un permiso ajeno y una denegación false; se eliminan las otras dos claves true.

El caso6 usa un hook exclusivamente del fixture sintético; no se añadió bypass de producción. Solo check_permissions para el smoke test: ninguna ability operativa ejecutada, aprobación creada o diff aplicado. La pausa externa es declarada por el operador, no impuesta por software. Esta aceptación se limita al almacenamiento inicialmente vacío y no certifica aplicación/recuperación/MCP HTTP del hosting.

Comando local ejecutado: `AC_SOURCE_EFFECTS_REUSE_NETWORK=ac-source-effects-ogxctuhl_default AC_SOURCE_EFFECTS_PACKAGE=local-rc3 python3 tests/integration/wordpress-source-effects/run.py --full-profile --scopes-only`. Generación, sintaxis shell y git diff --check correctos. Equipo32GiB libres redondeados antes/después; recursos previos y respaldo conservados.
