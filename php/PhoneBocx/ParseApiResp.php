<?php

namespace PhoneBocx;

class ParseApiResp
{
    private $lines = [];
    private $pb;

    public static function launch(string $file)
    {
        $p = new ParseApiResp($file);
        $p->go();
    }

    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new \Exception("$file is not a file");
        }
        foreach (file($file) as $line) {
            $t = trim($line);
            if (!$t || preg_match("/^[;#]/", $t)) {
                continue;
            }
            $this->lines[] = $t;
        }
        $this->pb = PhoneBocx::create();
    }

    public function go()
    {
        foreach ($this->lines as $l) {
            $j = json_decode($l, true);
            if (empty($j['actions'])) {
                return;
            }
            foreach ($j['actions'] as $a) {
                $func = "action_" . $a['type'];
                if (method_exists($this, $func)) {
                    $this->$func($a);
                }
            }
        }
        /*
        $wg = new UpdateWireguard();
        $wg->go();
        */
    }

    public function action_log($a)
    {
        print "I want to log whatever " . json_encode($a) . " is\n";
    }

    public function action_info($a)
    {
        foreach ($a['contents'] as $k => $v) {
            print "I am seting $k to $v\n";
            $this->pb->setKey($k, $v);
        }
    }
}
