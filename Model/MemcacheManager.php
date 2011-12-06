<?php
namespace Synth\MemcachedBundle\Model;

use Memcached;

/**
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @author Dom Udall <dom@synthmedia.co.uk>
 */
class MemcachedManager
{
    /**
     * @var Memcached
     */
    protected $memcached;

     /**
     * @var array
     */
    protected $startStack;

    /**
     * Prefix all the memcache keys with the following
     * @var string
     */
    protected $keyPrefix;

    /**
     * This allows the clearing of the memcache values set for this application.
     *
     * @example http://code.google.com/p/memcached/wiki/FAQ#Deleting_by_Namespace
     */
    protected $originalKeyPrefix;

    public function __construct($memcachedClass, $servers, $keyPrefix = "")
    {
        $this->memcached = new $memcachedClass;

        foreach ($servers as $server) {
            if (!isset($server['host'])) {
                throw new \Exception("Memcached host must be set for server $server");
            }

            if (!isset($server['port'])) {
                throw new \Exception("Memcached port must be set for server $server");
            }

            if (!isset($server['weight'])) {
                $server['weight'] = 0;
            }

            $this->addServer($server['host'], $server['port'], $server['weight']);
        }

        $this->originalKeyPrefix = $keyPrefix;
    }

    protected function getNamespaceKey() {
        return $this->originalKeyPrefix . ":" . "__NamespaceKey";
    }

    protected function generateKeyPrefix() {

        $namespaceKey = $this->getNamespaceKey();

        $namespaceValue = $this->memcached->get($namespaceKey);

        if ($namespaceValue === false) {
            $this->memcached->set($namespaceKey, 1);
            $namespaceValue = 1;
        }

        $this->keyPrefix = $this->originalKeyPrefix . ":" . $namespaceValue . ":";

        return $this->keyPrefix;
    }

    protected function incrementNamespaceValue() {
        $namespaceKey = $this->getNamespaceKey();
        $this->memcached->increment($namespaceKey);
    }

    /**
     * Increments the given key by 1 using memcache.
     *
     * @param string $key The key to increment
     */
    public function increment($key) {
        $this->memcached->add($this->keyPrefix . $key, 0);
        $this->memcached->increment($this->keyPrefix . $key);
    }

    /**
     *
     * @param string $server
     * @param int $port
     * @return Atrox_Core_Cache_memcache
     */
    public function addServer($server = "127.0.0.1", $port = 11211, $weight = null) {
        $this->memcached->addServer($server, $port, $weight);
        $this->generateKeyPrefix();
        return $this;
    }

    /**
     *
     */
    public function set($key, $data, $tags = false, $expire = false) {
        $this->memcached->set($this->keyPrefix . $key, $data, false, $expire);

        if ($tags) {
            if (!is_array($tags)) {
                $tags = array($tags);
            }

            if (!$tagIndex = $this->memcached->get($this->keyPrefix . "__AtroxTagIndex")) {
                $tagIndex = array();
            }

            foreach ($tags as $tag) {
                $tagIndex[$tag][] = $key;
            }
            $this->memcached->set($this->keyPrefix . "__AtroxTagIndex", $tagIndex);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#get($key)
     */
    public function get($key) {
        return $this->memcached->get($this->keyPrefix . $key);
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#get($key)
     */
    public function getWithoutPrefix($key) {
        return $this->memcached->get($key);
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#start($key, $tag)
     */
    public function start($key, $tag = false, $expire = false) {
        if ($content = $this->memcached->get($this->keyPrefix . $key)) {
            echo $content;
            return false;
        } else {
            $this->startStack[] = array($key, $tag, $expire);
            ob_start(array($this, "writeOutputBufferToCache"));
        }
        return true;
    }

    /**
     *
     * @param $buffer
     * @return unknown_type
     */
    public function writeOutputBufferToCache($buffer) {
        $details = array_pop($this->startStack);
        $this->set($details[0], $buffer, $details[1], $details[2]);
        return $buffer;
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#end()
     */
    public function end($flush = true) {
        if ($flush) {
            ob_end_flush();
        } else {
            ob_end_clean();
        }
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#clearAll()
     */
    public function clearAll() {
        $this->incrementNamespaceValue();
        $this->generateKeyPrefix();
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#clear($key)
     */
    public function clear($key) {
        $this->memcached->delete($this->keyPrefix . $key);
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#get($key)
     */
    public function clearWithoutPrefix($key) {
        $this->memcached->delete($key);
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#clearTag($tag)
     */
    public function clearTag($tag) {
        if ($tagIndex = $this->memcached->get($this->keyPrefix . "__AtroxTagIndex")) {
            if (isset($tagIndex[$tag])) {
                foreach ($tagIndex[$tag] as $key) {
                    $this->clear($key);
                    unset($tagIndex[$tag]);
                }
            }
            $this->memcached->set($this->keyPrefix . "__AtroxTagIndex", $tagIndex);

        } else {
            $this->clearAll();
        }
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#getFileContents($filename, $expire, $context)
     */
    public function getFileContents($filename, $expire = false, $context = null) {
        $key = $this->keyPrefix . "__File:" . md5($filename);
        if ($data = $this->memcached->get($key)) {
            return $data;
        } else {
            try {
                $data = @file_get_contents($filename, 0, $context);
                $this->memcached->set($key, $data, false, $expire);
            } catch(Exception $e) {
                echo $e->getMessage();
                throw new Atrox_Core_Exception_NoSuchFileException("'{$filename}' does not exist");
            }
        }
        return $data;
    }

    /**
     * (non-PHPdoc)
     * @see Atrox/Core/Cache/Atrox_Core_Cache_ICache#clearFileContents($filename)
     */
    public function clearFileContents($filename) {
        $key = "__File:" . md5($filename);
        $this->clear($key);
    }

    public function listContents($filter = null) {
        $list = array();
        $allSlabs = $this->memcached->getExtendedStats("slabs");
        $items = $this->memcached->getExtendedStats("items");
        foreach ($allSlabs as $server => $slabs) {
            foreach ($slabs as $slabId => $slabMeta) {
                $cdump = $this->memcached->getExtendedStats("cachedump", (int)$slabId);
                foreach ($cdump as $server => $entries) {
                    if ($entries) {
                        foreach ($entries as $eName => $eData) {
                            $list[] = $eName;
                        }
                    }
                }
            }
        }
        sort($list