<?php
require_once('wp-load.php');
update_option('chroma_faq_schema_disabled', 'yes');
update_option('chroma_breadcrumbs_schema_disabled', 'yes');
echo "Chroma FAQ and Breadcrumb Schemas have been disabled.";
unlink(__FILE__);
?>
