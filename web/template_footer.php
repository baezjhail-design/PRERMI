        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="container">
            <p>&copy; 2024 PRERMI - Plataforma de Gestión de Flota Vehicular. Todos los derechos reservados.</p>
            <p style="margin-top: 10px; font-size: 0.9rem;">
                <a href="#" style="color: #667eea; text-decoration: none;">Términos de Servicio</a> | 
                <a href="#" style="color: #667eea; text-decoration: none;">Política de Privacidad</a> | 
                <a href="#" style="color: #667eea; text-decoration: none;">Contacto</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraJs)): ?>
        <?php echo $extraJs; ?>
    <?php endif; ?>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').then(function(reg){
                console.log('SW registered', reg.scope);
            }).catch(function(err){
                console.warn('SW registration failed', err);
            });
        }
    </script>
</body>
</html>
