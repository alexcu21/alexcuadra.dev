# Propuestas privadas del blog: acceso real verificado — 27 octubre 2026

Instalación rc.2, almacenamiento, propietario2, respaldo inicial y permiso privado completados por Alex. El acceso inicial se verificó mediante el conector real. No repetir instalación, grant o preflights iniciales. No se creó una propuesta para demostrar acceso.

## Evidencia del hosting aportada por Alex

[After-scope](blog-proposal-rc2-destination-after-scope-2026-10-27.json): ready=true,15comprobaciones principales/11del perfil correctas. Usuario2 conserva subscriber y ocho capacidades anteriores, añadiendo únicamente alexcuadra_prepare_post_changes. Sin grant a roles u otras cuentas;16permisos amplios de edición/administración/publicación denegados. Cuatro tablas exactas InnoDB/utf8mb4_bin, propietario2 y propuestas/revisiones/recibos vacíos. ZIP SHA256 `7e5bc2b3213a8497dc47426b4565bbf528400d69f4e4f3e57f40f6119d4d6def`.

[Exportación](blog-proposal-backup-destination-export-2026-10-27.json) y [verificación](blog-proposal-backup-destination-verify-2026-10-27.json) del respaldo inicial: propietario1fila, otras tablas0; integridad correcta y sin escrituras de BD. El artefacto privado no se descargó, publicó ni restauró sobre el CMS vivo. La recuperación con get/list/replay se ensayó con datos sintéticos y herramientas exactas en un entorno aislado; no se confunde con una restauración del hosting.

## Verificación independiente del conector

[Recibo MCP](blog-proposal-live-mcp-2026-10-27.json): siete llamadas correctas, una de descubrimiento, cinco de información de herramientas y una de listado privado. Ninguna mutación solicitada ni cuerpo de artículo leído.

- Catálogo18abilities: conserva las13anteriores y añade inspect-post-context, create-post-proposal, update-post-proposal, get-post-proposal y list-post-proposals bajo alexcuadra-blog/.
- Entradas y salidas cerradas recursivamente. Las cinco herramientas son idempotentes y destructive=false; inspección/get/list son readonly=true y create/update readonly=false porque guardan planes privados.
- public=false y show_in_rest=false se conservan en la habilidad WordPress. meta.mcp.public=true registra su exposición como herramienta MCP; no elimina el control de permiso/propietario ni declara públicas sus propuestas.
- list-post-proposals con after=0/limit=1 devuelve success=true, items=[] y next_after=null. Esto verifica acceso al inventario del actor conectado; el diagnóstico CLI aportado por Alex comprueba que el único usuario con el nuevo scope es2. No se consultó por separado el perfil personal del usuario del conector.

No se ejecutaron inspect-post-context, create/update o get de documentos para probar registro. La disponibilidad en catálogo de las herramientas anteriores no sustituye otra prueba funcional de sus datos; los lectores ya tenían cierre real previo y no se repiten sin causa.

## Alcance y siguiente incremento

La capacidad actual permite preparar/revisar planes privados de cambios y traducciones previstas. No crea borradores o traducciones reales, edita posts, aprueba, aplica o publica contenido. Una primera propuesta real debe tener fuente y diff concretos; no se crea un cambio arbitrario por continuidad de la instalación.

Privacidad entre propietarios, revocación, desactivación, concurrencia y fallos siguen respaldados por laboratorio; no se hicieron esas mutaciones en el hosting para repetir evidencia. No hay huella global nueva ni certificación de conservación de toda la BD. No se reabrió la incidencia histórica de postmeta o la propuesta227/revisión1. No hubo SSH del agente, credenciales nuevas, correo o deploy.

PHP8.5.12 del hosting frente a8.5.11 del laboratorio; huellas de entradas/perfil contrastadas, sin auditoría de árboles completos u opciones/cachés reales. Las tablas iniciales vacías no prueban creación/replay reales por MCP: el piloto privado continúa pendiente y se mantiene separado de escritura pública.

Siguiente piloto con fuente/diff explícitos o ampliar taxonomías propias de proyectos. El contacto/producción/CMS429/accesibilidad siguen en el inventario y no se declaran resueltos por este cierre. Para respaldos futuros, pausar escritores y retirar solo el scope nuevo; el respaldo inicial no incluye planes posteriores.

## Actualización: piloto privado completado

[Propuesta1/v1 y evidencia](MCP_BLOG_PROPOSAL_PILOT_2026-10-27.md): creación/get/inventario/replay exacto por MCP y fuente observada sin cambios. Esta evidencia posterior sustituye la espera del primer piloto descrita arriba; no convierte la prueba de instalación inicial en una prueba de publicación. No repetir preflights que requieren tablas vacías ni borrar la propuesta.
