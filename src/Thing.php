<?php
/**
 * BittyPHP/Thing
 *
 * Licensed under The MIT License
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace BittyPHP;

class Thing extends \ArrayObject
{
    /**
     * Stored Hook functions
     * @var array
     */
    private $hooks = array();

    /**
     * Constructor
     *
     * @param array $input Source array data
     * @param array $hooks Hook functions
     */
    public function __construct(array $input = array(), $hooks = array())
    {
        parent::__construct(array(), self::ARRAY_AS_PROPS);

        if (!empty($hooks) && is_array($hooks)) {
            foreach ($hooks as $key => $val) {
                $this->hook($key, $val);
            }
        }

        $this->set($input);
    }

    /**
     * Set values
     * @param mixed $arg1 Item key name or Source array data
     * @param mixed $arg2 Item value if $arg1 is key name
     */
    public function set($arg1, $arg2 = null)
    {
        if (is_scalar($arg1)) {
            $this->offsetSet($arg1, $arg2);
        } elseif (is_array($arg1) || $arg1 instanceof \Traversable) {
            foreach ($arg1 as $key => $val) {
                $this->offsetSet($key, $val);
            }
        } elseif (!empty($arg1)) {
            throw new \InvalidArgumentException(sprintf('Invalid type. Input was %s', gettype($arg1)));
        }
    }

    /**
     * Set value getting Hook
     *
     * @param  $name Array key name
     * @param callable $hook Value replace script
     */
    public function hook($name, $hook)
    {
        if (!is_callable($hook)) {
            throw new \InvalidArgumentException('Argument 2 must be callable.');
        }

        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
        }

        $this->offsetSet($name, null);
        $this->hooks[$name] = $hook->bindTo($this);
    }

    /**
     * Override ArrayObject::offsetGet method with Hook
     *
     * @param  mixed $input The new array or object to exchange with the current array.
     * @return array The old array.
     */
    public function offsetGet($name)
    {
        if (!empty($this->hooks[$name])) {
            $hook = $this->hooks[$name];
            $this->offsetSet($name, $hook());
            unset($this->hooks[$name]);
        }

        if ($this->offsetExists($name)) {
            return parent::offsetGet($name);
        }
    }

    /**
     * Override ArrayObject::exchangeArray method
     *
     * @param  mixed $input The new array or object to exchange
     *                      with the current array.
     * @return array The old array.
     */
    public function exchangeArray($input)
    {
        $old = parent::exchangeArray(array());
        $this->set($input);
        return $old;
    }

    /**
     * Is item has child?
     *
     * @param  mixed   $name Item key name
     * @return boolean
     */
    public function hasChild($name)
    {
        if (parent::offsetExists($name)) {
            $value = parent::offsetGet($name);
            return (is_array($value) || $value instanceof \Traversable);
        }
        return false;
    }



    /***************************************************************************
     * Other helper methods
     **************************************************************************/

    /**
     * Convert to JSON
     *
     * @param  integer $options json_encode constants
     * @param  integer $depth   Maximum depth
     * @return string  JSON string
     */
    public function toJSON($options = 0, $depth = 512)
    {
        return json_encode(parent::getArrayCopy(), $options, $depth);
    }

    /**
     * Alter array_* function
     * @param  string $name Function name
     * @param  mixed  $args Arguments
     * @return mixed
     */
    public function __call($name, $args = array())
    {
        if (function_exists('array_'.$name)) {
            $name = strtolower($name);
            if (
                   'keys' === $name
                || 'values' === $name
                || 'count_values' === $name
                || 0 === strncmp($name, 'diff', 4)
                || 'slice' === $name
            ) {
                $args = array_merge(array(parent::getArrayCopy()), $args);
                return call_user_func_array('array_'.$name, $args);
            }
        }

        throw new \BadMethodCallException(sprintf('Method "%s" is not defined', $name));
    }



    /***************************************************************************
     * Method aliases
     **************************************************************************/

    public function has($name)
    {
        parent::offsetExists($name);
    }

    public function get($name)
    {
        parent::offsetGet($name);
    }

    public function remove($name)
    {
        parent::offsetUnset($name);
    }

    public function toArray()
    {
        return parent::getArrayCopy();
    }
}
