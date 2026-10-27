# Paquete de propuestas privadas — 26 octubre 2026

**Candidato revisable completado; destino real pendiente.** ZIP0.1.0-rc.1,17.074bytes, SHA256 `ca33ebd4b3a874a939b8d18dbf42c2cea324325b26716f01208cd1f85154635a`. Dos construcciones idénticas, ocho archivos verificados contra ZIP/manifiesto/fuentes. [Procedimiento, conservación y diagnóstico actual](../WORDPRESS_MCP_BLOG_PROPOSAL_PACKAGE.md), [recibo detallado](editorial-blog-proposal-package-2026-10-26.json).

## Pruebas del paquete real

- **149 funcionales sobre el ZIP instalado:**72WordPress/MariaDB/Polylang,34conexión/SIGKILL y43MCP/HTTP. Se adaptaron únicamente namespace/prefijo/rutas/nombres de herramientas de las pruebas previas. Requieren los archivos realmente instalados por el instalador de WordPress, no un mount de código alternativo ni el MU fixture anterior.
- **29 contratos cerrados:** cinco entradas/salidas y objetos anidados sin campos adicionales; validación real con rest_validate_value_from_schema para revisiones, contexto, inventario y traducción. Campos desconocidos, postmeta, propietario cliente, slug, afirmación de escritura pública e ID de traducción reservado rechazados. Perfil de plugin desconocido falla. Scanner de tokens PHP sin llamadas a escritor de fuente, grants o hooks de activación/desinstalación.
- **82 casos del planificador** contra el módulo derivado del paquete, con mismos casos sintéticos previos. **13 guards de sitio** en procesos separados (flag/URL/entorno/DB/multisite/tipos), sin presentar esos guards como integración nativa.
- **Nueve recibos de ciclo:** before, after-install, after-enable, after-storage, after-owner y after-scope correctos; índice inesperado produce ready=false y rechazo real de una lectura del repositorio; reparación explícita del fixture recupera after-scope. Retirada withdrawn correcta, conservando una cabecera/revisión/recibo creados expresamente para probar retención y las ocho capacidades previas.15checks por recibo, sin sumar repeticiones de checks al conteo273de pruebas anterior.
- Activación no crea tablas ni grants. Instalación DDL/fila de propietario/grant son acciones explícitas del fixture en laboratorio. Scopes/credenciales de la suite funcional retirados; el ciclo retira el scope y desactiva el candidato mientras mantiene activo el lector0.1.0-rc.1.

## Derivación y admisión

Namespace nuevo AlexCuadra\EditorialBlogProposals y tablas ac_blog_proposal_* separados de experimentos y proyectos. Cuatro módulos derivados de los ya ensayados, con cambios explícitos de namespace/nombre del guard/prefijo, guard de sitio/perfil y admisión cerrada del esquema. El builder registra hashes de origen y de cada archivo; no afirma igualdad byte por byte con el laboratorio, porque esas transformaciones son deliberadas. Fuente publicada nunca escrita por el paquete.

Inputs y outputs cerrados de cinco herramientas alexcuadra-blog/*; el contexto publicado requiere el nuevo scope privado y oculta cuerpos/IDs de destinos no públicos. No cambia el lector público. No se incluyen schemas genéricos de salida del antiguo fixture.

Admisión de esquema compara motor/collation, columnas/defaults/extra, índices completos, FK/agrupación/reglas sin cascadas y triggers. Solo en laboratorio se admite el trigger conocido de inyección de fallo; en destino ninguno. Una estructura parcial o no admitida se rechaza sin corregirla automáticamente.

Perfil mínimo ensayado: WP7.1.3/PHP8.5.11/MariaDB11.8.9/Polylang3.9.1/Adapter0.7.1, lector0.1.0-rc.1 y candidato, Twenty Twenty-Five1.5. Hash wpdb y entradas del tema fijados; sin drop-ins/caché persistente. Laboratorio admite un MU de test y callbacks instrumentados bajo guard local exacto. La admisión live no los permite. Tema/plugins/MU/query filters del hosting todavía deben contrastarse; no existe aprobación de instalación o scope real.

## Aislamiento y verificación final

Proyectos finales alexcuadra-blog-proposal-release-20261026-functional-r2 y lifecycle-r2: ambos contenedores detenidos, PortBindings={} y redes internal=true. También detenidos los tres proyectos previos (funcional/ciclo iniciales y probe de perfil). Sin datos del hosting importados, SSH, correo, despliegue frontend o cambios de CMS. No se podaron imágenes o volúmenes.

Los volúmenes tmpfs son efímeros: al detener y arrancar un laboratorio antiguo para una lectura de perfil, la BD ya no estaba instalada. No se contó ese intento como evidencia; se hizo un probe fresco para obtener tema/hashes/métodos de registro. Tras añadir tema/drop-ins/hash wpdb al perfil se reconstruyó el ZIP y se repitieron funcionales y ciclo completos en proyectos nuevos. No se intentó recuperar datos reales ni reutilizar un baseline histórico.

El diagnóstico final usa wp_has_ability para consultar registro sin notices por herramientas ausentes. Conserva deprecaciones conocidas de Colors.php/WP-CLI y avisos de WordPress.org bloqueado/HTTP_HOST en instalación. No se presentan logs como libres de avisos. Cuando el esquema no es verificable, no asume que esté vacío; devuelve fallo controlado de metadatos si la conexión/consulta no es admitida.

## Límites

No se ensayó pérdida del acuse TCP del propio COMMIT, ni snapshot atómico de grupo/fuente frente a todos los escritores/cachés externos. La barrera no protege código arbitrario que opere con mysqli fuera del protocolo. No hay publicación/aprobación/aplicación/recuperación certificadas. Huellas editoriales conservadas durante las fases operativas; fixtures hacen cambios sintéticos expresos, y no se afirma conservación global de toda la BD.

El diagnóstico before real es el siguiente paso. La conservación/respaldos/restauración operativos y compatibilidad del perfil real deben cerrarse antes de habilitar escritura privada real. Los checks de aceptación inicial vacía no son una orden de vaciar tablas después de su uso; en withdrawn la condición inicial no se exige (el fixture confirma1/1/1filas conservadas).

No se cambian plugins/ZIP instalados, propuesta227/revisión1, capacidades reales, producción/staging/contacto, DNS o la incidencia histórica de postmeta. No repetir piloto o grants anteriores. El manifiesto queda hold-pending-destination-profile.
