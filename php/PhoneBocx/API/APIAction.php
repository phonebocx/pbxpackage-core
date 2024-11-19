<?php

namespace PhoneBocx\API;

use PhoneBocx\API;
use PhoneBocx\ParseApiResp;

class APIAction
{
  public static function handle($a)
  {
    $c = $a['contents'] ?? null;
    if (!$c) {
      throw new \Exception("No contents on " . json_encode($a));
    }
    $url = $c['url'] ?? null;
    if ($url) {
      throw new \Exception("URL override not implemented");
    }
    $api = new API($c['request'] ?? null);
    if (!empty($c['full'])) {
      $api->addApiParams();
    }
    // print "Params are " . json_encode($api->getParams()) . "\n";
    $resp = $api->launch();
    foreach ($resp as $k => $v) {
      if ($k == 'actions') {
        foreach ($v as $i => $row) {
          $rtype = $row['type'] ?? 'err';
          if ($rtype == 'api') {
            // No recursive API calls
            $resp['actions'][$i]['type'] = 'noapi';
          }
        }
      }
    }
    // Now, be recursively recursive
    $p = new ParseApiResp();
    $p->addLine($resp);
    $p->go();
  }
}
