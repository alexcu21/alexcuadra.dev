# Autenticación de WordPress desde Workers

Estado: transporte preparado; pendiente crear credenciales y validar en staging. No activar todavía la restricción de WPGraphQL. La autenticación no evita necesariamente el limitador de infraestructura de Hostinger.

## 1. Crear una cuenta de servicio

En WordPress → Usuarios → Añadir nuevo, crea una cuenta dedicada, por ejemplo `portfolio_staging_reader`, con rol **Suscriptor**. Usa una contraseña de acceso fuerte y guárdala en tu gestor. No usar una cuenta administradora ni dar permisos de edición para resolver consultas fallidas. Si un CPT exige permisos adicionales, revisaremos su registro antes de ampliar el rol.

Abre el perfil de esa cuenta → **Contraseñas de aplicación / Application Passwords**. Crea una llamada `Portfolio Worker staging`. Copia la contraseña generada en tu gestor; WordPress solo la muestra una vez. Esta es la credencial que usará el Worker, no la contraseña de inicio de sesión.

Si la sección no aparece, detente e informa qué muestra el perfil: HTTPS o un plugin de seguridad pueden afectar su disponibilidad. No instalar otro plugin de autenticación por defecto.

## 2. Guardar secretos en Cloudflare

Workers & Pages → `alexcuadra-dev-staging` → Settings → Variables and Secrets (variables del **runtime**, no las de Builds). Añade como **Secret**:

- `WPGRAPHQL_USERNAME`: usuario de la cuenta de servicio.
- `WPGRAPHQL_APPLICATION_PASSWORD`: contraseña de aplicación generada.

Mantén `WPGRAPHQL_AUTH_ENABLED` ausente o con valor `false` mientras guardas ambos secretos. Guardar/desplegar la configuración según indique Cloudflare. Nunca compartir los valores por chat, capturas o Git.

Para desarrollo local, los mismos nombres van en `.dev.vars`, ignorado por Git. No usar prefijos `PUBLIC_`. El build y CI no necesitan credenciales.

## 3. Validación coordinada antes de restringir GraphQL

Cuando los dos secretos estén guardados, avisa sin compartirlos. Activaremos `WPGRAPHQL_AUTH_ENABLED=true` en runtime y comprobaremos las rutas de inicio, proyectos, artículos y About, además de una consulta autenticada de identidad para confirmar que WordPress reconoce al usuario. Una página HTTP 200 por sí sola no demuestra autenticación si todavía se permiten consultas anónimas.

La implementación aplica las credenciales a todas las consultas del cliente desde el servidor; no toma credenciales del visitante. Exige HTTPS, falla con 503 si la configuración activa es incompleta, no registra secretos y no reintenta errores automáticamente.

La consulta de Home incluye `viewer { databaseId }` para verificar identidad sin añadir otra petición. Con autenticación activa, una identidad nula produce 503 `CMS_IDENTITY_UNCONFIRMED`. Una identidad válida genera el evento `cms_auth_verified` y la cabecera `X-CMS-Auth: verified`; el identificador se elimina antes de devolver los datos al template y nunca se registra. Sin autenticación activa no se emite esa confirmación.

**La caché compartida se omite con autenticación.** La deduplicación dentro de cada petición continúa. Antes de activar, evaluaremos la mayor carga potencial; la reutilización entre peticiones autenticadas requiere otro contrato de contenido público y pruebas contra borradores, contenido privado y protegido con contraseña. No basta con comprobar que existen campos en la respuesta.

Solo tras validar la identidad y los permisos, activar en WPGraphQL «Limit the execution of GraphQL operations to authenticated requests». Confirmar que la consulta anónima se rechaza y las rutas del Worker funcionan. Esto afecta también a producción Pages, desarrollo y cualquier otro consumidor del mismo CMS: **inventariarlos y adaptar todos antes de activar la restricción global**.

## Reversión

Si falla antes de restringir el CMS, volver a `WPGRAPHQL_AUTH_ENABLED=false`. Si ya está restringido y es necesario volver al acceso anónimo, primero desactivar la restricción de WPGraphQL. Revocar únicamente la contraseña de aplicación afectada en el perfil de servicio si se filtra; crear otra y actualizar el secreto.

## Fuentes

- [WPGraphQL: Authentication and Authorization](https://www.wpgraphql.com/docs/authentication-and-authorization)
- [WordPress: Application Passwords Integration Guide](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/)
- [Cloudflare: Secrets](https://developers.cloudflare.com/workers/configuration/secrets/)
- [Astro: integración Cloudflare](https://docs.astro.build/en/guides/integrations-guide/cloudflare/)
