# Reportes Automáticos de Gráficas

Esta carpeta almacena automáticamente los archivos de datos generados por el panel de gráficas del sistema BIOMASA.

## Estructura de Archivos

Cuando presionas el botón "💾 Descargar Datos" en el panel de gráficas, se generan dos archivos:

1. **JSON** (`datos_graficas_[periodo]_[timestamp].json`) - Datos en formato JSON
   - Contiene un resumen con estadísticas de temperatura, energía y ganancias
   - Incluye la fecha de generación

2. **CSV** (`datos_graficas_[periodo]_[timestamp].csv`) - Datos en formato CSV
   - Tabla con todas las mediciones individuales
   - Columnas: Fecha, Hora, Temperatura, Energía, Relay, Ventilador, Peltier1, Peltier2, Gases
   - Compatible con Excel, Google Sheets, y otros programas de análisis

## Características

✅ Los datos se guardan con timestamp automático
✅ Los archivos son independientes y portables
✅ Puedes analizar los datos offline
✅ Compatible con herramientas de análisis de datos

## Acceso a los Datos

- **Ubicación**: `PRERMI/BIOMASA/reportes/`
- **Generación automática**: Cada vez que descargas datos desde el panel

## Precios Configurados

- **Precio por kWh**: 65.00 RD$ (Pesos Dominicanos)
- Ajusta este valor en la API: `obtener_datos_graficas.php` (línea ~120)
