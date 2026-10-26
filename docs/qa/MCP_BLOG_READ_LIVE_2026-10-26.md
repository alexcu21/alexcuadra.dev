# Lector MCP del blog: cierre real — 26 octubre 2026

**Completado:** paquete0.1.0-rc.1 instalado y activo en el CMS; usuario2 con capacidad directa alexcuadra_read_public_posts. Las dos herramientas funcionan desde el conector real. No se requiere repetir instalación, activación o grants.

## Evidencias separadas

Alex ejecutó los comandos en el hosting y aportó los cuatro recibos: [before](editorial-blog-read-destination-before-2026-10-26.json), [after-install](editorial-blog-read-destination-after-install-2026-10-26.json), [after-enable](editorial-blog-read-destination-after-enable-2026-10-26.json) y [after-scope](editorial-blog-read-destination-after-scope-2026-10-26.json). Todos ready=true con14checks correctos; el último añade únicamente el scope nuevo a la lista efectiva. Subscriber/read, los cuatro scopes anteriores de proyectos y las16denegaciones de edición/administración se conservan. Sin grants a roles u otros propietarios según el diagnóstico.

Raíz correcta confirmada: `/home/u776991923/domains/alexcuadra.dev/public_html/content`. La instalación del public_html superior declara otro dominio y fue descartada antes de instalar. El archivo del diagnóstico está en private-tools fuera del directorio público. No se cambiaron las URLs de WordPress para superar el guard.

El paquete coincide con SHA256 d8f8fcb1dcc83962036a70c3fb0e3a57e44dbeb96b5556e8abe1dcb7fbd205e6. WP7.1.3/PHP8.5.12/Polylang3.9.1/Adapter0.7.1; PHP es un parche posterior al ensayo local8.5.11, dentro de la rama admitida por el diagnóstico.

## Comprobación independiente por MCP

[Recibo del conector](editorial-blog-read-live-mcp-2026-10-26.json). Una llamada de descubrimiento, dos de esquemas, dos de listado y tres get; ocho llamadas sin mutaciones solicitadas:

- Catálogo13abilities: las11anteriores más list-public-posts y get-public-post. Esquemas de entrada cerrados y anotaciones readonly/idempotent/destructive=false.
- Listados EN/ES: dos entradas por idioma y next_page=null. Relaciones recíprocas239EN↔228ES y241EN↔204ES.
- DTO limitado a id/title/slug/locale/translations/categories/tags. Categorías y etiquetas con taxonomy e idioma correspondientes; relaciones públicas de traducción presentes.
- get239EN y get228ES coinciden exactamente con sus elementos del listado.
- get239ES se rechaza con “Public blog read unavailable.” El conector no expone código HTTP; no afirmar404 observado.

## Alcance y límites

Los comandos de instalación/configuración/activación/grant fueron ejecutados por Alex. El agente hizo lecturas mediante MCP, sin SSH al hosting ni credenciales nuevas. No se probaron revocación real, borradores privados o desactivación en el hosting: esas pruebas permanecen documentadas como laboratorio, sin repetir mutaciones por demostración.

No hay huella global nueva ni certificación de conservación de toda la BD. Incidencia histórica de postmeta, propuesta227 y revisión1 intactas por este trabajo, sin reabrir auditorías. No hubo edición de cuerpos, publicación CMS, cambios frontend/contacto, despliegue o correo.

Este lector permite inventariar metadatos y relaciones; la capacidad editorial de escritura todavía falta. Siguiente incremento: contrato y pruebas locales de propuestas privadas para posts/traducciones, con revisión de cambios y aprobación/aplicación separadas. También siguen pendientes taxonomías propias de proyectos, promoción del frontend reciente y configuración permanente de contacto; ver [inventario](../NEXT_INCREMENTS_2026-10-26.md).
