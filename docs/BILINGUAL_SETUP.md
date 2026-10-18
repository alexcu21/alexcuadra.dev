# Bilingüismo EN/ES: implementación guiada

## Decisión y compatibilidad

Ruta propuesta: Polylang gratuito para idiomas/relaciones y WPGraphQL Polylang de Valu Digital como candidato de integración. No usar ACF PRO ni una segunda fuente de idioma basada en fields paralelos. Alex autorizó operar en producción aceptando interrupciones; conservar backups y aplicar cambios por pasos.

| Componente | Verificación del 18 octubre 2026 |
| --- | --- |
| WordPress | 7.1.3, confirmado por Alex |
| PHP | 8.5.11, confirmado por Alex |
| ACF / WPGraphQL for ACF | 6.8.10 gratuito / 3.0.0, confirmados |
| WPGraphQL | 2.24.2, reportado por Alex anteriormente |
| Polylang candidato | 3.8.11 según directorio oficial; mínimo WP 6.5/PHP 7.4, probado por su autor hasta WP 7.1.3 |
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

## Reversión

Si falla la integración, restaurar primero el frontend compatible y desactivar únicamente el conector cuando corresponda; no desinstalar Polylang ni borrar términos de idioma/traducciones como reparación automática. Desactivar Polylang puede cambiar filtros y URLs: repetir smoke y restaurar configuración/backup si es necesario. No activar a la vez otra estrategia de idioma.

## Fuentes

- [Polylang en WordPress.org](https://wordpress.org/plugins/polylang/)
- [Conector oficial Valu Digital y comportamiento de consultas](https://github.com/valu-digital/wp-graphql-polylang)
- [Releases del conector](https://github.com/valu-digital/wp-graphql-polylang/releases)
- [Polylang: sincronización de metadatos](https://polylang.pro/documentation/support/guides/synchronize-metadatas-between-translations/)
- [Polylang: CPT y taxonomías](https://polylang.pro/documentation/support/guides/multilingual-custom-post-types-and-taxonomies/)
