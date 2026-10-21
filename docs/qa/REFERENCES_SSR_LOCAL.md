# Referencias: templates reales bajo HTTP — 19 octubre 2026

Estado: comprobación SSR local aislada completada. No equivale a aceptación visual ni smoke del CMS compartido.

## Prueba

`npm run test:references-ssr` inicia Astro con raíz de fixture y monta mediante injectRoute los tres archivos reales de aplicación: archivo, categoría y ficha. No se copian sus templates ni se sustituye su layout: se ejecutan BaseLayout, Nav, Footer, renderer editorial y loaders reales. El fixture reemplaza únicamente el transporte GraphQL con respuestas sintéticas y cloudflare:workers con contacto false; middleware usa withCmsRequest real, datos de autenticación ficticios y escenarios por petición con AsyncLocalStorage.

No carga .dev.vars ni realiza peticiones al CMS. No abre navegador ni captura UI; el puerto4398 es exclusivo de este test HTTP y no una vista alternativa de la aplicación bloqueada en4325. El proceso se detiene al finalizar. La aplicación y las capacidades MCP no se modifican.

## Resultados

- Cinco escenarios200: archivo con categoría en página posterior; categoría con referencias paginadas; categoría vacía; ficha completa; ficha sin imagen/cita.
- Cada éxito conserva un H1, main y canonical de producción; no aparecen widget de contacto ni script editorial no permitido.
- Imagen real del campo featuredImage se inserta; bloque de imagen/cita ausente no se renderiza.
- Tres escenarios404/no-store/noindex: categoría inexistente, ficha inexistente y ficha bajo categoría incorrecta.
- Cuatro escenarios503/no-store/noindex/Retry-After120: caída inicial, fallo en segunda página de categorías, fallo en segunda página de referencias y fallo tardío de navegación compartida después de cargar la ficha. Ningún cuerpo503 filtra HTML parcial, detalles upstream ni contenido de la ficha.

Suite HTTP correcta,160 pruebas unitarias correctas, build correcto y git diff --check sin errores. Incorporada test:references-ssr a CI. No se repite smoke remoto para esta entrega.

## Pendientes

QA visual de páginas/temas/idiomas y teclado; verificación con GraphQL real y Workers antes de publicar. Paginación anidada de categorías de una ficha y modelo idiomático/SEO editorial siguen abiertos. El fixture no prueba esos puntos ni runtime Cloudflare: usa Astro dev y un entorno Cloudflare simulado para permitir ejecutar los templates.

Sin despliegue, escritura CMS, nuevas credenciales o ampliación MCP. Contacto desactivado.
