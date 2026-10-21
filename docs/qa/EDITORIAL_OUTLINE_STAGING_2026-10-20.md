# Índice y anchors editoriales en staging — 20 octubre 2026

## Entrega

**Desplegado para revisión** en [staging](https://alexcuadra-dev-staging.alexcu21.workers.dev/). Versión `1b12660e-22b5-4e0d-8120-cf89c4ab60e5`, deployment `bf288cd4-3920-4183-bacf-49b9cea04c02`, tráfico100% confirmado por API después del smoke. Creado21oct02:30UTC,20oct20:30 Managua. Sustituye `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`, conservada como destino de rollback compatible. [Resultado saneado](EDITORIAL_OUTLINE_STAGING_RESULT_2026-10-20.json).

El paquete frontend actual incluye el compilador de anchors y el índice desplegable de Blog/LAB EN/ES que estaban solo locales. No hubo modificaciones del código de producto adicionales en este incremento; se verificó y publicó el árbol preparado, conservando los numerosos cambios locales. Los plugins/fixtures MCP no se empaquetan ni instalan como parte del Worker.

## Verificación previa y artefacto

- `npm test`:237 correctas,0 fallos. `npm run test:references-ssr`:suite correcta con templates reales y fixtures sintéticos, incluidos índice/fragmentos EN/ES. El primer arranque SSR falló por EPERM del sandbox en4398; funcionó al autorizar el servidor de fixtures. No se cambió producto para ocultarlo ni se usó para acceder al navegador bloqueado.
- `npm run build` correcto:160 archivos revisados,0 errores/advertencias/hints de Astro check. El primer build necesitó permisos para el puerto interno9229 del plugin Cloudflare; repetición autorizada correcta. Se mantienen los avisos conocidos Shiki/CSP del build, distintos del conteo de Astro check; no se desactivó CSP.
- Configuración generada apunta solo a `alexcuadra-dev-staging`, sin rutas de producción, auth CMS true, contacto false y keep_vars. Artefacto contiene marcadores de índice/labels EN/ES/anchors. Cuatro assets de texto públicos revisados no contienen referencias a los tres bindings sensibles comprobados; esto no se presenta como auditoría universal de secretos. No se leyó/imprimió `.dev.vars` ni destinatario privado.
- Dry-run correcto. SHA256 del entry y del árbol compilado sellados, comprobados antes de publicar; CLI y errores remotos mantenidos en memoria, solo proyección de IDs/checks guardada. Metadatos activos confirmaban bbb al100% antes de subir, sin despliegue concurrente identificado.
- Baseline remota antes de publicar:Home200, identidad CMS verificada, noindex/sin formulario; un asset CSS200 y `/api/contact`404/CONTACT_DISABLED/no-store.

No se repiten290 checks del MCP/Docker, porque el prototipo no cambió. No se repitieron sitemap/archivos/404 ya correctos ajenos al delta ni el ensayo de rollback completado anteriormente.

## Aceptación HTTP del delta publicado

[Herramienta reproducible](../../scripts/outline-smoke.mjs), destino fijo staging,30s entre comienzos de peticiones respaldadas por CMS, sin reintentos. Baseline y verificación son fases separadas; la última hora de petición se conserva entre fases. No es automatización de navegador ni QA visual.

| Ficha | HTTP | Idioma | Títulos/index links |
| --- | --- | --- | --- |
| `/blog/example-post-structuring-a-test-case` | 200 | EN | 3/3 |
| `/es/blog/publicacion-de-ejemplo-como-estructurar-un-caso-de-prueba` | 200 | ES | 3/3 |
| `/lab/qa-training-lab` | 200 | EN | 6/6 |
| `/es/laboratorio/qa-training-lab-2` | 200 | ES | 6/6 |

Todas verifican identidad CMS, staging noindex, canonical de producción y alternates recíprocos, una H1/main enfocable, renderer único y ausencia de formulario/widget/error overlay. CSP presente y hashes de scripts ejecutables inline correctos, sin unsafe-inline/unsafe-eval.

En cada ficha el índice nativo details abierto aparece con etiqueta EN/ES correcta. Sus enlaces cubren todos los títulos admitidos, cada destino es único y existe, el texto coincide y el objetivo tiene tabindex=-1. El CSS WordPressContent servido devuelve200, contiene las reglas del índice y su hash coincide con el asset local verificado. Contacto comprobado otra vez tras publicar:404/CONTACT_DISABLED/no-store.

No hubo fallo HTTP en estas cinco visitas CMS (baseline+cuatro fichas). Eso no resuelve la causa histórica del429 ni certifica toda la matriz del sitio. No se cambió un umbral del hosting ni se hicieron pruebas de carga.

## Configuración, rollback y límites

Postflight remoto confirma auth/credencial CMS presentes, contacto false y coordinador local. Bindings, runtime y named handlers de ambas versiones coinciden exactamente; rollback compatible a bbb disponible. Solo comprobación de compatibilidad, no se ejecutó una nueva reversión. Inspección final confirma nueva versión al100% y deployment indicado; no se exponen valores de vars, autores o destinatarios.

Sin CMS editorial/configuración/permisos/MCP, Worker de producción, contacto/envíos o challenges modificados. Dev local conservaPID6326 en4325; no se reinició ni se accedió por HTTP/navegador a ese origen. Sin reset/commit/limpieza del árbol.

**Revisión visual/teclado/scroll/foco/zoom200%/lector pendiente**, también para las variantes restantes de otras páginas. No usar el nuevo origen staging para eludir el bloqueo del navegador. La entrega está lista para revisión, no aprobada para producción. Cambio de título/orden puede alterar fragmentos generados según los límites del [QA local](EDITORIAL_OUTLINE_LOCAL_2026-10-20.md).

Para una futura entrega, con build correspondiente y el mismo control de staging:

```sh
node scripts/outline-smoke.mjs baseline
# Publicar únicamente el paquete staging verificado y registrar su versión.
node scripts/outline-smoke.mjs outline
```

No ejecutar esas visitas de nuevo sin cambios/necesidad. El siguiente trabajo frontend es cerrar esa aceptación con acceso efectivamente permitido o evidencia manual y continuar el diagnóstico429. Trabajo MCP independiente disponible: implementar en laboratorio el contrato de aplicación/recuperación ya definido; ningún permiso real se amplía por este deploy.
