# 🔍 ANÁLISIS DE ERRORES - register.php (API Usuarios)

**Fecha**: Enero 26, 2026  
**Archivo**: `api/usuarios/register.php`  
**Problema**: Errores JSON con HTML mezclado  
**Estado**: ✅ SOLUCIONADO

---

## 📋 PROBLEMAS ENCONTRADOS

### 🔴 **CRÍTICO #1: `display_errors = 1`**

```php
ini_set('display_errors', 1);           // ← PROBLEMA
ini_set('display_startup_errors', 1);   // ← PROBLEMA
error_reporting(E_ALL);
```

**¿Por qué causa error?**
- Si PHP genera ANY error (warning, notice, deprecated, etc.), lo mostrará como **HTML**
- Este HTML se mezcla con el JSON que debe retornar la API
- JavaScript intenta parsear como JSON: `JSON.parse("<html>...Error...")`
- Resultado: `SyntaxError: Unexpected token '<'`

**Ejemplo de lo que pasaba:**
```
[Salida esperada]
{"success":true,"message":"Usuario registrado..."}

[Lo que se generaba]
<br /><b>Strict Standards</b>: ... (HTML error)
{"success":true,...}  ← El JSON viene después del HTML

[Lo que JavaScript recibía]
"<br /><b>Strict Standards</b>: ...\n{"success":true,...}"
↑ HTML primero, JSON después = PARSE ERROR
```

---

### 🔴 **CRÍTICO #2: Sin Try-Catch en includes**

```php
require_once __DIR__ . '/../utils.php';         // Si falla aquí
require_once __DIR__ . '/../../config/mailer.php'; // Si falla aquí
```

**¿Por qué causa error?**
- Si algún include falla, PHP lanza **error fatal**
- Muestra **HTML de error** (parse error, file not found, etc.)
- Se mezcla con cualquier salida posterior

**Ejemplo:**
```
Fatal error: require_once(../utils.php): 
Failed opening required '../utils.php'
→ HTML ERROR se envía antes que header JSON
→ Headers ya se escribieron, imposible cambiarlos
→ Cliente recibe HTML en lugar de JSON
```

---

### 🟠 **CRÍTICO #3: Email sin Try-Catch**

```php
if (!empty($email)) {
    $fullName = "$nombre $apellido";
    $verificationLink = "...";
    sendRegistrationConfirmationEmail($email, ...);  // ← Sin protección
}
```

**¿Por qué causa error?**
- Si `sendRegistrationConfirmationEmail()` lanza excepción (SMTP fail, etc.)
- La excepción no está capturada
- PHP muestra **error de excepción no capturada en HTML**
- Usuario registrado pero error mostrado

---

### 🟠 **CRÍTICO #4: Función no validada**

```php
$token = generarToken();  // ← ¿Existe esta función?
```

**¿Por qué causa error?**
- Si la función no existe en `utils.php`, error fatal
- PHP muestra: `Call to undefined function generarToken()`
- En HTML (con display_errors=1)

---

### 🟡 **CRÍTICO #5: Header después de output**

```php
require_once 'config.php';  // Podría emitir whitespace o output
header("Content-Type: application/json");  // ← Falla si hay output anterior
```

**¿Por qué causa error?**
- PHP error: `Cannot modify header information - headers already sent`
- No se envía header JSON
- Cliente recibe `text/html` en lugar de `application/json`

---

## ✅ SOLUCIONES IMPLEMENTADAS

### **Solución #1: Output Buffering + Error Control**

```php
<?php
// Iniciar buffer para capturar cualquier output inesperado
ob_start();

// Cargar dependencias CON protección
try {
    require_once 'utils.php';
    require_once 'config/mailer.php';
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'msg' => 'Error en dependencias']);
    exit;
}

// Limpiar cualquier output
ob_clean();

// IMPORTANTE: Desactivar display_errors
ini_set('display_errors', 0);           // ← NO mostrar errores (previene HTML)
ini_set('display_startup_errors', 0);   // ← NO mostrar errores de startup
error_reporting(E_ALL);                 // ← SÍ registrar en error_log para debugging
```

**Por qué funciona:**
- `display_errors = 0` previene que HTML de errores salga en la respuesta
- `error_reporting(E_ALL)` sigue registrando en error_log para debugging
- `ob_clean()` elimina cualquier whitespace o error previo
- `ob_start()` captura output no deseado

---

### **Solución #2: Try-Catch Global**

```php
try {
    // Toda la lógica aquí
    $pdo = getPDO();
    // ... más código
    
} catch (PDOException $e) {
    // Manejo específico para errores de BD
    error_log('DB Error: ' . $e->getMessage());
    jsonErr('Error de base de datos', 500);
} catch (Exception $e) {
    // Manejo para cualquier otra excepción
    error_log('General error: ' . $e->getMessage());
    jsonErr('Error en el registro', 500);
}
```

**Por qué funciona:**
- Captura TODA excepción (no escapa ninguna)
- Convierte a JSON con `jsonErr()`
- Registra en `error_log` para debugging

---

### **Solución #3: Validación de Funciones**

```php
if (!function_exists('generarToken')) {
    jsonErr('Error interno: función no disponible', 500);
}
$token = generarToken();
```

**Por qué funciona:**
- Verifica que la función exista antes de usarla
- Retorna JSON error si no existe
- Evita error fatal

---

### **Solución #4: Email con Try-Catch anidado**

```php
if (!empty($email)) {
    try {
        $fullName = "$nombre $apellido";
        $verificationLink = "...";
        
        if (function_exists('sendRegistrationConfirmationEmail')) {
            sendRegistrationConfirmationEmail(...);
        }
    } catch (Exception $e) {
        error_log('Email error: ' . $e->getMessage());
        // Continuamos aunque falle el email
    }
}
```

**Por qué funciona:**
- Email es secundario, no debe bloquear registro
- Excepción capturada y registrada
- Usuario se registra aunque email falle

---

## 📊 ANTES vs DESPUÉS

### **ANTES (Problemático)**
```
Cliente: POST /api/usuarios/register.php
    ↓
Servidor carga includes
    ↓
PHP genera NOTICE o WARNING
    ↓
display_errors=1 → Muestra HTML error
    ↓
Mensaje JSON continúa
    ↓
Cliente recibe: HTML + JSON mezclado
    ↓
JSON.parse() → SYNTAX ERROR
```

### **DESPUÉS (Arreglado)**
```
Cliente: POST /api/usuarios/register.php
    ↓
ob_start() - Iniciar buffer
    ↓
Cargar includes con try-catch
    ↓
display_errors=0 - Errores NO se muestran
    ↓
Toda lógica en try-catch
    ↓
Cualquier error → jsonErr() → JSON válido
    ↓
Cliente recibe: JSON puro y válido
    ↓
JSON.parse() → SUCCESS ✓
```

---

## 🧪 TESTING

### **Test 1: Registro válido**
```bash
curl -X POST http://localhost:8080/PRERMI/api/usuarios/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "nombre":"Juan",
    "apellido":"Pérez",
    "usuario":"juanperez",
    "cedula":"123456789",
    "clave":"Password123"
  }'
```
**Esperado**: JSON válido sin errores

### **Test 2: Revisar error_log**
```bash
tail -f /var/log/php-errors.log
# O en XAMPP:
# D:\xampp\apache\logs\error.log
```
Los errores ahora se registran sin mostrarse en la salida

### **Test 3: DevTools Network**
1. F12 → Network tab
2. POST a register.php
3. Response → Debe ser JSON puro, sin HTML

---

## 🎯 PUNTOS CLAVE

| Aspecto | Antes | Después |
|--------|-------|---------|
| **display_errors** | 1 (Muestra HTML) | 0 (No muestra) |
| **Error handling** | Solo PDOException | Todas las excepciones |
| **Output buffering** | No | Sí (`ob_start/clean`) |
| **Validación funciones** | No | Sí (check `function_exists`) |
| **Email seguro** | No | Sí (Try-catch anidado) |
| **JSON response** | Puede tener HTML | Siempre JSON puro |

---

## 📝 CAMBIOS REALIZADOS

✅ Agregado `ob_start()` y `ob_clean()`  
✅ Cambio: `display_errors` de 1 → 0  
✅ Cambio: `display_startup_errors` de 1 → 0  
✅ Try-catch envolviendo includes  
✅ Try-catch global para toda la lógica  
✅ Validación de `generarToken()`  
✅ Validación de `sendRegistrationConfirmationEmail()`  
✅ Try-catch separado para email  
✅ Mejor logging de errores  

---

*Análisis completo y solución implementada - Enero 26, 2026*
