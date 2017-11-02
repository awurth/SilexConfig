<?php

/*
 * This file is part of the awurth/silex-config package.
 *
 * (c) Alexis Wurth <awurth.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AWurth\Silex\Config\Exception;

/**
 * Parameter Not Found Exception.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
class ParameterNotFoundException extends \InvalidArgumentException
{
    /**
     * @var string
     */
    private $key;

    /**
     * Constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Gets the key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
