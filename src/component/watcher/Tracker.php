<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/3
 * Time: 15:10
 */

namespace base\component\watcher;


use base\component\watcher\resource\ResourceInterface;

class Tracker {

    /**
     * 存放追踪资源的数组
     * @var array
     */
    protected $tracked = [];

    public function register(ResourceInterface $resource) {
        $descendants = [];
        if (is_dir($resource->getPath())) {
            $descendants = $resource->getDescendants();
        }
        $this->tracked[$resource->getKey()] = $resource;
        $this->tracked = array_merge($this->tracked, $descendants);
    }

    /**
     * 检查是否有改变
     */
    public function checkTrackings() {
        foreach ($this->tracked as $key => $res) {
            if ($res->detectChanges()) {
                echo $key . PHP_EOL;
                echo '======================change================================' . PHP_EOL;
                return true;
            }
        }
        return false;
    }

    public function getTracked() {
        return $this->tracked;
    }

}