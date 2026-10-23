# Proposals rc.2 — procedimiento revisable de instalación y reversión

Estado: **Alex autorizó explícitamente** rc.2, opt-in/dos tablas, scope prepare únicamente a usuario2/subscriber y una propuesta privada de verificación, respondiendo «si procede» después de la revisión. Alex ejecuta SSH desde su terminal; el agente no lo ejecuta. FaseA confirmada por Alex: transferencia y SHA256 correctos en la ruta privada prevista. FaseB1 confirmada por Alex: plugin0.1.0-rc.2 inactivo y PACKAGE_FILES_VERIFIED. La instalación reportó purga de cachés. FaseB2 confirmada por Alex: constante/activación/esquema verificados y tablas vacías.14 Projects con huella c38acd1690f173488f075443d03a80c96b6b483a5bd407040cbbd40a7ff462af, Read EN/ES y permisos/rol intactos. FaseC pendiente; todavía no se afirma grant real. No volver a pedir esta misma autorización.

Actualización: Alex devolvió el preflight de2026-10-23T03:40:56Z, todos los checks y huellas
correctos, operador CLI observado1. [Salida recibida](qa/MCP_PROPOSALS_FINAL_PREFLIGHT_2026-10-23.json).
La decisión específica fue aprobada posteriormente por Alex. No repetir la
comprobación ni los laboratorios salvo demora/cambio/deriva que lo justifique.

## Paquete y alcance concreto

- Sitio https://content.alexcuadra.dev, single site.
- Editorial Proposals0.1.0-rc.2, archivo local
  `/Users/Alex/Desktop/AlexC/alexcuadra.dev/artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.2.zip`.
- SHA256 `c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07`.
- Único scope nuevo propuesto: `alexcuadra_prepare_project_changes` directamente en usuario2,
  conservando subscriber/read/`alexcuadra_read_public_projects`. No modifica el rol global.
- Seis operaciones privadas, sin apply/recover/approve ni edición de Projects.
- Constante nueva `AC_EDITORIAL_PROPOSALS_SITE=https://content.alexcuadra.dev` en configuración
  privada; no copiar ni imprimir wp-config. Tablas nuevas con prefijo del sitio
  ac_proposal_owners/ac_proposal_requests y opción ac_proposals_storage_version0.1.
- Smoke propuesto tras autorización: una propuesta privada de verificación sobre Project146EN,
  actualización y restauración de su patch, lectura/historial, denegaciones y comprobación
  de fuentes/permisos. Consume una de ocho plazas y hasta tres solicitudes únicas; conservar
  el registro, no borrarlo automáticamente. No publicar ni modificar el Project original.

Evidencia local completada: respaldo restaurado, esquema adversarial, plugins exactos,
tema/MU originales y303 checks,41 tests de su incremento. Ver QA de componentes. Son datos
sintéticos y HTTP interno con contexto HTTPS simulado, con límites explícitos de proveedor.

## Paso actual: preflight de solo lectura

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-proposals-final-preflight.sh
```

Devolver salida desde EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1 hasta
END_EDITORIAL_PROPOSALS_FINAL_PREFLIGHT_V1. El script lee versiones/activación, huellas de
plugins/MU/temas/raíz de wp-content, tema activo, motor de posts/postmeta, ausencia de módulo/
opt-in/tablas/marker y capacidades de usuario2. Comprueba lectura sin edición/borrado de Projects146EN/196ES sin devolver sus campos. Identifica IDs de operadores con manage_options;
no concede nada ni enumera nombres/correos/credenciales/contenidos. No ejerce DDL ni certifica
sus privilegios. Hashes globales usan rutas relativas y SHA256, nunca cuerpos de archivos.

No crea scratch, ZIP, dump ni archivos remotos. Lee aproximadamente198MiB ya existentes.
El arranque normal de WP-CLI carga plugins/tema/MU; no se afirma que los hooks del proveedor
sean incapaces de actualizar sus propias cachés. El código del operador no escribe datos,
opciones, permisos, archivos o esquema. Un false mantiene HOLD y se analiza antes de actuar.

## Secuencia propuesta después de revisar salida y recibir aprobación específica

1. Confirmar metadata/huellas sin deriva y usuario2 con solo lectura. Conservar respaldo
   privado original y evidencia de restauración. No repetir exportaciones por rutina;
   si hay cambios relevantes posteriores, decidir un respaldo actualizado antes de instalar.
2. Alex aprueba paquete/hash, opt-in, dos tablas, scope únicamente a usuario2 y smoke descrito.
   Resolver operador CLI a partir de migrationOperatorIds observados, sin inventar un adminID.
3. Preparar ruta privada nueva fuera del webroot,0700; transferir únicamente el ZIP,0600.
   Comparar SHA256 en servidor antes de instalar. No usar una URL pública ni sobrescribir
   una instalación existente. No enviar secretos al chat.
4. En el directorio del CMS, instalar el ZIP privado sin activación ni --force. El nombre,
   versión, archivos y hash deben coincidir. No activar el laboratorio de Changes.
5. Incorporar únicamente la constante indicada, activar Proposals y ejecutar explícitamente
   `AlexCuadra\EditorialProposals\install_storage()` por WP-CLI con el operador manage_options
   observado. Exigir true, versión0.1 y esquema exacto; no alterar tablas incompatibles.
   Fallo: detener, sin scope ni reparaciones automáticas. Puede quedar una tabla vacía parcial;
   conservarla y diagnosticarla. No reintentar DDL a ciegas.
6. Comprobar que el usuario2 todavía es denegado para preparar, que Read funciona y que fuentes,
   relaciones/idiomas y permisos ajenos permanecen iguales. Guardar huellas antes/después.
7. Conceder solo prepare directamente a usuario2. Verificar de nuevo rol, capacidades efectivas
   y ausencia de edición/admin/approve/apply/recover. Registrar únicamente metadatos del cambio.
8. Ejecutar smoke autorizado mediante el conector y sesión autenticada: create/update/restore
   privado e historial, idioma exacto y propuestas ajenas denegadas. Verificar en transporte
   real que contenido fuente no cambia y que REST/GraphQL no exponen el nuevo CPT.
9. Mantener instalado solo si resultados/permisos son correctos; ante fallo cerrar admisiones
   con la reversión siguiente. Preservar propuesta de prueba/tablas/recibos y respaldo.
10. Tras validar instalación y recuperación, Alex decide retención. No programar borrados.

Los comandos siguientes usan el operador1 observado y una carpeta privada nueva elegida para este despliegue. No se han ejecutado.

## Comandos concretos para revisión, aún sin ejecutar

Destino privado nuevo elegido para este despliegue; mkdir falla si ya existe. Después de
aprobar el alcance, Alex ejecuta por fases y devuelve cada resultado antes de continuar.
No ejecutar toda la secuencia sin verificar los puntos de parada.

### A. Carpeta privada y transferencia

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'umask 077; mkdir -p "$HOME/.ac-editorial-deploy" && chmod 700 "$HOME/.ac-editorial-deploy" && mkdir "$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023"'
scp -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -P 65002 /Users/Alex/Desktop/AlexC/alexcuadra.dev/artifacts/wordpress/alexcuadra-editorial-proposals-0.1.0-rc.2.zip u776991923@194.164.64.104:.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023/candidate.zip
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'chmod 600 "$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023/candidate.zip" && sha256sum "$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023/candidate.zip"'
```

Exigir SHA256 c31e6814864be0692035de7b46322fc2fc0159d2104e0b4d25404c3b15859f07.
Si falla un comando, parar; no sobrescribir ni limpiar rutas existentes.

### B. Instalar, opt-in y preparar almacenamiento, todavía sin scope

FaseB1 autorizada y pendiente: el wrapper siguiente sustituye los primeros comandos manuales
hasta PACKAGE_FILES_VERIFIED. No lo repetir si ya completó la instalación.

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-proposals-install-inactive.sh
```

Alex devuelve versión/estado, PACKAGE_FILES_VERIFIED y END_EDITORIAL_PROPOSALS_INSTALL_INACTIVE_V1.
No continuar a opt-in/activación/migración hasta revisar ese resultado. No scope todavía.

Dentro de una sesión SSH que Alex abra, tras verificar el hash:

```sh
cd "$HOME/domains/alexcuadra.dev/public_html/content"
wp --path=. plugin install "$HOME/.ac-editorial-deploy/proposals-0.1.0-rc.2-20261023/candidate.zip"
wp --path=. plugin get alexcuadra-editorial-proposals --fields=name,version,status --format=json
wp --path=. eval '$d=WP_PLUGIN_DIR . "/alexcuadra-editorial-proposals"; $expected=["alexcuadra-editorial-proposals.php"=>"a9a1b498ab18472a30966cfcd160ef91d35aa62420911e31ca30e7ec3452949e","storage.php"=>"074f96126397e91b68f407feb3989cc147d9b81aeffb6790766486b9433639af","README.md"=>"52de66e733a0ba4a41f004a24c8804c15102187578e7e45e0a3f3c8d99eea617"]; $names=array_keys($expected); sort($names); if (array_values(array_diff(scandir($d),[".",".."])) !== $names) { WP_CLI::error("UNEXPECTED_PACKAGE_FILES"); } foreach ($expected as $file=>$hash) { if (is_link($d . "/" . $file) || hash_file("sha256",$d . "/" . $file) !== $hash) { WP_CLI::error("PACKAGE_HASH_MISMATCH"); } } WP_CLI::line("PACKAGE_FILES_VERIFIED");'
```

Parar y verificar0.1.0-rc.2/inactivo y bytes instalados contra el manifiesto del ZIP antes
de habilitarlo. No --force, no actualizar otros plugins. Después:

```sh
wp --path=. config set AC_EDITORIAL_PROPOSALS_SITE 'https://content.alexcuadra.dev' --type=constant
wp --path=. plugin activate alexcuadra-editorial-proposals
wp --path=. --user=1 eval '$r=AlexCuadra\EditorialProposals\install_storage(); if ($r !== true || !AlexCuadra\EditorialProposals\storage_schema_valid()) { WP_CLI::error("PROPOSALS_STORAGE_NOT_READY"); } WP_CLI::line("PROPOSALS_STORAGE_READY");'
```

La faseB2 se ejecutará con el wrapper siguiente, que sustituye las tres líneas anteriores:

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-proposals-activate-storage.sh
```

Captura huellas privadas antes, crea un helper/JSON de hashes en carpeta nueva0700/0600 fuera
del webroot, añade constante/activa/migra como1 y compara todos los Projects/meta/relaciones,
caps de2, rol subscriber, otros plugins activos y código de Read. Exige tablas vacías,
esquema exacto, lectura EN/ES y denegación de2 para preparar. No concede el scope. Conserva
el pequeño scratch de auditoría, sin copiar wp-config/SQL/contenidos. Alex devuelve hasta
END_EDITORIAL_PROPOSALS_ACTIVATE_STORAGE_V1 antes de faseC. No repetir si activación ya empezó.

Cada fallo detiene la fase; no continuar a la siguiente línea después de error. No se ha
ensayado DDL con privilegios del hosting; fallo de permisos se diagnostica sin reparación
automática. Verificar fuentes/Read y denegación de usuario2 sin prepare antes de faseC.

### C. Scope exacto, solo después de revisar faseB

FaseB completa. El wrapper autorizado sustituye el add-cap manual siguiente y añade
pre/postflight; no ejecutar ambos ni repetir si ya se concedió el permiso.

```sh
ssh -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=10 -p 65002 u776991923@194.164.64.104 'sh -s' < /Users/Alex/Desktop/AlexC/alexcuadra.dev/wordpress/operations/editorial-proposals-grant-scope.sh
```

Exige operador1, esquema listo, cuenta2/subscriber y huella de los14 Projects idéntica aB2.
Solo añade prepare directamente a2; compara exactamente ese único cambio, rol, dependencias,
fuentes y Read EN/ES. Ante fallo posterior retira solo el scope que acaba de añadir y muestra
resultado de reversión; no elimina un grant previo ni cambia roles. No crea propuestas.
Alex devuelve hasta END_EDITORIAL_PROPOSALS_GRANT_SCOPE_V1; luego smoke real ya aprobado.
El conector nativo ya descubrió11 abilities/seis nuevas y confirmó forbidden antes del grant,
con lectura146EN y traducción196ES intactas.

```sh
wp --path=. user add-cap 2 alexcuadra_prepare_project_changes
```

No cambiar roles. Revisar capacidades efectivas y smoke descrito mediante el conector;
Alex conserva control de SSH. Los informes de aceptación no deben contener passwords,
wp-config, SQL ni campos originales privados.

### D. Cierre de admisiones si se requiere revertir

Solo retirar cambios propios de este despliegue, después de confirmar que se aplicaron:

```sh
wp --path=. user remove-cap 2 alexcuadra_prepare_project_changes
wp --path=. plugin deactivate alexcuadra-editorial-proposals
wp --path=. config delete AC_EDITORIAL_PROPOSALS_SITE --type=constant
```

Conservar ZIP privado, tablas/recibos/propuestas y backup. No ejecutar uninstall ni DROP.
Estos comandos son un procedimiento manual revisable, no un instalador transaccional;
la ejecución y verificación por fases se coordinarán tras la aprobación.

## Reversión limitada

Cerrar admisiones retirando únicamente el scope prepare del usuario2 y desactivando solo
Proposals; retirar la constante si se añadió en este despliegue. Preservar Editorial Read,
subscriber/read/scope público, credenciales existentes, proyectos, tablas, propuestas y recibos.
Comprobar que Read continúa y el resto de permisos/huellas no cambian. No uninstall, DROP,
borrado de datos, migración destructiva ni restauración completa de la DB compartida.
Proposals nunca aplica fuentes: no hay un rollback de contenido de Projects que ejecutar.

## Almacenamiento previsto

Local: utiliza el ZIP existente, sin nuevas imágenes, dependencias, dumps o volúmenes para
este preflight. Paquete11369B y contenido35615B. Remoto: ZIP privado más plugin y dos tablas
nuevas; InnoDB, índices, logs y backups añaden consumo no limitado por ese tamaño. Cuota
lógica8MiB por dueño incluyendo payloads/recibos, no garantía de disco físico. No hay poda.
El espacio remoto amplio medido anteriormente no sustituye revisar errores reales de cuota.
