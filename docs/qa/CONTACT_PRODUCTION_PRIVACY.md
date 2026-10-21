# Privacidad: producción conectada y Home actualizado

18 de octubre de 2026. Worker `alexcuadra-dev`, versión `0739b280-1c56-436d-ab28-603970271293`. `/privacy/` consume la página EN 180 publicada y responde HTTP 200 en [alexcuadra.dev](https://alexcuadra.dev/privacy/).

`CONTACT_FORM_ENABLED=false` explícito en producción. El artefacto mantiene entrypoint oficial de Astro, exports vacíos y cero bindings Durable Object; no incorpora los bindings de contacto de staging ni habilita envío en producción. Se preservaron el dominio y las credenciales runtime existentes mediante el despliegue normal.

Validación: 133 pruebas, build de producción, dry-run y smoke remoto de ocho páginas correctos. El smoke incluye autenticación CMS, CSP/hashes, SEO, renderer editorial, privacidad sin marcadores y contrato 404/CONTACT_DISABLED/no-store; también favicon y dos controles 404. No aparece formulario/widget en Home. Revisión en navegador y [captura](privacy-production-render.png).

Después de verificar el destino se actualizó Home ID 19 en WordPress, únicamente los tres campos editoriales de privacidad:

- privacy_url: `https://alexcuadra.dev/privacy/`
- privacy_link_label: `Read the contact privacy notice`
- privacy_text: `Your name, email address and message are used to respond to your enquiry. Messages are kept for 30 days.`

Los dos últimos reemplazan textos provisionales anteriores. La interfaz del formulario consume esos valores del CMS al habilitarse; no se introducen copias editoriales en el componente. El guardado de Home fue confirmado en UI y la URL se verificó mediante GraphQL en la auditoría posterior.

`contact:readiness` termina ahora con exit 0: staging configurado/desactivado, aviso propio 200 en ambos entornos, noindex solo en staging y enlace editorial correcto. `activationReady` y `runtimeVerified` continúan false: falta piloto real de widget/envío y no se declara recepción Gmail. No hubo correo ni challenge durante este incremento.

Referencia anterior de producción: versión `efda03ba-b1be-4965-b34a-74ec34085f17`, deployment `61bc9820-8d0d-414c-a3e7-08a4e9987744`. Solo se guardó referencia; no se ensayó rollback. [Registro resumido](CONTACT_PRODUCTION_PRIVACY.json).

Siguiente paso: piloto temporal autorizado de staging según CONTACT_STAGING_PILOT.md; producción permanece con contacto desactivado. ES está publicada en WordPress, sin ruta ES frontend nueva en este incremento.
