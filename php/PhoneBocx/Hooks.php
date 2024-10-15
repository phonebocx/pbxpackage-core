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

    public function __construct(PhoneBocx $pb, array $phphooks)
    {
        $this->pb = $pb;
        $this->phphooks = $phphooks;
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
                    throw new \Exception("Tried to call $classname::$func triggered by $hookname but it does not exist");
                }
                $retarr[$pkg] = $classname::$func($model);
            }
        }
        $retarr['__model'] = $model;
        return $retarr;
    }

    public function processAutoloader(string $pkg)
    {
        $data = $this->phphooks[$pkg]['hooks'];
        if (empty($data['autoloader'])) {
            // No autoloader
            return;
        }
        if (is_callable($data['autoloader'])) {
            // Well, you know what you're doing.
            $data['autoloader']();
            return;
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
            include $path;
            $this->autoloaders[$path] = true;
        }
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
}
