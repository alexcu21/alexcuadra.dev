# Validación y tratamiento de campos — 26 octubre 2026

## Conclusión

Los datos del formulario se validan en el servidor y se envían como texto plano. No se acepta HTML enriquecido ni se confía en los límites del navegador como control suficiente. Normalizar/validar texto y sanitizar HTML son operaciones diferentes: no se borra código que alguien quiera incluir en una consulta técnica; se conserva como texto sin interpretarlo.

Fuentes contrastadas: [OWASP Input Validation](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html) recomienda reglas por campo y preservación de puntuación/Unicode legítimos; [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) distingue validación, codificación por contexto y sanitización de HTML, y describe textContent como destino de texto.

## Recorrido revisado

| Frontera | Control observado |
| --- | --- |
| Navegador | Campos obligatorios, tipos/maxlength; petición JSON al endpoint propio, sin credenciales del visitante. Avisos mediante textContent. |
| HTTP/lector | POST, origen configurado exacto, Content-Type JSON, límite16KiB incluso si Content-Length miente; UTF-8 estricto y timeout5s de lectura. |
| Esquema | Solo name/email/message/locale/turnstileToken; tipos string y campos requeridos; límites100/254/5000 y token2048. Longitudes en unidades UTF-16, como maxlength del navegador. |
| Texto | NFC, recorte de espacios exteriores, saltos de línea normalizados; nombre/correo de una línea. No eliminación indiscriminada de apóstrofos, acentos, alfabetos o código escrito por el usuario. |
| Correo | Validación conservadora del formato; dirección sin display name/cabeceras. Validar formato no demuestra propiedad ni existencia del buzón. To/From/asunto fijados en servidor; Reply-To solo desde el correo validado. Contenido en propiedad text, nunca html/headers/cc/bcc del visitante. |
| Persistencia | No inserción del cuerpo en WordPress/SQL. Solo HMAC y fecha en SQLite, con parámetros de consulta. |
| Registros propios | Códigos/fases/duraciones/requestId; no valores de campos ni errores crudos. Los logs automáticos de Cloudflare pueden contener metadatos/IP y no se presentan como logs sin datos personales. |

Turnstile, cuotas, deduplicación y CSP siguen como capas adicionales; no sustituyen la validación ni el uso correcto de los datos al mostrarlos. Si se añade una bandeja o plantilla HTML, será necesario codificar esos datos en su contexto de salida; el formato actual de texto no certifica una futura vista HTML.

## Refuerzo implementado

Se rechazan controles C1 y sustitutos Unicode aislados, además de los controles C0/DEL ya cubiertos. La comprobación ocurre antes de trim: un salto de línea final en nombre/correo ya no desaparece silenciosamente. En el mensaje se conservan tabulaciones y saltos válidos; CR/CRLF y separadores U+2028/U+2029 se normalizan a LF. En campos de una línea, esos separadores se rechazan.

No se rechaza todo Unicode ni se aplica normalización de compatibilidad: nombres con acentos, apóstrofos, chino, árabe y emoji con modificadores/ZWJ siguen siendo válidos. Un mensaje con etiquetas o consultas SQL continúa siendo texto literal. No se añaden campos ni cambios en destino, correo HTML, almacenamiento, flags, secretos o permisos.

## Verificación

- **324 pruebas unitarias** correctas. Cuatro regresiones nuevas: conservación de texto internacional/código, controles y Unicode mal formado, salida estructurada sin HTML/cabeceras y rechazo antes de servicios externos.
- **Siete integraciones de runtime** correctas. La nueva verifica rechazo de texto inválido antes del challenge/email y aceptación de Unicode válido en el binding nativo local de correo; egreso real bloqueado, solo datos ficticios.
- Build correcto con0errores/0advertencias. No hay cambios visuales, por lo que no se repite la revisión de diseño ya realizada para las páginas de contacto.
- No se enviaron ataques ni mensajes a servicios de correo reales. No es una prueba de penetración ni una certificación de todo el sitio.

## Despliegue

Pendiente de registrar la publicación de staging; CONTACT_FORM_ENABLED=false. Producción sigue en `feba64f8-5406-4c69-8522-6c3753681a37`. No se reabre el piloto ya confirmado ni se pide otra recepción.

Reversión: versión previa del Worker de staging; no hay migración ni cambio de secretos. Texto legítimo normalizado de la misma forma mantiene su huella de deduplicación. La nueva canonicalización de separadores de párrafo solo afecta mensajes que los contengan; no se invalidan reservas globalmente.

Próximo alcance editorial: contrato de lectura/propuestas MCP para posts, traducciones y taxonomías. Preparación de contacto en producción permanece separada de su activación permanente.
