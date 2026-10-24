# Descubrimiento y esquemas operativos por MCP real

Verificación completada contra el conector nativo WordPress:una llamada discover y seis get-ability-info. Catálogo17abilities; las6operativas aparecen y sus esquemas de entrada/salida y metadatos coinciden exactamente con el módulo operating-abilities.php del ZIP rc.3 sellado. [Recibo](MCP_OPERATING_NATIVE_DISCOVERY_2026-10-23.json).

El ZIP y el módulo se verificaron contra su manifiesto/SHA; PHP local ejecutó únicamente la función pura operating_ability_specs para producir el contrato esperado. La comparación normalizó el orden de claves de objetos y mantuvo el de arrays. Coinciden readonly/destructive/idempotent, public=false, show_in_rest=false y mcp public/type. mcp.public indica exposición al adaptador: no prueba autorización anónima.

No hubo llamadas execute-ability, revisiones/propuestas nuevas, aprobación de diff ni aplicación/recuperación. No se reconsultó identidad del conector ni se probó privacidad de otros usuarios/anonimato. La lectura del catálogo no certifica ejecución de las operaciones, guardas durante una ejecución, respuesta de caché ni edición productiva. No se afirma conservación global de toda la BD después de autenticarse por HTTP: la autenticación puede tener su propia contabilidad; no se solicitaron mutaciones editoriales.

No SSH, Docker, descargas ni respaldo nuevo. Solo archivos de evidencia pequeños locales;32GiB libres redondeados. Esta verificación no resuelve la diferencia histórica de postmeta ni sustituye la baseline del grant. La auditoría de almacenamiento sigue completada; el diagnóstico acotado de locks quedó inconcluso con0candidatos/0intentos.
