# Repetición idempotente y lectura por idioma — comprobación real

Resultado:9comprobaciones correctas por MCP real, sobre revisión1 existente. [Recibo](MCP_OPERATING_NATIVE_IDEMPOTENCY_2026-10-24.json). Se reutilizaron exactamente los parámetros/request_id605030ef5efbfbd1e15f9b0d127f281d del plan autorizado; no se generó otra solicitud lógica ni se preparó una revisión nueva con un identificador distinto.

Preflight:usuario2/subscriber, inspección positiva de revisión1/EN con hash original y caducidad1792866125, propuesta227 en versión/base previstas y borrador sin aplicar. Se llamó prepare una vez como repetición exacta. Devolvió el mismo recibo:review_id1, hash `ecaf5b7835e98fb157e70e852aa718322e8df5f6d82c807040b1489f673e9738` y expires_at1792866125. El código sellado devuelve el recibo persistido antes de la ruta INSERT cuando encuentra la misma solicitud/hash.

Pasaron:

1. Recibo original devuelto íntegramente en sus campos previstos.
2. Revisión aún legible en EN.
3. Inspección completa idéntica antes/después, incluidos valores privados comparados solo en memoria.
4. Hash original conservado.
5. Caducidad original conservada; el replay no la renueva.
6. Inspección ES rechazada.
7. La respuesta ES no devolvió documento:solo `Editorial operation rejected.` con isError=true.
8. DTO completo de la propuesta227 idéntico antes/después.
9. Propuesta en borrador y applied=false.

Total:7llamadas execute(1identidad,3inspecciones,2lecturas de propuesta,1replay de preparación). Hubo2llamadas prepare a lo largo de las dos fases, ambas con una única clave lógica y un único recibo/review_id observado. No se consultaron conteos SQL globales ni cuota después del replay:la afirmación de no repetir la creación se apoya en la revisión preexistente, el recibo idéntico y la ruta de código verificada; no se presentan conteos nuevos como observados.

No afirmar error404 exacto, código no devuelto por el conector, privacidad de otras cuentas/anonimato o aplicación/recuperación productiva. Tampoco se prueba aquí el rechazo de una aplicación caducada:se verificó que el recibo no cambia su expiración. No se ejecutaron approve/apply/recover/consumidor ni una actualización de propuesta. No se reconstruye ni resuelve la diferencia histórica postmeta y no se afirma preservación global de toda la BD.

Sin SSH, Docker, descargas ni respaldo nuevo.30GiB libres redondeados antes/después; la variación frente a31GiB del incremento anterior no se atribuye a estas llamadas. Solo evidencias de metadata pequeñas; valores de patch/documento omitidos. No se repitió el conjunto de pruebas sintéticas del candidato sin cambios. No borrar ni regenerar la revisión al caducar.
