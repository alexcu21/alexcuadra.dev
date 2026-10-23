# Candidato de empaquetado operativo local

Versión0.1.0-rc.1. [ZIP](../artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.1.zip), [manifiesto](../artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.1.manifest.json), [QA](qa/MCP_LOCAL_PACKAGE_2026-10-23.md). productionReady=false/localOptinOnly=true. No instalar en el CMS compartido: conserva guardas de laboratorio y falta admisión de destino.

Construcción reproducible: python3 scripts/package-editorial-operating.py. El constructor rechaza modificar una versión sellada; cambiar versión/plan de release antes de generar bytes distintos. El ZIP contiene solo código/bootstrap/README/inventario; el runner verifica hashes/rutas y ejecuta los módulos extraídos. No se incluyen datos privados, herramientas de prueba o backup.

Activación y desactivación como plugin normal ya probadas localmente, sin cambios de baseline: [QA](qa/MCP_PACKAGE_LIFECYCLE_2026-10-23.md). La admisión productiva sigue pendiente; rc.1 permanece solo local.

Plan de reversión para el candidato final:

1. Suspender entradas operativas/MCP y consumidor; coordinar escritores externos y drenar operaciones. No borrar tablas, revisiones, recibos, archivos privados o respaldo.
2. Desactivar únicamente el plugin operativo y retirar su opt-in; read y proposals rc.2 permanecen en sus versiones actuales. Revocar solo los scopes que se hayan añadido expresamente en esa instalación, usando baseline comprobada; no tocar rol subscriber ni grants preexistentes.
3. Verificar lectura pública EN/ES, denegación de aplicación/recuperación, roles/caps y plugin/source hashes esperados. Una desactivación no revierte ediciones públicas ya confirmadas.
4. Si hay que revertir contenido, preparar y revisar la inversa exacta y obtener aprobación independiente; si hay conflicto, preservar el estado del editor y detener la recuperación. Restaurar el backup completo requiere plan/validación distintos, no rollback ciego de SQL.

Este plan no se ha ejecutado en destino ni autoriza cambios. Pendiente salida del preflight de Alex y admisión/preflight/reversión de la versión apta para destino antes de pedir aprobación.
