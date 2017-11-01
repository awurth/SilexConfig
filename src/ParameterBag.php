<?php

/*
 * This file is part of the awurth/silex-config package.
 *
 * (c) Alexis Wurth <awurth.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AWurth\Silex\Config;

use AWurth\Silex\Config\Exception\ParameterNotFoundException;

/**
 * Parameter Bag.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
class ParameterBag
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * Constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->add($parameters);
    }

    /**
     * Adds parameters.
     *
     * @param array $parameters
     */
    public function add(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Gets all parameters.
     *
     * @return array
     */
    public function all()
    {
        return $this->parameters;
    }

    /**
     * Clears all parameters.
     */
    public function clear()
    {
        $this->parameters = [];
    }

    /**
     * Gets a parameter.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws ParameterNotFoundException
     */
    public function get($name)
    {
        $name = (string) $name;

        if (!array_key_exists($name, $this->parameters)) {
            throw new ParameterNotFoundException($name);
        }

        return $this->parameters[$name];
    }

    /**
     * Returns true if a parameter name is defined.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists((string) $name, $this->parameters);
    }

    /**
     * Removes a parameter.
     *
     * @param string $name
     */
    public function remove($name)
    {
        unset($this->parameters[(string) $name]);
    }

    /**
     * Sets a parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value)
    {
        $this->parameters[(string) $name] = $value;
    }
}
