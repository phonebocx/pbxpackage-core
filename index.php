<?php
// Scaffolding for the html interface

use PhoneBocx\Packages;
use PhoneBocx\WebAuth;
use PhoneBocx\WebUI\DebugTools;

include "/usr/local/bin/phpboot.php";

$html = [
  "favicon" => "/core/sendfax.ico",
  "head" => [],
  "styles" => [],
  "headers" => [],
  "body" => [],
  "footer" => [],
  "scripts" => ["/core/js/jquery-3.6.0.min.js"],
  "rawscripts" => [],
  "end" => ["</html>"],
];

$action = $_REQUEST['action'] ?? '';
if ($action === 'logout') {
  WebAuth::logOut();
}

$packages = Packages::getLocalPackages();
$hookfiles = [];
foreach ($packages as $name => $dir) {
  $g = glob("$dir/meta/*webhook.php");
  foreach ($g as $hookfile) {
    $f = basename($hookfile);
    $id = str_replace('_webhook.php', '', $f);
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
foreach ($hookfiles as $p => $f) {
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
    $fname($html, $hookfiles);
    $includes[$fname] = $f;
  }
}

if (!empty($_REQUEST['debug'])) {
  $d = new DebugTools($_REQUEST);
  $d->setLoggedIn(WebAuth::isLoggedIn());
  $html = $d->updateHtmlArr($html);
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
print "<!-- start footers -->\n" . implode("\n", $html['footer']) . "\n<!-- end footers -->\n";
print "<!-- start scripts -->\n";
foreach ($html['scripts'] as $js) {
  print "  <script src='$js'></script>\n";
}
foreach ($html['rawscripts'] as $id => $raw) {
  print "  <script scriptid='$id'>$raw</script>\n";
}
print "<!-- end scripts -->\n";
print "</body>\n";
print implode("\n", $html['end']) . "\n";
