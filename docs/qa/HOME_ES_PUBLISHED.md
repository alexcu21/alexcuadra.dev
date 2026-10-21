# Home española — 19 octubre 2026

Versión final staging: `51a87ffc-3cc2-4d89-b1c4-e401c1ecdab5`. Navegador final a397 px: sin desbordamiento; solo Inicio tiene aria-current=page, Sobre mí enlaza ES y Proyectos/Artículos mantienen destinos EN identificados. Escape devuelve al contenido con el menú cerrado.

- CMS compartido: Inicio ID193 publicada mediante traducción de Home19. Idioma ES, relación a EN19 y campos públicos verificados por GraphQL. Presentación, tres roles y seis campos de habilidades traducidos del original; nombre, foto, redes y correo heredados sin publicar datos nuevos en el informe.
- Home_page ID12 (`group_676ca62b35b45`): preservada regla PostType=Page AND Page=19 y añadido grupo OR Page=193. No se amplió Contact form a la página española ni se cambió About/categorías/proyectos.
- HomePage compartido por `/` y `/es/`; lee fuentes reales por relación Polylang desde19. Ausencia de traducción devuelve404/no-store/noindex. Canonical de producción por ruta, lang y alternates recíprocos solo con ambas páginas públicas.
- Skills recibe campos del idioma de la portada. Nav/Footer/CTA usan lector Home EN compartido con el resto de la petición para datos comunes y deduplicación. Menú ES y marca enlazan `/es/` cuando su fuente es pública. Inicio no queda activo en todas las rutas ES.
- Se preservan proyectos y categorías existentes: fichas EN identificadas al visitante, nombres y URLs de referencias sin alterar su clasificación. No se promete contenido ES en fichas no traducidas.
- 134 pruebas unitarias y build correctos. Smoke focalizado `--require-cms-auth --check-seo --check-csp --check-home-es --check-contact-disabled --home-only` pasa: dos portadas, identidad de servicio, SEO, campos ES, enlaces reales, alternates, CSP y ausencia de formulario. No equivale a repetir la matriz completa del sitio.
- Navegador móvil comprueba título, presentación, foto, habilidades, proyectos, categorías y CTA ES. Roles permiten salto de línea para textos españoles largos.
- Publicación CMS compartida realizada; sin despliegue Worker producción y sin envíos. El formulario sigue false y piloto de correo aplazado.

Pendiente: traducciones/templates de colecciones, decisiones editoriales de referencias y rediseño visual final conforme al plan.

![Home ES](home-es-staging.png)
