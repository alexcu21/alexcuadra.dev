# Instalación operativa rc.3 — fase inactiva para aprobación

Estado: preparada, no autorizada ni ejecutada en el hosting. El preflight aportado por Alex del24octubre00:50:30UTC (23octubre en Nicaragua) pasa11/11 y coincide con el candidato. [Resultado](qa/MCP_OPERATING_ADMISSION_LIVE_PREFLIGHT_2026-10-23.json). `installationAuthorized=false` se mantiene; una comprobación técnica no concede autorización de instalación.

Alcance de esta aprobación: crear una carpeta privada de staging, subir el ZIP sellado, verificar SHA256 e instalar únicamente `alexcuadra-editorial-operating` **inactivo**. Sin opt-in, activación, migración, grants, nuevas propuestas ni edición pública. No reinstalar Read o Proposals rc.2. El instalador bloquea una instalación existente y no tiene force ni activate. El siguiente paso necesita revisar la salida; no continuar automáticamente a otras fases.

Candidato0.1.0-rc.3: ZIP36877bytes,15 archivos/116493bytes expandidos, SHA256 `d0b09a1612fa95606915b33fa8664ae29589008abed4ca77c3c35a5259b8ed30`. [Paquete](../artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip), [manifiesto](../artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.manifest.json). No se reconstruyen bytes para desplegar.

Validación local del script exacto:9 escenarios correctos, con datos sintéticos. [QA y límites](qa/MCP_INACTIVE_DEPLOYMENT_2026-10-23.md). El paquete mantiene las597 comprobaciones previas; no se suman ensayos repetidos como controles nuevos.

## Controles concretos

El [script de instalación](../wordpress/operations/editorial-operating-install-inactive.sh) lleva embebidos el módulo de admisión exacto del ZIP, el inventario completo con bytes/hashes y el [comprobador](../wordpress/operations/editorial-operating-install-check.php). Se genera con `python3 scripts/prepare-editorial-operating-install.py` sin conectar al servidor. Verifica el SHA del ZIP, ausencia de destino/symlink y al menos16MiB libres en el filesystem antes de instalar. Esa lectura de espacio no certifica la cuota individual del proveedor.

Se revalida prospectivamente el perfil productivo antes y después, sin activar la guarda. El destino debe continuar sin constantes operativas, tablas/marker/abilities operativos ni los nuevos scopes de usuario2. Un directorio privado0700 conserva código del comprobador y baseline0600. El baseline contiene solo huellas/conteos: posts (incluidas propuestas privadas), postmeta, usermeta, términos, relaciones, owners/requests rc.2, roles y lista activa. Los datos se leen para hash en memoria, no se escriben cuerpos/SQL/credenciales al staging, repositorio o chat.

Después se exige igualdad de esas huellas, versión rc.3 inactiva y los15 archivos exactos; se rechazan symlinks, extras, faltantes y alteraciones. El script exige el recibo OPERATING_INSTALL_INACTIVE_RESULT antes de mostrar END, porque exit0 por sí solo no acredita que wp eval-file haya terminado sin error PHP. No se promete ausencia de cambios en transients/opciones de caché normales del instalador, ni bloqueo de escritores externos: una modificación editorial concurrente causa discrepancia y obliga a detenerse para revisar. No se restaura ciegamente una baseline.

Almacenamiento:153370bytes de ZIP+archivos instalados, más unos20KiB del comprobador, baseline pequeño, directorios y temporales/cachés del instalador. La carga útil prevista es menor que1MiB; el consumo transitorio de WordPress/Hostinger puede ser mayor. No se crea/descarga otro respaldo real. En el equipo de Alex se conservan el ZIP y respaldo existentes, más scripts/registros locales; Docker puede usar RAM/swap/capas. No borrar respaldo ni laboratorios.

## Comandos preparados para Alex, después de autorizar esta fase

El agente no ejecuta SSH/SCP. Si falla un comando, detenerse y pasar su salida; no repetir con force ni borrar archivos para continuar.

1. Crear staging privado exclusivo. Si ya existe el destino, este paso falla para evitar sobrescribirlo:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'set -eu; umask 077; test ! -L "$HOME/.ac-editorial-deploy"; mkdir -p "$HOME/.ac-editorial-deploy"; test "$(stat -c %a "$HOME/.ac-editorial-deploy")" = 700; mkdir "$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024"'
```

2. Subir solo el ZIP de código a esa carpeta nueva:

```bash
scp -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -P 65002 /Users/Alex/Desktop/AlexC/alexcuadra.dev/artifacts/wordpress/alexcuadra-editorial-operating-0.1.0-rc.3.zip u776991923@194.164.64.104:/home/u776991923/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024/candidate.zip
```

3. Proteger y comprobar el archivo remoto. El SHA esperado figura arriba:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'set -eu; file="$HOME/.ac-editorial-deploy/operating-0.1.0-rc.3-20261024/candidate.zip"; test -f "$file"; test ! -L "$file"; chmod 600 "$file"; sha256sum "$file"'
```

4. Instalar inactivo y verificar la baseline y archivos:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-operating-install-inactive.sh
```

Pasar la salida completa hasta `END_EDITORIAL_OPERATING_INSTALL_INACTIVE_V1`. Se espera versión rc.3/status inactive y `OPERATING_INSTALL_INACTIVE_RESULT` con packageFilesVerified/sourcesAndPrivateProposalsUnchanged/userMetadataAndRolesUnchanged/activePluginsUnchanged=true; active/capabilitiesAdded/editorialDataWritten=false. No mostrar baseline.base64 ni logs privados completos si hay fallo.

## Detención y reversión de esta fase

Con resultado correcto se conserva el plugin inactivo y se detiene la secuencia. No hay edición pública o scopes que revertir. Si falla después de copiar el plugin, se conservan ZIP/directorio/baseline y se revisa el estado; no reinstalar con force ni borrar tablas/propuestas/respaldo. Si fuera necesario desactivar únicamente este candidato, el comando de detención es:

```bash
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'cd "$HOME/domains/alexcuadra.dev/public_html/content" && wp --path=. plugin deactivate alexcuadra-editorial-operating'
```

No desinstala ni elimina datos. No retirar constantes o grants ajenos a esta fase. Retirar los archivos instalados sería una decisión posterior de Alex; no se programa limpieza automática.

## Fases posteriores, todavía fuera de esta aprobación

Opt-in/activación y preparación de seis tablas requieren comandos/baseline específicos y autorización nueva. La aceptación global exige que el operador confirme una pausa de escritores externos; el programa no puede certificarla. Grants apply/recover de usuario2 y approve del operador1 deben revisarse como cambios exactos separados. No se conceden edit_posts/manage_options al suscriptor. Una edición pública exige además su diff y aprobación independiente; «continúa» no autoriza un diff desconocido. [Plan general de reversión](WORDPRESS_MCP_OPERATING_PACKAGE.md).
