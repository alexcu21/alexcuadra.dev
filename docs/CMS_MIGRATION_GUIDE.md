# Fase 1: inventario y transición de CPT a ACF

Estado: iniciada el 18 de octubre de 2026. Alex confirma que no existe staging de WordPress y autorizó trabajar en producción aceptando interrupciones. Los Workers leen el mismo CMS. La ruta actual está en [CPT_TRANSITION.md](wordpress/CPT_TRANSITION.md): plugin y export ACF recibidos, análisis de equivalencia y guía de sustitución sin copiar contenido. La sección de staging siguiente se conserva como alternativa, no como requisito.

## Hallazgos confirmados por lectura del esquema

| Entidad | Clave interna expuesta en ContentType.name | GraphQL singular | GraphQL plural |
| --- | --- | --- | --- |
| Proyectos | project | project | projects |
| Artículos | articles | article | articles |

Fuente: [inventario autenticado](CMS_SCHEMA_INVENTORY.md), obtenido con `npm run cms:inventory`. El script solo consulta metadatos; no lee cuerpos editoriales, modifica WordPress ni imprime credenciales. Los cinco tipos del informe son los visibles para el usuario de servicio, no un listado completo de todos los registros PHP del servidor. Los valores null de configuración se consideran desconocidos; no equivalen a false.

No crear un CPT llamado projects por deducción desde la query. Se propone conservar project y articles, sujeto a contrastar con el plugin. La posibilidad de mantener las entradas sin copiarlas depende de conservar registros, permisos, metadatos y funciones adicionales.

## Paso 1: disponer de una copia de WordPress

En Hostinger hPanel → Websites → Dashboard del sitio que contiene content.alexcuadra.dev → WordPress → Staging, comprobar si está disponible Create staging. Si la herramienta no aparece o pide cambiar de plan, informar antes de contratar, instalar plugins o elegir una alternativa. Se evaluará una copia manual o local según acceso y recursos disponibles.

Antes de crear o modificar registros: disponer de backup de base de datos y archivos/medios del CMS y conservar el plugin original. No compartir dumps ni wp-config.php por chat ni guardarlos en el repositorio público.

Crear la copia con su base de datos y archivos independientes, en la URL que permita Hostinger. Revisar login, medios, CPT y grupos ACF. No usar Publish/Push to live: esa acción puede sobrescribir producción. La nueva copia requiere control de acceso real y noindex; también revisar envío de correos, webhooks y tareas automáticas antes de usarla. Reemplazar o revocar en la copia las credenciales de aplicación clonadas y crear una cuenta/contraseña de servicio exclusiva para ese entorno. Verificar los destinos en wp-config desde el hosting, sin publicar sus valores.

Cloudflare gestiona el DNS del dominio: si la nueva URL no resuelve, obtener primero de Hostinger el nombre y destino exactos antes de añadir registros. No modificar content ni el apex existente a ciegas.

## Paso 2: obtener las definiciones actuales

1. WordPress → Plugins instalados: identificar nombre, versión y carpeta del plugin custom que registra los CPT. No desactivarlo ni desinstalarlo.
2. Hostinger → Files → File Manager → directorio de esa instalación → wp-content/plugins → carpeta del plugin. Descargar **solo esa carpeta** como ZIP y facilitarla para inspección, o indicar una ruta local si ya está descargada. Revisar que no incluya claves ni archivos de configuración ajenos. No descargar todo WordPress como sustituto.
3. WordPress → ACF → Tools/Herramientas → Export Field Groups: exportar en JSON los grupos que corresponden a Home, About, proyectos y artículos. Incluir definiciones CPT/taxonomías si ya existen en ACF. No importar ni recrear field keys todavía. El JSON de configuración no sustituye un backup de los valores de contenido.
4. Registrar versiones de WordPress, PHP, ACF gratuito y WPGraphQL for ACF. WPGraphQL se había reportado como 2.24.2; confirmar cualquier actualización posterior. No aumentar privilegios de la cuenta de servicio para obtener este inventario.

## Paso 3: análisis antes de la guía de fields

Inspeccionar register_post_type, taxonomías, supports, REST/rewrite, capabilities, GraphQL, hooks de activación/desinstalación, resolvers y reglas ACF. Producir una tabla plugin→ACF con cada valor exacto y cualquier lógica que deba conservarse como código. La UI de ACF no reemplaza automáticamente lógica PHP custom.

Después guiar a Alex para registrar cada CPT en la copia de WordPress con un solo proveedor activo por clave, comparar IDs/metadatos/términos y validar consultas. Solo si la equivalencia falla se diseña una migración de datos; no copiar entradas por defecto. LAB será un CPT nuevo y se define después de resolver nombres y colisiones.

La guía concreta de creación de fields (nombre, tipo, reglas, traducción y GraphQL) se entrega grupo por grupo tras revisar los exports. El contenido se carga con información real; no se usan cifras de las maquetas.

## Referencias oficiales

- [Hostinger: crear staging de WordPress](https://www.hostinger.com/support/5720286-how-to-create-a-wordpress-staging-environment-in-hostinger/)
- [ACF: registro de CPT](https://www.advancedcustomfields.com/resources/registering-a-custom-post-type/)
- [ACF: post types, taxonomías e importación](https://www.advancedcustomfields.com/resources/post-types-and-taxonomies/)
- [ACF: exportación de grupos de campos](https://www.advancedcustomfields.com/resources/frequently-asked-questions/)
