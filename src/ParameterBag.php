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

use AWurth\Silex\Config\Exception\ParameterCircularReferenceException;
use AWurth\Silex\Config\Exception\ParameterNotFoundException;
use RuntimeException;

/**
 * Parameter Bag.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
class ParameterBag
{
    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var bool
     */
    protected $resolved = false;

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

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     */
    public function resolve()
    {
        if ($this->resolved) {
            return;
        }

        $parameters = [];
        foreach ($this->parameters as $key => $value) {
            $value = $this->resolveValue($value);
            $parameters[$key] = $this->unescapeValue($value);
        }

        $this->parameters = $parameters;
        $this->resolved = true;
    }

    /**
     * Replaces parameter placeholders (%name%) by their values.
     *
     * @param mixed $value     A value
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     *
     * @throws ParameterNotFoundException          if a placeholder references a parameter that does not exist
     * @throws ParameterCircularReferenceException if a circular reference if detected
     * @throws RuntimeException                    when a given parameter has a type problem
     */
    public function resolveValue($value, array $resolving = [])
    {
        if (is_array($value)) {
            $args = [];
            foreach ($value as $k => $v) {
                $args[$this->resolveValue($k, $resolving)] = $this->resolveValue($v, $resolving);
            }

            return $args;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->resolveString($value, $resolving);
    }

    /**
     * Resolves parameters in a string.
     *
     * @param string $value     The string to resolve
     * @param array  $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved string
     *
     * @throws ParameterNotFoundException          if a placeholder references a parameter that does not exist
     * @throws ParameterCircularReferenceException if a circular reference if detected
     * @throws RuntimeException                    when a given parameter has a type problem
     */
    public function resolveString($value, array $resolving = [])
    {
        // We do this to deal with non-string values (boolean, integer, ...)
        // as the preg_replace_callback throws an exception when trying
        // a non-string in a parameter value
        if (preg_match('/^%([^%\s]+)%$/', $value, $match)) {
            $key = $match[1];

            if (isset($resolving[$key])) {
                throw new ParameterCircularReferenceException(array_keys($resolving));
            }

            $resolving[$key] = true;

            return $this->resolved ? $this->get($key) : $this->resolveValue($this->get($key), $resolving);
        }

        return $this->resolveInsideString($value, $resolving);
    }

    /**
     * Resolves parameters inside a string.
     *
     * @param string $value     The string to resolve
     * @param array  $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return string The resolved string
     *
     * @throws ParameterCircularReferenceException if a circular reference if detected
     * @throws RuntimeException                    when a given parameter has a type problem
     */
    protected function resolveInsideString($value, array $resolving = [])
    {
        return preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($resolving, $value) {
            // Skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            $key = $match[1];
            if (isset($resolving[$key])) {
                throw new ParameterCircularReferenceException(array_keys($resolving));
            }

            $resolved = $this->get($key);

            if (!is_string($resolved) && !is_numeric($resolved)) {
                throw new RuntimeException(sprintf('A string value must be composed of strings and/or numbers, but found parameter "%s" of type %s inside string value "%s".', $key, gettype($resolved), $value));
            }

            $resolved = (string) $resolved;
            $resolving[$key] = true;

            return $this->isResolved() ? $resolved : $this->resolveString($resolved, $resolving);
        }, $value);
    }

    /**
     * Returns whether the parameters are resolved.
     *
     * @return bool
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * Escape parameter placeholders %.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function escapeValue($value)
    {
        if (is_string($value)) {
            return str_replace('%', '%%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->escapeValue($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * Unescape parameter placeholders %.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function unescapeValue($value)
    {
        if (is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->unescapeValue($v);
            }

            return $result;
        }

        return $value;
    }
}
