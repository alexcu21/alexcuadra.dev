# Lector MCP del blog: integración WordPress y HTTP — 26 octubre 2026

**Completado:71 comprobaciones reales**, 34 de WordPress/Abilities y37de transporte Apache/MCP. Se conserva el candidato sin cambios de código, SHA256 `a78ef3d34caf89c99de0c0f17d8bc29559b0a40f849a93080123e1915a1b8f63`. No se instaló ni se asignaron permisos en el CMS real.

[Resultados completos](editorial-blog-read-integration-2026-10-26.json), [laboratorio reproducible](../../tests/integration/wordpress-blog-read/README.md).

## Perfil y aislamiento

WordPress7.1.3, PHP8.5.11, Polylang3.9.1, MCP Adapter0.7.1 y MariaDB11.8.9. Imágenes oficiales fijadas por digest; ZIP de los plugins descargados desde WordPress.org al host y verificados por SHA256. Se montan únicamente scripts, candidato y esos ZIP en lectura. No se importan backups, opciones, datos ni credenciales del hosting.

Proyecto Docker propio `alexcuadra-blog-read-lab-20261026`, red internal, sin puertos publicados y volúmenes tmpfs limitados a256MiB cada uno para BD/WordPress. WordPress bloquea HTTP a hosts externos y el fixture impide correo. Las descargas previas de imágenes/ZIP sí usaron red del host; los intercambios HTTP de la prueba ocurrieron solo dentro de la red del laboratorio.

Al terminar se confirmaron ambos contenedores detenidos, red Internal=true y PortBindings vacíos. No se podaron imágenes/volúmenes ni se tocaron proyectos ajenos. Espacio global observado35GiBantes/33GiBdespués; no se atribuye toda la diferencia al ensayo. Los ZIP públicos en caché suman aproximadamente1,2MiB y las imágenes se conservan para reutilizarlas.

## Qué quedó probado

- Registro y ejecución reales mediante Abilities API y los meta-tools oficiales de MCP Adapter.
- Lectura nativa post por idioma, búsqueda solo en títulos, paginación pública completa y metadatos con campos exactos.
- Traducciones recíprocas de entradas y de categorías/etiquetas, con asignaciones reales en Polylang. Cuerpo, metadatos privados y metadatos de términos fuera del DTO.
- Borradores, privados, futuros, papelera, contraseña, páginas/proyectos, idioma desconocido e ID ausente rechazados en la API funcional. HTTP comprueba además privados/contraseña/ausentes/idioma erróneo y esquemas inválidos.
- Anónimo, contraseña incorrecta, usuario sin scope, administrador sin scope y permiso de proyectos sin permiso de blog rechazados. El lector conserva subscriber/read, sin edit_posts/manage_options.
- Inicialización/notification, encabezados de protocolo y sesión; sesiones ausentes/desconocidas/de otro usuario rechazadas. Sesión cerrada o credencial revocada no permite reutilización.
- Revocar el scope bloquea una sesión ya abierta. Desactivar el plugin retira abilities y ejecución; reactivarlo restaura la lectura autorizada.
- Huellas de posts, postmeta, términos, taxonomías, relaciones y termmeta idénticas antes/después de las lecturas observadas. No se afirma identidad de toda la BD: sesiones, usuarios y permisos sintéticos se crean/modifican expresamente como parte del ensayo.
- Application Passwords y capacidades sintéticas retiradas al terminar; sin correo ni permisos reales alterados.

## Registro y avisos

La primera pasada ya cumplió34+37comprobaciones, pero se detectó una definición repetida de WP_DEBUG en el compose. Se corrigió para usar la variable del entrypoint oficial y se repitió la integración. La pasada final conserva notices esperados de las pruebas de rechazo y advertencias de comprobaciones de actualización bloqueadas por la red. No se presentan los logs como libres de avisos ni esas advertencias como incidentes del CMS real.

Se añadió validación explícita de recibos al runner para evitar falso éxito si WP-CLI saliese0sin completar la fase. El verificador se comprobó con los recibos reales y8escenarios de entrada: válido, marcador ausente, JSON inválido, fallo declarado, transporte no confirmado, limpieza incompleta, conteo inconsistente y marcador duplicado. Este refuerzo del runner se validó sobre la salida capturada; no se suman sus escenarios a las71comprobaciones WordPress/HTTP.

## Límites y siguiente incremento

No reproduce todos los plugins/MU plugins/efectos/restricciones del hosting. El candidato sigue limitado a entorno local y opt-in; el conector real conserva sus11abilities anteriores. No hay cuerpos, edición, propuestas, nuevas traducciones ni taxonomías de proyectos añadidos por estas herramientas.

**Siguiente:** preparar paquete de destino y preflight de solo lectura, revisar el guard del sitio y el scope mínimo para la instalación. La instalación y el primer grant real requieren autorización sobre ese paquete concreto; no se reutiliza el permiso de proyectos ni se concede un rol Editor. La incidencia histórica de postmeta/propuesta227/revisión1 no se altera ni se vuelve a auditar por esta prueba.

Staging continúa en `b8e868a5-95d2-40a8-95b9-6ffd0101cb5d`; producción en `feba64f8-5406-4c69-8522-6c3753681a37`; contactofalse. No hay despliegues frontend, cambios CMS/DNS, envíos ni push en este incremento.
