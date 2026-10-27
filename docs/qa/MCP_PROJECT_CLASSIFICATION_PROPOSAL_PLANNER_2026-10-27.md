# Planificador local de clasificación — 27 octubre 2026

Implementado PHP puro en `wordpress/experimental/editorial-project-classification-proposals/planner.php`, sin modificar paquetes sellados o registrar herramientas. Alcance: documento inicial privado revisable con antes/después por taxonomía/idioma/ID y etiquetas. Sustitución de conjuntos explícitos, campos omitidos conservados y vacíos como retirada total. No almacenamiento, versiones posteriores, replay durable, aprobación o aplicación pública.

62 comprobaciones sintéticas pasan: EN/ES, IDs exactos, separación de taxonomías, campos cerrados, límites/listas/UTF-8, autorización proyectada, invariancia de entradas, canonicalización, vínculo al propietario, huellas del documento y detección de cambio en asignaciones/catálogo/etiquetas/traducciones. Se rechazan taxonomías del blog y mapeo automático entre idiomas. [Recibo](project-classification-proposal-planner-2026-10-27.json).

La prueba inicial detectó una expectativa sensible al orden de claves del fixture; se corrigió la comparación para normalizar mapas y conservar orden estricto de listas. No se alteró el contrato de producción. Comandos: PHP lint en ambos archivos, prueba CLI y `git diff --check`.

Límites: no WordPress/Polylang/HTTP/DB reales, atomicidad de catálogo o permisos nativos. El futuro adaptador debe aportar proyecciones confiables completas del lector, nunca argumentos del cliente; verificar visibilidad/reciprocidad y detectar inconsistencias. Las etiquetas requieren escape al mostrarlas. Una huella no equivale a aprobación o autenticidad criptográfica.

No se llamó al CMS, cambió fuente/asignación, concedió permisos, creó copia o propuesta real ni desplegó código. Catálogo MCP20, piloto blog1/v1 y respaldos existentes conservan el último estado verificado. Siguiente: persistencia privada con versiones/replay y pruebas de aislamiento/concurrencia, antes de empaquetar o conectar MCP.
