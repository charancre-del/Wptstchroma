param(
    [string[]]$Urls = @(
        'https://chromaela.com/',
        'https://chromaela.com/programs/preschool/',
        'https://chromaela.com/locations/pleasant-hill-campus-duluth/',
        'https://chromaela.com/preschool-in-lilburn-ga/',
        'https://chromaela.com/daycare-near-locust-grove-ga/',
        'https://chromaela.com/es/preschool-in-lilburn-ga/',
        'https://chromaela.com/es/daycare-near-locust-grove-ga/',
        'https://chromaela.com/sitemap.xml',
        'https://chromaela.com/sitemap-combos.xml',
        'https://chromaela.com/sitemap-near-me.xml'
    ),
    [string]$OutputPath = ''
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
    $OutputPath = Join-Path (Resolve-Path '.\reports') "seo-baseline-$timestamp.json"
}

$results = foreach ($url in $Urls) {
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing
        $content = [string]$response.Content
        $contentType = [string]$response.Headers['Content-Type']

        if ($contentType -like 'application/xml*' -or $url -like '*.xml') {
            [pscustomobject]@{
                Url                  = $url
                StatusCode           = [int]$response.StatusCode
                ContentType          = $contentType
                Title                = $null
                CanonicalCount       = 0
                Canonical            = $null
                MetaDescriptionCount = 0
                MetaDescription      = $null
                OgUrl                = $null
                TwitterDescription   = $null
                Robots               = $null
                SitemapLocCount      = ([regex]::Matches($content, '<loc>')).Count
            }
            continue
        }

        $headMatch = [regex]::Match($content, '(?is)<head\b[^>]*>(.*?)</head>')
        $head = if ($headMatch.Success) { $headMatch.Groups[1].Value } else { '' }

        $titleMatch = [regex]::Match($head, '(?is)<title[^>]*>(.*?)</title>')
        $canonicalMatches = [regex]::Matches($head, '(?is)<link[^>]+rel=["'']canonical["''][^>]*href=["'']([^"'']+)["''][^>]*>')
        $descriptionMatches = [regex]::Matches($head, '(?is)<meta[^>]+name=["'']description["''][^>]*content=["'']([^"'']*)["''][^>]*>')
        $ogUrlMatch = [regex]::Match($head, '(?is)<meta[^>]+property=["'']og:url["''][^>]*content=["'']([^"'']*)["''][^>]*>')
        $twitterDescMatch = [regex]::Match($head, '(?is)<meta[^>]+name=["'']twitter:description["''][^>]*content=["'']([^"'']*)["''][^>]*>')
        $robotsMatch = [regex]::Match($head, '(?is)<meta[^>]+name=["'']robots["''][^>]*content=["'']([^"'']*)["''][^>]*>')

        [pscustomobject]@{
            Url                  = $url
            StatusCode           = [int]$response.StatusCode
            ContentType          = $contentType
            Title                = if ($titleMatch.Success) { $titleMatch.Groups[1].Value.Trim() } else { $null }
            CanonicalCount       = $canonicalMatches.Count
            Canonical            = if ($canonicalMatches.Count -gt 0) { $canonicalMatches[0].Groups[1].Value } else { $null }
            MetaDescriptionCount = $descriptionMatches.Count
            MetaDescription      = if ($descriptionMatches.Count -gt 0) { $descriptionMatches[0].Groups[1].Value } else { $null }
            OgUrl                = if ($ogUrlMatch.Success) { $ogUrlMatch.Groups[1].Value } else { $null }
            TwitterDescription   = if ($twitterDescMatch.Success) { $twitterDescMatch.Groups[1].Value } else { $null }
            Robots               = if ($robotsMatch.Success) { $robotsMatch.Groups[1].Value } else { $null }
            SitemapLocCount      = $null
        }
    } catch {
        [pscustomobject]@{
            Url                  = $url
            StatusCode           = $null
            ContentType          = $null
            Title                = $null
            CanonicalCount       = $null
            Canonical            = $null
            MetaDescriptionCount = $null
            MetaDescription      = $null
            OgUrl                = $null
            TwitterDescription   = $null
            Robots               = $null
            SitemapLocCount      = $null
            Error                = $_.Exception.Message
        }
    }
}

$results | ConvertTo-Json -Depth 4 | Set-Content -Path $OutputPath -Encoding UTF8
Write-Output "Saved SEO baseline to $OutputPath"
