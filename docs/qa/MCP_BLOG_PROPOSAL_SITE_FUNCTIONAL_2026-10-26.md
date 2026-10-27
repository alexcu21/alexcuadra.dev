# Propuestas: pruebas con perfil ampliado — 26 octubre 2026

**149 comprobaciones funcionales correctas:**72WordPress/MariaDB/Polylang,34conexión/interrupción y43MCP/HTTP. Además46de arranque y7del protocolo de procesos. [Recibo con casos, política y hashes reales del candidato](blog-proposal-site-profile-functional-2026-10-26.json).

## Candidato y perfil

Se deriva en caché privada un candidato **0.1.0-lab.site.1**, exclusivamente local, desde el ZIP rc.1 verificado por SHAca33eb…635a. rc.1 y sus fuentes/ZIP/manifiesto se conservan intactos. El experimento exige flag propio, entorno local, DB_HOST=db y home/site HTTPScontent.alexcuadra.dev. No tiene admisión de producción y no genera ZIP distribuible.

Perfil de16plugins activos más el candidato: versiones y hashes de main files locales, dos MU de Hostinger y entradas del tema Twenty Twenty-Four1.6 contrastados con el inventario ya verificado. Se comprueba también el hash del guard de laboratorio. Los callbacks de inyección de fallos solo se admiten bajo ese guard experimental. No se modifica la lista de compatibilidad publicada de rc.1.

WP7.1.3/PHP8.5.11/MariaDB11.8.9, mismos digests de imágenes. El hosting declara PHP8.5.12: se ensaya la misma rama, no se certifica identidad exacta del runtime. Código del respaldo más tres ZIP sellados, base/configuración/usuarios/contenido sintéticos; sin importar SQL/wp-config/opciones/cuentas/licencias del hosting. Las condiciones dependientes de opciones reales, preview o cachés externos siguen como límites.

## Qué pasó

- CRUD de planes privados, historia inmutable, versiones/CAS y replay exacto, cuotas, rollback y conservación de tablas editoriales durante operaciones. Traducciones son intenciones sin ID/post/enlace/publicación; destinos vinculados draft/private bloquean duplicación.
- Procesos WordPress separados con misma petición producen una sola revisión; dos versiones concurrentes tienen un ganador. KILL/cierre/sustitución de mysqli, cambio del modo de errores, control de transacción y orden del guard fallan sin registros privados parciales. SIGKILL anterior revierte y posterior al commit conserva recibo/replay.
- Credenciales/sesiones MCP reales de prueba, contexto publicado con scope privado, propietarios diferentes, campos ajenos, historia privada, credencial revocada y scope revocado rechazados. Las credenciales y scopes sintéticos se retiraron según los recibos de las tres fases.
- Entradas/salidas cerradas de rc.1 se mantienen sin cambios en el experimento; no se afirma que se repitieran los29casos específicos del incremento anterior.

El listado de46checks de arranque precede a la instalación del experimento. Las149operaciones son posteriores; no atribuir las149al rc.1 cerrado sobre perfil mínimo ni declarar aceptación del hosting por ese resultado.

## Harness y diagnósticos

Dos ejecuciones se bloquearon en sincronización de workers (padre/hijo esperando lectura), y se detuvo únicamente su contenedor CLI antes de parar el proyecto. Se sustituyeron las tuberías de diagnósticos por archivos privados, marcadores con plazo45s, límite de8MiB y limpieza de procesos; los workers de test vacían sus buffers antes de anunciar disponibilidad. Esto no cambia el código de operaciones del candidato. Siete pruebas independientes cubren ejecución normal, mucho stdout/stderr, buffers, salida temprana y resultados duplicados/inválidos.

El entorno habilita solo HTTP interno a wordpress y mantiene bloqueados egress/correo. Los fixtures se copian al volumen con usuario del contenedor; no se amplían permisos de la caché privada del Mac. WP_DEBUG sigue activo, display desactivado y log conservado privadamente:16.074bytes/86líneas,66Deprecated y2Notice, cero Warning/Fatal detectados. No se ocultaron por bajar error_reporting; no se presenta el ensayo como libre de avisos. La salida ordinaria incluyó mensajes de purga de LiteSpeed en los fixtures de creación/edición; no equivale a certificar los efectos de caché del hosting o del escritor público futuro.

Proyectos funcionales y de diagnóstico confirmados detenidos; PortBindings={} y red interna propia reutilizada nuevamente vacía. Sin prune, SSH al hosting, correos reales, cambios del CMS o deploy. No se repiten auditorías de postmeta ni operaciones sobre propuesta227/revisión1.

## Dato que falta para preparar una versión distribuible

El recibo real anterior no incluía hashes de main files de plugins. Se genera un diagnóstico **autocontenido de solo lectura**, [blog-proposal-code-fingerprint.php](../../wordpress/operations/blog-proposal-code-fingerprint.php), con expectativas derivadas del código ensayado. [Prueba nativa](blog-proposal-code-fingerprint-lab-2026-10-26.json):16main files/versiones, MU, tema, núcleo y ausencia del scope nuevo coinciden en el laboratorio. No depende de los otros dos PHP de preflight.

Imprime BLOG_PROPOSAL_CODE_FINGERPRINT_RESULT y codeMatchesLocalFixture; **installationApproved siempre false**. No instala, concede capacidades o lee cuerpos/credenciales. No verifica árboles completos de plugins y no es una auditoría global de integridad. Las huellas de entradas no justifican cambiar componentes del hosting para forzar coincidencia.

Alex puede subir solo ese PHP a private-tools y ejecutar el [comando del procedimiento](../WORDPRESS_MCP_BLOG_PROPOSAL_PACKAGE.md). Tras revisar la salida: preparar candidato distribuible separado de rc.1 con admisión del perfil contrastado y cerrar respaldo/restauración/instalación/scope por fases. No instalar el experimento ni rc.1 ahora. No hay approve/apply/recover/publicación certificadas ni pérdida del acuse TCP del propio COMMIT reproducida.

## Actualización: resultado del servidor recibido

Alex aportó el diagnóstico:11checks correctos,16main hashes/versiones, dosMU, entradas del tema y wpdb coinciden. [Recibo transcrito y procedencia](blog-proposal-code-fingerprint-destination-2026-10-26.json). Solo difiere del recibo de laboratorio PHP8.5.12 y laboratoryFixtureIgnored=false. No se ejecutó una nueva consulta remota independiente ni se verificaron árboles completos. Nuevo scope ausente, ocho capacidades conservadas, installationApproved=false. La espera de huellas queda cerrada; siguiente candidato distribuible y ensayo de admisión/respaldo/restauración, sin instalar rc.1 ni el experimento.
