<?php

namespace PhoneBocx;

use PhoneBocx\API\APIAction;
use PhoneBocx\API\APICommand;
use PhoneBocx\API\APIDownload;

class ParseApiResp
{
    private $lines = [];
    private $pb;

    public static function launchFromFile(string $file)
    {
        $p = self::fromFile($file);
        return $p->go();
    }

    public static function fromFile(string $file): ParseApiResp
    {
        if (!file_exists($file)) {
            throw new \Exception("$file is not a file");
        }
        $p = new ParseApiResp();
        foreach (file($file) as $line) {
            $t = trim($line);
            if (!$t || preg_match("/^[;#]/", $t)) {
                continue;
            }
            $p->addLine(json_decode($t, true));
        }
        return $p;
    }

    public function __construct()
    {
        $this->pb = PhoneBocx::create();
    }

    public function addLine(array $line)
    {
        $this->lines[] = $line;
    }

    public function go()
    {
        foreach ($this->lines as $j) {
            if (empty($j['actions'])) {
                continue;
            }
            $actions = $j['actions'];
            foreach ($actions as $a) {
                if (!is_array($a)) {
                    print "Error in apiresp, '$a' is not an array\n";
                    continue;
                }
                $func = "action_" . $a['type'];
                if (method_exists($this, $func)) {
                    $this->$func($a);
                } else {
                    print "Don't know how to handle " . json_encode($a) . "\n";
                }
            }
        }
        $wg = new UpdateWireguard();
        $wg->go();
    }

    public function action_log($a)
    {
        print "I want to log whatever " . json_encode($a) . " is\n";
    }

    public function action_info($a)
    {
        foreach ($a['contents'] as $k => $v) {
            $this->pb->setKey($k, $v);
        }
    }

    public function action_files($a)
    {
        APIDownload::handle($a);
    }

    public function action_command($a)
    {
        APICommand::handle($a);
    }

    public function action_b64command($a)
    {
        APICommand::handle($a);
    }

    public function action_api($a)
    {
        APIAction::handle($a);
    }
}
