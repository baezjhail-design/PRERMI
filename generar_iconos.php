<?php
// Script para generar iconos PNG desde SVG
// Se puede ejecutar manualmente si es necesario

function generatePNG($svgFile, $pngFile, $size) {
    if (!file_exists($svgFile)) {
        echo "Error: $svgFile no existe\n";
        return false;
    }
    
    // Intenta usar ImageMagick si está disponible
    if (extension_loaded('imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->readImage($svgFile);
            $imagick->setImageFormat("png");
            $imagick->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
            $imagick->writeImage($pngFile);
            echo "✓ Generado: $pngFile\n";
            return true;
        } catch (Exception $e) {
            echo "Error con ImageMagick: " . $e->getMessage() . "\n";
        }
    }
    
    echo "⚠ ImageMagick no disponible. Usa los iconos SVG en manifest.json\n";
    return false;
}

$basePath = __DIR__ . '/web/assets/icons/';
generatePNG($basePath . 'icon-192.svg', $basePath . 'icon-192.png', 192);
generatePNG($basePath . 'icon-512.svg', $basePath . 'icon-512.png', 512);
echo "\nDone!\n";
?>