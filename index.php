<?php
// Scaffolding for the html interface

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

if (is_dir("/packages/core")) {
  $hooks = glob("/packages/*/meta/*_webhook.php");
  include "/packages/core/php/boot.php";
} else {
  $hooks = glob("/goldlinux/*/meta/*_webhook.php");
  include "/goldlinux/core/php/boot.php";
}

$hooknames = [];
foreach ($hooks as $f) {
  $hookname = basename(dirname(dirname($f)));
  $hooknames[] = $hookname;
  include $f;
  $fname = "${hookname}_mainhook";
  if (function_exists($fname)) {
    $fname($html);
  }
}

foreach ($hooknames as $hookname) {
  $fname = "${hookname}_footerhook";
  if (function_exists($fname)) {
    $fname($html, $hooknames);
  }
}

print '<!doctype html><html lang="en"><head profile="http://www.w3.org/2005/10/profile">' . implode("\n", $html['head']) . "\n";
print "<!-- styles -->\n" . implode("\n", $html['styles']) . "\n</head>\n";
print "<body>\n";
if (WebAuth::$isloggedin) {
  print "<script>window.isloggedin=true;</script>\n";
} else {
  print "<script>window.isloggedin=false;</script>\n";
}
print "<!-- headers -->\n" . implode("\n", $html['headers']) . "\n<!-- end headers -->\n";
print "<!-- start body -->\n" . implode("\n", $html['body']) . "\n<!-- end body -->\n";
print "<!-- start footers -->\n" . implode("\n", $html['footer']) . "\n<!-- end footers -->\n</body>\n";
print "<!-- start scripts -->\n";
foreach ($html['scripts'] as $js) {
  print "  <script src='$js'></script>\n";
}
print "<!-- end scripts -->\n";
print implode("\n", $html['end']) . "\n";
