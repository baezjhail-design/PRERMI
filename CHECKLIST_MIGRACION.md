# ✅ CHECKLIST DE MIGRACION - NUEVA SCHEMA

**Status Actual**: Phase 2 Completado - Listo para Validación

---

## PRE-MIGRACION (Antes de Ejecutar SQL)

- [ ] **Backup Completo de Base de datos**
  ```bash
  mysqldump -u usuario -p prer_mi > backup_prer_mi_$(date +%Y%m%d_%H%M%S).sql
  ```
  Location: `_BACKUPS/`

- [ ] **Revisar que no hay procesos ejecutándose**
  - [ ] APIs no están procesando requests
  - [ ] No hay transacciones abiertas
  - [ ] Admin no está editando datos

- [ ] **Revisar permisos de usuario MySQL**
  - [ ] User tiene privilegios ALTER TABLE
  - [ ] User tiene privilegios CREATE TABLE
  - [ ] User tiene privilegios DROP FOREIGN KEY

---

## MIGRACION SQL (Ejecutar Archivo)

- [ ] **Descargar archivo**
  - [ ] File: `/PRERMI/MIGRACION_NUEVO_SCHEMA.sql`
  - [ ] Ubicación local: `_SQL_SCRIPTS/`

- [ ] **Ejecutar Migration Script**
  ```bash
  mysql -u usuario -p prer_mi < MIGRACION_NUEVO_SCHEMA.sql
  ```

- [ ] **Verificar que NO hay errores**
  - [ ] Query 1: Remover FK ✓
  - [ ] Query 2: Crear tabla `sensores` ✓
  - [ ] Query 3: Crear tabla `mediciones` ✓
  - [ ] Query 4: Crear tabla `mediciones_biomasa` ✓
  - [ ] Query 5: Modificar depositos.id_contenedor ✓
  - [ ] Query 6: Modificar sanciones.contenedor_id ✓

---

## VALIDACION INMEDIATA (Post-SQL)

- [ ] **Ejecutar Script de Validación**
  ```
  http://localhost/PRERMI/validar_migracion.php
  ```

- [ ] **Revisar Reporte de Validación**
  - [ ] Status es "ÉXITO"
  - [ ] No hay errores críticos (verificar sección "errores": [])
  - [ ] 3 tablas nuevas existen
  - [ ] 3 tablas nuevas tienen registros válidos = 0 (vacías es normal al inicio)
  - [ ] Todos los índices están presentes
  - [ ] Test INSERT/DELETE pasaron ✓

---

## TESTING DE APIS (Manual)

### Test Sensores API

- [ ] **POST sensores/registrar**
  ```
  curl -X POST http://localhost/PRERMI/api/sensores/registrar.php \
    -H "Content-Type: application/json" \
    -d '{"user_id":1,"sensor_ir":128,"ruta_imagen":"/test/img.jpg"}'
  ```
  Expected: `{"status":"ok","biomasa_id":1,"sensor_ir":128,...}`

- [ ] **GET sensores/obtener**
  ```
  curl "http://localhost/PRERMI/api/sensores/obtener.php?user_id=1&limit=10"
  ```
  Expected: `{"status":"ok","registros":[{...}]}`

### Test Mediciones API

- [ ] **POST mediciones/registrar**
  ```
  curl -X POST http://localhost/PRERMI/api/mediciones/registrar.php \
    -H "Content-Type: application/json" \
    -d '{"user_id":1,"peso":50.5,"sensor_metal":0,"estado":"disponible"}'
  ```
  Expected: `{"status":"ok","medicion_id":1,"peso":50.5,...}`

- [ ] **GET mediciones/obtener**
  ```
  curl "http://localhost/PRERMI/api/mediciones/obtener.php?user_id=1&limit=10"
  ```
  Expected: `{"status":"ok","registros":[{...}]}`

### Test Biomasa API

- [ ] **POST biomasa/registrar**
  ```
  curl -X POST http://localhost/PRERMI/api/biomasa/registrar.php \
    -H "Content-Type: application/json" \
    -d '{"user_id":1,"relay":1.0,"ventilador":75.5,"peltier1":28.3,"peltier2":27.9,"gases":150.2}'
  ```
  Expected: `{"status":"ok","biomasa_id":1,"relay":1.0,...}`

- [ ] **GET biomasa/obtener**
  ```
  curl "http://localhost/PRERMI/api/biomasa/obtener.php?user_id=1&limit=10"
  ```
  Expected: `{"status":"ok","registros":[{...}]}`

---

## TESTING DE PAGINAS WEB

### Usuario Session

- [ ] **Acceder a Dashboard de Usuario**
  - [ ] URL: `/web/usuarios/dashboard_usuario.php`
  - [ ] ✓ Página carga sin errores
  - [ ] ✓ Datos de usuario visible
  - [ ] ✓ Historial de depósitos aparece (si hay registros)

- [ ] **Acceder a Depósitos del Usuario**
  - [ ] URL: `/web/usuarios/depositos_usuario.php`
  - [ ] ✓ Página carga sin errores
  - [ ] ✓ Tabla de depósitos visible
  - [ ] ✓ Columnas correctas: ID, Contenedor, Peso, Créditos, Fecha
  - [ ] ✓ Sin errores SQL en console

- [ ] **Acceder a Sanciones del Usuario**
  - [ ] URL: `/web/usuarios/sanciones_usuario.php`
  - [ ] ✓ Página carga sin errores
  - [ ] ✓ Tabla de sanciones visible
  - [ ] ✓ Columnas correctas: ID Sanción, Motivo, Peso, Fecha
  - [ ] ✓ Sin errores SQL en console
  - [ ] ✓ Mensaje "Sin sanciones" si no hay datos

### Admin Session

- [ ] **Acceder a Dashboard Admin**
  - [ ] URL: `/web/admin/dashboard.php`
  - [ ] ✓ Página carga sin errores
  - [ ] ✓ Stats cards visibles
  - [ ] ✓ Secciones de vehículos, contenedores, sanciones
  - [ ] ✓ Sin errores en logs

---

## TESTING DE TRANSACCIONES

- [ ] **Usuarios pueden registrar depósitos**
  - [ ] Via `/api/contenedores/registrar_depositos.php`
  - [ ] ✓ INSERT exitoso en tabla `depositos`
  - [ ] ✓ peso registrado correctamente
  - [ ] ✓ Respuesta API exitosa

- [ ] **Admins pueden crear sanciones**
  - [ ] Via `/api/sanciones/crear_sancion.php`
  - [ ] ✓ INSERT exitoso en tabla `sanciones`
  - [ ] ✓ User_id validado contra `usuarios` tabla
  - [ ] ✓ Respuesta API exitosa

- [ ] **Sensores reportan correctamente**
  - [ ] Via ESP32 → `/api/sensores/registrar.php`
  - [ ] ✓ Lectura registrada en tabla `sensores`
  - [ ] ✓ User ID asociado correctamente
  - [ ] ✓ Timestamp automático

- [ ] **Mediciones registran peso**
  - [ ] Via ESP32/Form → `/api/mediciones/registrar.php`
  - [ ] ✓ Peso registrado en tabla `mediciones`
  - [ ] ✓ Metal sensor detecta correctamente
  - [ ] ✓ Estado inicial correcto

- [ ] **Reactor Biomasa envia telemetría**
  - [ ] Via IoT → `/api/biomasa/registrar.php`
  - [ ] ✓ Datos registrados en tabla `mediciones_biomasa`
  - [ ] ✓ Todos los campos (relay, ventilador, peltiers, gases)
  - [ ] ✓ Timestamps correctos

---

## TESTING DE INTEGRIDAD

- [ ] **Verificar FKs funcionan**
  - [ ] Intentar INSERT con user_id inválido en sensores → Error esperado ✓
  - [ ] Intentar INSERT con user_id inválido en mediciones → Error esperado ✓
  - [ ] Intentar INSERT con user_id inválido en mediciones_biomasa → Error esperado ✓

- [ ] **Verificar Indexes están optimizados**
  ```sql
  SELECT * FROM sensores WHERE user_id = 1 ORDER BY fecha DESC LIMIT 10;
  EXPLAIN el query anterior → Debe usar índice (user_id, fecha)
  ```
  - [ ] Query rápido (< 10ms)
  - [ ] EXPLAIN muestra "Using index" o "Using index, Using filesort"

- [ ] **Verificar Cascading Deletes**
  - [ ] Eliminar usuario de prueba
  - [ ] Verificar que sensores/mediciones/biomasa se eliminan automáticamente
  - [ ] Log_sistema registra la operación

---

## PERFORMANCE VERIFICATION

- [ ] **Benchmark sensores/obtener**
  - [ ] GET con limit=100 → Tiempo < 50ms esperado
  - [ ] GET con limit=1000 → Tiempo < 200ms esperado

- [ ] **Benchmark mediciones/obtener**
  - [ ] GET con limit=100 → Tiempo < 50ms esperado
  - [ ] GET con limit=1000 → Tiempo < 200ms esperado

- [ ] **Benchmark biomasa/obtener**
  - [ ] GET con limit=100 → Tiempo < 50ms esperado
  - [ ] GET con limit=1000 → Tiempo < 200ms esperado

---

## CLEANUP Y FINALIZACION

- [ ] **Decidir: Eliminar tabla vieja o mantenerla**
  - [ ] OPCION A: Eliminar `contenedores_registrados` (recomendado)
    ```sql
    DROP TABLE IF EXISTS contenedores_registrados;
    ```
  - [ ] OPCION B: Mantenerla para referencia histórica

- [ ] **Si se mantiene, crear tabla backup**
  ```sql
  CREATE TABLE contenedores_registrados_archived AS 
  SELECT * FROM contenedores_registrados;
  ```

- [ ] **Documentar cambios**
  - [ ] Archivo CHANGELOG.md actualizado
  - [ ] README.md actualizado con nuevos endpoints
  - [ ] RESUMEN_MIGRACION_FASE2.md archivado

- [ ] **Notificar a equipo**
  - [ ] Developers sobre nuevas APIs
  - [ ] ESP32 team sobre cambio de endpoints
  - [ ] Admins sobre nueva estructura

---

## ROLLBACK PLAN (Si algo falla)

Si hay problemas durante testing:

1. **Restore from Backup**
   ```bash
   mysql -u usuario -p prer_mi < backup_prer_mi_XXXXX.sql
   ```

2. **Revert Code Changes**
   - Git revert a versión pre-migracion
   - O manual undo de cambios a PHP files

3. **Root Cause Analysis**
   - Revisar logs_sistema para errores
   - Revisar validar_migracion.php para diagnóstico
   - Contactar soporte si necesario

4. **Re-attempt** (después de fix)
   - Resolver issue identificado
   - Ejecutar nuevamente desde start

---

## SIGN OFF

- [ ] **Proyecto Manager**: Autoriza ejecución
  - Nombre: ________________  Fecha: __________

- [ ] **DBA**: Verifica integridad post-migracion
  - Nombre: ________________  Fecha: __________

- [ ] **QA**: Completa testing
  - Nombre: ________________  Fecha: __________

- [ ] **Dev Lead**: Aprueba sistema para producción
  - Nombre: ________________  Fecha: __________

---

**✅ MIGRACION COMPLETADA**: _________________ (Fecha)

**📝 Notas Finales**:
```
___________________________________________________________________
___________________________________________________________________
___________________________________________________________________
```
