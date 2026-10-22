# Plan de rediseño de alexcuadra.dev

Actualización de contacto: Turnstile staging guardado según Alex; bindings de envío y límite inicial (5 intentos/minuto por IP y ubicación) preparados en Wrangler local. Formulario desactivado; destinatario privado, despliegue, interfaz y prueba real pendientes. Ver `docs/CONTACT_FORM_PLAN.md`.

Creado: 17 de octubre de 2026. Actualizado: 18 de octubre de 2026. Estado: ejecución por fases; infraestructura y autenticación operativas, modelado/bilingüismo en curso, interfaz nueva pendiente.

## Estado actual y seguimiento

22 octubre, candidato de propuestas privadas0.1.0-rc.1 preparado: [runbook y límites](WORDPRESS_MCP_PROPOSALS_CANDIDATE.md). Seis abilities sin apply/recover/approve, ZIP/manifiesto/hash, guard exacto por opt-in, almacenamiento separado y sin scopes automáticos.95 comprobaciones con stubs PHP y ZIP determinista, sin aceptación de WordPress/DB/transporte. Instalación HOLD por ensayo aislado/respaldo+archivo restaurados/retención y aprobación concreta pendientes. SSH solo por Alex; CMS≈690,5MiB medido y29GiB locales disponibles, sin descargas/Docker/deploy/contacto. Mejora Astro–WordPress sigue última.

21 octubre, matriz de efectos/tratamiento cerrada: [diseño vigente](WORDPRESS_MCP_EFFECTS_POLICY.md), [QA](qa/MCP_EDITORIAL_EFFECTS_2026-10-21.md). Hostinger notificación/llms excluyen project por código; sync Polylang vacío observado, sujeto a guard de perfil; LiteSpeed exige diferir su purga inmediata. Detectada llamada innecesaria a wp_update_post para tagline. Protocolo de adaptación por operación/outbox/reconciliación definido, sin implementar. Preflight8/8 y huellas146/196 conservadas; no escritura/instalación/scopes ni deploy. Próxima entrega concreta: candidato operativo de seis abilities privadas de propuestas existentes, sin apply/recover registrados, con paquete/hash/guard/permisos/privacidad/archivo/recuperación revisables antes de pedir instalación. No seguir ampliando laboratorio por inercia. Contacto apagado, visual/429 abiertos y mejora Astro–WordPress última.

21 octubre, revisión operativa del CMS real completada: diagnóstico8/8 correcto y9 escenarios locales del diagnóstico; usuario2/lectura y muestras146↔196/ACF intactos. Revisados15 métodos de hooks instalados: efectos Hostinger/LiteSpeed y sincronización condicional Polylang requieren tratamiento por contexto antes de escritura. [Decisión HOLD, puertas y reversión](WORDPRESS_MCP_OPERATIONAL_READINESS.md), [QA](qa/MCP_EDITORIAL_OPERATIONS_2026-10-21.md). Manifiesto fija base0.5-lab/contratos/evidencia531; no ZIP operativo, instalación ni scopes reales. No repetir laboratorio funcional: próximo incremento acotado es matriz de efectos/contextos y tratamiento para candidato operativo, respaldo/restauración y archivo privado pendientes. Sin frontend/deploy/contacto/navegador; staging TOC ya actualizado, visual/429 abiertos. Conector Astro–WordPress permanece en la última etapa.

20 octubre, actualización frontend staging completada para revisión:1b12660e (deploymentbf288cd4-3920-4183-bacf-49b9cea04c02),100% activo. TOC/anchors Blog/LAB EN/ES antes locales ya publicados;237 unitarias/suite SSR/build/dry-run y cuatro fichas200 con índice/destinos/CSS/SEO/CSP/identidad/contactfalse correctos. [QA vigente](qa/EDITORIAL_OUTLINE_STAGING_2026-10-20.md). Rollback compatiblebbb conservado; sin CMS/permisos/MCP/producción/envíos/navegador, dev/cambios preservados. QA visual/zoom/lector y causa429 pendientes; no promover a producción. Siguiente trabajo técnico independiente: implementación del contrato MCP en laboratorio, no instalación real.

20 octubre, contrato local de aplicación/recuperación definido, sin código nuevo: cinco schemas previstos, aprobación exacta por operador distinto, efecto público de editar Project publicado reconocido, permisos separados y recuperación con control de versiones/reserva. [Contrato](WORDPRESS_MCP_SOURCE_APPLICATION.md), [QA documental](qa/MCP_EDITORIAL_APPLICATION_CONTRACT_2026-10-20.md). Runtime0.3/lectura intactos, último290 no repetido; sin CMS/Workers/contacto/envíos/navegador. Próxima entrega frontend prioritaria: preparar [actualización staging TOC/anchors](STAGING_NEXT_RELEASE.md) sin esperar a terminar MCP; QA visual/429 siguen abiertos, sin promover a producción. Siguiente incremento de código MCP: implementar el contrato en laboratorio antes de paquete/permisos/diff real aprobados.

20 octubre, integración ACF/Polylang/WPGraphQL aislada completada:290 checks correctos (236 base+54 integración), campos originales/EN/ES y privacidad GraphQL de propuestas verificados, fuentes intactas; prepare no concede edición de Projects. [QA](qa/MCP_EDITORIAL_GRAPHQL_STACK_2026-10-20.md). Prototipo0.3/lectura0.1.0 intactos; sin CMS/permisos reales/Workers/contacto/envíos/navegador, solo nuevo lab detenido. Próximo: contrato local separado de aplicación/recuperación de fuentes; no instalar escritura ni crear Editor por este resultado. Reconciliación CMS antes de apply, TOC visual/429 y política ACF de fuentes con contraseña siguen abiertos.

20 octubre, idempotencia y cuotas MCP completadas solo en laboratorio0.3: request_id obligatorio y recibos por propietario, transacción de admisión/cambio/historial/recibo,8 propuestas/128 escrituras/8MiB lógicos.236 checks correctos, seis carreras HTTP verifican versiones, duplicados y último cupo; fuentes intactas, fallos sin efectos parciales. [QA](qa/MCP_EDITORIAL_IDEMPOTENCY_2026-10-20.md). Sin CMS/permisos reales/Workers/contacto/envíos/navegador, lectura0.1.0 intacta; no hace falta Editor. Próximo: ACF/Polylang/WPGraphQL aislados y privacidad de propuestas; aplicación/publicación de fuentes separadas. TOC visual/429 pendientes.

20 octubre, historial y recuperación MCP completados solo en laboratorio0.2: snapshots y cabeza en CAS único, recuperación como versión nueva sin editar fuentes, misma autorización; límites32/2MiB sin poda, baseline legacy sin pasado inventado.181 checks correctos, incluidas carreras HTTP update/update y update/restore con un ganador/un conflicto y una sola revisión nueva. [QA](qa/MCP_EDITORIAL_HISTORY_2026-10-20.md). Sin CMS/permisos reales/Workers/contacto/envíos/navegador, lectura0.1.0 intacta; no hace falta Editor. Próximo: idempotencia/cuotas por propietario en laboratorio; integración real y apply separados. TOC visual/429 pendientes.

20 octubre, transporte HTTP de propuestas completado en laboratorio:48 checks nuevos y117 en runner combinado; dos carreras entre procesos independientes confirman un ganador/un conflicto por ronda, sin pérdida de campos. Sesiones/usuarios/revocación/edición REST denegada y hashes fuentes verificados. [QA](qa/MCP_EDITORIAL_CHANGES_HTTP_2026-10-20.md). Prototipo y lectura0.1.0 intactos; sin CMS/permisos reales/Workers/contacto/envíos/navegador. No hace falta Editor; lab propio detenido, cambios locales preservados. Próximo: historial durable y recuperación de propuestas propias en laboratorio, después idempotencia/cuotas/apply separado; WPGraphQL real/TOC visual/429 pendientes. HTTP del prototipo ya está probado.

20 octubre, siguiente tramo MCP preparado localmente: propuestas privadas de cuerpo/subtítulo, sin aplicar/publicar fuentes, prototipo bloqueado fuera de laboratorio.67 pruebas WP/meta-tools+2 guards correctos; dueño/scopes/versiones/CAS y hashes fuentes verificados. **No hace falta usuario Editor** para preparar estas propuestas; suscriptor con capacidad específica basta, sin concederla al usuario real. [QA](qa/MCP_EDITORIAL_CHANGES_LAB_2026-10-20.md). Próximo: HTTP/sesiones y concurrencia por peticiones independientes en Docker. Lectura instalada0.1.0 intacta, sin CMS/Workers/contacto/envíos/navegador; TOC visual/429 siguen pendientes.

20 octubre, MCP de lectura instalado tras autorización de Alex: Editorial Read0.1.0 y scope solo usuario2/subscriber, sin edición/admin ni rol global ampliado. Conector real descubre2 abilities propias además de3 core; paginación/búsqueda/146↔196/rechazos verificados. Huellas14 proyectos, otros permisos, rol y otros plugins activos intactas. [QA actual](qa/MCP_EDITORIAL_INSTALLED_2026-10-20.md). CMS cambia plugin/capacidad, contenido no; sin despliegues/contacto/envíos/navegador. Siguiente: contrato local de edición acotada y pruebas aisladas, sin ampliar acceso real; lectura ya completada. TOC visual/429 siguen pendientes.

20 octubre, revisión SSH real acotada completada: cuenta2/subscriber/read sin scope ni edición/admin comprobados; preflight final exit0. Inventario y filtros relevantes revisados, sin override adicional identificado en esa superficie; no auditoría completa de cada contexto. Perfil real WP7.1.3/PHP8.2.34/Polylang3.9.0/Adapter0.7.1 reproducido en laboratorio separado:91 framework/HTTP+5 escenarios correctos. [QA](qa/MCP_EDITORIAL_PREFLIGHT_2026-10-20.md). Propuesta de instalación0.1.0/capacidad individual/rollback lista para decisión expresa; **no instalada ni permisos reales cambiados**. Contacto/Workers/navegador intactos; TOC visual/429 siguen abiertos.

20 octubre, revisión de permisos visible por MCP: usuario2/subscriber y3 core confirmados, capacidades no expuestas y versiones denegadas. Diagnóstico WP-CLI read-only y [propuesta instalación/reversión](WORDPRESS_MCP_READ_INSTALL.md) preparados;5 escenarios aislados correctos y sin escrituras del diagnóstico. [QA/límites](qa/MCP_EDITORIAL_PREFLIGHT_2026-10-20.md). Alex tiene SSH; falta conexión concreta/salida para completar ACL real antes de decidir instalación. No instalado ni ampliado acceso real; contacto/Workers intactos.

20 octubre, transporte HTTP MCP de lectura completado en Docker aislado:36 HTTP +55 framework correctos, subscriber/Application Password y capacidad específica sin admin, aislamiento/revocación y ausencia de cambios editoriales comprobados. [QA](qa/MCP_EDITORIAL_HTTP_2026-10-20.md). Paquete0.1.0 intacto, sin instalación/permisos reales/CMS/Workers/contacto/envíos. Siguiente: revisar ACL y presentar instalación/asignación mínima para decisión expresa antes de aplicarlas al sitio; escritura/ACF/cuerpos separados. TOC visual/429 siguen pendientes y navegador no reintentado/eludido.

20 octubre, etapa independiente MCP: contrato y extensión de lectura0.1.0 preparados,55 checks con WordPress/adaptador oficiales en Docker aislado, ZIP verificable. Sin instalación/permisos reales/CMS/contacto/Workers. [QA](qa/MCP_EDITORIAL_READ_LAB_2026-10-20.md). Falta transporte HTTP MCP y revisión antes de instalar/asignar permisos; no confundir pruebas PHP del adaptador con conexión final del cliente. TOC visual y429 siguen pendientes.

20 octubre, recuperación del Worker staging ensayada: rollback a versión anterior compatible y restauración original con Home autenticada/asset/contacto false verificados en tres fases.237 unitarias pasan; herramientas operativas añadidas, sin subir código nuevo ni CMS/producción alterados. [QA/alcance](qa/STAGING_RECOVERY_2026-10-20.md). Este par de versiones de código/configuración ya tiene ensayo; no equivale a restauración de datos o ensayo producción. TOC y aceptación visual siguen pendientes; alternativa independiente: MCP de lectura en laboratorio.

20 octubre, anchors/TOC compartidos preparados en local: Blog/LAB EN/ES, documento único con campos ACF, IDs seguros/únicos y details sin scripts.228 unitarias/suite SSR ampliada/build correctos, sin CMS/deploy/contacto. [QA](qa/EDITORIAL_OUTLINE_LOCAL_2026-10-20.md). Falta aceptación visual/teclado/fragmentos/zoom y despliegue de este componente; no cerrar esa parte por aprobar HTML SSR. Próximo trabajo independiente si el navegador sigue bloqueado: ensayo de recuperación staging.

20 octubre, QA remoto editorial completado por tramos en staging existente: Blog404 y archivos/fichas LAB EN/ES+referencias correctos con identidad/SEO/CSP/idiomas/404. Añadida reanudación del smoke y corregido temporizador que consumía el timeout durante la espera entre peticiones.218 pruebas pasan, sin deploy/CMS/producción/contacto. No resuelve la causa del429 histórico ni cierra QA visual/zoom/lector/recuperación. [QA y continuación](qa/EDITORIAL_FOLLOWUP_2026-10-20.md). Próximo incremento local: anchors/TOC del renderer compartido; MCP editorial permanece separado.

19 octubre, continuidad local: lectores legacy de referencias/categorías/slugs paginados y contratos de rutas centralizados,160 pruebas y build correctos. Sin cambios de idiomas/URLs/CMS ni deploy; runtime SSR integral y QA visual siguen pendientes. [QA](qa/REFERENCES_PAGINATION_LOCAL.md). Incorporada etapa técnica independiente [MCP editorial con permisos acotados](WORDPRESS_MCP_EDITORIAL_PLAN.md), todavía sin implementación ni ampliación de acceso.

19 octubre, primera entrega visual publicada para revisión en staging tras confirmación de Alex: Home, tokens Obsidian Flow claros/oscuros, tipografía y tarjetas compartidas. Corregida caché Vite para mantener dev abierto durante build;145 pruebas/build/smoke correctos. QA visual completo aún pendiente por permiso de observación del navegador; no se declara cerrado el sistema visual. [Detalle](qa/HOME_VISUAL_STAGING.md).

19 octubre: primera entrega visual Home/sistema compartido preparada localmente según Obsidian Flow. Pruebas y build pasan; falta revisión visual móvil/escritorio y ambos temas por bloqueo del origen local del navegador. NO desplegada. [Detalle](qa/HOME_VISUAL_LOCAL.md).

19 octubre, SEO técnico: sitemap SSR desde inventario CMS y robots por entorno desplegados en staging,34 URLs/8ES verificadas,145 pruebas pasan. No se han publicado todavía estos endpoints en producción ni enviado sitemap a Search Console. Pendientes SEO editorial, clasificación de referencias, QA integral y rediseño visual. [QA](qa/SITEMAP_BILINGUAL_STAGING.md).

19 octubre, recuperación de errores: HTML se completa antes de enviar respuesta para convertir fallos CMS tardíos en503 íntegro en vez de200 parcial. Regresión reproducida en Astro SSR y 139 pruebas pasan; staging actualizado. Home mantiene fichasEN válidas tras nuevas traducciones. No se declara resuelto el límite upstream429. [QA](qa/CMS_STREAMING_RECOVERY.md).

19 octubre: Valexweb y Norceca traducidos/publicados, cuatro proyectos ES reales en staging. Fichas y alternates comprobados; persiste incidencia intermitente de carga CMS/frontend, sin solución permanente confirmada. Quedan textos de tres proyectos y rediseño/QA integral. [QA](qa/VALEXWEB_NORCECA_ES.md).

19 octubre: Christianity Today146EN completado con información de Alex y traducción196ES publicada/vinculada; tarjeta y fichas con metadatos recíprocos verificadas. Corregido título largo en móvil. Quedan otras traducciones y el rediseño de caso de estudio completo; no se considera cerrado el template visual final. [QA](qa/CHRISTIANITY_TODAY_PUBLISHED.md).

19 octubre: primera ficha ES real publicada, alexcuadra.dev195↔50EN; tarjeta y detalle operativos en staging con slug editorial `alexcuadra-dev-2`, selector y hreflang recíprocos. Smoke y navegador pasan. Christianity Today sin texto en CMS; datos editoriales solicitados a Alex. No se declara completa la traducción de proyectos. [QA](qa/FIRST_PROJECT_ES_PUBLISHED.md).

19 octubre, proyectos: templates de archivo/detalle EN/ES y menú español integrados en staging. CMS tiene cero proyectos ES; estado vacío explícito y fichas sin traducción404. Lectura EN paginada y por idioma, sin alterar contenido/categorías. Falta traducir proyectos reales y validar sus fichas españolas. [QA](qa/PROJECTS_LOCALIZED_STAGING.md).

19 octubre, Home ES: publicada traducción Inicio 193↔Home19 y template `/es/` en staging, campos, selector y navegación localizados. Proyectos y referencias conservan destinos actuales; no implica traducción de colecciones ni rediseño visual final. 134 pruebas, build y smoke de dos portadas pasan. [QA](qa/HOME_ES_PUBLISHED.md).

19 octubre, traducción About: Sobre mí 191 publicada y relacionada con 68 en Polylang; campos traducidos, regla ACF ampliada solo a la nueva página. Ruta ES y menú conectados en staging con selector/hreflang recíprocos. [QA](qa/ABOUT_ES_PUBLISHED.md). Siguiente bloque: traducción real de Home y su template; Projects/Articles y fases visuales siguen pendientes.

19 octubre, template About: EN usa lector localizado y descripción CMS; renderer compartido y ruta `/es/sobre-mi/` implementados en staging. Traducción ES ausente en CMS: 404 explícito y ningún enlace/hreflang ficticio. Preparación técnica completada, publicación editorial española pendiente. [QA](qa/ABOUT_LOCALIZED_STAGING.md).

19 octubre, siguiente incremento: menú, control de tema y footer reciben locale; textos ES y destinos EN explícitos en privacidad española, sin rutas inexistentes. Mejora de Escape/foco en menú móvil. Ver [QA](qa/NAVIGATION_BILINGUAL_STAGING.md). Navegación localizada no implica que Home/Projects/About/Articles estén traducidos.

19 octubre: primer tramo ES desplegado en staging, `/es/privacidad/` desde página 182, junto a EN 180, con enlaces de idioma y hreflang recíprocos condicionados a fuentes publicadas. 134 pruebas, build y smoke bilingüe correctos. Producción, menús ES y demás templates pendientes. Contacto sigue desactivado y su prueba de entrega aplazada. Ver [evidencia](qa/PRIVACY_BILINGUAL_STAGING.md).

Esta lista refleja lo verificado hasta el 18 de octubre. `[x]` indica un entregable completado dentro del alcance descrito; `[ ]` indica trabajo pendiente. Una fase con pendientes no se considera cerrada. El [registro de ejecución](EXECUTION_STATUS.md) conserva las evidencias y el historial; las anotaciones antiguas no sustituyen este estado vigente.

### Completado

- [x] Analizar mockups y acordar que Christianity Today define el template individual reutilizable de todos los proyectos.
- [x] Definir posicionamiento senior con calidad, automatización y DevOps, CMS como fuente editorial y prohibición de cifras ficticias del mockup.
- [x] Distinguir Articles (publicaciones externas/menciones), Posts (blog propio) y LAB (proyectos personales).
- [x] Actualizar la base a Astro 7.4.0, adapter Cloudflare 14.4.0, Tailwind 4.3.3 y Wrangler 4.151.1; validar pruebas y builds locales.
- [x] Preparar CI con instalación reproducible, pruebas y builds de staging/producción, sin secretos del CMS.
- [x] Migrar producción y staging a Workers; dominio alexcuadra.dev operativo y comprobaciones HTTP autenticadas correctas.
- [x] Implementar autenticación de servicio en servidor y activar en WordPress la restricción de GraphQL a solicitudes autenticadas; comprobar local y ambos Workers.
- [x] Centralizar errores de CMS, timeout, deduplicación por petición, 503 controlado y logs sin respuestas/credenciales.
- [x] Evitar consultas al CMS en 404, favicon y sondas rest_route; incorporar caché pública limitada a contratos explícitos.
- [x] Inspeccionar el plugin custom y sustituir el registro de project/articles por ACF conservando claves, IDs y slugs, sin copiar entradas; revisar exports y lectura publicada.
- [x] Confirmar backup reciente de base de datos/archivos y autorización para trabajar directamente en el CMS de producción.
- [x] Configurar Polylang 3.8.11 con EN/ES e inglés predeterminado; probar el conector 0.7.1 en laboratorio y activarlo en el CMS.
- [x] Habilitar Projects/Articles como traducibles y verificar idioma, traducciones y filtros en Page, Post, Project, Article y Category.
- [x] Asignar EN a los siete Projects publicados; verificar hashes editoriales, campos ACF, imágenes y relaciones tras el cambio. Home/About ya están en EN.
- [x] Identificar con Alex las nueve referencias externas cuyo texto local está en español; asignación en WordPress todavía pendiente.
- [x] Auditar uso de categorías en proyectos/posts/referencias publicados; Alex confirma ausencia de otros consumidores ingleses de podcast/valexweb/wordpress-tv. Decisión posterior de Alex: conservar esas categorías en EN; cambio a ES descartado por ahora.
- [x] Implementar cliente EN/ES opcional, paginación, DTOs públicos y relaciones de Home/About sin IDs españoles supuestos; mantener las rutas actuales compatibles.
- [x] Validar 92 pruebas unitarias, 13 comprobaciones del cliente contra WordPress aislado y compilación Astro/Cloudflare. La matriz de seguridad mantiene cuatro fallos conocidos de política ACF protegida, reproducibles sin el conector; no se declaran resueltos.
- [x] Guardar planes/guías en docs y actualizar README como entrada al proyecto.
- [x] Preparar mapa técnico de rutas EN/ES y SEO base del layout: canonical estable, locale explícito, imágenes sociales opcionales y eliminación de duplicados. Rutas ES/hreflang/sitemap siguen pendientes.
- [x] Comprobar SEO base en HTML local de siete páginas con autenticación CMS; build correcto y vista previa actualizada. Despliegue de estos cambios pendiente.
- [x] Centralizar HTML editorial en WordPressContent con sanitización de servidor y estilos core acotados en About, proyectos y referencias; 98 pruebas unitarias y smoke local de contenido/SEO correctos. Despliegue y revisión visual completa pendientes.

- [x] Incorporar al MVP el formulario con Turnstile y definir su contrato; validación y Siteverify preparados con pruebas simuladas. Endpoint local conectado a un adaptador de envío Cloudflare simulado, desactivado por defecto; interfaz/bindings/configuración reales pendientes.
- [x] Configurar Email Sending de forms.alexcuadra.dev: Enabled reportado por Alex y DNS autoritativo confirmado, conservando la recepción del dominio principal. Destinatario Verified confirmado por Alex; integración local preparada, binding y entrega real pendientes.

### Pendiente antes de cerrar las fases en curso

- [ ] Resolver el modelo de relaciones de Articles respetando la decisión de mantener sus categorías EN; la clasificación de las fichas queda diferida hasta entonces.
- [ ] Crear y revisar las traducciones reales; ampliar las reglas ACF de Home/About a los IDs españoles que se creen.
- [ ] Completar los nuevos fields/CPT con ACF gratuito; LAB y qa-training-lab EN/ES ya creados/publicados (19 octubre), otros modelos del plan siguen pendientes.
- [ ] Completar contenido y evidencias reales del posicionamiento senior, calidad, automatización y DevOps.
- [ ] Implementar formulario EN/ES accesible: fields CMS, widget, endpoint Workers, validación, límites distribuidos y entrega controlada. Confirmar destinatario/proveedor antes de configurar envíos; seguir CONTACT_FORM_PLAN.md.
- [ ] Conectar las rutas/templates EN/ES, selector de traducciones reales y política de URLs legacy; el nuevo cliente localizado todavía no está desplegado.
- [ ] Implementar el sistema visual y rediseñar Home, proyectos, referencias, blog, About y LAB.
- [ ] Completar revisión visual/accesibilidad de Gutenberg, anchors/TOC y componentes especiales. Sanitización y estilos básicos core ya implementados localmente; embeds/custom blocks aún no soportados.
- [ ] Completar SEO: metadatos, lang, canonical/hreflang, sitemap, robots y datos estructurados.
- [ ] Completar 404 de detalles inexistentes, estados vacíos y contratos de datos opcionales.
- [ ] Completar cabeceras/CSP, revisión de exposición de HTML/enlaces y seguridad del rediseño.
- [ ] Validar caché/SSR bilingüe en Workers, E2E, accesibilidad, revisión visual móvil/escritorio y recuperación/rollback ensayado.
- [ ] Mantener seguimiento de 429/503 de Hostinger; los smoke correctos no prueban eliminación del limitador.


## 1. Objetivo y alcance

Presentar a Alex como desarrollador web senior con experiencia demostrable en calidad, automatización y DevOps. La prioridad es que una persona pueda entender su especialidad, revisar evidencia de proyectos y contactar. Mantener Astro, Tailwind, TypeScript, Cloudflare, WordPress, WPGraphQL y ACF gratuito. Mantener las URLs inglesas existentes para evitar perder enlaces y posicionamiento; añadir URLs españolas propias sin sustituir las actuales.

Las cifras de los mockups NO son reales y no deben migrarse como contenido ni usarse como valores por defecto. Las propuestas son únicamente una guía visual y de los datos que habrá que agregar. El posicionamiento acordado es desarrollo web senior, calidad, automatización y DevOps; SDET puede describir una especialidad respaldada por contenido, sin sustituir ese posicionamiento. Sitio bilingüe en inglés y español desde el rediseño. Mantener inglés en las URLs actuales y agregar español bajo `/es/`; gestionar ambas versiones y sus metadatos en WordPress, con traducciones revisadas y publicación independiente por idioma. Véase la propuesta de la sección 13.

### Fuente única de contenido: content.alexcuadra.dev

Toda la información publicable se agregará y administrará en **content.alexcuadra.dev**: identidad y posicionamiento, textos de secciones, especialidades, experiencia, proyectos, casos de estudio, tecnologías utilizadas, resultados, métricas, testimonios, artículos, menciones, imágenes, CV, disponibilidad, contacto, enlaces y metadatos SEO. Astro consume ese contenido por GraphQL autenticado y lo presenta; no mantener copias editoriales en componentes, archivos Markdown locales o constantes del frontend.

Los mockups determinan el tipo de información y su composición, no su veracidad. No importar sus cifras, nombres de testimonios, tecnologías atribuidas a clientes, estados de CI ni afirmaciones de rendimiento. Definir primero los campos o CPT necesarios con ACF gratuito, cargar los datos reales en WordPress y después conectar los componentes. Si un dato opcional falta, ocultar el bloque; si falta contenido esencial, mantenerlo en borrador y bloquear la publicación de esa página. No completar huecos con cifras ficticias ni mostrar cero como si fuera una medición.

Código, tokens visuales, estructura de componentes, validadores y mensajes técnicos de error siguen en el repositorio. Conteos, tiempo de lectura y otros valores derivados pueden calcularse desde contenido real del CMS; no convertirse en datos ficticios de marketing. Las etiquetas editoriales y de navegación se incluyen en los ajustes o páginas del CMS cuando se requiera editarlas. Los fixtures de pruebas serán sintéticos, explícitos y exclusivos del entorno de test; nunca serán fallback de producción.

El inventario de contenido debe mapear cada bloque del mockup a entidad/campo, responsable de carga, evidencia y estado (pendiente, borrador o aprobado). Para DevOps, documentar contribuciones concretas en CI/CD, despliegues, infraestructura, observabilidad o recuperación únicamente donde Alex haya participado, indicando rol, alcance y resultados verificables. Las herramientas de DevOps no se atribuyen a un proyecto solo porque aparezcan en la maqueta.

## 2. Análisis de las propuestas locales

Se revisaron las cuatro imágenes `screen.png`, sus correspondientes `code.html`, `obsidian_flow/DESIGN.md` y el inventario de texto del sitio dentro de `docs/design-proposal/`.

| Propuesta | Qué conservar | Qué ajustar antes de implementar |
| --- | --- | --- |
| Home / Senior Web Developer & SDET | Hero de posicionamiento, disciplinas, proyectos con contexto, CTA visible; buen puente entre frontend y calidad | Reducir altura y densidad en móvil. El terminal de pruebas y la disponibilidad deben ser información real cargada en WordPress; si falta, ocultar el bloque. No lanzar un botón de ejecutar CI público |
| Template individual de proyectos (carpeta `christianity_today_case_study_alex_cuadra_portfolio`) | Estructura reutilizable para TODOS los proyectos: rol, alcance, problema, decisiones, arquitectura, resultados y navegación entre proyectos | Christianity Today es solo el ejemplo visual del template, no una página especial ni su contenido definitivo. Cada proyecto aporta sus propios datos desde WordPress; mostrar únicamente los bloques que correspondan |
| Articles & Mentions / archive | Tarjetas por formato, búsqueda, filtros por plataforma y temas, referencia destacada | El CPT articles contiene referencias externas: publicaciones de Alex alojadas fuera del sitio o menciones de terceros. El blog propio usa post y necesita un archivo/template diferenciado aunque el mockup no lo distinga. Reducir estadísticas y evitar filtros vacíos |
| Article template | Lectura con buen ancho, resumen, bloques de código, tabla de contenidos, autor y relacionados | En móvil, TOC desplegable. Sin contador de aplausos, bookmarks con cuenta ni estado de CI en vivo en MVP. Compartir/copiar puede usar APIs del navegador; estados accesibles al completar o fallar |
| Obsidian Flow | Plus Jakarta Sans para títulos/cuerpo, JetBrains Mono para código y metadatos; cian, violeta y verde con jerarquía | Hay contradicciones entre tokens YAML, descripción y HTML: fondo `#0e131f` vs `#030712`, radios y primarios distintos. Definir una única fuente de tokens antes de construir componentes |

### Dirección visual recomendada

Base oscura `#0e131f`, superficie `#161c28`, texto `#dde2f3`, texto secundario `#bcc9cd`, acento principal `#4cd7f6`, secundario `#d0bcff` y éxito `#4edea3`. Conservar verde solo para estados comprobados. Verificar contraste por pareja real, incluyendo botones, chips, foco y texto sobre gradientes; no asumir que la paleta garantiza accesibilidad.

Contenedor de 1200 px; móvil de una columna con 16 px de margen, tablet con 24–32 px, escritorio con composición de hasta 12 columnas. Cuerpo de 16–18 px y lectura de 65–75 caracteres. Títulos fluidos con `clamp()`, sin copiar tamaños de escritorio al móvil. Desenfoques decorativos discretos, sin animaciones continuas costosas; respetar `prefers-reduced-motion` y colores forzados.

Implementar componentes Astro reutilizables: SiteHeader, MobileNav, Button, Badge, SectionHeader, ProjectCard, MediaCard, Metric, Prose, CodeBlock, TableOfContents, Breadcrumbs y ContactCTA. Mantener JavaScript limitado al menú, tema, filtros y acciones de copiar/compartir. Usar el Tailwind instalado y los iconos SVG actuales; los scripts CDN de Tailwind y las fuentes de iconos de los HTML son referencias de mockup, no dependencias de producción.

Mantener el selector de tema actual, pero diseñar y validar expresamente la variante clara: no existe en las referencias. Fuentes locales/subconjuntos y solo pesos necesarios; reservar proporciones para imágenes. Un único H1, skip link, navegación por teclado, foco visible, menú con `aria-expanded` y objetivos táctiles suficientes.

## 3. Arquitectura de información

La tabla siguiente describe las rutas inglesas existentes. Las equivalentes españolas se especifican en la sección 13 y forman parte del rediseño.

| Ruta | Alcance MVP |
| --- | --- |
| `/` | Posicionamiento, 3–4 especialidades, 3–4 proyectos destacados seleccionados, contenido reciente y contacto |
| `/work/` | Archivo de proyectos; filtro por disciplina/tecnología cuando el volumen lo justifique |
| `/work/[slug]` | Caso de estudio: contexto, rol, alcance, problema, decisiones, entregables y resultados verificables |
| `/about/` | Trayectoria, contribuciones, experiencia y formas de colaborar; ampliar la página actual |
| `/articles/` | Archivo de referencias externas y menciones, desde el CPT articles; indicar plataforma y distinguir publicación de Alex de mención de terceros |
| `/blog/` y `/blog/[slug]` | Archivo y lectura desde post nativo implementados localmente, con variantes ES, menú/sitemap y ejemplo202↔204 publicado. QA: docs/qa/BLOG_BILINGUAL_LOCAL.md; despliegue frontend pendiente |
| `/articles/category/[slug]` | Conservar archivos actuales de plataformas como páginas indexables; no cambiar slugs existentes |
| `/articles/category/[categorySlug]/[articleSlug]` | Conservar URLs de artículos; validar la relación categoría/artículo y devolver 404 si no existe |
| `/#contact` | Formulario con Turnstile, validación servidor y entrega real; email/perfiles como alternativa. Newsletter fuera del MVP |
| `/lab/` y `/lab/[slug]` | Implementados localmente con variantes `/es/laboratorio/`, CPT `lab_project` y qa-training-lab219↔221 publicado. QA: docs/qa/LAB_BILINGUAL_LOCAL.md. Datos CMS reales, sin ejecutar aplicaciones ni CI desde el portfolio |

La navegación del rediseño debe distinguir Blog de las publicaciones externas/menciones, además de Work, About, Lab y Contact; Home desde el logotipo. Ajustar etiquetas y menú móvil con contenido administrado en WordPress. Home puede mostrar ambos en una sección de novedades solo si cada tarjeta identifica claramente su origen y destino. El avatar de cuenta de las propuestas no aporta un flujo al portfolio público y se omite. No lanzar RSS, newsletter, búsqueda remota o telemetría sin definir su fuente y funcionamiento.

## 4. Modelo editorial con ACF gratuito

Primero exportar/inventariar CPT, taxonomías y grupos ACF existentes desde WordPress y confirmar versiones de ACF, WPGraphQL y WPGraphQL for ACF. Los nombres actuales comprobados en código son `projects`, `articles`, `homePageFields`, `projects`, `articlesFields` y `aboutPage`. No renombrarlos directamente ni inventar queries contra campos aún no registrados. Los nombres nuevos siguientes son una propuesta, sujetos a comprobar colisiones en el esquema.

### Reutilizar primero

| Entidad | Campos propuestos / tipo | Uso |
| --- | --- | --- |
| Página Home existente | `specialties` relationship, `headline` text, `intro` textarea, `availability` select, `availability_note` text, `resume` file opcional, `featured_projects` relationship | Sustituir roles numerados por un mensaje editorial; selección explícita de destacados |
| Página de ajustes del sitio, slug `site-settings` | `contact_email` email, `github_url` URL, `linkedin_url` URL, `youtube_url` URL, `social_description` textarea, `default_social_image` image | Datos globales en una página normal con campos simples, sin ACF Options Page; decidir visibilidad y excluirla de navegación/sitemap |
| CPT `projects` existente | `summary` textarea, `client_name` text, `role` text, `period` text, `live_url` URL, `repository_url` URL, `featured` true/false, `display_order` number, `discipline` taxonomy | Tarjetas y cabecera del caso; conservar campos legacy hasta migrar |
| Caso de estudio, mismo proyecto | `problem` textarea, `approach` WYSIWYG, `outcomes` WYSIWYG, `testimonial_quote` textarea opcional, `testimonial_author` text, `evidence_url` URL | Estructura común más cuerpo Gutenberg para arquitectura, capturas, listas y bloques de código |
| CPT `articles` existente: referencias externas | `content_kind` select (article, talk, podcast, video, interview), `reference_kind` select (authored, mention), `source_url` URL y `source_name` text requeridos para publicar, `source_language` select opcional, `published_at` date, `duration_minutes` number opcional, `featured` true/false, `summary` textarea, `social_image` image opcional; conservar quote existente | Formato, autoría de la pieza y plataforma son dimensiones distintas. Alex puede ser autor en una plataforma externa o la persona mencionada; ambos pertenecen a articles. Cuerpo local para contexto/resumen editorial, sin asumir reproducción íntegra |
| Tipo nativo `post`: blog del sitio | título, extracto, contenido Gutenberg, autor, fecha, imagen destacada y temas nativos; SEO y traducción asociados | Publicación propia alojada en el portfolio, lectura completa en /blog/[slug]. No crear un CPT blog ni reutilizar articles para este propósito. quote sigue perteneciendo al grupo legacy de articles |
| Página About existente | Campos actuales más `resume` file opcional; trayectoria en Gutenberg | No hace falta crear un CPT de experiencia para una sola biografía |

Conservar categorías actuales para Medium, Podcast, ValexWeb y WordPress.TV durante la migración. Añadir taxonomía de temas si se necesitan filtros transversales y tecnología/disciplina para proyectos. No convertir de golpe Podcast en taxonomía distinta: guardar una tabla de equivalencias y preservar sus archivos públicos.

No usar Repeater, Flexible Content, Gallery, Clone, ACF Blocks ni Options Pages PRO. Para colecciones variables, usar relaciones entre posts o Gutenberg. Las imágenes de un caso pueden vivir en bloques de imagen nativos. Confirmar que el editor y los tipos de campo escogidos están disponibles en la instalación gratuita real.

### Especialidades y contenido transversal

Incluir un CPT ligero `specialty` para las tarjetas editables de especialidades: título, extracto, cuerpo, `icon_key` select limitado al catálogo local, `display_order` number y relación opcional a proyectos. La Home selecciona registros mediante `specialties` relationship. Permite desarrollo web, calidad, automatización y DevOps sin campos numerados ni Repeater PRO; la taxonomía de disciplina de proyectos clasifica trabajos y no sustituye la descripción editorial de cada especialidad.

Ampliar `site-settings` y los campos de cada página para títulos y descripciones SEO, CTA y etiquetas editoriales; agregar los campos antes de conectar la UI. Imágenes y CV se administran en la biblioteca de medios de WordPress. Las métricas reales, si se publican, se guardan en el contenido del proyecto o en el CPT de métricas previsto, con fuente, fecha y método. La telemetría externa futura deberá tener su configuración y representación editorial en WordPress y una integración explícita; no crear contadores simulados en el frontend.

### CPT LAB confirmado: proyectos personales

Crear `lab_project` mediante ACF como entidad independiente de los CPT de proyectos de clientes y artículos. LAB es parte del alcance confirmado, no una opción condicionada al número de entradas. El nombre interno propuesto debe comprobarse por colisiones; no crear también `lab_experiment`.

Campos nativos: título, slug, extracto, cuerpo Gutenberg, imagen destacada, fechas y revisiones. Campos ACF gratuitos: `status` select (idea, in_progress, beta, released, archived), `repository_url` URL opcional, `demo_url` URL opcional, `purpose` textarea, `learning_goals` textarea, `technical_approach` WYSIWYG, `quality_strategy` WYSIWYG, `devops_notes` WYSIWYG, `last_reviewed_at` date, `featured` true/false y `display_order` number. Taxonomías de tecnologías/temas cuando aporten navegación; relaciones opcionales a artículos que explican el trabajo.

Archivo `/lab/` y detalle `/lab/[slug]`, con equivalentes `/es/laboratorio/` y `/es/laboratorio/[slug-es]`. Incluir selector de idioma, SEO y estados editoriales como el resto del sitio. Puede reutilizar componentes del template individual de proyectos, con variante para objetivos, avances, aprendizajes y próximos pasos; no exigir cliente, impacto comercial ni métricas de producción.

Primera entrada `qa-training-lab` publicada EN219↔ES221 el19 de octubre con autorización de Alex y datos verificados del README. Repo/demo sin enlazar hasta verificar visibilidad pública; no añadir métricas ni información operativa privada. No modificar ni ejecutar su repositorio desde el portfolio. Todo su contenido de presentación se carga en content.alexcuadra.dev; la aplicación vive y se ejecuta en su entorno propio. Ver docs/qa/LAB_BILINGUAL_LOCAL.md.

### CPT de métricas condicionado a necesidad editorial

`project_metric` solo si se requieren métricas estructuradas y filtrables en varios proyectos: relación al proyecto, etiqueta, valor, unidad, valor anterior opcional, método, fecha de medición, fuente y permiso de publicación. Para MVP, resultados en Gutenberg evitan crear un CPT exclusivamente para reproducir tarjetas de una maqueta. Nunca usar columnas numeradas `metric_1`…`metric_4` para una lista que vaya a crecer.

Gestionar los registros de CPT/taxonomías y los grupos de campos desde ACF y exportar su configuración JSON/PHP compatible para versionado y backup. No volver a registrar los mismos CPT en otro plugin después de la transición. Este repositorio frontend no contiene el plugin custom actual: inspeccionarlo antes de desactivarlo y decidir dónde conservar los exports y cualquier lógica residual necesaria. WPGraphQL for ACF es el puente gratuito para exponer los grupos; activar Show in GraphQL y nombres únicos en CPT, taxonomías y grupos que corresponda.

## 5. GraphQL solo autenticado

**Completado:** transporte autenticado desplegado y restricción de WPGraphQL activa; acceso anónimo rechazado e identidad de servicio verificada en local, staging y producción. El visitante entra sin login: la autenticación ocurre entre Astro SSR y WordPress. Los pasos siguientes se conservan como contrato operativo y de recuperación, no como configuraciones todavía pendientes. Volver al Pages legacy anónimo requeriría coordinar también la restricción del CMS.

Flujo: navegador → Astro/Cloudflare → WPGraphQL con credencial de servicio → normalización de contenido publicado → HTML público. No añadir un proxy genérico que permita al visitante ejecutar queries arbitrarias con la credencial.

1. Crear un usuario de servicio con permisos mínimos para leer el contenido publicado requerido. No usar una cuenta administradora. Confirmar capabilities de CPT, ACF y taxonomías; la autenticación no implica autorización a todos los campos.
2. Preferir Application Password dedicada sobre HTTPS para servidor a servidor, si funciona con la instalación WPGraphQL real. Verificar soporte: la documentación advierte limitaciones; puede requerir integración/extensión compatible para reconocer `/graphql`. Alternativa: JWT con mecanismo de renovación documentado. No asumir que un password normal o una cookie del administrador servirá en Cloudflare.
3. Centralizar el transporte. Configuración propuesta: `WPGRAPHQL_URL` y secretos `WPGRAPHQL_USERNAME`, `WPGRAPHQL_APP_PASSWORD`. Variables del servidor, nunca `PUBLIC_*`. En Basic, codificar `usuario:application_password` y enviar Authorization; Base64 no cifra la credencial y requiere HTTPS.
4. Cargar secretos en runtime con el mecanismo compatible con Astro 5 + adapter Cloudflare 12: `astro:env/server`/`getSecret` o binding de runtime por contexto. No limitarse a `process.env` ni incrustar secretos con `import.meta.env` al compilar. Local: `.dev.vars` excluido de Git; Cloudflare: secretos del proyecto/entorno, separados para preview y producción. No enviar credenciales de producción a previews de PR no confiables.
5. Ejecutar POST, timeout acotado y clasificación de fallos: HTTP 401/403, `errors` GraphQL incluso con HTTP 200, respuesta inválida y caída de red. No registrar cabeceras, credenciales o errores crudos que puedan contener datos privados. No reintentar fallos de autenticación; como máximo reintento acotado de fallos transitorios idempotentes.
6. Cargar datos globales una vez por petición y pasarlos a Nav/Footer/CTA; eliminar peticiones desde componentes presentacionales. Caché por petición primero. Caché compartida de contenido publicado solo después de definir TTL, invalidación y separación estricta de borradores/previews; no almacenar indiscriminadamente respuestas autenticadas.
7. Consultar por slug/URI configurables en vez de IDs Home=19 y About=68. Tipar DTOs del frontend y normalizar nulos de ACF/imágenes/enlaces. Separar datos ausentes, 404 y caída de CMS: 404 real para contenido inexistente; respuesta 503 controlada para indisponibilidad, sin simular un archivo vacío ni exponer stack traces.
8. En staging validar primero lectura autenticada de Home, About, proyectos y artículos; activar restricción y confirmar que anónimo falla y usuario de servicio funciona. Desplegar frontend configurado y coordinar activación en producción con una ventana breve de verificación y rollback. Rotar/revocar credenciales sin reconstruir el frontend.

Este transporte todavía NO está implementado ni probado contra WordPress: requiere secretos y acceso al esquema. Las pruebas añadidas reproducen rechazo por autenticación, pero no demuestran que la instalación real acepte Application Passwords.

## 6. Hallazgos del código y SEO

Corregidos en esta revisión: fechas ISO restadas como números al ordenar proyectos; consulta redundante en cada PortfolioPreview; `<style>` dentro de otro `<style>` en el archivo de categorías.

Resuelto durante la ejecución: transporte común con errores tipados, timeout, validación de Home y 503 controlado; se eliminó el fallback de arrays vacíos ante fallo del CMS. Pendientes prioritarios del código legacy: Detalles inexistentes lanzan excepciones en lugar de devolver 404. El artículo consulta `featuredImage.node.sourceUrl` pero intenta renderizar `article.imageUrl`. Varias relaciones ACF/imágenes se desestructuran asumiendo que nunca son null. Las listas legacy aún no tienen paginación completa y `getAllArticleSlugs` se limita a 100; el nuevo cliente localizado sí pagina por cursores, pendiente de conectar a las rutas.

MainHead tiene viewport/descripciones duplicados, imágenes OG/Twitter vacías, idioma OG español frente a sitio inglés y referencias a manifest/iconos que no existen en `public`. About usa una descripción Lorem Ipsum. El enlace LinkedIn está rotulado YouTube. Corregirlos durante la base del rediseño; añadir imagen social real, metadata por contenido y canonical de producción. Añadir sitemap y robots con exclusión de páginas técnicas; JSON-LD Person, Article y breadcrumbs únicamente con datos reales. No eliminar URLs legacy durante el cambio visual.

## 7. GitHub y despliegue

Hallazgo inicial (ya atendido): no había `.github/workflows` previo. API de GitHub para `alexcu21/alexcuadra.dev`: `total_count: 0` y sin ejecuciones de Actions. Por tanto no existe un workflow remoto que pueda estar fallando. Esto no descarta compilación automática conectada directamente desde Cloudflare; no se ha inspeccionado su dashboard, configuración ni logs.

Añadido `.github/workflows/ci.yml`: PRs, push a main y ejecución manual; checkout, Node 22, instalación por lockfile, pruebas y `astro check && astro build`; permisos de lectura, cancelación de ejecuciones obsoletas y timeout. No usa CMS real ni secretos. Acciones oficiales verificadas en GitHub: checkout v7.0.1 y setup-node v7.1.0, con credenciales de checkout no persistentes. Se usan tags de versión; fijarlas a SHA y automatizar su actualización puede añadirse con la política del repositorio.

El build SSR actual no visita las páginas, así que compilar NO confirma que GraphQL esté accesible ni que el HTML renderice bien. No incluir secretos en builds de forks. Una futura prueba de integración autenticada debe ejecutarse solo en un entorno confiable y sin imprimir respuestas privadas.

Estado actual: Workers de producción y staging operativos, build por entorno y secretos runtime configurados; Pages anterior queda como respaldo con sus builds automáticos desactivados. La migración de adapter/runtime ya se ejecutó y validó. Queda ensayar recuperación y revisar visualmente el rediseño. No añadir un segundo despliegue de Actions si Cloudflare ya despliega por push. Configurar CI obligatorio en protección de main cuando el workflow esté publicado y haya ejecutado correctamente; la creación local no activa esta protección.

## 8. Pruebas y criterios de aceptación

### Pruebas incorporadas y evolución

`npm test` usa `node:test` sin dependencias nuevas ni CMS externo. Contratos de respuesta del cliente para Home, About, proyectos, categorías, artículo y slugs; variables GraphQL separadas; artículo ausente; errores GraphQL con HTTP 200; HTTP 403 y fallo de red. Pruebas de orden de fechas reales y colecciones vacías. Los mocks comprueban la lógica del cliente, no la existencia del esquema de WordPress. `npm run build` aporta validación Astro/TypeScript y compilación para Cloudflare. Baseline inicial: 14/14 pruebas pasaban; build correcto sin errores de tipos ni la anterior advertencia de CSS anidado. Persisten avisos de compatibilidad sharp/Cloudflare, Browserslist antiguo y sugerencias por símbolos sin uso. Ese párrafo describe la revisión inicial. Estado local vigente: 87 pruebas unitarias y build pasan; 13 comprobaciones adicionales del cliente contra WordPress aislado pasan. CI incluye también build:production. El laboratorio de seguridad conserva sus cuatro fallos documentados y no forma parte de CI normal.

### Durante la implementación

- Transporte: Authorization correcto solo en servidor, secretos ausentes, credencial inválida, timeout, errores HTTP/GraphQL, logs redactados y rotación. Prueba explícita que no retorna borradores ni datos privados con el usuario de servicio.
- Fuente editorial: modificar un texto, especialidad o metadato en WordPress debe reflejarse tras la invalidación/TTL definidos, sin editar ni reconstruir componentes; verificar ausencia de cifras de mockup y de fallbacks editoriales ficticios en producción.
- Modelo: nulos ACF, imágenes ausentes, fechas, relaciones, selección y orden de destacados, paginación por cursores, enlaces externos y variantes de formato.
- Rutas SSR: `/`, `/work/`, `/about/`, `/articles/`, un proyecto, una categoría y un artículo con fixtures; 404 de slug inválido, 503 de CMS caído. Verificar cuerpo y status, no solo HTTP 200: Astro puede comenzar una respuesta antes de fallar durante streaming.
- E2E con Playwright en la fase de componentes: navegación móvil/desktop, tema persistente, teclado, filtros/búsqueda/paginación, TOC, copiar enlace y ausencia de credenciales en HTML/bundles/red del navegador. Servidor de fixtures determinista local para no depender de WordPress en cada PR.
- Accesibilidad con axe + revisión manual de teclado, lector de pantalla, zoom y reduced motion. Capturas de regresión de cuatro plantillas en 375, 768 y 1440 px, con fuentes/imágenes estabilizadas.
- Rendimiento: medir producción/staging, no Astro dev. Objetivo LCP ≤2.5 s, CLS ≤0.1 e INP ≤200 ms cuando exista medición de campo. Lighthouse móvil ≥90 como referencia de laboratorio con entorno estable, sin publicarlo como métrica real de clientes.

No afirmar cobertura 100%, cero defectos o métricas de CI en UI a partir de estas pruebas. Añadir E2E/visuales cuando existan los nuevos componentes, para comprobar comportamiento real en lugar de snapshots del código actual que se reemplazará.

## 9. Fases y entregables

| Fase | Estado al 18 octubre | Entregable | Criterio de salida |
| --- | --- | --- | --- |
| 0. Base, esta revisión | Completada: revisión inicial entregada | Plan, contratos de pruebas, CI local y correcciones pequeñas verificadas | Tests y compilación pasan; sin cambios de WordPress o deploy |
| 0.1. Actualización y compatibilidad | Avanzada; revisión visual y rollback pendientes | Matriz de versiones verificadas, migraciones por lotes, lockfile reproducible y validación del runtime Cloudflare | Sin conflictos de peers/engines; pruebas, build y smoke SSR pasan; staging y rollback verificados |
| 1. Contenido, transición CPT y esquema | En curso: registro CPT listo; carga editorial/idiomas pendientes | Inspección del plugin custom y transición de sus CPT a ACF; guía asistida de campos/CPT e idiomas, inventario/export ACF, textos EN/ES y evidencias de desarrollo senior, calidad, automatización y DevOps; carga completa en content.alexcuadra.dev; tabla legacy→nuevo y usuario de servicio | Datos publicables completos en WordPress, sin cifras de mockup; queries validadas en staging; respaldos y rollback disponibles |
| 2. Datos y autenticación | En curso: auth lista; cliente EN/ES probado; rutas pendientes | Transporte servidor, DTOs por idioma, relaciones de traducción, rutas EN/ES, paginación, globales por petición, estados 404/503 y secretos runtime | Anónimo rechazado y SSR autenticado operativo; sin secretos en cliente/logs |
| 3. Sistema visual y Home | Pendiente | Tokens unificados, temas, navegación y componentes Astro responsive; Home con contenido real | Revisión visual desktop/móvil y pruebas de teclado; sin enlaces de maqueta |
| 4. Proyectos y LAB | Pendiente: modelo LAB aún sin crear | Archivo y template individual reutilizable para proyectos; CPT, archivo y detalle LAB con qa-training-lab como primera entrada prevista | Datos reales en CMS, variantes EN/ES, un proyecto y una entrada LAB completos; rutas antiguas conservadas |
| 5. Editorial y About | Pendiente; contrato y validadores de contacto preparados | Archivo, filtros/paginación, artículo, TOC, perfiles y formulario Turnstile con backend de correo | Contenido accesible; challenge/límites válidos y entrega de contacto comprobada |
| 6. QA y salida | Pendiente: existen pruebas de base, no aceptación final | E2E EN/ES, axe, capturas, SEO técnico/editorial bilingüe, revisión Cloudflare, autenticación y monitoreo | CI verde, staging aprobado, canonical/hreflang/sitemap validados, 404/503 correctos y rollback ensayado |
| 7. Opcional | Opcional, sin iniciar | Telemetría real o suscripción | Fuente de datos/proveedor definido y contenido suficiente |

Secuencia vigente: base actualizada y autenticación ya operativas; continuar modelado e idiomas antes de construir pantallas que requieran datos inexistentes. Mantener abiertos los criterios de revisión visual y recuperación de la fase 0.1 hasta verificarlos. Aplicar migración aditiva: crear campos, poblar y validar, desplegar queries compatibles y retirar legacy en una entrega posterior. Backup de base de datos y configuración antes de cambios; rollback del frontend a último deploy compatible y restauración del esquema si fuera necesario.

## 10. Decisiones abiertas

Pendientes: evidencia de desarrollo web senior, calidad, automatización y DevOps; disponibilidad y CTA principal; mantener tema claro; permisos para mostrar resultados de clientes; contenido y estado real de qa-training-lab.

Resuelto: Workers con producción y staging, autenticación por Application Password de usuario de servicio y GraphQL restringido; Articles son referencias externas/menciones y Posts son el blog propio; ACF gratuito mantiene el registro de los CPT; Polylang/conector probados y activos. No volver a solicitar estas decisiones ya confirmadas.

Los documentos resultantes de planificación, seguimiento y guías se guardan en `docs/`. Los documentos se versionan en Git; las propuestas de referencia bajo `docs/design-proposal/` permanecen locales. Los documentos nuevos también deben guardarse en `docs/`. README.md permanece en la raíz como entrada del proyecto.

## Referencias oficiales consultadas

- [WPGraphQL: autenticación y problemas comunes](https://www.wpgraphql.com/docs/common-issues)
- [WPGraphQL: seguridad](https://www.wpgraphql.com/docs/security)
- [Astro 5: Cloudflare y secretos de runtime](https://v5.docs.astro.build/en/guides/integrations-guide/cloudflare/)
- [ACF: integración con WPGraphQL y CPT](https://www.advancedcustomfields.com/blog/wpgraphql/)
- [ACF: Repeater y funciones PRO](https://www.advancedcustomfields.com/resources/how-to-use-the-repeater-field/)

## 11. Seguridad: requisitos del rediseño

Esta sección es parte del alcance y de los criterios de salida. Autenticación servidor, restricción de WPGraphQL, controles de contenido publicado, errores/logs redactados y protecciones de CI ya están implementados dentro del alcance del estado inicial. Sanitización básica del HTML editorial ya implementada localmente; CSP/cabeceras completas, controles del nuevo diseño y aceptación final siguen pendientes; la checklist de estado distingue ambos grupos. La revisión del código identifica superficies de riesgo; no demuestra que el sitio haya sufrido un ataque ni sustituye una auditoría del servidor de WordPress.

### Prioridad y responsables

| Prioridad | Medida | Dónde | Evidencia para cerrar |
| --- | --- | --- | --- |
| P0, antes de restringir GraphQL | Autenticación de servicio, permisos mínimos, secretos de runtime y pruebas de lectura de publicados | Astro + WordPress + Cloudflare | Anónimo denegado; servicio lee solo el contenido necesario; revocar la credencial impide el acceso |
| P0, antes de publicar rediseño | Sanitización del HTML y validación de URLs del CMS | Capa de normalización Astro | Payloads XSS bloqueados sin romper bloques editoriales legítimos |
| P0 | Evitar filtrado de secretos, borradores y respuestas privadas | Logs, HTML, bundles y cachés | Pruebas negativas y revisión de artefactos de producción |
| P1, antes de producción | Cabeceras de seguridad y CSP compatible con scripts/estilos de Astro | Respuesta SSR y assets Cloudflare | Pruebas sobre respuestas desplegadas, incluidas 404/503 |
| P1 | Límites de consultas, timeouts y protección ante abuso | WPGraphQL, transporte SSR y hosting del CMS | Consultas reales funcionan; solicitudes excesivas/profundas se rechazan de forma acotada |
| P1 | Actualizaciones revisadas y cadena de suministro | npm, Actions y plugins WP | Inventario de avisos relevantes, correcciones verificadas y CI verde |
| P1 | Recuperación y vigilancia | WordPress + Cloudflare | Restauración de backup ensayada, alertas de caída/auth y responsable definido |
| Condicional | CSRF, antispam y límites de escritura | Formularios/webhooks futuros | Implementar antes de habilitar cada endpoint de escritura |

### A. Secretos y aislamiento del CMS

- Antes de introducir secretos nuevos, ampliar exclusiones para `.dev.vars`, variantes `.dev.vars.*` y `.env.*`, dejando solo archivos de ejemplo sin valores reales. El `.gitignore` actual únicamente cubre `.env` y `.env.production`; no protege todas las variantes. Comprobar también archivos ya versionados: ignorar no retira secretos del historial. Si se descubre uno expuesto, revocar/rotar primero y tratar el historial por separado.
- No guardar contraseñas de WordPress, Application Passwords ni tokens en localStorage, cookies públicas, campos ACF, documentación, fixtures, reportes o commits. El valor `theme` actual puede seguir en localStorage porque no es una credencial.
- Usuario de servicio de lectura: comprobar que no tiene capabilities de publicar/editar, administrar usuarios/plugins o leer privados que el portfolio no necesita. Excluir borradores y privados explícitamente en consultas y normalización; no confiar solo en que la cuenta esté autenticada. Verificar con fixtures y registros privados de prueba.
- URLs del endpoint configuradas por servidor: aceptar exclusivamente HTTPS y el host del CMS previsto. Nunca construir el destino de una petición autenticada desde parámetros del visitante o un campo editorial. Impedir seguir redirecciones del endpoint hacia hosts no autorizados para evitar fuga de Authorization/SSRF. Si se añade un proxy de imágenes o fetch de medios, aplicar la misma política de destinos y límites.
- Separar credenciales de desarrollo, staging y producción, documentar propietario y revocación y probar rotación sin rebuild. No copiar secretos a previews no confiables. Las páginas de preview con contenido privado requieren acceso real y `Cache-Control: private, no-store`; `noindex` no controla el acceso.

### B. HTML de WordPress y enlaces

Actualmente About, proyectos y artículos insertan HTML del CMS con `set:html`; Astro no debe considerarse un sanitizador de ese contenido. El `set:html` de Icon usa SVG del catálogo local y tiene un origen distinto: no permitir que reciba SVG arbitrario del CMS.

Crear una sola función de sanitización del lado servidor, con una biblioteca mantenida compatible con el runtime Cloudflare y una política explícita. No crear un sanitizador con expresiones regulares. Mantener títulos y resúmenes como texto escapado normal. Permitir solo etiquetas/atributos editoriales necesarios; retirar scripts, atributos `on*`, `srcdoc`, URLs ejecutables, estilos arbitrarios, formularios y SVG/MathML no necesarios. Preservar enlaces, listas, citas, tablas, imágenes, encabezados y código usados realmente. Validar el HTML final después de cualquier transformación adicional.

Para embeds de YouTube/podcast, preferir componentes propios con proveedor e ID validados. Si se permiten iframes, limitar hosts HTTPS, permisos y sandbox a lo indispensable para cada proveedor; no aceptar HTML de embed libre. Para enlaces externos aceptar `https:` y, si el contenido legacy lo necesita, `http:`; tratar `mailto:` únicamente en componentes de contacto. Rechazar `javascript:`, `data:`, variantes codificadas, URLs protocol-relative inesperadas y destinos no válidos. Usar `rel="noopener"` explícito en enlaces con `target="_blank"`; añadir `noreferrer` cuando la política de privacidad lo requiera. Los enlaces inseguros deben omitirse o mostrarse como texto, sin sustituirlos por un enlace engañoso.

### C. Cabeceras HTTP y CSP

Configurar cabeceras en middleware/respuestas SSR de Astro. Cloudflare indica que `_headers` no aplica a respuestas generadas por Pages Functions/SSR; reservarlo para assets estáticos si corresponde. Comprobar el hosting real antes de elegir el mecanismo.

Base propuesta: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()` y protección de framing con CSP `frame-ancestors 'none'` y `X-Frame-Options: DENY` para este portfolio sin necesidad de ser embebido. HSTS solo en producción tras confirmar HTTPS estable; no activar `includeSubDomains` ni preload sin revisar todos los subdominios y su impacto.

Construir CSP a partir de los recursos reales: `default-src 'self'`, `object-src 'none'`, `base-uri 'none'`, `frame-ancestors 'none'`, `form-action 'self'`, y directivas específicas para scripts, estilos, fuentes, imágenes y embeds. Como GraphQL se consulta desde SSR, el navegador no necesita permiso `connect-src` hacia WordPress. Añadir únicamente orígenes de recursos públicos que la UI use. Para fuentes remotas actuales, permitir sus hosts concretos o autoalojarlas en el rediseño.

El script inline del tema y los estilos de Astro requieren hashes o nonces compatibles con esta versión; no imponer una CSP que rompa el sitio ni resolverlo con `script-src *`, `unsafe-eval` o un `unsafe-inline` permanente. Nonce único criptográfico por respuesta y coordinación con caché si se elige esa estrategia; hashes de build para contenido estático cuando resulte viable. Validar primero con `Content-Security-Policy-Report-Only` en staging y después aplicar en producción. No enviar reportes con URLs/datos privados a un tercero sin definir su tratamiento. Mantener política de desarrollo separada para HMR/WebSocket y barra de Astro.

### D. WordPress y disponibilidad

- Registrar versiones reales y mantener core, ACF, WPGraphQL, puente ACF y plugins de autenticación actualizados tras staging. Retirar plugins/temas sin uso; restringir administración a las personas necesarias y activar MFA para administradores donde la instalación lo permita. No confundir Application Password de servicio con login interactivo de un administrador.
- Verificar validación TLS entre Cloudflare, frontend y origen CMS. En el hosting de WordPress, desactivar display de errores en producción y proteger backups, dumps y logs contra descarga pública. No cambiar infraestructura sin revisar configuración y recuperación.
- Configurar límite de profundidad según las queries reales y la versión instalada; no asumir que el valor por defecto de la documentación esté activado en esta instalación. Acotar tamaño de página/aliases/batching y tiempo de ejecución donde el plugin/hosting lo permita. No bloquear introspección como sustituto de autorización; conservar herramientas de esquema solo para entornos/usuarios autorizados.
- Aplicar rate limiting al CMS y endpoints públicos costosos con las capacidades reales del proveedor. No asumir que el dominio del CMS está detrás de Cloudflare ni que el plan incluye todas sus reglas WAF. Definir umbrales a partir de tráfico legítimo y caché; una cabecera Origin/CORS no autentica una petición servidor a servidor.
- Mantener GraphQL de lectura para el portfolio: queries fijas conocidas por el servidor, sin aceptar documentos arbitrarios de clientes ni exponer un endpoint de ejecutar CI. Si el plugin permite limitar operaciones de escritura para el usuario de servicio, hacerlo y verificarlo además de los permisos de WordPress.
- Emitir errores genéricos al visitante y eventos operativos acotados sin datos sensibles; alertar sobre 401/403 persistentes, timeouts y 5xx. Servir degradación controlada cuando el CMS falle y evitar multiplicar consultas/reintentos por componente.

### E. Dependencias, CI y recuperación

Realizar `npm audit` sobre el lockfile y revisar si cada aviso afecta dependencias de producción o herramientas de desarrollo. No se ha ejecutado una auditoría de dependencias en esta revisión; no se declara el proyecto libre de vulnerabilidades. Priorizar avisos explotables y corregir versiones en una entrega comprobable; evitar `npm audit fix --force` automático que cambie el stack sin validación. Considerar las ramas/versiones soportadas de Astro y del adapter y su compatibilidad al planificar actualizaciones.

Fijar Actions a SHA revisados y añadir actualización automatizada con Dependabot; mantener permisos mínimos y nunca ejecutar código de PR no confiable con secretos mediante `pull_request_target`. La CI añadida usa `pull_request`, lectura de contenido y checkout sin credenciales persistentes, pero aún usa tags de versiones. Antes de producción configurar protección de main, revisión de cambios sensibles y gestión de secretos según las capacidades de GitHub disponibles. No introducir un escáner que suba código privado sin acordar servicio y acceso.

Backups separados de base de datos, medios y registros ACF/CPT; verificar restauración antes de migrar. Documentar rollback del frontend y del esquema, revocación de credenciales y responsable de incidentes. Un despliegue anterior no garantiza rollback si se han eliminado campos: mantener migraciones aditivas hasta completar la transición.

### F. Pruebas de seguridad que se incorporarán por fase

1. **Autenticación:** ausencia/expiración/revocación de credencial, permisos insuficientes, HTTP 200 con errores GraphQL, ausencia de borradores/privados y rechazo de operaciones anónimas. No usar secretos reales en fixtures.
2. **XSS y URLs:** scripts, eventos inline, URLs `javascript:`/`data:`/codificadas, SVG externo y embeds fuera de allowlist; confirmar que código, enlaces e imágenes legítimos se conservan. Prueba real en navegador de que payloads no se ejecutan.
3. **SSRF y secretos:** URL de CMS manipulada y redirección a otro host rechazadas; sin Authorization en HTML, bundles, mensajes al visitante, logs ni llamadas del navegador.
4. **Cabeceras:** CSP y cabeceras presentes en 200/404/503 sobre staging SSR; tema, estilos, imágenes y embeds autorizados funcionan; framing externo denegado. Validar separadamente assets estáticos y desarrollo.
5. **Caché y abuso:** preview/borrador nunca compartidos; paginación y profundidad acotadas; timeout/reintento limitados y 503 genérico. Pruebas de límites en staging controlado, sin prueba de carga sobre producción.
6. **Contacto confirmado como requisito:** formulario con Turnstile validado en servidor, texto plano/escaping, cuerpo y campos acotados, email validado, destinatario fijo, rate limit distribuido, origen permitido e idempotencia de envío. No es ya una función hipotética ni solo un mailto. Para webhooks, firma/HMAC, expiración y protección contra replay. Un formulario sin backend y prueba de entrega no se considera entregado. Contrato y guía: [CONTACT_FORM_PLAN.md](CONTACT_FORM_PLAN.md).

No se añaden hoy tests ficticios de controles que aún no existen: cada control se entrega con sus pruebas en la fase de implementación correspondiente. Los criterios P0 y P1 se incorporan a las fases 2 y 6 y bloquean la salida de producción hasta verificarse.

### Fuentes de seguridad

- [WPGraphQL: seguridad y límites de profundidad](https://www.wpgraphql.com/docs/security)
- [Cloudflare: cabeceras en respuestas estáticas y SSR](https://developers.cloudflare.com/pages/configuration/headers/)
- [OWASP: prevención de XSS y sanitización](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP: Content Security Policy](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)


## 12. Fase 0.1: actualización de paquetes y verificación de compatibilidad

Objetivo: establecer una base actualizada, soportada y reproducible antes de desarrollar el rediseño y la autenticación. Mantener las tecnologías del stack; una actualización mayor puede requerir migrar configuración o integración sin cambiar de framework. Base técnica actualizada y Workers validados tanto en staging como en producción. Pruebas y build pasan; la instalación limpia anterior reportó 0 avisos conocidos. Quedan revisión visual y ensayo documentado de rollback para cerrar todos los criterios de esta fase. El estado y las evidencias de ejecución se registran en EXECUTION_STATUS.md.

### Inventario inicial y restricciones históricas

La tabla conserva las versiones del lockfile inicial, anteriores a la migración; no describe el lockfile vigente. Base actual verificada: Astro 7.4.0, @astrojs/cloudflare 14.4.0, Tailwind 4.3.3 y Wrangler 4.151.1. CMS confirmado: WordPress 7.1.3, PHP 8.5.11, ACF gratuito 6.8.10, WPGraphQL 2.24.2, WPGraphQL for ACF 3.0.0, Polylang 3.8.11 y conector 0.7.1.

| Paquete / entorno | Base inicial histórica | Compatibilidad considerada para el objetivo |
| --- | --- | --- |
| Astro | 5.1.1 | Versión soportada, engines de Node, cambios mayores y APIs de contenido/SSR |
| @astrojs/cloudflare | 12.1.0 | Declara peer Astro `^5.0.0`; no conservar este adapter al actualizar Astro a otro major sin comprobar un adapter compatible |
| @astrojs/tailwind | 5.1.4 | Declara Astro 3/4/5 y Tailwind `^3.0.24`; no asumir compatibilidad con Astro 6/7 o Tailwind 4 |
| tailwindcss | 3.4.17 | Configuración JS, utilities, reset, colores, tipografía y compatibilidad del pipeline de estilos |
| @astrojs/check | 0.9.4 | Compatibilidad con Astro elegido y TypeScript; peer actual TypeScript `^5.0.0` |
| TypeScript | 5.7.2 | Requisitos de Astro/check y diagnósticos de archivos Astro, JS y tipos de runtime |
| graphql-request | 7.1.2 | Peer GraphQL `14 - 16`; resolución instalada de `graphql`, fetch, errores y autenticación en runtime de Workers |
| dotenv | 16.4.7 | Carga de configuración local frente a secretos de runtime; retirar solo si se demuestra redundancia |
| @types/node | 22.10.3 | Alinear con Node de herramientas; estos tipos no garantizan que APIs Node funcionen en Cloudflare |
| Node / npm | Local verificado: Node 22.23.2 / npm 10.9.8; CI usa Node 22 | Elegir versión soportada por la combinación objetivo y fijarla de forma coherente en local, CI y build del hosting |
| Dependencias transitivas | Vite, Wrangler, Miniflare/workerd, esbuild, etc. del lockfile | Revisar versiones que arrastran Astro y adapter, engines, avisos de seguridad y módulos nativos |
| WordPress / ACF / WPGraphQL / puente ACF / autenticación | Versiones del servidor todavía no inventariadas | Compatibilidad conjunta con PHP, esquema GraphQL, permisos y reconocimiento de credenciales |

### Procedimiento de selección

1. Guardar baseline: commit de partida, lockfile, salida de tests/check/build, rutas y capturas de referencia, configuración de despliegue y avisos existentes. Añadir smoke tests de las rutas actuales antes de cambios mayores; las 14 pruebas actuales no cubren todo el runtime ni la interfaz.
2. Consultar `npm outdated` y `npm audit`; revisar changelogs, guías oficiales de migración y metadatos `engines`/`peerDependencies` de las versiones candidatas con `npm view`. Registrar para cada paquete: instalada, candidata, soporte, dependencias compatibles, cambios requeridos y evidencia de pruebas. No seleccionar automáticamente `latest` para todos.
3. Definir un conjunto objetivo coherente de Astro + adapter Cloudflare + integración de Tailwind + checker + TypeScript + Node. Revisar también dependencias transitivas mediante `npm ls`; no usar `--force`, `--legacy-peer-deps` ni overrides para ocultar incompatibilidades. Si un paquete bloquea el conjunto, actualizar/migrar su integración o documentar un objetivo temporal soportado y una fecha de resolución.
4. Aplicar primero parches/minors compatibles y después majors en entregas separadas. Para saltar desde Astro 5, revisar las guías de 6 y 7 y validar cada frontera necesaria; no es obligatorio publicar cada versión intermedia. Actualizar Astro y sus integraciones compatibles como conjunto, no el core aislado. Evitar mezclar actualización mayor, nueva autenticación y rediseño en una sola entrega.
5. Mantener `package.json` y `package-lock.json` sincronizados. Tras cada lote, hacer instalación limpia con `npm ci` en un checkout de validación/CI, sin borrar el entorno de desarrollo en uso. Revisar el diff del lockfile y las dependencias nuevas. Una instalación sin error no demuestra compatibilidad funcional.

### Cambios que requieren atención expresa

- **Cloudflare:** las guías modernas describen cambios desde Astro 6 en adapter, desarrollo con workerd, acceso a bindings/secretos y despliegue. Confirmar Pages frente a Workers y el destino soportado por la versión candidata antes de aceptar el upgrade. Si requiere migrar el modo de hosting, preparar esa migración y su rollback conservando Cloudflare. La decisión no puede basarse solo en que `astro build` pase.
- **API de secretos:** la sección 5 describe la base Astro 5 actual. Después de escoger versiones objetivo, actualizarla al mecanismo de runtime de esas versiones y probar rotación. No copiar accesos legacy como `Astro.locals.runtime.env` a una versión que haya cambiado esa API.
- **Tailwind:** comprobar si la integración actual admite Astro objetivo. Si se pasa a Tailwind 4, planificar explícitamente su integración Vite, imports CSS, tokens/configuración, cambios de utilities/preflight y requisitos de navegador. Validar visualmente componentes actuales y ambos temas antes de construir el nuevo sistema. Si se conserva Tailwind 3 temporalmente, demostrar compatibilidad y soporte en lugar de mantener una integración incompatible.
- **Contenido Astro:** revisar `src/content/config.ts`, colecciones Markdown y APIs retiradas/deprecadas; el proyecto conserva contenido local aunque las páginas usen WordPress. No eliminarlo automáticamente sin comprobar usos y acordar su destino.
- **Pruebas y CI:** alinear versión Node, tipos y lockfile; comprobar compatibilidad de futuros Playwright/axe/sanitizador con Node y Workers donde proceda. Una biblioteca válida en Node puede usar DOM o módulos nativos no disponibles en Workers.
- **WordPress:** actualizar plugins/core en staging separado, con backup. Comparar esquema antes/después y ejecutar consultas reales de todos los campos usados. Confirmar que se mantiene ACF gratuito y no aparece dependencia de funciones PRO. No actualizar a la vez el CMS y el cliente sin saber qué lado provocó una regresión.

### Puertas de salida obligatorias

- `npm ci` reproducible y `npm ls` sin peers inválidos relevantes; engines compatibles documentados.
- `npm test`, `npm run check` y `npm run build` pasan; revisar avisos nuevos y diferencias respecto al baseline.
- Arranque de desarrollo y ejecución del build en el runtime Cloudflare correspondiente; smoke tests de Home, About, archivos, proyecto, categoría, artículo y errores 404/503. Verificar contenido renderizado además del status HTTP.
- Comparación visual de móvil/escritorio y temas; navegación, enlaces, imágenes, fuentes y scripts del tema sin regresiones ni errores de consola.
- Consultas al CMS de staging, autenticación cuando esté integrada y contrato de esquema compatibles; sin exponer secretos ni alterar caché privada.
- Revisar avisos de seguridad restantes por severidad y exposición; resolver bloqueantes antes de producción y registrar excepciones justificadas con seguimiento.
- Staging desplegado en el hosting real, CI verde y rollback de dependencias/configuración probado. El resultado debe ser una matriz con estado **verificado**, **bloqueado** o **pendiente**, nunca una afirmación general de compatibilidad basada solo en versiones.

### Entregables

Matriz final de compatibilidad con versiones exactas y enlaces a guías; `package.json` y lockfile actualizados; versión de Node declarada y CI alineada; configuración Astro/Tailwind/Cloudflare migrada cuando proceda; evidencias de pruebas y despliegue de staging; registro de avisos pendientes y procedimiento de rollback. La implementación de las fases 2–6 utilizará esa matriz como base.

Referencias oficiales para seleccionar el objetivo, verificadas al preparar esta fase:

- [Astro: actualización del core y sus integraciones](https://docs.astro.build/en/upgrade-astro/)
- [Migración a Astro 6](https://docs.astro.build/en/guides/upgrade-to/v6/)
- [Migración a Astro 7](https://docs.astro.build/en/guides/upgrade-to/v7/)
- [Cloudflare adapter: compatibilidad y migraciones de runtime](https://docs.astro.build/en/guides/integrations-guide/cloudflare/)

## 13. Propuesta bilingüe: inglés y español

### URLs y navegación recomendadas

Conservar inglés como idioma predeterminado sin prefijo y añadir `/es/` para español. Esto evita migrar todos los enlaces actuales a `/en/`. Una URL siempre representa un único idioma y devuelve HTML completo en SSR: no traducir mediante JavaScript ni variar el idioma de una misma URL según cookies o Accept-Language.

| Inglés existente | Español propuesto |
| --- | --- |
| `/` | `/es/` |
| `/about/` | `/es/sobre-mi/` |
| `/work/` | `/es/proyectos/` |
| `/work/[slug]` | `/es/proyectos/[slug-es]` |
| `/articles/` | `/es/articulos/` |
| `/articles/category/[slug]` | `/es/articulos/categoria/[slug-es]` |
| `/articles/category/[categorySlug]/[articleSlug]` | `/es/articulos/categoria/[categoria-es]/[articulo-es]` |
| `/blog/` y `/blog/[slug]` (blog por implementar) | `/es/blog/` y `/es/blog/[slug-es]` |
| `/#contact` | `/es/#contacto` |
| `/lab/` y `/lab/[slug]` | `/es/laboratorio/` y `/es/laboratorio/[slug-es]` |

Los slugs españoles de contenido se editan en WordPress y pueden diferir de los ingleses. Las bases `/es/proyectos/` y `/es/articulos/` las controla el router Astro: no requieren una función de traducción de bases CPT de pago en WordPress. No crear `/en/` como copia indexable; si llega a existir, redirigir de forma permanente al equivalente inglés correcto. Definir una política única de slash final y una sola versión HTTPS/host.

Selector accesible **English / Español**, visible en desktop y menú móvil, sin banderas como sustituto de idioma. Enlaza a la traducción equivalente mediante su relación real de WordPress, no cambiando un prefijo a ciegas. La URL elegida controla el idioma; puede recordarse la preferencia para sugerencias, sin redirecciones automáticas que impidan acceder a un idioma. Si no hay traducción, mostrar un aviso localizado y un enlace explícito a la portada/archivo del otro idioma; no aparentar que existe un equivalente.

### Gestión en content.alexcuadra.dev

**Opción recomendada, condicionada a compatibilidad:** Polylang gratuito para organizar pares EN/ES de páginas, proyectos, artículos, proyectos LAB, especialidades y taxonomías, más una extensión compatible para exponer idiomas y relaciones en WPGraphQL. WPGraphQL lista una extensión gratuita WPGraphQL Polylang; su existencia no garantiza que la versión actual sea compatible con las versiones elegidas. La fase 0.1 debe verificar mantenimiento, licencias, soporte, esquema, autenticación y comportamiento de ACF antes de instalarla en producción. No asumir sincronización avanzada de ACF, slugs idénticos entre traducciones o funciones PRO.

**Alternativa sin plugin multilingüe:** campos ACF gratuitos `content_locale` select (`en`, `es`) y `translation_key` text compartido por las dos versiones, con un registro independiente por idioma. Un pequeño plugin de sitio valida unicidad por tipo + translation_key + idioma y expone búsquedas/relaciones de traducción a WPGraphQL. Las taxonomías también requieren una estrategia de idioma y equivalencias. Es viable, pero implica mantener validación, filtros y experiencia editorial propios; no mezclar este mecanismo y Polylang como dos fuentes de verdad.

Tomar la decisión definitiva al terminar la matriz de compatibilidad. En ambas opciones, toda traducción vive en WordPress, incluidos navegación, CTA, etiquetas de filtros, mensajes editoriales, disponibilidad, alt de imágenes, títulos SEO y descripciones. Añadir una página de ajustes por idioma, relacionada con su equivalente; campos técnicos compartidos como email/URLs pueden copiarse de forma explícita, sin sobrescribir los traducibles. Datos numéricos/fuentes pueden mantenerse coherentes entre versiones; sus etiquetas y explicaciones se traducen. Separar alt localizado de la imagen compartida cuando la biblioteca de medios no permita resolverlo por idioma.

No crear parejas de campos `headline_en` y `headline_es` en cada registro como solución general: duplican el esquema y dificultan publicación, slugs y SEO independientes. Reutilizar el mismo grupo ACF en los registros de ambos idiomas. Relaciones de Home, proyectos destacados, especialidades y términos deben apuntar a registros del idioma correspondiente. Los nombres internos de campos y valores de select permanecen estables; se traducen sus etiquetas visibles, no las claves del contrato GraphQL.

### Comportamiento técnico y editorial

Configurar i18n de Astro con locales `en`/`es`, inglés predeterminado sin prefijo, según las APIs de la versión seleccionada en fase 0.1. Usar un mapa central de rutas localizadas, plantillas Astro compartidas y adaptadores de datos por idioma. Evitar duplicar el markup completo de cada página. DTO común: idioma, URI pública, equivalentes publicados, título, contenido, SEO y estado editorial. No asumir nombres exactos de campos GraphQL de una extensión antes de inspeccionar su esquema.

Incluir locale en consultas, filtros, paginación, caché y claves de deduplicación; normalizar relaciones antes de construir enlaces. Un CMS autenticado no debe hacer visibles borradores de traducciones. No servir texto inglés bajo una URL española como fallback indexable. Una traducción no publicada no entra al sitemap ni a hreflang; el acceso a una URL española inexistente devuelve 404 localizada. Una caída del CMS sigue siendo 503 y no se convierte en “traducción ausente”.

Publicar Home, About, contacto, navegación y archivos principales completos en ambos idiomas antes del lanzamiento bilingüe. Los casos/artículos pueden traducirse de forma progresiva; cada archivo lista únicamente contenido publicado de ese idioma. Un recurso externo puede estar en otro idioma: indicar idioma del original y traducir la descripción, sin fingir que el recurso externo está traducido. Traducción automática solo como borrador revisado por Alex; no publicación automática de resultados, testimonios o detalles técnicos.

Criterios: `lang="en"`/`lang="es"`, formatos de fechas/números con Intl, equivalencias correctas, ninguna mezcla accidental de CTA/menú, navegación por teclado y selector utilizable sin JS. Pruebas de pares completos, traducción ausente, borrador, slugs distintos, filtros/paginación por idioma y aislamiento de caché; verificar títulos largos en ambos idiomas y los dos temas.

## 14. Ejecución guiada: creación de campos y carga editorial

Estado: inventario, exports y sustitución de registros CPT completados; nuevos fields del rediseño y LAB pendientes. Las menciones de staging en este procedimiento se aplican al frontend y, cuando exista, al entorno de pruebas. Para el CMS Alex decidió trabajar en producción con backup reciente y acepta interrupciones; esa decisión ya está autorizada y no debe solicitarse de nuevo.

Durante la fase 1, el asistente debe guiar a Alex paso a paso en WordPress; no limitarse a entregar nombres de campos ni asumir que ya existen. Las instrucciones se basarán en la versión e interfaz reales de ACF gratuito y de los plugins instalados. No se han creado campos ni instalado plugins como parte de esta propuesta.

Secuencia de trabajo por bloques pequeños:

1. Revisar juntos CPT, grupos ACF y esquema actuales; exportar/resguardar antes de cambiar. Confirmar el mecanismo multilingüe de la sección 13 y crear idiomas/relaciones en staging.
2. Entregar una ficha por grupo: nombre visible, entidad y regla de ubicación, nombre GraphQL único y lista exacta de campos. Para cada campo indicar etiqueta, nombre interno, tipo gratuito, requerido/opcional, opciones, validación, formato de retorno, comportamiento de traducción y ejemplo claramente ficticio solo para pruebas.
3. Guiar la creación en ACF: dónde entrar, qué grupo/campo agregar, cómo configurar ubicación, exposición GraphQL y publicación. Si una opción exige PRO o no está en esa versión, adaptar el modelo a campos simples, relaciones, Gutenberg o CPT; no pedir una licencia de pago como requisito implícito.
4. Empezar por ajustes globales EN/ES y Home; luego especialidades, proyectos/casos, proyectos personales LAB, artículos/medios, SEO y About. Mantener campos antiguos hasta validar los nuevos. Crear registros de cada idioma y relacionarlos; no duplicar grupos ACF por idioma.
5. Tras cada bloque, comprobar con Alex el resultado y ejecutar una consulta autenticada del esquema real. Verificar campo retornado, tipo, nulos, formato de medios y relaciones por idioma. Si falta acceso al dashboard, seguir guiando con instrucciones precisas; pedir solo la información necesaria sin solicitar credenciales en el chat.
6. Cargar textos reales, archivos, evidencias y traducciones revisadas; preparar checklist de pendientes por página. Configurar SEO de cada idioma y comprobar el render en staging antes de declarar el bloque terminado.
7. Exportar y versionar configuración ACF/CPT sin datos secretos; actualizar diccionario de campos y contrato de queries. Probar migración en staging y conservar rollback antes de retirar campos legacy.

Ficha mínima que se entregará para cada campo: **grupo → ubicación → etiqueta → nombre interno → tipo → opciones/validación → retorno → traducción/compartido → exposición GraphQL → consulta de verificación**. La creación de un campo no se considera completada hasta verlo funcionar con datos reales en la vista correspondiente.

## 15. SEO ampliado: técnico, editorial y bilingüe

### Metadatos y contenido desde WordPress

Grupo SEO gratuito reutilizable para páginas y CPT: `seo_title` text, `seo_description` textarea, `social_image` image y `indexable` true/false con valor inicial definido. Puede ampliarse con título/descripción social si hay necesidad editorial real. No imponer longitudes como garantías de cómo Google mostrará el snippet; dar orientación y revisar calidad/claridad. Fallback editorial: título/extracto reales del mismo registro e idioma, nunca contenido de mockup ni texto del otro idioma.

Canonical se genera con el dominio público y la ruta normalizada; no usar la URI del CMS. Las excepciones de canonical deben ser administrativas y justificadas, por ejemplo republicación íntegra con un original externo; una mera mención resumida mantiene su canonical propio. No exponer edición libre de URLs canónicas a campos sin validación. Si ya existe plugin SEO, inventariarlo antes de agregar grupos duplicados y escoger una única fuente compatible con WPGraphQL y el modelo bilingüe.

Títulos/descripciones únicos por página e idioma, encabezados semánticos, enlaces descriptivos y contenido original que explique rol, decisiones y resultados verificables en desarrollo web, calidad, automatización y DevOps. Plan editorial por intención de búsqueda en cada idioma, sin traducción literal forzada ni repetición de palabras clave. Relacionar especialidades, casos de estudio y artículos del idioma correspondiente. No crear archivos vacíos o páginas casi idénticas solo para aumentar URLs.

### Canonical, hreflang e indexación

Cada traducción publicada tiene canonical autorreferente. No canonicalizar español hacia inglés. Emitir alternates absolutos `hreflang="en"` y `hreflang="es"` solo para equivalentes publicados, con reciprocidad e inclusión de la propia página; usar `x-default` hacia el equivalente inglés cuando exista y sea el destino predeterminado. No enlazar cada artículo español a la Home inglesa como si fuera su traducción. Si solo existe un idioma, no inventar un par.

Sitemap XML generado desde el inventario real del CMS: rutas SSR no quedan cubiertas automáticamente por conocer los archivos Astro. Recorrer conexiones con paginación, excluir borradores, ajustes técnicos, páginas noindex, URLs inexistentes y parámetros de búsqueda; `lastmod` procede de cambios reales del contenido. Si incluye alternates, mantenerlos coherentes con el HTML y su reciprocidad. Validar sitemaps de ambos idiomas sin generar contenido vacío si el CMS falla; servir un error temporal en lugar de sustituir por un sitemap incompleto.

Robots permite recursos necesarios y referencia el sitemap. Previews/staging requieren control de acceso y noindex; robots no protege datos privados. No bloquear por robots páginas en las que el buscador deba leer noindex. En búsquedas/filtros, definir una política antes de lanzar: búsquedas internas noindex; solo archivos curados con contenido útil son indexables. Paginas de paginación accesibles por enlaces reales, canonical propio cuando representan conjuntos distintos y navegación por idioma sin ciclos ni cadenas de redirecciones.

Preservar URLs inglesas y crear mapa 301 para cambios inevitables; probar artículos, categorías y enlaces entrantes. 404 genuinas para contenido eliminado/no existente y 503 temporales para fallos del CMS, evitando soft-404 y respuestas 200 vacías.

### Datos estructurados, medios y rendimiento

JSON-LD Person/ProfilePage donde corresponda, BlogPosting/Article para publicaciones propias del tipo post y BreadcrumbList para páginas jerárquicas. Las fichas locales de articles describen referencias externas: no atribuir a Alex la autoría de una mención de terceros ni representar un resumen como si alojara el artículo completo. Usar IDs estables y `inLanguage`; datos deben coincidir con contenido visible y evidencia real. No añadir ratings, premios, empleos o cifras no verificadas. La validez del schema no garantiza resultados enriquecidos. Revisar XSS al serializar JSON-LD desde campos editoriales.

Imágenes con alt localizado, dimensiones, formatos eficientes y proporción reservada; imagen principal priorizada cuando sea LCP y lazy loading para imágenes bajo el pliegue. Imagen social pública y válida por contenido/idioma, sin bloquear rastreadores con autenticación del endpoint GraphQL. Fuentes optimizadas, poco JS y HTML SSR con el contenido principal ya presente. Mantener las metas de Core Web Vitals de la sección 8 como objetivos medibles.

### Verificación y seguimiento

Añadir pruebas de head renderizado por idioma: canonical, lang, títulos/descripciones, robots, hreflang recíproco y ausencia de duplicados/valores vacíos; validar JSON-LD, URLs de sitemap, paginación, redirecciones y exclusión de contenido privado. Comprobar respuestas SSR completas y enlaces sin depender de JS.

En staging, crawler controlado y revisión manual de los tipos de página EN/ES; después de publicar, verificar Search Console del dominio, enviar sitemap y revisar indexación, errores, consultas por idioma y Core Web Vitals. Registrar baseline previo y comparar tras el cambio; no prometer mejoras de ranking. La configuración de Search Console se hará con acceso autorizado durante ejecución.

Fuentes oficiales para la propuesta bilingüe y SEO:

- [Astro: rutas internacionales](https://docs.astro.build/en/guides/internationalization/)
- [Polylang: páginas, CPT y traducciones](https://polylang.pro/documentation/support/getting-started/translating-pages-posts-categories-and-tags/)
- [WPGraphQL: extensión Polylang gratuita; compatibilidad por validar](https://www.wpgraphql.com/extenstion-plugins/wpgraphql-polylang)
- [Polylang: funciones de traducción de bases de URL de pago](https://polylang.pro/documentation/support/guides/translating-urls-slugs/)
- [Google: gestión de sitios multilingües](https://developers.google.com/search/docs/specialty/international/managing-multi-regional-sites)
- [Google: equivalentes localizados y hreflang](https://developers.google.com/search/docs/specialty/international/localized-versions)

## 16. Transición de CPT del plugin custom a ACF

**Registro y lectura publicada completados:** Alex sustituyó el proveedor conservando project/articles y nombres GraphQL; export final revisado y baseline de siete proyectos/diez referencias validado sin copiar entradas. ACF conserva campos/relaciones consultados y el plugin original permanece desactivado como respaldo. No se declara auditado todo el contenido no publicado ni toda la edición administrativa. La investigación y rutas siguientes describen el procedimiento acordado y alternativas, no una migración todavía por ejecutar. Alex autorizó trabajar directamente en el CMS de producción con backup; no se exige crear staging WordPress para repetir este paso. El staging del frontend sí está disponible.

### Aclaración y primera hipótesis que investigar

Los CPT actuales de proyectos y artículos se registran mediante un plugin personalizado de WordPress. El objetivo acordado es sustituir ese registro por la interfaz de CPT de ACF gratuito, para facilitar mantenimiento. Los nombres GraphQL `projects` y `articles` usados en el frontend no prueban cuáles son las claves internas `post_type` de WordPress: hay que obtenerlas del código del plugin y del servidor.

**Puede que no sea necesaria una migración de contenido.** Si ACF registra exactamente las mismas claves internas, se trataría de cambiar quién registra los CPT; los registros existentes pueden continuar asociados al mismo tipo. La hipótesis inicial se verificó para el registro y lectura publicada de este proyecto; el alcance de cierre se detalla arriba. Cambiar una etiqueta visible o el proveedor de registro no obliga por sí mismo a copiar las entradas.

La importación documentada por ACF desde CPT UI no implica una importación automática desde cualquier plugin custom. Inspeccionar su código primero y, si no existe importador apropiado, recrear las definiciones equivalentes en ACF siguiendo la guía asistida de la sección 14.

### Investigación obligatoria antes de cambiar

Obtener el plugin custom, su versión y una copia de staging. Inventariar `register_post_type`, taxonomías, hooks de activación/desinstalación, permisos, meta/campos, registros de GraphQL, resolvers, filtros, shortcodes y automatizaciones. La desactivación podría retirar funciones adicionales; desinstalar puede incluso ejecutar limpieza de datos. No eliminar ni desinstalar el plugin durante la exploración.

Por cada CPT registrar: clave interna, etiquetas, `supports`, `public`/`publicly_queryable`, `show_ui`, `show_in_rest`, jerarquía, archivo/rewrite, capabilities/map_meta_cap, taxonomías y nombres/visibilidad GraphQL. Comparar grupos ACF y sus field keys, reglas de ubicación y metadatos existentes; no recrear field keys innecesariamente. Si el plugin almacena información fuera de posts/postmeta o calcula campos mediante resolvers, documentar y preservar esa lógica expresamente.

### Ruta A recomendada: conservar las claves internas

1. Exportar backup de base de datos, medios, configuración y plugin; registrar IDs, slugs, estados, autores, fechas, contenidos, metadatos, términos, relaciones y conteos por CPT/idioma. Guardar consultas GraphQL y rutas frontend de referencia.
2. Preparar en staging las definiciones ACF equivalentes. Coordinar un único proveedor activo por clave: no mantener registro simultáneo desde ACF y plugin para el mismo CPT. Preparar el cambio controlado sin dejar visible al público un estado intermedio.
3. Desactivar únicamente el registro sustituido cuando sea posible; si hay que desactivar el plugin completo, separar antes sus otras funciones necesarias. Activar el registro ACF con las mismas claves, taxonomías y permisos. Conservar o reproducir los nombres GraphQL y cualquier hook requerido para exponerlos; ACF UI no garantiza reemplazar todas las extensiones custom.
4. Verificar que las entradas existentes aparecen sin copiar contenido: mismos IDs, metadatos, campos, imágenes, relaciones, términos y estados. Refrescar reglas de permalinks una vez si cambia/lo requiere el registro, no en cada petición. Revisar esquema GraphQL y consultas autenticadas.
5. Probar archivos/detalles, SEO, permisos, editor y creación/edición de un registro nuevo. Añadir idiomas una vez estabilizada esta transición para no mezclar dos causas de cambios de datos.
6. Repetir procedimiento validado en producción con backup y rollback. Si falla, restaurar el proveedor anterior de registro y desactivar el nuevo, sin borrar posts ni instalar rutinas de limpieza. No eliminar el plugin hasta confirmar que ninguna lógica residual lo necesita.

### Ruta B excepcional: cambiar la clave del CPT

Solo si se demuestra necesario, diseñar una migración idempotente y ensayada, con dry-run, mapeo origen/destino y verificación de todos los IDs y referencias. Preferir conservar IDs al reasignar tipo cuando sea compatible; si hay que copiar, mapear IDs, relaciones, medios, términos, traducciones, revisiones y metadatos, evitando duplicados. Usar APIs/herramientas WordPress con alcance explícito; no ejecutar reemplazos SQL globales ni asumir que una exportación/importación preserva todo.

Actualizar ubicación de grupos ACF, permisos, esquema/resolvers GraphQL, queries, rutas, sitemap, canonical y redirecciones que cambien. No renombrar al mismo tiempo field keys o nombres GraphQL sin una razón y un mapeo independientes. Comparar conteos y valores antes/después y validar cada relación, no solo el número de posts.

### Evidencia de cierre y alcance

Entregable: informe que determine **cambio de registro sin mover contenido** o **migración de contenido necesaria**, con motivo, claves exactas, configuración ACF exportada, lógica custom que se conserva, procedimiento repetible y rollback. Pruebas de equivalencia de datos, lectura GraphQL con permisos mínimos, edición de campos, URLs anteriores y bilingüismo posterior.

`lab_project` es un CPT nuevo creado directamente en ACF: no requiere migrar entradas legacy. Su primera entrada se redactará con datos reales de qa-training-lab. El template de la carpeta Christianity Today sirve para todas las fichas individuales de proyectos; no crea un CPT ni un template exclusivo de ese cliente.

Fuentes oficiales para validar el procedimiento:

- [ACF: registro de CPT y clave del tipo](https://www.advancedcustomfields.com/resources/registering-a-custom-post-type/)
- [ACF: post types, taxonomías e importación desde CPT UI](https://www.advancedcustomfields.com/resources/post-types-and-taxonomies/)

## 17. Compatibilidad editorial con Gutenberg y estilos básicos

**Base local completada:** componente compartido WordPressContent, sanitización con allowlist mediante sanitize-html 2.18.1 y CSS editorial acotado para headings, listas, imágenes/captions, columnas, citas, tablas, galería core y código. Conectado a los tres puntos anteriores de HTML del CMS. Decisión inicial aplicada: CSS propio (alternativa A), sin importar todo el CSS de WordPress ni usar funciones ACF PRO. Las columnas apilan en móvil y el contenido editorial H1 pasa a H2. Políticas y límites en [WORDPRESS_CONTENT.md](WORDPRESS_CONTENT.md). 98 pruebas pasan; build y smoke SSR local con el renderer compartido correctos. No se declaran terminados los bloques especiales, anchors/TOC, revisión visual móvil/escritorio o despliegue.

Evaluar e implementar soporte de contenido creado con los bloques nativos de WordPress en artículos, casos de estudio, About y LAB. No exigir convertir headings, imágenes, columnas o citas en campos ACF: el cuerpo editorial vive en Gutenberg; ACF conserva los datos estructurados del template.

Recibir HTML renderizado por WordPress vía GraphQL no importa automáticamente las hojas de estilo del tema ni los estilos globales de Gutenberg. Inventariar bloques y HTML reales del CMS antes de elegir el conjunto CSS; no cargar todo el tema de WordPress ni scripts del editor en el frontend Astro.

| Bloque / formato | Soporte previsto |
| --- | --- |
| Párrafos, negrita, cursiva, enlaces y listas | Semántica y espaciado coherentes con Prose; enlaces seguros y foco visible |
| Encabezados | H2–H6 dentro del cuerpo, con escala responsive y anchors/TOC; H1 reservado al título de página. Detectar y corregir H1 editoriales o niveles incorrectos antes de publicar |
| Imágenes y figuras | Imagen, alt localizado, caption, dimensiones y alineación; tamaños responsive y límites del ancho de lectura |
| Citas y pullquotes | Blockquote, cite y variantes permitidas, con contraste y tipografía de ambos temas |
| Columnas y grupos | Reconocer columnas, bloques anidados y alineaciones; apilar en móvil según el comportamiento editorial permitido, sin desbordamiento |
| Tablas | Encabezados semánticos, captions y scroll horizontal contenido en móvil |
| Código y preformateado | Preservar espacios, escapes y scroll; resaltado compatible, sin ejecutar contenido |
| Separadores, botones y media-text | Evaluar según inventario real; variantes acotadas y enlaces validados |

Comparar tres alternativas en staging: (A) CSS propio y acotado para HTML semántico y clases core, (B) importar solo estilos core de los bloques utilizados y adaptar tokens, (C) renderizado por bloques para casos que necesiten componentes específicos. Recomendación inicial: A para tipografía y estructura básica, B solo donde reduzca mantenimiento sin conflictos con Tailwind; C para embeds/interacciones que no puedan resolverse de forma segura con HTML. Documentar la elección y el coste de actualización de WordPress.

Crear una capa `.wp-content`/Prose aislada y una allowlist de clases estructurales necesarias, como `wp-block-columns`, `wp-block-column`, `wp-block-image`, `wp-block-quote`, clases de alineación y layouts soportados. Verificar qué selectores/tokens de layout genera la versión real; CSS core por sí solo puede no incluir estilos dinámicos del tema. Mapear tamaños y colores permitidos a tokens del diseño y valorar alinear presets del editor mediante configuración versionada de WordPress, compatible con ACF gratuito.

Valorar ajustes editoriales básicos de alineación, tamaños, proporciones de columnas y espaciados. No prometer copia pixel-perfect del tema del CMS ni permitir cualquier CSS inline. Si Gutenberg necesita estilos inline, admitir únicamente propiedades y valores validados (por ejemplo proporciones de columnas acotadas), o convertirlos a clases controladas, coordinándolo con sanitización y CSP. Mantener prohibición de scripts, eventos, HTML peligroso y URLs ejecutables. No eliminar indiscriminadamente todas las clases/estilos y romper columnas legítimas, ni permitir `style` completo solo para conservarlas.

Durante la guía de WordPress, crear con Alex una página de prueba de bloques que contenga todos los formatos acordados; explicar qué opciones son compatibles. Comprobar HTML que entrega GraphQL, sanitización, render SSR y paridad visual EN/ES, móvil/escritorio y temas claro/oscuro. Pruebas de regresión: columnas anidadas, imágenes con caption, textos largos, tablas, citas, código y payloads XSS. Confirmar que la sanitización conserva el formato aprobado y elimina el peligroso. Las actualizaciones de WordPress/core block CSS requieren repetir estas comprobaciones.

Esta valoración se hace en fase 1 al inventariar contenido; la implementación se comparte entre fase 3 (Prose y estilos) y fases 4–5 (templates), con aceptación en fase 6. No está implementada todavía.

Referencia: [WordPress: arquitectura de estilos del editor y frontend](https://developer.wordpress.org/block-editor/explanations/architecture/styles/).

## 18. Aclaración editorial confirmada: Articles frente a Posts

Alex confirmó que articles es exclusivamente el archivo de referencias a otras webs/plataformas: publicaciones suyas alojadas fuera del sitio y menciones de terceros. post es el blog nativo del portfolio. El hecho de que el mockup no los diferencie no autoriza fusionarlos ni migrar registros entre ambos.

- Fase 1: conservar ambos tipos, inventariar por separado y preparar fields de fuente/formato/relación para articles. No usar quote o campos de referencias como requisito del blog.
- Fase 2: clientes/DTO independientes para posts y articles, con controles de publicación, idioma, paginación y URLs seguras. Conservar las rutas actuales de referencias; implementar las rutas propuestas del blog sin renombrar las existentes.
- Fase 5: archivo y lectura Blog para post; archivo y ficha contextual de publicaciones externas/menciones para articles. Las tarjetas externas indican plataforma, si Alex es autor o mencionado y una acción explícita para abrir la fuente. La ficha local puede presentar resumen y contexto; no borrar de inmediato el contenido legacy ni redirigir sus URLs automáticamente.
- Bilingüismo: traducir la ficha/resumen local de una referencia sin fingir que la fuente externa tiene traducción. Registrar el idioma de origen cuando se necesite y conservar su URL real.
- SEO: canonical propio para fichas locales con contexto original, salvo excepciones justificadas; autoría/JSON-LD de acuerdo con el contenido alojado y la fuente. No usar el autor de la ficha como autor automático de la publicación externa.
- QA: demostrar que /blog consume post y /articles consume articles, que no hay registros cruzados por usar categorías compartidas, que funcionan ambos templates y enlaces externos, y que se conservan URLs legacy. Los filtros de formato no sustituyen la separación de modelos.

Este cambio actualiza el plan; todavía no implementa el blog, los nuevos fields ni el rediseño editorial.

## 19. Formulario de contacto con Turnstile

Requisito del MVP confirmado por Alex. Alcance, seguridad, fields editoriales, decisiones de envío y pasos guiados en [CONTACT_FORM_PLAN.md](CONTACT_FORM_PLAN.md). Implementar en fase 5 y validar integralmente en fase 6; no activar un formulario que solo muestre el widget o acepte mensajes sin entrega. Los textos EN/ES se cargarán en el CMS con ACF gratuito, los secretos/configuración técnica permanecen en Workers. El backend no consulta WordPress en cada POST.

Avance de contacto: campos ACF importados y completos EN, esquema GraphQL verificado; interfaz local conectada a CMS y Turnstile, aún desactivada. Pruebas unitarias 115 y build correctos. Pendientes validación de navegador, límites globales/idempotencia, despliegue y entrega.

## 20. Última etapa: mejorar el conector Astro–WordPress

Añadida por Alex el20 octubre2026 y **aplazada al final del plan**, después de los incrementos editoriales/MCP, aceptación y entregas pendientes. Esta etapa corresponde a la capa de datos del portfolio (`src/data/client.js`, solicitudes CMS y WPGraphQL), independiente del adaptador MCP de herramientas editoriales. No adelantar su implementación por un continúa genérico mientras queden etapas anteriores.

Primero medir consultas, latencia, errores y consumo real sin exponer credenciales. Después evaluar mejoras de caché/invalidation, deduplicación, paginación, tiempos de espera, manejo de indisponibilidad y respuestas429, manteniendo autenticación de servidor, visibilidad pública e idioma exacto. Decidir cambios a partir de esa evidencia, sin introducir reintentos que aumenten la carga del CMS. Conservar URLs, IDs, field keys y relaciones Polylang; los secretos permanecen fuera del cliente.

Entrega por incrementos: diagnóstico y contrato de datos → implementación local con fixtures y regresiones de SSR/seguridad/bilingüismo → revisión en staging con solicitudes espaciadas y rollback → promoción expresamente autorizada. Aceptación: paridad de contenido/rutas/SEO EN/ES, ningún contenido privado expuesto, menor carga o latencia medible y comportamiento claro ante fallos. Esta anotación no cambia el conector ni despliega nada; contacto sigue desactivado por decisión vigente de Alex.
