# Smoke nativo real de Proposals rc.2

Autorización previa de Alex vigente. Identidad MCP2/subscriber. Se creó únicamente la propuesta227 sobre146EN, se actualizó y se restauró su revisión inicial como nuevo head. Tres revisiones enlazadas; siempre draft/applied=false. Lectura e historial correctos; idioma incorrecto y versión antigua rechazados. Repetir la creación con la misma clave devolvió el DTO inicial y conservó el head restaurado.

El informe JSON contiguo conserva solo metadatos. Claves de idempotencia y payloads permanecen fuera del repositorio. Tres solicitudes de escritura únicas; confirmar ledger1/3 en postflight antes de cerrar. Una propuesta ocupa1/8 del límite y tres solicitudes3/128; sin eliminación automática.

Pendiente: salida del postflight final ejecutado por Alex para huellas de14 Projects, baseline de permisos/dependencias y privacidad desde CLI. No se afirma verificación HTTP GraphQL ni cierre del despliegue. El agente no ejecutó SSH. Sin descarga de respaldo ni crecimiento local significativo: solo scripts e informes pequeños.
