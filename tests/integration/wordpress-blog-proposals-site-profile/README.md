# Arranque del perfil ampliado

Preparar código local con scripts/prepare-blog-proposal-site-code.py y --archive apuntando al respaldo privado ya existente. El script admite únicamente su tamaño/SHA conocidos, verifica cada archivo seleccionado y excluye backup/ y wp-config. No invoca SSH o PHP del respaldo. Código/procedencia quedan en caché privada ignorada.

Después ejecutar `BLOG_SITE_PROFILE_PROJECT=alexcuadra-blog-site-profile-20261026-r6 python3 tests/integration/wordpress-blog-proposals-site-profile/run.py` solo para una repetición justificada, con un nombre nuevo si hace falta. Requiere la red interna propia del laboratorio de paquete indicada en el runner, vacía y con etiquetas verificadas; no reutiliza redes ajenas ni publica puertos. Usa base sintética, código copiado a tmpfs768MiB, BD256MiB y un guard de HTTP/correo. Configuración y overlays sintéticos van en caché compartida con Docker, sin permisos globales nuevos.

46casos de arranque/inclusión/versiones/procedencia y reproducción del bloqueo del preflight. No instala el candidato ni concede el scope nuevo; no prueba CRUD, aprobación o publicación. Al finalizar detiene solo su proyecto y conserva recursos sin prune. [Evidencia y límites](../../../docs/qa/MCP_BLOG_PROPOSAL_DESTINATION_PROFILE_2026-10-26.md).
