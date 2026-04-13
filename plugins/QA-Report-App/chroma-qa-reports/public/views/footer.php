</main>
<?php
// Intentionally avoid full wp_footer() on the QA portal for the same reason
// as the custom header template above: only the portal's own scripts should run.
wp_print_footer_scripts();
?>
</body>

</html>
