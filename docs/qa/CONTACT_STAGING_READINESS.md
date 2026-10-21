# Contacto: auditoría remota previa — 18 octubre 2026

## Estado posterior al despliegue y alta HMAC

La auditoría actual confirma todos los checks de metadatos de staging, incluido CONTACT_DEDUP_SECRET. El endpoint responde 404/CONTACT_DISABLED/no-store y la ruta propia de privacidad staging responde 200/noindex. La ruta de producción sigue 404 y el enlace editorial de Home no corresponde aún al portfolio. La comprobación distingue esas tres superficies; exit 2 sigue siendo correcto hasta resolver las conexiones pendientes y no implica que falte todavía HMAC. No se envió correo. El resultado anterior inferior es histórico.

Comando nuevo: `npm run contact:readiness`. Consulta exclusivamente staging mediante comandos de lectura de Wrangler, endpoint GET y privacidad pública del CMS. No despliega, no cambia secrets/DNS, no resuelve challenges, no reserva capacidad del coordinador y no envía correo. Datos resumidos en [CONTACT_STAGING_READINESS.json](CONTACT_STAGING_READINESS.json); no se conservan valores de secrets, destinatario, variables privadas, autor ni metadatos crudos de cuenta.

## Resultado observado

Una versión recibe 100 % del tráfico. En sus metadatos:

- Presentes: secrets `CONTACT_TO_EMAIL`, `TURNSTILE_SECRET_KEY` y sitekey pública esperada.
- Ausentes: bindings `CONTACT_EMAIL`, `CONTACT_RATE_LIMITER`, `CONTACT_COORDINATOR` y secret `CONTACT_DEDUP_SECRET`.
- No coincide la configuración esperada desactivada de `CONTACT_FORM_ENABLED`, `CONTACT_ORIGIN` y `CONTACT_FROM_EMAIL`; una comparación fallida no significa necesariamente que el formulario esté activo: también puede faltar la variable.
- `/api/contact` devuelve 404 genérica, sin código `CONTACT_DISABLED` ni cabecera `no-store`. El contrato nuevo no está desplegado.
- La política enlazada de ValexLinks devuelve HTTP 200/HTML sin redirección. Su contenido describe cuentas, enlaces cortos, autenticación y emails de acceso de otro producto. No cubre este formulario del portfolio. Alex eligió una página propia de alexcuadra.dev.

Por tanto **no está listo para activar ni para una prueba de envío real**. El exit code 2 del comando señala requisitos pendientes y es el resultado esperado en este estado; no implica fallo de las pruebas unitarias. Código 0 solo significaría coincidencia de metadatos, endpoint desactivado y URL de privacidad accesible; nunca aprobación editorial, runtime ni entrega certificada. `activationReady` permanece false.

Validación local del incremento: 133 pruebas correctas, Astro sin errores/warnings (seis hints previos), build de staging correcto y diff sin errores de whitespace. Las pruebas nuevas cubren redacción de metadatos privados, despliegues con tráfico repartido y exclusión de contenido CMS no publicable de `/privacy/`.

## Próximos pasos concretos

1. Revisar [CONTACT_PRIVACY_DRAFT.md](../CONTACT_PRIVACY_DRAFT.md), completar identidad/contacto públicos, proveedor/retención del buzón y configuración de logs/copias; publicar página EN en WordPress y obtener ID real.
2. Configurar ese ID como `CONTACT_PRIVACY_PAGE_ID` y probar `/privacy/` antes de cambiar el enlace editorial de Home. La ruta local ya está preparada; sin contenido configurado da 404.
3. Configurar secreto HMAC aleatorio exclusivo de staging (mínimo 32 caracteres), sin mostrarlo en chat/Git. Confirmar capacidad y costes de Durable Objects del plan y retención antes de provisionar almacenamiento remoto.
4. Build staging, revisar artefacto y desplegar con `CONTACT_FORM_ENABLED=false`. Volver a ejecutar auditoría y smoke; comprobar el namespace/límites sin dar por buena una simple lista de nombres.
5. Completar challenge y accesibilidad reales; prueba de entrega al destino verificado solo con autorización explícita. Activar después de cerrar criterios.

La presencia de un nombre secret no valida su valor. Los metadatos de bindings no prueban permisos de Email Sending, tipo SQLite del namespace remoto, disponibilidad de cuota, límites efectivos, hostname Turnstile ni entrega. El script inspecciona todas las versiones con tráfico si hay despliegue gradual; no certifica solo la más reciente ignorando las demás.

`npm run contact:readiness -- --privacy-text` muestra texto editorial público para revisión, limitado a 256 KiB de lectura y 12.000 caracteres de salida; no lo escribe al informe. La lectura no usa credenciales ni cookies del CMS al visitar la política externa.
