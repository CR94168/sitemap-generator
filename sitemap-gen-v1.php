<?php

$target_url = $argv[1];

echo $target_url . "\n";
echo "creating sitemap ... \n";

//$target_file = $argv[2];
$target_file = getDomain($target_url) . "-sitemap.xml";
$target_file = "./sitemap-result/" . $target_file;

$sitemap_template = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url>
<loc>' . $target_url . '</loc>
<lastmod>2022-01-01</lastmod>
<priority>1.0</priority>
</url>
</urlset>';

file_put_contents($target_file, $sitemap_template);

function getLastPathSegment($url)
{
    $path = parse_url($url, PHP_URL_PATH); // to get the path from a whole URL
    $pathTrimmed = trim($path, '/'); // normalise with no leading or trailing slash
    $pathTokens = explode('/', $pathTrimmed); // get segments delimited by a slash

    if (substr($path, -1) !== '/') {
        array_pop($pathTokens);
    }
    return end($pathTokens); // get the last segment
}

function getDomain($url)
{
    $pieces = parse_url($url);
    $domain = isset($pieces['host']) ? $pieces['host'] : $pieces['path'];
    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return $regs['domain'];
    }
    return false;
}
