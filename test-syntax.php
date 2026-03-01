<?php

$dirs = [
    __DIR__ . '/plugins/chroma-seo-pro-reset',
    __DIR__ . '/plugins/chroma-tour-form',
    __DIR__ . '/chroma-excellence-theme'
];

$has_errors = false;

foreach ($dirs as $dir) {
    if (!is_dir($dir))
        continue;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isDir())
            continue;
        if (pathinfo($file->getFilename(), PATHINFO_EXTENSION) !== 'php')
            continue;

        $path = $file->getPathname();
        $output = [];
        $return_var = 0;

        exec("php -l " . escapeshellarg($path) . " 2>&1", $output, $return_var);

        if ($return_var !== 0) {
            $has_errors = true;
            echo "ERROR in $path:\n";
            echo implode("\n", $output) . "\n\n";
        }
    }
}

if (!$has_errors) {
    echo "No syntax errors found in the scanned directories.\n";
}
