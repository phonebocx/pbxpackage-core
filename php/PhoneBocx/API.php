<?php

namespace PhoneBocx;

use GuzzleHttp\Client;

class API
{
    private string $command;
    private string $baseurl;
    private array $params = [];
    private PhoneBocx $pbx;

    public function __construct(string $command = "poll", string $urloverride = "")
    {
        $this->command = $command;
        if ($urloverride) {
            $this->baseurl = $urloverride;
        } else {
            $this->baseurl = "https://api.sendfax.to/device/v1";
        }
        $this->pbx = PhoneBocx::create();
        $this->genBaseParams();
    }

    public function launch()
    {
        $client = $this->getClient();
        $r = $client->post('', $this->params);
        if ($r->getStatusCode() > 299) {
            $fullurl = $this->getApiURL() . '?' . http_build_query($this->params['query']);
            throw new \Exception("Tried to post to $fullurl, status code was " . $r->getStatusCode() . " from " . $r->getBody());
        }
        return json_decode($r->getBody(), true);
    }

    public function addQueryParam(string $k, string $v)
    {
        $this->params['query'][$k] = $v;
    }

    public function addJsonVal(string $k, string $v)
    {
        $this->params['json'][$k] = $v;
    }

    public function getApiURL()
    {
        return trim($this->baseurl, "/") . "/" . $this->command;
    }

    public function getClient()
    {
        $g = new Client(['base_uri' => $this->getApiURL()]);
        return $g;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function genBaseParams()
    {
        $settings = $this->pbx->getSettings();
        $retarr = ["query" => [
            'product_uuid' => $this->pbx->getDevUuid(),
            'systemid' => $settings['systemid'] ?? 'ERRSYSID',
            'booted' => $this->pbx->getRunningOs(),
            'fax' => 'v3',
        ], "json" => []];
        foreach (['svceid', 'eid', 'tokuuid'] as $d) {
            $v = $settings[$d] ?? null;
            if ($v) {
                $retarr['json'][$d] = $v;
            }
        }
        if (file_exists("/etc/wireguard/public")) {
            $retarr['json']['wgpublic'] = trim(file_get_contents("/etc/wireguard/public"));
        }
        $this->params = $retarr;
        return $this->params;
    }

    public function addApiParams(): array
    {
        $e = CoreInfo::getInterfaceInfo();
        foreach ($e as $int => $data) {
            $mac = $data['address'] ?? null;
            if ($mac) {
                $this->params['json'][$int] = $mac;
            }
        }
        $dmi = glob("/sys/class/dmi/id/*");
        foreach ($dmi as $f) {
            if (!is_file($f)) {
                continue;
            }
            $n = basename($f);
            if ($n == 'uevent' || $n == 'modalias') {
                continue;
            }
            $this->params['json'][$n] = trim(file_get_contents($f));
        }
        // Force refresh
        $d = Dahdi::getDahdiScanCmd("true", false);
        if ($d) {
            $this->params['json']['dahdiscan'] = base64_encode($d);
        }
        return $this->params;
    }
}
