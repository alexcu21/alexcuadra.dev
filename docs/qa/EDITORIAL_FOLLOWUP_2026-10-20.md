# Reanudación de QA editorial — 20 octubre 2026

## Alcance y corrección del smoke

Se comprueba el Worker staging existente `bbb91792-89ba-4fac-88fb-6b67d1eb7b6c`; sin despliegue, cambios CMS/roles/hosting ni producción. Se conservan los cambios locales y contacto false. Este incremento modifica únicamente herramientas de QA, pruebas y documentación.

`--editorial-start-at blog-404|lab|references` permite continuar los casos pendientes sin volver a consultar Home, sitemap, rutas técnicas o endpoint de contacto. Requiere `--check-blog-lab --editorial-only`, rechaza combinaciones con otras suites y opciones duplicadas antes de hacer peticiones. Blog404 usa un slug inglés previamente verificado, distinto del destino español real; no redescubre fichas que ya pasaron. No persiste ni inventa éxitos de las comprobaciones omitidas. Identidad CMS se exige en cada página200 del tramo reanudado; se mantienen SEO/CSP, idiomas, alternates, renderer, H1, landmark y ausencia de formulario en LAB.

Encontrado un fallo del propio smoke: el `AbortSignal.timeout` se creaba antes de esperar el intervalo entre peticiones. Con separación30s podía agotarse antes de enviar la siguiente consulta. `createSmokeFetch` ahora crea la señal después de la espera y conserva el presupuesto completo de red. Contacto conserva15s y demás rutas30s; sin reintentos. Pruebas con reloj inyectado reproducen el caso gap=timeout y el orden espera→temporizador→fetch.

218 pruebas unitarias correctas, incluidas siete nuevas de reanudación/temporizador. Fixture sin red comprueba las rutas exactas de los tres puntos de reanudación, rechazo previo a HTTP de planes inválidos y detención en503 sin consultar secciones posteriores. No se repite build ni suite SSR porque no cambió código del frontend; su último build validado corresponde a la versión de staging indicada.

## Blog: casos pendientes completados

Comando inicial:

```sh
node scripts/smoke.mjs https://alexcuadra-dev-staging.alexcu21.workers.dev --check-blog-lab --editorial-only --editorial-start-at blog-404 --blog-entry-path /blog/example-post-structuring-a-test-case --require-cms-auth --check-seo --check-content --check-csp --check-contact-disabled --request-gap-ms 30000
```

Correctos: `/blog/__missing_entry__` y `/es/blog/example-post-structuring-a-test-case` respondieron404/no-store/noindex. La pasada se detuvo en la primera consulta a LAB por el timeout de la herramienta descrito arriba; no hubo fallo CMS registrado para esa petición. No se atribuye ese timeout a429. Una única consulta aislada posterior a `/lab/` respondió200 en1207ms, identidad CMS verificada/noindex y ficha QA Training Lab presente. Ese diagnóstico motivó corregir el temporizador; no se repitieron los casos Blog correctos.

## LAB y referencias

Tras corregir el temporizador, comando reanudado únicamente desde LAB:

```sh
node scripts/smoke.mjs https://alexcuadra-dev-staging.alexcu21.workers.dev --check-blog-lab --editorial-only --editorial-start-at lab --require-cms-auth --check-seo --check-content --check-csp --check-contact-disabled --request-gap-ms 30000
```

Resultado: exit0, siete rutas comprobadas con30s entre comienzos de peticiones. Archivos `/lab/` y `/es/laboratorio/`, fichas `/lab/qa-training-lab` y `/es/laboratorio/qa-training-lab-2` y archivo de referencias `/articles/` respondieron200 con identidad CMS verificada, canonical y CSP/hashes correctos. LAB conserva idiomas/alternates recíprocos reales, una H1, landmark enfocable y renderer editorial compartido en ambas fichas. No hay formulario/widget de contacto en sus cuatro páginas. El archivo de referencias no anuncia categorías exclusivas del Blog como uncategorized.

`/lab/__missing_entry__` y `/es/laboratorio/qa-training-lab` respondieron404/no-store/noindex. Las cuatro rutas públicas LAB figuran también en el XML de43URLs guardado en el incremento anterior: comprobación del archivo local, sin solicitar otro sitemap al CMS. La captura de logs no registró eventos `cms_failure` durante las comprobaciones de este incremento y mostró reutilización HIT de metadatos/disponibilidad. Tail cerrado al terminar.

Se completan así los casos remotos pendientes de aquella pasada (Blog404, LAB y referencias), sumados a sus éxitos anteriores. **No fue una única pasada integral correcta ni una certificación de disponibilidad continua.** No hay reintentos automáticos ni una nueva pasada de Home/Blog/sitemap. El único diagnóstico LAB aislado y la reanudación posterior se justificaron por el fallo del temporizador reproducido y corregido; no se insistió tras otro429. [Resultado saneado](EDITORIAL_FOLLOWUP_RESULT_2026-10-20.json).

## Límites y continuidad

Los429 upstream confirmados en incrementos anteriores siguen siendo un incidente distinto del timeout del smoke. Esta corrección no cambia el limitador del hosting ni acredita estabilidad continua. No se conocen todavía la regla ni su ventana; hace falta correlación con logs/configuración del hosting antes de cambiarlo. Se dejó el identificador de correlación y diagnóstico saneado en [CMS_429_DIAGNOSTIC_2026-10-20.md](CMS_429_DIAGNOSTIC_2026-10-20.md). No se desactivaron protecciones ni se ampliaron permisos.

QA visual/zoom200% y lector de pantalla siguen abiertos. No se reintentó ni se eludió el bloqueo vigente del navegador. Esta revisión HTTP no lo sustituye. Contacto permanece desactivado, sin challenge ni correo. MCP editorial continúa como etapa separada. No se aprueba salida a producción por estas pruebas focalizadas.

Próximo incremento local disponible: anchors/tabla de contenidos del renderer editorial compartido, pendientes del plan, con fixtures deterministas y sin alterar contenido CMS. Después continuar aceptación visual y ensayo de recuperación antes de proponer producción. El diagnóstico de infraestructura requiere logs del hosting; no bloquea este trabajo local ni debe tratarse como resuelto por un smoke correcto.
