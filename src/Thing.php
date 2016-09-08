<?php
/**
 * BittyPHP/Thing
 *
 * Licensed under The MIT License
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace BittyPHP\Thing;

class Thing implements \IteratorAggregate, \ArrayAccess, \Serializable
{
    /**
     * The raw(no filtered) data
     *
     * @var array
     */
    protected $_raw;

    /**
     * The source(filtered) data
     *
     * @var array
     */
    protected $_data;

    /**
     * The data loader script
     *
     * @var callable
     */
    protected $_loader;

    /**
     * The data filtering script
     *
     * @var callable
     */
    protected $_filter;

    /**
     * The item filtering scripts
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Initialize
     *
     * @param mixed $loader The data loading script
     * @param mixed $filter The data filtering script
     */
    public function __construct($loader = null, $filter = null)
    {
        if (!$this->setLoader($loader) && method_exists($this, 'loader')) {
            $this->setLoader(array($this, 'loader'));
        } elseif (
            empty($filter)
            && is_object($loader)
            && method_exists($loader, 'filter')
        ) {
            $this->setFilter(array($loader, 'filter'));
        }

        if (
            null === $this->_filter
            && !$this->setFilter($filter)
            && method_exists($this, 'filter')
        ) {
            $this->setFilter(array($this, 'filter'));
        }
    }

    /**
     * Initial loading source data
     */
    private function loadSourceData()
    {
        if (null !== $this->_raw) {
            return;
        }

        $this->_raw = $this->_data = array();

        if (null !== $this->_loader) {
            $res = $this->applyCallable($this->_loader);
            if (is_array($res)) {
                $this->_raw = $this->_data = $res;
            }
        }

        if (null !== $this->_filter) {
            $res = $this->applyCallable($this->_filter, $this->_raw);
            if (is_array($res)) {
                $this->_data = array_replace_recursive($this->_raw, $res);
            }
        }

        if (!empty($this->_filters)) {
            foreach ($this->_filters as $key => $filter) {
                if (array_key_exists($key, $this->_data)) {
                    $value = $this->_data[$key];
                    if (is_callable($filter)) {
                        $this->_data[$key] = $filter($value, $key);
                    }
                }
            }
        }
    }

    /**
     * Apply callable function
     *
     * @param  mixed $function The data loading script
     * @return mixed Result data
     */
    private function applyCallable($function, $args = null)
    {
        $res = null;
        if (is_callable($function)) {
            $res = $function($args);
        }
        return $res;
    }

    /**
     * Set data loader
     *
     * @param  mixed $function The data loading script
     * @return bool  Success or failed
     */
    public function setLoader($function)
    {
        if (is_callable($function)) {
            if ($function instanceof \Closure) {
                $function = $function->bindTo($this);
            }
            $this->_loader = $function;
            return true;
        } else {
            $class = null;
            if (is_object($function)) {
                $class = $function;
            } elseif (is_string($function) && class_exists($function)) {
                $class = new $function();
            }

            if (!empty($class) && method_exists($class, 'loader')) {
                $this->_loader = array($class, 'loader');
                return true;
            }
        }
        return false;
    }

    /**
     * Set data filter
     *
     * @param  mixed $function The data filtering script
     * @return bool  Success or failed
     */
    public function setFilter($function)
    {
        if (is_callable($function)) {
            if ($function instanceof \Closure) {
                $function = $function->bindTo($this);
            }
            $this->_filter = $function;
            return true;
        } else {
            $class = null;
            if (is_object($function)) {
                $class = $function;
            } elseif (is_string($function) && class_exists($function)) {
                $class = new $function();
            }

            if (!empty($class) && method_exists($class, 'filter')) {
                $this->_filter = array($class, 'filter');
                return true;
            }
        }
        return false;
    }

    /**
     * Add item filter
     *
     * @param  mixed  $name     The item key name
     * @param  mixed  $function The item filtering script
     * @return object Current object
     */
    public function addFilter($name, $function)
    {
        if (!is_scalar($name)) {
            throw new \InvalidArgumentException('Filter name must be scalar value.');
        }

        if ($function instanceof \Closure) {
            $function = $function->bindTo($this);
        }

        $this->_filters[$name] = $function;
        return $this;
    }

    /**
     * Get raw(no filtered) data
     *
     * @return array The collection's source data
     */
    public function raw()
    {
        $this->loadSourceData();
        return $this->_raw;
    }

    /**
     * Set collection item
     *
     * @param  mixed  $name  The data key
     * @param  mixed  $value The data value
     * @return object Current object
     */
    public function set($name, $value)
    {
        $this->loadSourceData();
        $this->_data[$name] = $value;
        return $this;
    }

    /**
     * Get collection item for key
     *
     * @param  string $key     The data key
     * @param  mixed  $default The default value to return if data key does not exist
     * @return mixed  The key's value, or the default value
     */
    public function get($name, $default = null)
    {
        $this->loadSourceData();
        return array_key_exists($name, $this->_data) ? $this->_data[$name] : $default;
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     * @return bool
     */
    public function has($name)
    {
        $this->loadSourceData();
        return array_key_exists($name, $this->_data);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function remove($name)
    {
        $this->loadSourceData();
        unset($this->_data[$name]);
    }

    /**
     * Remove all items from collection
     */
    public function clear()
    {
        $this->_data = array();
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        $this->loadSourceData();
        return $this->_data;
    }

    /**
     * Get collection keys
     *
     * @return array The collection's source data keys
     */
    public function keys()
    {
        $this->loadSourceData();
        return array_keys($this->_data);
    }

    /**
     * Get collection values
     *
     * @return array The collection's source data values
     */
    public function values()
    {
        $this->loadSourceData();
        return array_values($this->_data);
    }

    /**
     * Returns true if $value is present in this collection.
     *
     * @param  mixed $value The value to check for
     * @return bool  true if $value is present in this collection
     */
    public function contains($value, $strict = true)
    {
        $this->loadSourceData();
        return in_array($value, $this->_data, $strict);
    }

    /**
     * Detect item is empty
     *
     * @param bool
     */
     public function isEmpty($name)
     {
         $this->loadSourceData();
         if (array_key_exists($name, $this->_data)) {
             $value = $this->_data[$name];
             return ('' === $value || null === $value || false === $value);
         }

         return true;
     }


    /***************************************************************************
     * Magick methods
     **************************************************************************/

    /**
     * Get collection item for key
     *
     * @param  string $name The data key
     * @return mixed  The key's value, or the default value
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Set collection item
     *
     * @param string $name  The data key
     * @param mixed  $value The data value
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $name The data key
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Remove item from collection
     *
     * @param string $name The data key
     */
    public function __unset($name)
    {
        return $this->remove($name);
    }

    /**
     * Collection cloning
     *
     * @param object Cloned Collection object
     */
    public function __clone()
    {
        if (!empty($this->_data)) {
            foreach ($this->_data as $key => $value) {
                if (is_object($value)) {
                    $this->_data[$key] = clone $value;
                } else {
                    $this->_data[$key] = $value;
                }
            }
        }
    }


    /***************************************************************************
     * ArrayAccess interface
     **************************************************************************/

    /**
     * Get collection item for key
     *
     * @param  string $name The data key
     * @return mixed  The key's value, or the default value
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * Set collection item
     *
     * @param string $name  The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Does this collection have a given key?
     *
     * @param  string $name The data key
     * @return bool
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Remove item from collection
     *
     * @param string $name The data key
     */
    public function offsetUnset($name)
    {
        return $this->remove($name);
    }


    /***************************************************************************
     * IteratorAggregate interface
     **************************************************************************/

    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $this->loadSourceData();
        return new \ArrayIterator($this->_data);
    }


    /***************************************************************************
     * Serializable interface
     **************************************************************************/

    /**
     * Get serialized data
     *
     * @return string PHP Serialized string
     */
    public function serialize()
    {
        $this->loadSourceData();
        return serialize($this->_data);
    }

    /**
     * Set serialized data
     *
     * @return object Current Collection object
     */
    public function unserialize($data)
    {
        $this->loadSourceData();
        return $this->set(unserialize($data));
    }
}
