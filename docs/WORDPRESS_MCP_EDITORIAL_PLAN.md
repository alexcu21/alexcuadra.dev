# Etapa técnica: MCP editorial con permisos acotados

Estado20 octubre2026: **Editorial Read0.1.0 instalado y verificado mediante el MCP real**, tras autorización expresa de Alex. Usuario2 conserva subscriber y recibe únicamente `alexcuadra_read_public_projects`; rol global y otros permisos intactos. Dos abilities propias disponibles a través de los tres meta-tools existentes. Lectura mínima completada; escritura/ACF/cuerpos siguen pendientes. Etapa separada del trabajo visual/editorial del portfolio.

## Punto de partida

Servidor WP7.1.3/PHP8.2.34/Polylang3.9.0/Adapter0.7.1. Revisión SSH acotada de permisos/dependencias/plugins/filtros y91 pruebas framework/HTTP+5 escenarios en esa combinación precedieron a la instalación. No es auditoría completa de todos los plugins/contextos. [Revisión previa](qa/MCP_EDITORIAL_PREFLIGHT_2026-10-20.md), [instalación y aceptación reales](qa/MCP_EDITORIAL_INSTALLED_2026-10-20.md).

Inventario actual: `core/get-site-info`, `core/get-user-info`, `core/get-environment-info`, `alexcuadra/list-public-projects`, `alexcuadra/get-public-project`. Las capacidades core conservan sus permisos propios; la conexión y execute-ability no conceden edición genérica. User-info se consultó solo con id/roles, sin perfil completo. Capacidades efectivas seleccionadas verificadas por SSH, sin elevar el usuario ni leer credenciales.

[Paquete0.1.0](../artifacts/wordpress/alexcuadra-editorial-read-0.1.0.zip) y [fuente](../wordpress/plugins/alexcuadra-editorial-read/alexcuadra-editorial-read.php) intactos. Dos abilities de metadatos públicos MCP-only, sin cuerpo/ACF/opciones/usuarios. Requieren `read`+capacidad específica y no la conceden automáticamente. Denegación real antes de concesión, paginación EN/ES, búsquedas, traducciones y rechazos verificados después. Hash de14 proyectos, otros permisos, rol global y otros plugins activos sin cambios. [Instalación/reversión](WORDPRESS_MCP_READ_INSTALL.md).

## Incrementos de esta etapa

Actualización20 octubre: laboratorio **0.5**,531 comprobaciones correctas en `test:mcp-applications` (376 regresiones+155 nuevas). Once abilities; ya aplica/recupera cuerpo/tagline sintéticos con aprobación independiente consumible, estado congelado, eventos/recibos históricos, recuperación condicional y reserva de cuota. Cuatro carreras HTTP entre dueños/sesiones y fallos/COMMIT incierto comprobados; [QA vigente](qa/MCP_EDITORIAL_APPLICATIONS_2026-10-20.md). **Siguiente: revisión operativa de hooks/editores/esquema, retención/export/backups y candidato verificable con rollback**, sin instalar ni conceder permisos reales. No necesita Editor. Staging TOC ya actualizado de forma independiente; visual/429 pendientes. Mejora del cliente Astro–WordPress al final del proyecto, como indica la última sección.

1. **Completado:** contrato mínimo de lectura, schemas restringidos, búsqueda/paginación e idioma exacto, traducciones públicas recíprocas. Sin contenido privado/cuerpos/ACF arbitrarios. Revisión de permisos acotada realizada; no se presenta como auditoría completa.
2. **Completado:** extensión0.1.0 probada en dos combinaciones aisladas, instalada y habilitada por aprobación de Alex. Conector real verifica seis proyectos EN/seis ES,146↔196, búsqueda y errores específicos; denegación sin scope comprobada antes de asignarlo. No repetir aprobación/instalación de lectura ya completadas. Credenciales reales intactas; revocación de contraseña/sesión y desactivación/restauración probadas con datos sintéticos, no repetidas en el sitio real.
3. **En curso:** propuestas, revisiones, aprobación y aplicación/recuperación acotadas verificadas solo en laboratorio0.5. Falta reconciliar esquema/hook/editores del sitio y límites de transacción, retención/backups y preparar paquete operativo. Editar publicado produce efecto público y requiere autorización específica. Borradores nuevos/traducciones/publicación de borradores/retirada siguen fuera del contrato. No exponer SQL/PHP genérico, gestión de plugins/usuarios u opciones globales. Runtime congela propuestas, consume aprobaciones y reserva un intento de recuperación dentro de vigencia; renovar una revisión caducada requiere capacidad nueva sin podar anteriores. No auditoría externa ni backup; integración no resuelve política histórica ACF de fuentes con contraseña.
4. Presentar a Alex futuros paquetes de edición y permisos concretos antes de instalarlos/asignarlos en el CMS compartido. Ampliar capacidades será una decisión expresa, sin convertir automáticamente el usuario en administrador. La aprobación de lectura0.1.0 ya existe y no se pide de nuevo; no autoriza escritura. Verificar rechazos/concurrencia/recuperación antes de una edición real autorizada. Rollback de lectura preparado; no revocar credenciales reales por iniciativa propia.

## Criterios de cierre

- [x] Lectura disponible desde el chat y schemas reales inspeccionados.
- [x] Lectura pública paginada operativa con capacidad específica, sin exponer cuerpos/ACF/meta privados.
- [ ] Edición restringida a campos/CPT autorizados y respeto de traducciones, IDs y field keys.
- [ ] Pruebas de edición con permisos insuficientes, campos prohibidos, conflictos y borradores sin publicación accidental.
- [x] Credencial existente conservada fuera de Git/chat; destinatario privado del contacto no accesible por estas abilities.

La autorización de conectar el adaptador no habilita por sí sola cambios de roles o instalación de capacidades de escritura. Esta etapa no activa contacto ni modifica DNS, Workers o políticas del navegador.

## Al final del proyecto: conector Astro–WordPress

Alex solicitó aplazar su mejora hasta terminar los incrementos previos. Se refiere al cliente de datos Astro/WPGraphQL, no al MCP editorial. Alcance y orden en la [última etapa del plan general](REDESIGN_PLAN.md#20-última-etapa-mejorar-el-conector-astrowordpress): medir primero; después optimizar caché, consultas y resiliencia preservando privacidad/bilingüismo, validar local y staging y preparar rollback. Todavía sin implementación.
