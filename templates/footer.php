    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeo1kMZ6v7g3f4LM0U6E8nUJxC5z5pC5nRAx0E4N8qFra4gy" crossorigin="anonymous"></script>
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
