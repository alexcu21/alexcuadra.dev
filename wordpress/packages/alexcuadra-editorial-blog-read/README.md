# Editorial Blog Read 0.1.0-rc.1

Paquete de revisión; instalación y primer scope reales pendientes de autorización. Expone únicamente metadatos de entradas nativas publicadas, sin contraseña, por idioma EN/ES: ID/título/slug, traducciones públicas recíprocas y categorías/etiquetas asignadas con sus idiomas y traducciones. Sin cuerpo, ACF, opciones, autores, correos, cuentas ni escritura.

Abilities: `alexcuadra/list-public-posts` y `alexcuadra/get-public-post`. Capacidad nueva separada `alexcuadra_read_public_posts`, junto con autenticación y read. El permiso de proyectos no concede este acceso. No hay activation hook, migración, asignación de capacidades ni actualización de roles.

Para registrar herramientas en el destino exige la constante `AC_EDITORIAL_BLOG_READ_SITE` con el valor exacto `https://content.alexcuadra.dev`, home_url y site_url coincidentes y un único sitio. Sin ese opt-in, aun activo, no registra herramientas. En laboratorio admite exclusivamente AC_EDITORIAL_BLOG_READ_LAB=true, entorno local y URLs http://wordpress. No activar ambos candidatos con el mismo namespace.

reader.php conserva exactamente el cuerpo funcional del candidato de laboratorio integrado con WordPress/Polylang/MCP. El empaquetador comprueba esa igualdad. El bootstrap de destino se verifica aparte antes de cualquier instalación real.

Preflight operativo fuera del plugin: `wordpress/operations/editorial-blog-read-preflight.php`. No se incluye en el ZIP. Las fases before/after-install/after-enable/after-scope leen estado y no instalan, activan, escriben opciones, asignan permisos ni acceden a Application Passwords.

Retirada: quitar solo el permiso nuevo al usuario autorizado y desactivar este plugin. Los lectores de proyectos y propuestas existentes permanecen independientes. No elimina contenido ni tablas, porque este lector no crea ninguno.
