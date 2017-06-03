<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/3
 * Time: 15:06
 */

namespace base\component\watcher;

use base\component\watcher\resource\DirectoryResource;
use base\component\watcher\resource\FileResource;
use base\component\watcher\Tracker;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;


class Watcher {
    /**
     * Tracker instance
     * @var  base\component\watcher\Tracker
     */
    protected $tracker;

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    /**
     * 注册一个被监听的resource
     * @param string $resource  [文件或者目录路径]
     */
    public function watch($resource) {
        if ( ! file_exists($resource) ) {
            throw new FileNotFoundException('Resource must be exist before you can watch it');
        }

        if (is_dir($resource)) {
            $resource = new DirectoryResource(new \SplFileInfo($resource));
            $resource->setupDirectory();
        } else {
            $resource = new FileResource($resource);
        }

        $this->tracker->register($resource);
    }

    public function start() {
//        print_r($this->tracker->getTracked());
        return $this->tracker->checkTrackings();
    }
}