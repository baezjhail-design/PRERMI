# Paleta de Colores PRERMI - Guía de Diseño

## 🎨 Paleta Principal

### Colores Primarios
- **Primary Color**: `#667eea` (Azul-violeta)
- **Secondary Color**: `#764ba2` (Violeta oscuro)
- **Accent Color**: `#00d4ff` (Cyan brillante)

### Gradientes Principales
```css
/* Gradiente Principal - Usado en botones, headers, fondos hero */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Gradiente Acento - Usado en highlights, stats */
background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
```

### Colores de Fondo
- **Dark Background**: `#0a0f1e` (Fondo oscuro principal)
- **Card Background**: `#1a1f35` (Fondo de tarjetas)
- **Light Background**: Gradiente principal (páginas de usuario)

### Colores de Texto
- **Text Light**: `#e8ecff` (Texto claro sobre fondos oscuros)
- **Text Muted**: `#8892b8` (Texto secundario/subtítulos)
- **Text Dark**: `#333333` (Texto sobre fondos claros)

### Colores de Estado
- **Success**: `#00e676` (Verde - Operaciones exitosas)
- **Warning**: `#ffeb3b` / `#ffc107` (Amarillo - Advertencias)
- **Danger**: `#f44336` / `#ff6b6b` (Rojo - Errores/Eliminación)
- **Info**: `#00d4ff` (Cyan - Información)

### Colores para Biodigestión (Dashboard Admin)
- **Temperatura OK**: `linear-gradient(135deg, #00d084, #00c470)` (Verde energía)
- **Temperatura Warning**: `linear-gradient(135deg, #ffeb3b, #ffc107)` (Amarillo)
- **Temperatura Danger**: `linear-gradient(135deg, #ff6b6b, #ee5a6f)` (Rojo)
- **Sistema Apagado**: `linear-gradient(135deg, #888, #444)` (Gris)

---

## 🖥️ Aplicación por Página

### index.php (Landing Page)
- **Fondo principal**: `#0a0f1e` (Dark Background)
- **Navbar**: `rgba(10, 15, 30, 0.95)` con backdrop blur
- **Hero Title**: Gradiente blanco a `#667eea`
- **Botones CTA**: Gradiente principal
- **Cards**: `#1a1f35` con borde `rgba(102, 126, 234, 0.1)`
- **Feature Icons**: Gradiente principal
- **Tech Badges**: `rgba(102, 126, 234, 0.1)` con hover a gradiente

### login.php
- **Fondo wrapper**: Gradiente principal
- **Card**: Blanco `#ffffff`
- **Header**: Gradiente principal
- **Inputs focus**: Border `#667eea` con shadow `rgba(102, 126, 234, 0.1)`
- **Botón login**: Gradiente principal
- **Links**: `#667eea` hover `#764ba2`

### user-dashboard.php
- **Fondo**: Gradiente principal
- **Navbar**: `rgba(0, 0, 0, 0.1)` con backdrop blur
- **Cards**: Blanco `#ffffff` con shadow
- **Stats Cards**: Gradientes según valor
- **Gráficas**: Paleta HSL generada dinámicamente
- **Modal Header**: Gradiente principal

### Registrofac.php (Registro Facial)
- **Fondo**: Gradiente principal
- **Card**: Blanco `#ffffff`
- **Navbar**: `rgba(0, 0, 0, 0.1)` con backdrop blur
- **Botón capturar**: Gradiente principal con hover elevation
- **Overlay facial**: Border `rgba(102, 126, 234, 0.7)` dashed
- **Preview border**: Sólido `#667eea`

### biores.php (Admin Dashboard)
- **Fondo**: `linear-gradient(135deg, #1f4037, #99f2c8)` (Verde industrial)
- **Cards sistema**: Según estado (verde/amarillo/rojo/gris)
- **Botón descarga**: Gradiente principal `#667eea` a `#764ba2`
- **Gráficas**: Colores semánticos (temperatura=naranjas, energía=verdes)

---

## 📐 Especificaciones de Diseño

### Border Radius
- **Pequeño**: `8px` (inputs, botones pequeños)
- **Mediano**: `12px` - `15px` (cards, botones principales)
- **Grande**: `20px` - `30px` (containers destacados, CTA boxes)
- **Extra grande**: `50px` (pills, badges)
- **Circular**: `50%` (avatares, iconos circulares)

### Shadows
```css
/* Card normal */
box-shadow: 0 10px 40px rgba(0,0,0,0.15);

/* Hover elevation */
box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2);

/* Botones hover */
box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);

/* Overlay oscuro */
background: rgba(0, 0, 0, 0.8);
```

### Transiciones
```css
/* Estándar */
transition: all 0.3s ease;

/* Hover Y-translate */
transform: translateY(-3px);
transform: translateY(-5px); /* Extra elevation */

/* Smooth scroll */
scroll-behavior: smooth;
```

### Tipografía
- **Font Principal**: 'Inter', 'Segoe UI', sans-serif
- **Weight Title**: `800` (Extra Bold)
- **Weight Heading**: `700` (Bold)
- **Weight Button**: `600` (Semi Bold)
- **Weight Body**: `400` (Regular)
- **Weight Light**: `300` (Light)

---

## 🎯 Reglas de Uso

### ✅ HACER
1. **Usar gradiente principal** para acciones primarias (login, submit, CTA)
2. **Mantener contraste** mínimo 4.5:1 para accesibilidad
3. **Aplicar backdrop-blur** en navbars flotantes para efecto glassmorphism
4. **Usar elevation hover** en cards y botones interactivos
5. **Aplicar border sutil** `rgba(102, 126, 234, 0.1)` en cards sobre fondos oscuros

### ❌ NO HACER
1. **NO mezclar** gradientes verde (#4ecdc4) con violeta (#667eea) en misma página
2. **NO usar** colores brillantes saturados en grandes áreas
3. **NO aplicar** más de 2 gradientes diferentes en una sección
4. **NO omitir** estados hover en elementos interactivos
5. **NO usar** texto oscuro (#333) sobre gradiente principal sin overlay

---

## 🔧 Código Reutilizable

### Variables CSS (Recomendado implementar)
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --accent-color: #00d4ff;
    --dark-bg: #0a0f1e;
    --card-bg: #1a1f35;
    --text-light: #e8ecff;
    --text-muted: #8892b8;
    --success-color: #00e676;
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-accent: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%);
}
```

### Clases Utilitarias
```css
.btn-primary {
    background: var(--gradient-primary);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.card-custom {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(102, 126, 234, 0.1);
    transition: all 0.3s ease;
}

.card-custom:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(102, 126, 234, 0.2);
}
```

---

## 📊 Gráficas (Chart.js)

### Paleta Recomendada
```javascript
const chartColors = {
    primary: '#667eea',
    secondary: '#764ba2',
    accent: '#00d4ff',
    success: '#00e676',
    warning: '#ffc107',
    danger: '#ff6b6b',
    info: '#00d4ff'
};

// Generación dinámica de colores para múltiples datasets
const generateHSLPalette = (count) => {
    return Array.from({length: count}, (_, i) => {
        const hue = Math.floor((i * 360 / count) % 360);
        return `hsl(${hue}deg 70% 55%)`;
    });
};
```

---

## 🌐 Iconos

### Font Awesome 6.4.0
- **Sistema**: `fa-industry`, `fa-microchip`, `fa-server`
- **Usuario**: `fa-user-circle`, `fa-id-card`, `fa-camera`
- **Energía**: `fa-bolt`, `fa-leaf`, `fa-solar-panel`
- **Acciones**: `fa-sign-in-alt`, `fa-rocket`, `fa-chart-line`
- **Estado**: `fa-check-circle`, `fa-exclamation-triangle`, `fa-times-circle`

---

## 📱 Responsive

### Breakpoints Bootstrap 5
- **XS**: < 576px
- **SM**: ≥ 576px
- **MD**: ≥ 768px
- **LG**: ≥ 992px
- **XL**: ≥ 1200px
- **XXL**: ≥ 1400px

### Adaptaciones Móviles
- Hero title: `3.5rem` → `2.5rem`
- Section title: `2.5rem` → `2rem`
- Padding: `6rem 0` → `4rem 0`
- Card padding: `2.5rem` → `1.5rem`

---

## 🎬 Animaciones

### Keyframes Disponibles
```css
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}
```

---

**Actualizado**: 2026-03-06  
**Versión**: 1.0  
**Mantenedor**: PRERMI Development Team
