# Conexión MCP WordPress — 19 octubre 2026

Actualización20 octubre: conexión nativa y lectura editorial ya operativas. Editorial Read0.1.0 instalado por autorización de Alex; usuario2/subscriber recibe solo la capacidad específica. Inventario actual5 abilities, mediante los mismos3 meta-tools:3 core+list/get de proyectos públicos. [Instalación/aceptación actuales](MCP_EDITORIAL_INSTALLED_2026-10-20.md). Las limitaciones de inventario inferior corresponden al historial anterior; escritura/ACF siguen sin exponerse.

Endpoint: `https://content.alexcuadra.dev/wp-json/mcp/mcp-adapter-default-server`.

Configuración local wordpress habilitada, comando npx con argumentos separados `-y`, `@automattic/mcp-wordpress-remote`. Credencial dedicada gestionada por Alex; no registrar valores ni reutilizar credenciales del frontend.

Se observó aviso administrativo por dos copias del adaptador cargadas. Tras la desactivación de Hostinger AI realizada por Alex, el inventario muestra Hostinger AI inactivo, MCP Adapter0.7.1 activo y desaparece el aviso. HTTP initialize pasa de403 a200 sin ampliar permisos del usuario. Esto apunta a interferencia del proveedor anterior, sin auditoría de su implementación.

Comprobación HTTP autenticada de solo lectura correcta con protocolo2025-11-25, sesión Mcp-Session-Id y cabecera MCP-Protocol-Version. tools/list devuelve mcp-adapter-discover-abilities, mcp-adapter-get-ability-info y mcp-adapter-execute-ability. No se ejecutaron capacidades ni se modificó contenido.

Límite: estas herramientas todavía no aparecen en el inventario nativo de esta sesión de Codex. Validar carga tras reiniciar el servidor MCP; un endpoint funcional no demuestra que el chat ya tenga herramientas disponibles. La prueba manual del proceso con protocolo2025-03-26 devolvió error de protocolo; no clasificarlo como fallo de credencial. Verificar negociación del cliente real si persiste al recargar.

La presencia de la herramienta execute-ability no garantiza edición de proyectos/ACF/Polylang. Descubrir e inspeccionar las capacidades efectivas antes de usarlas. Sin cambios de contacto, Worker o permisos del usuario.

## Verificación nativa posterior

Tras reinicio, las tres meta-tools ya aparecen en el chat. discover-abilities funciona y enumera solo core/get-site-info, core/get-user-info y core/get-environment-info. get-ability-info de core/get-site-info funciona. Ejecutarla con fields=name,url,wpurl,version devuelve Permission denied. Por tanto conexión y descubrimiento operativos; ejecución de esa capacidad denegada al usuario configurado. No se consultó admin_email ni se ejecutaron capacidades de escritura. No hay capacidades publicadas de proyectos, ACF o Polylang en este inventario. Siguiente paso: definir/exponer herramientas específicas de contenido con permisos acotados; no basta con instalar el adaptador ni se justifica convertir el usuario en administrador.
