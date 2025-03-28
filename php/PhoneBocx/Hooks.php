<?php

namespace PhoneBocx;

use Exception;
use PhoneBocx\Models\HookModel;

/** @package PhoneBocx */
class Hooks
{
    private PhoneBocx $pb;
    private array $phphooks;
    private array $autoloaders = [];
    private array $pkgsloaded = [];

    public function __construct(PhoneBocx $pb, array $phphooks)
    {
        $this->pb = $pb;
        $this->phphooks = $phphooks;
    }

    public function getPhpHooksArr(): array
    {
        return $this->phphooks;
    }

    /**
     * Triggers a hook. The HookModel object is passed around by reference
     * whenever possible. If there is some reason you can't pass your call
     * by reference, grab the updated model from $retarr['__model'] and use
     * that.
     *
     * @param string $hookname 
     * @param HookModel $model 
     * @return array 
     * @throws Exception 
     */
    public function trigger(string $hookname, HookModel $model)
    {
        $retarr = [];
        $funcs = $this->findHookFuncs($hookname);
        foreach ($funcs as $pkg => $f) {
            if (is_callable($f)) {
                $retarr[$pkg] = $f($model);
            } else {
                $tmparr = explode('::', $f);
                $classname = $tmparr[0];
                $func = $tmparr[1] ?? $hookname;
                if (!method_exists($classname, $func)) {
                    throw new \Exception("Tried to call $classname::$func triggered by $hookname but it does not exist. $pkg phphooks.php wrong?");
                }
                $retarr[$pkg] = $classname::$func($model);
            }
        }
        $retarr['__model'] = $model;
        return $retarr;
    }

    public function hasPkgBeenLoaded(string $pkg): bool
    {
        return (!empty($this->pkgsloaded[$pkg]));
    }

    public function processAutoloader(string $pkg): string
    {
        $data = $this->phphooks[$pkg]['hooks'] ?? [];
        if (empty($data['autoloader'])) {
            $this->pkgsloaded[$pkg] = '__none__';
            // No autoloader
            return "__none__";
        }
        if (is_callable($data['autoloader'])) {
            // Well, you know what you're doing.
            $data['autoloader']();
            $this->pkgsloaded[$pkg] = '__callable__';
            return "__callable__";
        }
        $path = $this->phphooks[$pkg]['dir'] . '/php/' . $data['autoloader'];
        if (!file_exists($path)) {
            throw new \Exception("Could not find $path");
        }
        // If 'autoloader_multiple' is set, we can include it again
        // even if we already have, so just unset our flag
        if (!empty($data['autoloader_multiple'])) {
            unset($this->autoloaders[$path]);
        }
        if (empty($this->autoloaders[$path])) {
            $this->autoloaders[$path] = true;
            $this->pkgsloaded[$pkg] = $path;
            include $path;
            return $path;
        }
        return "__duplicate__";
    }

    public function findHookFuncs(string $hookname)
    {
        $retarr = [];
        foreach ($this->phphooks as $pkg => $data) {
            $hooks = $data['hooks']['hooks'] ?? [];
            if (!empty($hooks[$hookname])) {
                $this->processAutoloader($pkg);
                $retarr[$pkg] = $hooks[$hookname];
            }
        }
        return $retarr;
    }

    public function findSchedulerFuncs()
    {
        $retarr = [];
        foreach ($this->phphooks as $pkg => $data) {
            $jobs = $data['hooks']['scheduler'] ?? [];
            if ($jobs) {
                $this->processAutoloader($pkg);
            }
            foreach ($jobs as $sname => $sclass) {
                $key = $pkg . "_" . $sname;
                $retarr[$key] = ["pkgname" => $pkg, "sname" => $sname, "sclass" => $sclass];
            }
        }
        return $retarr;
    }
}
