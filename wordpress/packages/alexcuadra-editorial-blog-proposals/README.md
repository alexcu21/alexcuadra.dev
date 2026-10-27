# Editorial Blog Proposals 0.1.0-rc.1

Paquete de revisión. No instalar ni conceder scopes reales antes de revisar preflight, perfil y conservación. Separa alexcuadra_prepare_post_changes del lector público y de proyectos. No auto-grants, activation hooks, migraciones o creación de tablas. No contiene escritor de posts, aprobación, aplicación, recuperación o publicación.

Cinco abilities alexcuadra-blog/*: inspect-post-context, create-post-proposal, update-post-proposal, get-post-proposal, list-post-proposals. Contexto bajo scope privado incluye campos/cuerpo de fuente publicada y base; nunca cuerpos privados/postmeta/IDs de destinos ocultos. El lector público conserva su DTO de metadatos.

Guard exacto de sitio único/home/site content.alexcuadra.dev mediante AC_EDITORIAL_BLOG_PROPOSALS_SITE. Rama de laboratorio solo AC_EDITORIAL_BLOG_PROPOSALS_LAB=true, local/http://wordpress/DB_HOST=db. Perfil deliberadamente conservador: WP7.1.3/PHP8.5, wpdb core fijado por hash, Polylang3.9.1, Adapter0.7.1, lector0.1.0-rc.1 opcional y este plugin; tema Twenty Twenty-Five1.5 con entradas verificadas y ningún drop-in. Otros temas/plugins/MU/query filters/caché deben revisarse/probarse antes de una nueva versión; no desactivarlos en el CMS para hacer pasar el perfil. El paquete queda cerrado ante un perfil no admitido. Las huellas de entrada no certifican integridad de todos los archivos del sitio.

Cuatro tablas propias ac_blog_proposal_* con InnoDB/collation/columnas/índices/FK comprobados; instalación separada explícita CLI/operador. Owner row separada, scope directo separado. No sustituye tablas/ZIP/propuesta227/revisión1 de proyectos. WordPress/MariaDB DDL no es transaccional: no reintentar ni borrar una instalación parcial a ciegas.

Conserva todas las revisiones/recibos, sin poda automática. Cuotas por propietario:16propuestas,64revisiones,128peticiones y8MiB lógicos;16versiones/propuesta y786432bytes/documento. Al agotarse rechaza nueva escritura; replay/lectura siguen disponibles. No mide disco físico, backups o journal. Export/archivo y respaldo se coordinan fuera del plugin; no se borran datos para liberar cuota sin revisión específica.

Retirada: quitar solo el scope privado nuevo, desactivar este plugin y retirar su constante específica. Conservar tablas/revisiones/recibos, lector público y permisos anteriores. Sin uninstall hook o DROP automático. No hay cambios públicos que revertir por guardar un plan privado. Se requiere respaldo verificado y plan de recuperación antes de habilitar escritura privada real.

La barrera wpdb/mysqli fija conexión/transacción y evita replay automático dentro de la operación. Los planes son historia, no aprobación vigente. Polylang/cachés/escritores externos requieren nueva coordinación antes de futuras escrituras de fuente. HTML se almacena como texto privado de revisión; vistas deben escaparlo. Este paquete no sanea ni publica ese HTML.
