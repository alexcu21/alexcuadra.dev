# Proposals rc.2 — procedimiento revisable de instalación y reversión

Estado: preparado para revisión, **sin autorización de instalación todavía**. Alex ejecuta
SSH desde su terminal; el agente no lo ejecuta. Primero devolver el preflight de solo lectura.
No instalar/conceder scopes solo porque las comprobaciones técnicas resulten correctas.

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

Los comandos de mutación y transferencia se concretarán con la salida actual del preflight;
no contienen aquí IDs/rutas remotas supuestos ni se han ejecutado.

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
