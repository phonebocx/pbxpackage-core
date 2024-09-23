<?php
// Scaffolding for the html interface

use PhoneBocx\WebAuth;

$html = [
  "favicon" => "/core/sendfax.ico",
  "head" => [],
  "styles" => [],
  "headers" => [],
  "body" => [],
  "footer" => [],
  "scripts" => ["/core/js/jquery-3.6.0.min.js"],
  "end" => ["</html>"],
];

$devdir = "/pbxdev";
$livedir = "/pbx";

// Loop over everything to find boot before we loop again to load
// modules.
$booted = false;
foreach ([$devdir, $livedir] as $b) {
  $boot = "$b/core/php/boot.php";
  if (file_exists($boot)) {
    include $boot;
    $booted = true;
    break;
  }
}

if (!$booted) {
  print "Could not boot!";
  exit;
}

$packages = [];
$livehooks = glob("$livedir/*/meta/*_webhook.php");
$devhooks = glob("$devdir/*/meta/*_webhook.php");

// Go through the live hooks
foreach ($livehooks as $l) {
  $chunks = explode('_', basename($l));
  $packages[$chunks[0]] = $l;
}

// But if there's any in dev, use those instead
foreach ($devhooks as $l) {
  $chunks = explode('_', basename($l));
  $packages[$chunks[0]] = $l;
}

$includes = [];

foreach ($packages as $p => $f) {
  if (strpos($f, "/origcore/") !== false) {
    continue;
  }
  include $f;
  $mname = $p . "_mainhook";
  if (function_exists($mname)) {
    $includes[$mname] = $f;
    $mname($html);
  }
  $fname = $p . "_footerhook";
  if (function_exists($fname)) {
    // Footerhooks have the package array passed to it
    $fname($html, $packages);
    $includes[$fname] = $f;
  }
}

print '<!doctype html><html lang="en"><head profile="http://www.w3.org/2005/10/profile">' . implode("\n", $html['head']) . "\n";
print "<!-- styles -->\n" . implode("\n", $html['styles']) . "\n</head>\n";
print "<body>\n";
if (WebAuth::isLoggedIn()) {
  print "<script>window.isloggedin=true;</script>\n";
} else {
  print "<script>window.isloggedin=false;</script>\n";
}
// print "<p><pre>" . json_encode($includes) . "</pre></p>\n";
print "<!-- headers -->\n" . implode("\n", $html['headers']) . "\n<!-- end headers -->\n";
print "<!-- start body -->\n" . implode("\n", $html['body']) . "\n<!-- end body -->\n";
print "<!-- start footers -->\n" . implode("\n", $html['footer']) . "\n<!-- end footers -->\n</body>\n";
print "<!-- start scripts -->\n";
foreach ($html['scripts'] as $js) {
  print "  <script src='$js'></script>\n";
}
print "<!-- end scripts -->\n";
print implode("\n", $html['end']) . "\n";
