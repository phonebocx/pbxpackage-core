<?php
// Scaffolding for the html interface

use PhoneBocx\Packages;
use PhoneBocx\WebAuth;
use PhoneBocx\WebUI\DebugTools;

include "/usr/local/bin/phpboot.php";

// Defaults
$html = [
  "favicon" => "/core/favicon.ico",
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
$finalhooks = [];
// Loop through twice. This first one is looking for early and final hooks.
// Early is used to add overrides (eg setting title, name, icon), and final
// is there to potentially update anything that couldn't be done previously.
foreach ($hookfiles as $p => $f) {
  $ename = $p . "_earlyhook";
  if (function_exists($ename)) {
    $html = $ename($html);
  }
  $fname = $p . "_finalhook";
  if (function_exists($fname)) {
    $finalhooks[] = $fname;
  }
}

// Now process the hooks again, this time running them if needed
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

if (WebAuth::isLoggedIn()) {
  $isl = "<script>window.isloggedin=true;</script>";
} else {
  $isl = "<script>window.isloggedin=false;</script>";
}
$body = [
  '<!doctype html><html lang="en">',
  '<head profile="http://www.w3.org/2005/10/profile">',
  implode("\n", $html['head']),
  "<!-- styles -->",
  implode("\n", $html['styles']),
  "<!-- end styles -->",
  "</head>",
  "<body>",
  $isl,
  // "<p><pre>" . json_encode($includes) . "</pre></p>",
  "<!-- headers -->",
  implode("\n", $html['headers']),
  "<!-- end headers -->",
  "<!-- start body -->",
  implode("\n", $html['body']),
  "<!-- end body -->",
  "<!-- start footers -->",
  implode("\n", $html['footer']),
  "<!-- end footers -->",
  "<!-- start scripts -->",
];
foreach ($html['scripts'] as $js) {
  $body[] = "<script src='$js'></script>";
}
$body[] = "<!-- end scripts -->";
$body[] = "<!-- start raw scripts -->";
foreach ($html['rawscripts'] as $id => $raw) {
  $body[] = "  <script scriptid='$id'>$raw</script>";
}
$body[] = "<!-- end raw scripts -->";
$body[] = "</body>";
$body[] = "<!-- end section -->";
$body[] = implode("\n", $html['end']);

// If there are any final hooks, run them over what will be returned
// Finalhooks, obviously, need to be passed by ref.
foreach ($finalhooks as $fh) {
  $fh($body, $html);
}

print implode("\n", $body);
