<?php
// Scaffolding for the html interface

use PhoneBocx\Packages;
use PhoneBocx\WebAuth;

include __DIR__ . "/php/boot.php";

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

$packages = Packages::getLocalPackages();
$hookfiles = [];
foreach ($packages as $name => $dir) {
  $g = glob("$dir/meta/*webhook.php");
  foreach ($g as $hookfile) {
    $id = basename($hookfile);
    if (!empty($hookfiles[$id])) {
      print "Found a dupe hookfile $id in $hookfile, previously:\n";
      print json_encode($hookfiles) . "\n";
      exit;
    }
    $hookfiles[$id] = $hookfile;
    include $hookfile;
  }
}

$includes = [];
foreach ($packages as $p => $f) {
  if (strpos($f, "/origcore/") !== false) {
    continue;
  }
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
