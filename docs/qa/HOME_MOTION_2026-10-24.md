# Hero compacto y animación real — 24 octubre 2026

Motion14.6.0 instalada con versión exacta. La portada muestra una tarjeta compacta de un proyecto ya leído del CMS y un retrato de4rem, en lugar del retrato grande dominante. Reutiliza la colección existente, sin nueva consulta, métricas ni terminal de pruebas simulada. El proyecto mostrado es el primero del orden por modificación que ya usa Home; en ES se conserva el destino inglés indicado explícitamente. La tarjeta destacada recibe un tratamiento más compacto; archivo y resto de tarjetas mantienen su proporción anterior.

## Movimiento implementado

- Título, descripción y acciones: translateY12→0,420ms, separaciones0/60/120ms. Opacity=1 durante toda la entrada para mantener texto legible.
- Tarjetas de proyectos y menciones en Home y archivo de proyectos: translateY12→0, opacity0.65→1,320ms, escalonado máximo180ms. Una ejecución por elemento, sin repetir al salir/volver a entrar en pantalla.
- Hovers CSS existentes conservados. Se anima el contenedor de lista y no el enlace, evitando competir con su transform de hover.
- prefers-reduced-motion cancela observadores/animaciones; el cambio dinámico se gestiona y los elementos ya animados no se repiten. Se restaura el valor/prioridad de opacity/transform originales, también después de que Motion confirme estilos finales.
- Limpieza en navegación Astro/pagehide y reinicialización al volver desde bfcache. No hay CSS que oculte el contenido esperando JS, temporizadores permanentes, scroll interceptado ni tipografía por letras.

El menú conserva su comportamiento previo; su animación de apertura propuesta todavía no se incorporó. Tampoco se animaron contadores ni datos ficticios.

## Evidencia y pruebas

Cinco pruebas del controlador correctas: inicio reducido sin observadores, una ejecución con límite de delay/restauración de estilos, cancelación al cambiar preferencia, activación desde preferencia reducida y fallback si falla animate. Son pruebas con dobles; no se presentan como una sesión del sistema operativo con reducción activada.

Suite SSR ampliada correcta: Home EN/ES, una H1, marcas de animación, avatar y enlace de proyecto, texto SSR sin ocultar, y Home sin proyectos que omite destacado pero conserva perfil. Incluye las regresiones de proyectos/referencias/blog/LAB existentes. No contar237 unitarias anteriores como ejecutadas de nuevo en este incremento.

Build final correcto:172 archivos,0 errores/warnings/hints de Astro check; permanecen avisos conocidos de Shiki y CSP. La configuración CSP del producto no cambió. Paquete y lockfile añaden únicamente Motion y sus tres dependencias nuevas; no se ejecutó audit fix ni se actualizaron dependencias ajenas.

Browser real en vista sintética: se capturaron matrices de transformación intermedias para los tres elementos del hero, manteniendo opacidad1; tarjetas con opacidad0.995505/0.978067 y desplazamientos0.154117/0.751988px durante reproducción. Después los elementos vuelven a opacity1/transform none y no quedan propiedades inline del efecto. [Muestras DOM](HOME_MOTION_RUNTIME_2026-10-24.json). La superficie de observación no expone document.getAnimations(); se usaron estilos computados durante la ejecución y al terminar, no una afirmación basada solo en el código.

Vista ES a320CSS: una H1, ancho y scrollWidth320, texto/acciones envueltos sin desbordamiento observado. En escritorio1280CSS, separación real de16px entre tarjeta destacada y perfil después de corregir un solapamiento causado por height:100% heredado. No es una aceptación integral de accesibilidad, rendimiento o zoom200%.

### Archivo compilado y CSP

Chunk PageMotion:9794B, gzip3876B, sin imports externos en ese archivo. Incluye mini, inView y el controlador; no es el tamaño total de JS del sitio ni una medición de transferencia del CDN.

El fixture `/preview/motion-csp` sirve **el chunk compilado exacto** como único script externo, sin inyección de Vite ni script inline. HTTP200 y SHA/tamaño coinciden con dist. La política conserva script-src self/challenges, style-src self/Google y el permiso de atributos de estilo ya existente; no agrega unsafe-inline/unsafe-eval a script-src. Browser: ambos efectos terminan con estilos limpios, contenido visible y0 warnings/errors. [Cabeceras, digest y observación](HOME_MOTION_CSP_2026-10-24.json). Esto valida el mecanismo de animación bajo esas reglas; no sustituye el postflight del Worker desplegado ni certifica todo su CSP generado.

### Vista para Alex

[Abrir vista local](http://127.0.0.1:4398/preview/home). Incluye «Repetir animación» y «Ver tarjetas». Son controles exclusivos del fixture, no aparecen en la portada del producto. La imagen del proyecto, avatar y textos son de demostración, no datos publicados de un cliente.

- [Hero oscuro escritorio](home-motion-dark-desktop-2026-10-24.png)
- [Hero ES oscuro320](home-motion-es-dark-320-2026-10-24.png)

Se deja un único servidor de revisión en4398 para esta vista. No reiniciar el servidor principal4325 ni confundir4398 con producción. Los fixtures usan respuestas GraphQL sintéticas, sin consultar el CMS.

## Recursos y límites

Sin SSH, Docker, backups, cambios CMS, contacto/envíos ni deploy. Los cuatro paquetes nuevos ocupan aproximadamente14MiB en node_modules, más cachés de npm/Vite; no se descargó WordPress. Volumen38GiB libres al inicio y36GiB en la lectura posterior: no se atribuye esa variación global a estos archivos. El bundle enviado es pequeño, pero los tamaños de paquetes/cachés locales son distintos.

La instalación informó6 avisos de npm:5moderados/1alto en la cadena previa de Cloudflare/Wrangler/miniflare/undici y fast-uri. La auditoría no identifica Motion ni sus tres dependencias nuevas entre los paquetes afectados; no se cambió esa cadena para resolver algo fuera del incremento. No se afirma que toda la aplicación esté libre de vulnerabilidades.

Quedan para producto: especialidades/LAB en Home, tarjetas editoriales y CTA; apertura animada del menú si aporta valor. La publicación de este frontend y la comprobación de su configuración/HTTP en staging son una entrega posterior. MCP sigue aplazado.
