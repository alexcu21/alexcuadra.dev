# Smoke nativo real de Proposals rc.2

Autorización previa de Alex vigente. Identidad MCP2/subscriber. Se creó únicamente la propuesta227 sobre146EN, se actualizó y se restauró su revisión inicial como nuevo head. Tres revisiones enlazadas; siempre draft/applied=false. Lectura e historial correctos; idioma incorrecto y versión antigua rechazados. Repetir la creación con la misma clave devolvió el DTO inicial y conservó el head restaurado.

El informe JSON contiguo conserva solo metadatos. Claves de idempotencia y payloads permanecen fuera del repositorio. Tres solicitudes de escritura únicas; ledger1/3 confirmado por el postflight final. Una propuesta ocupa1/8 del límite y tres solicitudes3/128; sin eliminación automática.

Postflight final ejecutado por Alex y recibido completo:14 Projects sin cambios, baseline de permisos/dependencias correcto y privacidad desde CLI para2/1/0. Ledger1/3,2162 bytes lógicos. Despliegue validado en el alcance acordado; GraphQL HTTP no verificado. Ver `MCP_PROPOSALS_LIVE_POSTFLIGHT_2026-10-23.json`. El agente no ejecutó SSH. Sin descarga de respaldo ni crecimiento local significativo: solo scripts e informes pequeños.
