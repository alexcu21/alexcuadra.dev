# Revisión del conector WP GraphQL Polylang 0.7.1

Fecha: 18 de octubre de 2026. Alex lo instaló sin activarlo. Mantenerlo desactivado hasta decidir la ruta de pruebas. Esta es una revisión estática acotada, no una certificación de seguridad ni prueba de compatibilidad.

## Alcance y procedencia

Tag oficial v0.7.1 de valu-digital/wp-graphql-polylang. Se leyeron el bootstrap, composer.json y los nueve archivos src: Loader, PostObject, TermObject, Helpers, LanguageRootQueries, PolylangTypes, MenuItem, StringsTranslations y OptionsPages. Se verificó inclusión de vendor/autoload.php; no se ejecutó el plugin ni se auditó un entorno WordPress aislado con PHP 8.5.

Última release/push observado: mayo 2025, repo no archivado. La consulta de avisos públicos de GitHub devolvió una lista vacía y Packagist mostraba cero avisos. Ausencia de avisos no demuestra ausencia de vulnerabilidades. Composer declara sin dependencias de producción; las herramientas de prueba figuran como require-dev. No se hizo un análisis exhaustivo de cadena de suministro.

## Hallazgos

| Área | Observación de código | Interpretación y prueba necesaria |
| --- | --- | --- |
| Traducciones de posts | translations crea modelos WPGraphQL y descarta los que is_private devuelve verdaderos. translation(language) crea el modelo sin esa comprobación explícita | Asimetría que exige probar borradores/privados con el usuario de servicio. WPGraphQL puede aplicar restricciones al resolver el modelo: no se demuestra fuga por esta observación |
| Mutaciones | Añade language a inputs create/update de posts y términos; actualiza mediante hooks de mutaciones de WPGraphQL | No añade una comprobación propia en esos hooks; se apoya en la autorización previa de la mutación base. No implica por sí solo bypass. Probar que Suscriptor no puede crear, editar ni cambiar idiomas |
| Contexto Polylang | Loader fuerza PLL_Admin_Model/PLL_Admin durante contexto GraphQL y omite el filtro de idioma del usuario | Es selección de contexto de ejecución; no equivale a otorgar rol administrador de WordPress. Probar efectos de filtrado y compatibilidad, sin asumir autenticación adicional |
| Filtros | Helpers mapea filtros de idioma a lang; por diseño la API puede devolver todos los idiomas sin filtro explícito | Frontend debe filtrar y validar locale; caché debe incluir idioma. No es autorización de contenido privado |
| Términos | Construye modelos WPGraphQL para traducciones de taxonomías | Probar visibilidad de taxonomías restringidas y que no rompa categorías/tags existentes |
| Opciones ACF | Solo se inicia si existen funciones/constantes de ACF Options For Polylang y Options Pages | No corresponde al modelo ACF gratuito actual. Evitar instalar esas dependencias; no son necesarias para páginas de ajustes normales |
| Dependencias antiguas de API | Llama a internals/clases de Polylang y WPGraphQL; contiene interpolación PHP heredada | Requiere prueba real PHP 8.5/Polylang instalado. No confundir deprecación o incompatibilidad con vulnerabilidad confirmada |

No se identificó un bypass de permisos confirmado en esta lectura. Tampoco se verificó ausencia de filtraciones mediante pruebas de ejecución. La restricción GraphQL del CMS y la cuenta Suscriptor siguen siendo necesarias; autenticación no reemplaza autorización.

## Opciones de continuación

1. **Piloto del conector existente:** entorno WordPress aislado con las versiones reales, fixtures sintéticos publicados/borrador/privado/con contraseña, pareja EN/ES y usuario Suscriptor. Probar consultas anónimas, translation/translations, mutaciones denegadas, caché e integración ACF. No usar datos privados reales como fixtures. No se impone staging en Hostinger, pero la instalación del ZIP no equivale a haber pasado estas pruebas.
2. **Puente de lectura propio:** mantener Polylang y crear únicamente metadata de idioma, relación de traducción pública y filtros. Sin mutaciones, Options Pages ni cambio global a contexto admin salvo necesidad demostrada. Debe incluir comprobaciones de permisos/estado, versionado, tests e integración real. Menor alcance no garantiza mayor seguridad; el mantenimiento pasa al proyecto.

Si no se dispone de entorno de prueba, comunicar esa limitación antes de pedir activar un conector no validado en producción. No desinstalar Polylang ni modificar idiomas para resolver la evaluación del puente.

Entorno local comprobado: PHP y cliente Docker presentes, pero Docker informa que no puede conectar con su daemon. No se arrancaron contenedores ni se copiaron datos o secretos de producción. Pruebas de integración pendientes de disponer del motor local.

## Fuentes

- [Código versionado del conector](https://github.com/valu-digital/wp-graphql-polylang/tree/v0.7.1/src)
- [PostObject.php](https://github.com/valu-digital/wp-graphql-polylang/blob/v0.7.1/src/PostObject.php)
- [Loader.php](https://github.com/valu-digital/wp-graphql-polylang/blob/v0.7.1/src/Loader.php)
- [WPGraphQL: modelo de seguridad](https://www.wpgraphql.com/docs/security)
- [Packagist: paquete](https://packagist.org/packages/valu/wp-graphql-polylang)
