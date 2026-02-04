<?php
require_once 'wp-load.php';
echo "react_settings_flag: " . (get_option('cqa_flag_react_settings') ? 'true' : 'false') . "\n";
echo "react_settings_default: " . (\ChromaQA\Feature_Flags::FLAGS['react_settings']['default'] ? 'true' : 'false') . "\n";
echo "page_on_front: " . get_option('page_on_front') . "\n";
echo "is_front_page: " . (is_front_page() ? 'true' : 'false') . "\n";
echo "is_home: " . (is_home() ? 'true' : 'false') . "\n";
@unlink(__FILE__);
