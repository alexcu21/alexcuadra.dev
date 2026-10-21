# Bilingüismo EN/ES: implementación guiada

## Decisión y compatibilidad

Ruta propuesta: Polylang gratuito para idiomas/relaciones y WPGraphQL Polylang de Valu Digital como candidato de integración. No usar ACF PRO ni una segunda fuente de idioma basada en fields paralelos. Alex autorizó operar en producción aceptando interrupciones; conservar backups y aplicar cambios por pasos.

| Componente | Verificación del 18 octubre 2026 |
| --- | --- |
| WordPress | 7.1.3, confirmado por Alex |
| PHP | 8.5.11, confirmado por Alex |
| ACF / WPGraphQL for ACF | 6.8.10 gratuito / 3.0.0, confirmados |
| WPGraphQL | 2.24.2, reportado por Alex anteriormente |
| Polylang instalado | 3.8.11, confirmado por Alex y probado en el laboratorio local |
| Conector Valu Digital | Última release encontrada v0.7.1, 6 mayo 2025; repo no archivado, última actividad push observada 6 mayo 2025 |

WordPress/PHP superan mínimos publicados de Polylang. Eso no prueba compatibilidad de toda la combinación ni del conector con PHP 8.5, ACF 6.8.10 y WPGraphQL for ACF 3.0.0. La release v0.7.1 del conector corrige compatibilidad con WPGraphQL de su época, no certifica versiones de 2026. Validar mediante piloto; no instalar master ni un fork al azar. Sin soporte verificable, evaluar un puente pequeño versionado sobre la API de Polylang o la alternativa de campos descrita en el plan antes de continuar.

## Contrato editorial y rutas

- Inglés en rutas actuales, español bajo /es/. Sin redirección automática por navegador.
- post: blog propio; articles: referencias externas/menciones; project: proyectos; lab_project: nuevo más adelante. Cada tipo mantiene su identidad en ambos idiomas.
- Registros separados por idioma, relacionados mediante Polylang. No campos headline_en/headline_es ni copia ficticia del contenido EN cuando falte ES.
- La ficha traducida de una referencia externa no cambia el idioma ni la autoría de su fuente. La URL externa se conserva y se identifica como tal.
- Reutilizar los mismos grupos ACF. No asumir las herramientas de sincronización por field de las versiones PRO. Desactivar sincronización global de Custom Fields para evitar sobrescribir textos entre idiomas; rellenar y comprobar cada traducción.
- Home y About actuales tienen reglas por ID 19/68. Antes de traducirlos, ampliar sus reglas mediante OR para incluir los IDs españoles reales, sin cambiar field keys ni exponer sus grupos a todas las páginas. No publicar Home ES sin sus fields.

## Secuencia de ejecución

### 1. Preparar Polylang

WP → Plugins → Añadir nuevo → buscar Polylang, autor WP SYNTEX. Instalar versión gratuita del directorio oficial y registrar la versión. No instalar Pro ni extensiones de terceros en este paso. Al activarlo se abre un asistente: revisar la pantalla de idiomas antes de aplicar asignaciones masivas o cambios de URLs. Si la interfaz difiere, continuar con guía según esa pantalla.

El idioma de las rutas existentes será inglés predeterminado; el idioma real de cada pieza debe revisarse antes de asignarlo en bloque. Una referencia a una fuente española puede tener ficha local inglesa: son idiomas distintos. No modificar slugs de las entradas existentes.

### 2. Configurar idiomas y límites

Crear códigos en y es, inglés predeterminado. Elegir el locale regional de cada uno con Alex, sin deducirlo solo del país o de la bandera. Mantener el idioma predeterminado sin prefijo en CMS donde aplique; las bases localizadas públicas las resuelve Astro, no una función PRO de traducción de bases CPT.

Activar traducción de páginas, posts, project, articles y categorías/tags que requieran equivalencias. lab_project se añadirá cuando exista. No traducir grupos ACF ni crear copias por idioma de sus definiciones. Revisar ajustes de medios antes de duplicar adjuntos o cambiar IDs: no duplicar físicamente imágenes por defecto. Desactivar detección/redirección automática del navegador.

Tras cada cambio, comprobar Home, APIs actuales y acceso autenticado. No publicar todavía registros ES: el frontend actual no filtra idiomas.

### 3. Conector y comprobación de esquema

Preparar instalación del conector desde su release oficial v0.7.1, previa revisión del paquete. No asumir que el ZIP de una rama de desarrollo sea un paquete instalable completo. Registrar versión y volver a verificar el esquema inmediatamente después de activar.

`npm run cms:languages` registra el estado; `npm run cms:languages -- --require-ready` exige language/translations/filtros en Page, Project, Article, Post y Category y configuración EN/ES con inglés predeterminado. El script usa las credenciales de .dev.vars sin mostrarlas. El informe está en wordpress/LANGUAGE_SCHEMA_STATUS.md. Una introspección correcta no prueba aislamiento de datos ni compatibilidad editorial completa.

**Importante:** el README del conector indica que GraphQL devuelve todos los idiomas por defecto. Antes de publicar traducciones, adaptar todas las consultas y DTO para filtrar por idioma y comprobar idioma en la respuesta. También categorías, relaciones, destacados y claves de caché deben incluirlo. Mantener queries legacy sin argumentos nuevos mientras el esquema aún no exista; no desplegar consultas contra fields hipotéticos.

### 4. Piloto y conexión del frontend

Crear una pareja controlada EN/ES en borrador con título, extracto, texto Gutenberg y un field ACF sencillo. Confirmar relación de traducción e independencia de valores al editar. La cuenta de servicio no debe poder leer borradores: revisar las relaciones desde admin o con una prueba autorizada sin elevar el usuario de producción.

Después preparar un piloto publicado acordado, sin introducirlo en destacados ni SEO final hasta validarlo. Probar consultas EN/ES sin mezcla, ausencia de traducción, URL distinta, estado privado y caché separada. Los campos globales de Home y About y relaciones de cada idioma requieren pruebas propias.

Implementar mapa de rutas Astro, templates compartidos y selector por traducción real, lang/canonical/hreflang recíproco solo para pares publicados. No publicar /es/ vacío ni una traducción automática sin revisar. Solo habilitar las rutas ES conforme exista contenido completo en CMS.

### 5. Criterio de salida

Identidad autenticada en los tres entornos, consultas anónimas rechazadas, contenido previo intacto, ninguna lista bilingüe mezclada, ACF EN/ES independiente, slugs antiguos conservados y pruebas de caché/SEO por idioma. Actualizar baseline consciente de los nuevos registros e idiomas, conservando la referencia legacy.

## Preparación del frontend: 18 octubre 2026

`src/data/content-language.js` implementa validación estricta EN/ES, rechazo de listas mezcladas y proyección de relaciones de traducción a metadatos públicos mínimos (ID, slug e idioma). Excluye borradores, privados, futuros, protegidos por contraseña, restringidos, idiomas desconocidos y destinos ambiguos. No copia contenido ni ACF de la traducción.

`src/data/localized-content.js` conecta esa política con consultas explícitas y paginadas para proyectos, referencias externas y categorías. API opcional: `getProjectsInfo('en'|'es')`, `getArticlesCategories('en'|'es')`, `getArticlesByCategory(slug, 'en'|'es')` y `getArticleBySlug(slug, 'en'|'es')`. Sin argumento de idioma siguen ejecutándose las consultas anteriores: las rutas existentes todavía usan ese modo compatible. No activar llamadas localizadas antes de que el esquema del conector y los CPT traducibles estén preparados. Las referencias externas siguen separadas del blog propio; este módulo no implementa posts del blog.

Proyectos y listas de referencias usan una proyección pública antes de compartir respuestas autenticadas en caché; vuelve a ejecutarse al leer la caché. La clave incluye la consulta completa (con literal EN/ES validado), variables, endpoint e identidad de servicio. Categorías y detalles de referencias no usan caché compartida. Los detalles con idioma distinto devuelven null, sin sustituir automáticamente otra traducción. Se detectan cursores repetidos y se limita cada listado a 101 páginas como protección ante una API defectuosa.

`src/data/localized-pages.js` resuelve Home/About a partir de los IDs ingleses existentes (19/68) y de las relaciones públicas reales de Polylang. `getHomeInfo('en'|'es')` y `getAboutFields('en'|'es')` conservan los formatos de retorno actuales. Para español primero valida los metadatos de la relación y después consulta los fields de la página traducida; no consulta ACF de un destino privado/protegido ni usa el contenido inglés como sustituto. Si una traducción no existe, la función interna devuelve null; los getters de páginas obligatorias producen el error controlado CMS_HOME_MISSING/CMS_ABOUT_MISSING. Home sigue comprobando la identidad de servicio. Estas páginas no usan caché compartida.

Las pruebas deterministas verifican consultas mediante fetch simulado, dos idiomas en la misma caché, rechazo de una entrada de caché con idioma incorrecto, proyección idempotente, exclusión de datos extra, filtros por categoría parametrizados, paginación y resolución de Home/About. `npm test`: 87 pruebas correctas. Además, el cliente real pasó **13/13 comprobaciones contra WordPress local**, con las versiones del laboratorio y las definiciones ACF legacy; incluye campos EN/ES, relaciones de páginas, lectura desde caché y traducciones borrador/privadas/futuras/protegidas. Véase `wordpress/POLYLANG_CLIENT_RESULTS.json`. La caché probada es una implementación en memoria de la interfaz Cache API, no la caché desplegada de Workers. Los cuatro fallos de política ACF de la matriz de seguridad anterior siguen documentados; no se han convertido en éxitos ni ocultado.

El conector ya fue activado por Alex en el CMS el 18 de octubre. La comprobación autenticada confirmó EN/ES e inglés predeterminado; Page, Post y Category tienen fields y filtros de idioma. Project y Article todavía no los exponen porque siguen sin habilitarse como traducibles. `cms:baseline -- --verify` confirmó IDs, slugs y hashes publicados intactos; los smoke de staging y producción pasaron las siete páginas con identidad CMS verificada en cada entorno. El detalle está en `wordpress/LANGUAGE_SCHEMA_STATUS.md`.

Alex habilitó Projects y Articles como traducibles. `cms:languages -- --require-ready --audit-content` confirmó el esquema completo y detectó siete proyectos y diez referencias sin idioma; Home/About están en EN. El baseline original sigue intacto y el smoke de producción pasó las siete páginas autenticadas tras este cambio. Se guardó una referencia adicional de hashes para la transición editorial, excluyendo únicamente modified/uri.

Pendiente antes de activar las rutas: asignar idiomas y resolver taxonomías; ampliar las reglas ACF de Home/About a sus futuras páginas españolas; adaptar templates, navegación y SEO y probar el piloto. Polylang 3.8.11 ya fue confirmado por Alex y coincide con el laboratorio. No se han creado rutas /es/ vacías ni se ha desplegado el nuevo cliente localizado.

Conservar las URLs antiguas de las nueve referencias españolas documentadas en `wordpress/ARTICLE_LANGUAGES.md`; el idioma se leerá del CMS, no del slug. Asignación de Projects completada por Alex y verificada: los siete publicados están en EN, sin cambios en hashes editoriales/ACF/medios/relaciones según la referencia de transición. Mantener Articles sin cambios hasta revisar sus categorías, que actualmente son EN aunque nueve fichas sean españolas. Alex decidió conservar esas categorías en inglés; la clasificación de Articles queda diferida mientras se define un modelo compatible con esa decisión. No publicar traducciones, cambiar slugs ni asignar todo el CMS a inglés. Mantener la restricción de GraphQL a solicitudes autenticadas.

## Decisión vigente: conservar las categorías en inglés

Alex decidió mantener podcast, valexweb y wordpress-tv como están, en EN. No se aplicó el cambio propuesto a ES ni se debe seguir solicitando. Medium permanece EN. Las diez referencias siguen pendientes de clasificación; no cambiar su idioma mientras no se resuelva cómo conservar la relación con esas categorías y las URLs legacy dentro de Polylang. La revisión de usos y la tabla de IDs se conservan como evidencia, no como una orden pendiente.

El plan bilingüe continúa. El modelo definitivo para fuentes/plataformas compartidas en inglés debe resolverse antes de activar los archivos de referencias ES; puede requerir una taxonomía de fuente sin traducción o una relación editorial específica, sin alterar las categorías actuales por defecto. No deshabilitar la traducción global de categorías de WordPress como atajo, porque también afecta al blog.

## Base técnica de rutas y SEO preparada

`src/data/site-routes.js` centraliza bases EN/ES y construye rutas desde slugs reales de WordPress; no genera traducciones ni publica rutas. Preserva las bases inglesas y valida los segmentos para evitar inyección de rutas. `src/data/page-seo.js` construye canonical del dominio de producción sin query/hash, valida imágenes sociales HTTPS opcionales y metadatos regionales coherentes.

MainHead elimina viewport/descripciones repetidos e imágenes sociales vacías; Open Graph usa en_US para las páginas inglesas actuales. BaseLayout recibe locale, regionalLocale e imagen opcional; no se supone una región española aún no confirmada. No se implementan todavía hreflang, selector, sitemap ni páginas /es/; deben depender de contenido real publicado y del modelo de relaciones. Las rutas públicas mantienen el modo actual. Pruebas unitarias: 92 correctas; 13 comprobaciones previas del cliente contra WordPress local siguen siendo evidencia de aquella ejecución.

Compilación final correcta y smoke local de siete páginas con identidad autenticada y `--check-seo` correcto. La vista previa se actualizó en localhost:4321. Verificación de HTML real: un viewport, una descripción, canonical de producción y metadatos sociales sin imágenes vacías. Estos cambios siguen locales, sin despliegue nuevo ni cambios de configuración en WordPress.

## Procedimiento de reversión

Si falla la integración, restaurar primero el frontend compatible y desactivar únicamente el conector cuando corresponda; no desinstalar Polylang ni borrar términos de idioma/traducciones como reparación automática. Desactivar Polylang puede cambiar filtros y URLs: repetir smoke y restaurar configuración/backup si es necesario. No activar a la vez otra estrategia de idioma.

## Fuentes

- [Polylang en WordPress.org](https://wordpress.org/plugins/polylang/)
- [Conector oficial Valu Digital y comportamiento de consultas](https://github.com/valu-digital/wp-graphql-polylang)
- [Releases del conector](https://github.com/valu-digital/wp-graphql-polylang/releases)
- [Polylang: sincronización de metadatos](https://polylang.pro/documentation/support/guides/synchronize-metadatas-between-translations/)
- [Polylang: CPT y taxonomías](https://polylang.pro/documentation/support/guides/multilingual-custom-post-types-and-taxonomies/)
