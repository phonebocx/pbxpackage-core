<?php

namespace PhoneBocx\AsRoot;

class ConsoleScreenshot
{
    private string $rundir = "/var/run";
    private string $filename = "screenshot.png";
    private bool $readonly;
    private bool $isroot;
    private int $maxage = 300;

    public function __construct(bool $readonly = true)
    {
        $this->readonly = $readonly;
        $this->isroot = (posix_getuid() === 0);
        if (!$readonly && !$this->isroot) {
            throw new \Exception("I am not readonly, and not being run as root, I am being run as " . posix_getuid());
        }
    }

    public function needsRefresh(): bool
    {
        $age = $this->getScreenshotAge();
        if ($age === null || $age > $this->maxage) {
            return true;
        }
        return false;
    }

    public function getScreenshotFile(): string
    {
        return $this->rundir . "/" . $this->filename;
    }

    public function getScreenshotAge(): ?int
    {
        if (!file_exists($this->getScreenshotFile())) {
            return null;
        }
        $x = stat($this->getScreenshotFile());
        return time() - $x['mtime'];
    }

    public function captureScreenshot(?int $maxage = null): array
    {
        $retarr = ["capture" => false, "error" => false, "updated" => false, "readonly" => $this->readonly];
        if ($maxage === null) {
            $maxage = $this->maxage;
        }
        if ($this->needsRefresh()) {
            $retarr["capture"] = true;
            $retarr["reason"] = "needs refresh";
        }
        if ($maxage === -1) {
            $retarr["capture"] = true;
            $retarr["reason"] = "forced";
        }
        if (!$retarr["capture"]) {
            return $retarr;
        }
        if ($this->readonly) {
            // This shouldn't happen
            throw new \Exception("Read only console asked to update");
        }
        if (!$this->isroot) {
            throw new \Exception("I am not root, how did this happen?");
        }
        $retarr["tmpfile"] = $this->rundir . "/new-" . $this->filename;
        $retarr["cmd"] = "fbgrab -d /dev/fb0 " . $retarr["tmpfile"];
        $retarr["output"] = [];
        exec($retarr["cmd"], $retarr["output"], $retarr["result"]);
        $s = stat($retarr["tmpfile"]);
        $retarr["stat"] = $s;
        if ($retarr["result"] !== 0) {
            $retarr["reason"] = "fbgrab failed did not exit 0";
            $retarr["error"] = true;
            return $retarr;
        }
        if ($s["size"] < 5000) {
            $retarr["reason"] = "size of captured file too small";
            $retarr["error"] = true;
            return $retarr;
        }
        // OK, it captured it.
        chmod($retarr["tmpfile"], 0777);
        $destfile = $this->getScreenshotFile();
        $oldfile = "$destfile.prev";
        if (file_exists($oldfile)) {
            @unlink($oldfile);
        }
        if (file_exists($destfile)) {
            rename($destfile, $oldfile);
        }
        // Now put the captured one in place
        rename($retarr["tmpfile"], $destfile);
        $retarr["updated"] = true;
        return $retarr;
    }
}
