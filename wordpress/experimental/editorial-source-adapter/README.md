# Adaptador de fuente — experimento local

Primera parte del adaptador de escritura. No es un plugin: no tiene cabecera instalable, hooks, abilities, migración ni paquete. No se incluye desde Read, Proposals o Changes Laboratory. `write_source` rechaza cualquier entorno distinto de local, origen distinto de `http://wordpress` o ausencia del opt-in explícito `AC_EDITORIAL_SOURCE_EXPERIMENT=true`. No se debe quitar este guard para instalarlo.

Contrato interno: recibe un documento de revisión ya autorizado por el futuro coordinador, un lector de snapshot fresco/bloqueado con la forma de `review_source`, un comprobador de esquema y una limpieza de caché WP/ACF. Requiere cuerpo/tagline con el mismo conjunto de campos antes/después. Nunca acepta título, slug, estado u otras claves de campo. Exige la referencia ACF original; diferencia fila ausente, cadena vacía y referencia presente. Comprueba versión/snapshot y esquema antes de escribir.

Solo cuerpo llama `wp_update_post`; solo tagline usa APIs ACF/meta. En ambos, el snapshot fresco posterior debe coincidir con lo previsto y conservar las demás filas/campos. Solo un cambio de cuerpo puede variar timestamps del post. Los retornos false de actualización de metadata no se interpretan automáticamente como fallo: la postcondición manda. Las cachés se limpian en finally incluso si falla una API. Al fallar cuerpo no continúa con metadata.

## Límite de este incremento

**No usar como ejecutor de escritura.** No valida aprobaciones/capacidades, no abre ni confirma transacciones, no registra eventos/recibos/intenciones, no difiere hooks LiteSpeed y no ejecuta compensaciones. Un fallo posterior al guardado exige rollback del futuro coordinador: este módulo no promete revertirlo. Los callbacks recibidos deben ser internos y fiables, nunca elegidos por un cliente. La validación y sanitización del documento de revisión preceden a esta capa. El lector debe rechazar metadatos duplicados y comprobar idioma/CPT/publicación y traducciones según el contrato de revisión.

La prueba `php tests/experimental/source-writer.php` usa dobles de las APIs WP y estado en memoria. Demuestra la frontera de llamadas y postcondiciones en39 comprobaciones, no comportamiento de hooks reales, SQL, ACF real, Polylang, concurrencia o purgas.

Siguiente incremento: intención durable por evento/Project, atómica con contenido/aprobación/recibo; consumidor solo de intenciones confirmadas y separación entre contenido aplicado y purga pendiente. Envolver exclusivamente la callback LiteSpeed auditada, restaurarla en finally y preservar operaciones ajenas. Probar rollback sin intención ejecutable, caída después de COMMIT, duplicados, fallo de purga y carreras en WordPress/SQL aislados antes de proponer un paquete real. El código experimental no debe llamar directamente al consumidor desde el guardado ni tratar una purga fallida como rollback de contenido.
