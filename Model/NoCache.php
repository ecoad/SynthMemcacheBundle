<?php
namespace Synth\MemcachedBundle\Model;

/**
 * Cache control that doesn't save anything to a cache. Use only for development.
 *
 * @author Dom Udall <dom.udall@clock.co.uk>
 */
class NoCache {
    protected $cache = array();

    public function addServer($server = "127.0.0.1", $port = 11211) {
        return $this;
    }

    public function set($key, $data, $tags = false, $expire = false) {
        $this->cache[$key] = $data;
    }

    public function get($key) {
        return isset($this->cache[$key]) ? $this->cache[$key] : false;
    }

    public function getWithoutPrefix($key) {
        return $this->memcache->get($key);
    }

    public function start($key, $tag = false, $expire = false) {
        if ($content = $this->get($key)) {
            echo $content;
            return false;
        } else {
            $this->startStack[] = array($key, $tag, $expire);
            ob_start(array($this, "writeOutputBufferToCache"));
        }
        return true;
    }

    public function writeOutputBufferToCache($buffer) {
        $details = array_pop($this->startStack);
        $this->set($details[0], $buffer);
        return $buffer;
    }

    public function end($flush = true) {
        if ($flush) {
            ob_end_flush();
        } else {
            ob_end_clean();
        }
    }

    public function clearAll() { }

    public function clear($key) {
        unset($this->cache[$key]);
    }

    public function clearWithoutPrefix($key) { }

    public function clearTag($tag) { }

    public function getFileContents($filename, $expire = false, $context = null) { }

    public function clearFileContents($filename) { }

    public function listContents($filter = null) {
        return $this->cache;
    }
}