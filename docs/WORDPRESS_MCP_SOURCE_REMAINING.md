# Cierre del adaptador y edición operativa — alcance restante

Estado23 octubre2026. Proposals rc.2 ya está instalado y validado: no reinstalar, repetir grant o recrear propuesta227. La autorización existente no incluye aplicar cambios a Projects reales. El trabajo nuevo sigue separado en `wordpress/experimental/editorial-source-adapter`.

## Incremento actual: adaptador de fuente y efectos

Completado:39 checks sintéticos de frontera,54 del protocolo SQLite,58 de integración CLI WP/MariaDB/ACF/LiteSpeed y46 HTTP Apache. Las suites son alcances distintos; no sumar las repeticiones del runner como cobertura nueva. HTTP usa Application Passwords sintéticas, cuatro checkpoints SIGKILL y verifica la cabecera nativa de purga. Se prueba rollback antes de COMMIT, persistencia tras COMMIT y repetición de invalidación sin nueva escritura tras perder confirmación. [Evidencia HTTP/MariaDB](qa/MCP_SOURCE_EFFECTS_HTTP_2026-10-23.json).

**Falta para cerrar este incremento:** repetir la integración con el perfil completo verificado del sitio: plugins activos/inactivos, MU, tema y configuración relevante observada; comprobar Polylang/EN–ES, traducciones vinculadas y callbacks ajenos con los mismos fallos y postcondiciones. El lector de snapshot actual es una fixture EN, no el autorizador/lector bilingüe final. Corregir únicamente fallos que revele ese perfil. La prueba de dos plugins no certifica los demás contextos.

Criterio de cierre: guard de perfil y esquema; cuerpo/tagline/ambos y recuperación en CLI/HTTP; idioma/IDs/filas ajenas preservados; aprobación/recibo/intención confirmados juntos; rollback sin purga objetivo; recuperación tras cortes de proceso; callbacks restaurados y operaciones ajenas conservadas. Un perfil no admitido debe quedar cerrado y reportar un bloqueo concreto, no ampliar el laboratorio indefinidamente. No añadir nuevas funcionalidades para cumplir esta etapa.

Estimación después de esta entrega: **1 iteración para perfil completo; una segunda solo si aparecen fallos que corregir.** No incluye habilitar edición real.

## Para tener edición real instalable y validada

| Entrega pendiente | Iteraciones estimadas | Resultado verificable |
| --- | --- | --- |
| Perfil completo y correcciones acotadas | 1–2 | Cierre del adaptador anterior |
| Integración del contrato operativo y candidato | 2–3 | Enlace con propuestas existentes, aprobación independiente consumible y validación antes/después; scopes mínimos; cuotas/archivo/retención del journal; DTO versionado para separar contenido y efecto; ZIP/manifiesto y pruebas, preflight y reversión concreta |
| Instalación y aceptación autorizadas | 1 | Paquete/permisos/diff revisables y aprobados; Alex ejecuta SSH por fases, verificación real sin ampliar permisos implícitamente |

**Total estimado restante:4–6 incrementos de trabajo, incluyendo el perfil completo.** Son entregas de trabajo, no un número garantizado de mensajes o comandos. Esperas de resultados/aprobación o un fallo nuevo pueden aumentar las vueltas. Si cambia la estimación, registrar el bloqueo y el trabajo concreto adicional antes de sumar otra fase.

La entrega de invalidación en el hosting no se demuestra con Apache: sigue separada de la invocación correcta de la API y debe informarse como tal en la aceptación real. Una edición pública requiere su diff concreto aprobado; no se programa ahora ni se considera autorizada por «continúa».

## Almacenamiento y límites que se conservan

36GiB libres al inicio de la prueba HTTP. Imágenes cacheadas, red interna sin puertos, tmpfs512+768MiB por proyecto nuevo; RAM/swap posible. Contenedores detenidos al finalizar. Sin descargas de respaldos, SQL/config real, dependencias nuevas, SSH del agente, borrados de laboratorios/respaldo, cambios Workers o contacto. Las credenciales HTTP son de usuarios sintéticos y se mantienen dentro del laboratorio; no se piden ni copian credenciales reales.
