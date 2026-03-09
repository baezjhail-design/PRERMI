# Exponer PRERMI mediante ngrok

Sigue estos pasos para exponer tu instalación local PRERMI (XAMPP) a internet y probarla en un celular.

Requisitos:
- XAMPP (Apache) corriendo y sirviendo el proyecto en `http://localhost/PRERMI` o puerto que uses.
- ngrok (https://ngrok.com/) instalado y autenticado.

1) Asegúrate de que Apache esté corriendo y puedas abrir `http://localhost/PRERMI/web/index.php` en tu equipo.

2) Abre una terminal (PowerShell) con privilegios normales y ejecuta (si tu Apache usa puerto 80):

```powershell
ngrok http 80 --host-header=localhost
```

Si Apache está en otro puerto (por ejemplo 8080):

```powershell
ngrok http 8080 --host-header=localhost:8080
```

3) ngrok te mostrará una URL pública, por ejemplo `https://abcd-1234.ngrok.io`. Abre esa URL en el móvil.

4) URLs y rutas: el frontend ahora usa rutas relativas (ej. `assets/css/style.css`, `admin/loginA.php`, `../api/...`). Si sirves desde `http://localhost/PRERMI`, la URL pública para acceder será `https://abcd-1234.ngrok.io/PRERMI/web/index.php` (o `https://abcd-1234.ngrok.io` si configuras ngrok para apuntar directamente al subdirectorio).

5) Opcional — forzar base URL (si tu configuración requiere cambiar `SERVER_BASE_URL` en `config`):
- Edita `d:\xampp\htdocs\PRERMI\config\app_config.php` o el archivo donde tengas definida la URL base y pon la URL pública de ngrok durante pruebas.

6) Recomendaciones:
- Para pruebas en móvil, usa el navegador en modo incógnito o limpia caché cuando cambies rutas.
- No uses credenciales reales en ambientes públicos.
- Para un proyecto más robusto, considera usar un proxy reverso en Apache que reescriba rutas y permita servir `PRERMI` desde la raíz.

7) Cerrar ngrok: Ctrl+C en la terminal donde se ejecuta ngrok.

Si quieres, genero un script PowerShell que arranque Apache (si usas XAMPP CLI) y luego ejecute ngrok automáticamente.