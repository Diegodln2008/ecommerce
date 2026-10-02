    <footer class="site-footer">
        <div class="container site-footer-inner">
            <a class="site-footer-brand" href="tienda-en-linea.php">FASTPACK<span>.</span></a>
            <p>Compras claras. Entregas con seguimiento.</p>
            <nav aria-label="Enlaces legales">
                <a href="aviso-privacidad.php">Aviso de privacidad</a>
                <a href="terminos-condiciones.php">Términos y condiciones</a>
                <a href="mailto:ventas@fastpack.mx">Contacto</a>
            </nav>
            <small>&copy; <?php echo date('Y'); ?> Fastpack. Todos los derechos reservados.</small>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <?php if (!empty($additionalScripts) && is_array($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($footerInlineScript)): ?>
        <script>
            <?php echo $footerInlineScript; ?>
        </script>
    <?php endif; ?>
    <?php if (!empty($additionalInline)): ?>
        <?php echo $additionalInline; ?>
    <?php endif; ?>
</body>
</html>
