# ZIP de propuestas privadas: laboratorio

```sh
python3 scripts/package-editorial-blog-proposals.py
sh tests/integration/wordpress-blog-proposals-release/run.sh functional
sh tests/integration/wordpress-blog-proposals-release/run.sh lifecycle
```

Usar proyecto fresco con BLOG_PROPOSAL_RELEASE_PROJECT y prefijo alexcuadra-blog-proposal-release- si se repite una prueba justificada. El runner detiene solo su proyecto al salir; volúmenes tmpfs efímeros, red internal, sin puertos y sin datos del hosting. No prune.

Instala el ZIP con el instalador normal y prueba sus archivos en wp-content/plugins. Mantiene activo el lector público sellado y nunca instala el viejo MU de herramientas de propuestas. prepare.py adapta únicamente namespace/prefijo/rutas/nombres de herramientas de los fixtures previos, en caché ignorada. Perfil con tema/drop-ins/WordPress/dependencias y esquema de almacenamiento admitidos.

Funcional:72+34+43casos nativos/fallos/HTTP y29de schemas cerrados. Ciclo: seis fases iniciales, deriva por índice inesperado/rechazo del repositorio/reparación del fixture y retirada con datos conservados; nueve recibos verificados. El modo probe solo lee metadatos del perfil en un laboratorio fresco.

Complementos en host: `python3 tests/editorial-blog-proposals/release-planner.py` (82casos contra planner del paquete), y release-guard.php para13escenarios de sitio en procesos aislados. [Recibo, hashes y límites](../../../docs/qa/MCP_BLOG_PROPOSAL_PACKAGE_2026-10-26.md).
