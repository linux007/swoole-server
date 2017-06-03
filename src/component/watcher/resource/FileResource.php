<?php
/**
 * Created by PhpStorm.
 * User: yuyc
 * Date: 2017/6/3
 * Time: 15:13
 */

namespace base\component\watcher\resource;

use base\component\watcher\resource\ResourceInterface;

class FileResource implements ResourceInterface {
    /**
     * SplFileInfo instance
     * @var \SplFileInfo
     */
    protected $resource;

    /**
     * resource的绝对路径
     * @var string
     */
    protected $path;

    /**
     * 资源文件的最后修改时间
     * @var
     */
    protected $lastModified;
    /**
     * resource文件是否存在
     * @var
     */
    protected $exists;

    public function __construct(\SplFileInfo $resource) {
        $this->resource = $resource;
        $this->path = $resource->getRealPath();
        $this->exists = file_exists($this->path);
        $this->lastModified = ! $this->exists ?: filemtime($this->path);
    }

    public function detectChanges() {
        clearstatcache(true, $this->path);

        // create
        if (! $this->exists && file_exists($this->path)) {
            $this->lastModified = filemtime($this->path);
            $this->exists = true;

            return true;
        } elseif ($this->exists && ! file_exists($this->path)) {
            //unlink
            $this->exists = false;

            return true;
        } elseif ($this->exists && $this->isModified()) {
            //modified
            $this->lastModified = filemtime($this->path);

            return true;
        }

        return false;
    }

    /**
     * 判断文件是否已经被修改
     * @return bool
     */
    public function isModified() {
        return $this->lastModified < filemtime($this->path);
    }

    public function getKey() {
        return md5($this->path);
    }

    public function getPath() {
        return $this->path;
    }
}