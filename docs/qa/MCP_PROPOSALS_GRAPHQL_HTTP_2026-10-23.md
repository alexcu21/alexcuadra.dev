# Privacidad GraphQL por HTTP — 23 octubre 2026

La consulta HTTPS anónima al endpoint real devuelve HTTP200 con cuatro errores explícitos de autenticación y sin data: RootQuery.project, contentNode, node y contentNodes. Resultado: acceso anónimo bloqueado. No demuestra filtrado de nodos ni paginación bajo una sesión autenticada; esos controles sí pasaron dentro de WordPress en el postflight anterior. No se usaron credenciales ni se cambió configuración, contenido o permisos. Sin SSH, Docker, descargas de respaldo ni borrados.

Verificador: `scripts/editorial-graphql-privacy.py`, solo query, endpoint/IDs fijos, TLS verificado, sin redirecciones, respuestas limitadas a1MiB, máximo10 páginas y pausa2s entre páginas. No imprime cuerpos ni contenido. No reintenta fallos. Distingue bloqueo HTTP401/403 y el conjunto exacto de cuatro errores GraphQL de autenticación; errores genéricos no cuentan como prueba. Ocho pruebas unitarias comprueban exposición por nodo/colección, campos ausentes, control público, errores, paginación/ciclos/límites y cambio de acceso.

El Python del equipo carecía de CA por defecto. Se usa el bundle existente `/etc/ssl/cert.pem` en macOS cuando falta el predeterminado, conservando comprobación de certificado/hostname. No se desactiva TLS ni se instala nada. Tras diagnosticar el bloqueo GraphQL se repitió la consulta con clasificación exacta; el resultado final usó una petición. No hubo429 ni reintentos automáticos.

Impacto local: scripts, pruebas e informes pequeños; unos35GiB libres al inicio. Máximo1MiB de respuesta en memoria, sin guardarla. No se modifica la política de conservación de respaldos. La edición de Projects permanece fuera del plugin instalado.

Siguiente incremento de edición: adaptador de escritura/efectos en un directorio experimental separado del laboratorio0.5 y de Proposals rc.2, preservando manifiestos y ZIP. Primero demostrar que tagline no invoca wp_update_post y que purgas se difieren hasta COMMIT mediante intención durable. No habilitar apply/recover reales ni ampliar scopes por esta comprobación HTTP. La prueba HTTP autenticada queda como cobertura pendiente, sin solicitar ni copiar credenciales.
