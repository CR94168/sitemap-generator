

<?php
require_once "simple-html-dom.php";

$target_url = $argv[1];

echo $target_url . "\n";
echo "creating sitemap ... \n";

//$target_file = $argv[2];
$target_file = getDomain($target_url) . "-sitemap.xml";
$target_file = "./sitemap-result/" . $target_file;

$start_url = $target_url;
$file = $target_file;

define('CLI', true);

$skip = array(
    "http://iprodev.com/print/",
);

// Define what file types should be scanned.
$extension = array(
    ".html",
    ".php",
    "/",
);

// Scan frequency
$freq = "daily";

// Page priority
$priority = "1.0";

// Init end ==========================

define('VERSION', "1.0");
define('NL', CLI ? "\n" : "<br>");

function rel2abs($rel, $base)
{
    if (strpos($rel, "//") === 0) {
        return "http:" . $rel;
    }
    /* return if  already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
    $first_char = substr($rel, 0, 1);
    /* queries and  anchors */
    if ($first_char == '#'  || $first_char == '?') return $base . $rel;
    /* parse base URL  and convert to local variables:
		$scheme, $host,  $path */
    extract(parse_url($base));
    /* remove  non-directory element from path */
    $path = preg_replace('#/[^/]*$#',  '', $path);
    /* destroy path if  relative url points to root */
    if ($first_char ==  '/') $path = '';
    /* dirty absolute  URL */
    $abs =  "$host$path/$rel";
    /* replace '//' or  '/./' or '/foo/../' with '/' */
    $re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
    for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
    }
    /* absolute URL is  ready! */
    return  $scheme . '://' . $abs;
}

function GetUrl($url)
{
    $agent = "Mozilla/5.0 (compatible; iProDev PHP XML Sitemap Generator/" . VERSION . ", http://iprodev.com)";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;
}

function Scan($url)
{
    global $start_url, $scanned, $pf, $extension, $skip, $freq, $priority;

    echo $url . NL;

    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (!filter_var($url, FILTER_VALIDATE_URL) || in_array($url, $scanned)) {
        return;
    }

    array_push($scanned, $url);
    $html = str_get_html(GetUrl($url));
    $a1   = $html->find('a');

    foreach ($a1 as $val) {
        $next_url = $val->href or "";

        $fragment_split = explode("#", $next_url);
        $next_url       = $fragment_split[0];

        if ((substr($next_url, 0, 7) != "http://")  &&
            (substr($next_url, 0, 8) != "https://") &&
            (substr($next_url, 0, 6) != "ftp://")   &&
            (substr($next_url, 0, 7) != "mailto:")
        ) {
            $next_url = @rel2abs($next_url, $url);
        }

        $next_url = filter_var($next_url, FILTER_SANITIZE_URL);

        if (substr($next_url, 0, strlen($start_url)) == $start_url) {
            $ignore = false;

            if (!filter_var($next_url, FILTER_VALIDATE_URL)) {
                $ignore = true;
            }

            if (in_array($next_url, $scanned)) {
                $ignore = true;
            }

            if (isset($skip) && !$ignore) {
                foreach ($skip as $v) {
                    if (substr($next_url, 0, strlen($v)) == $v) {
                        $ignore = true;
                    }
                }
            }

            if (!$ignore) {
                foreach ($extension as $ext) {
                    if (strpos($next_url, $ext) > 0) {
                        $pr = number_format(round($priority / count(explode("/", trim(str_ireplace(array("http://", "https://"), "", $next_url), "/"))) + 0.5, 3), 1);
                        fwrite($pf, "  <url>\n" .
                            "    <loc>" . htmlentities($next_url) . "</loc>\n" .
                            "    <changefreq>$freq</changefreq>\n" .
                            "    <priority>$pr</priority>\n" .
                            "  </url>\n");
                        Scan($next_url);
                    }
                }
            }
        }
    }
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



$pf = fopen($file, "w");
if (!$pf) {
    echo "Cannot create $file!" . NL;
    return;
}

$start_url = filter_var($start_url, FILTER_SANITIZE_URL);

$lastmod = new DateTime();
$show_lastmod = $lastmod->format(DateTimeInterface::ATOM);



fwrite($pf, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .

    "<!-- Created with CR94168 PHP XML Sitemap Generator " . VERSION . " CR94168 -->\n" .
    "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
    "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
    "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
    "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n" .
    "  <url>\n" .
    "    <loc>" . htmlentities($start_url) . "</loc>\n" .
    "    <lastmod>" . $show_lastmod . "</lastmod>\n" .
    "    <changefreq>$freq</changefreq>\n" .
    "    <priority>$priority</priority>\n" .
    "  </url>\n");

$scanned = array();
Scan($start_url);

fwrite($pf, "</urlset>\n");
fclose($pf);

echo "Done." . NL;
echo "$file created." . NL;
?>
