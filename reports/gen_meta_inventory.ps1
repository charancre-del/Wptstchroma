$files = Get-Content 'reports/php-files-list.txt'
$metaRows = @()
$saveRows = @()

$metaPattern = "add_meta_box\(\s*[""']([^""']+)[""']\s*,\s*[""']([^""']*)[""']\s*,\s*([^,]+?)\s*,\s*([^,\)]+)"
$savePattern = "add_action\(\s*[""'](save_post(?:_[a-zA-Z0-9_\-]+)?)[""']\s*,\s*([^,\)]+)"

foreach ($f in $files) {
    $content = Get-Content -Raw -Path $f

    foreach ($m in [regex]::Matches($content, $metaPattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)) {
        $metaRows += [pscustomobject]@{
            file           = $f
            box_id         = $m.Groups[1].Value.Trim()
            title          = $m.Groups[2].Value.Trim()
            callback       = ($m.Groups[3].Value -replace "\s+", " ").Trim()
            post_type_expr = ($m.Groups[4].Value -replace "\s+", " ").Trim()
        }
    }

    foreach ($s in [regex]::Matches($content, $savePattern, [System.Text.RegularExpressions.RegexOptions]::Singleline)) {
        $saveRows += [pscustomobject]@{
            file     = $f
            hook     = $s.Groups[1].Value.Trim()
            callback = ($s.Groups[2].Value -replace "\s+", " ").Trim()
        }
    }
}

$metaRows |
    Sort-Object file, box_id |
    Export-Csv -NoTypeInformation -Path 'reports/meta-box-inventory.csv'

$saveRows |
    Sort-Object file, hook, callback |
    Export-Csv -NoTypeInformation -Path 'reports/save-hook-inventory.csv'

Write-Output ("MetaBoxes=" + $metaRows.Count + " SaveHooks=" + $saveRows.Count)
