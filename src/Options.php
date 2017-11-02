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

/**
 * Options.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
class Options
{
    /**
     * @var bool
     */
    protected $enableImports = true;

    /**
     * @var bool
     */
    protected $enableParameters = true;

    /**
     * @var bool
     */
    protected $enableServices = true;

    /**
     * @var string
     */
    protected $importsKey = 'imports';

    /**
     * @var string
     */
    protected $parametersKey = 'parameters';

    /**
     * @var bool
     */
    protected $useParameterBag = true;

    /**
     * Tells whether imports are enabled.
     *
     * @return bool
     */
    public function areImportsEnabled()
    {
        return $this->enableImports;
    }

    /**
     * Sets whether to enable imports.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnableImports($enabled)
    {
        $this->enableImports = (bool) $enabled;

        return $this;
    }

    /**
     * Tells whether parameters are enabled.
     *
     * @return bool
     */
    public function areParametersEnabled()
    {
        return $this->enableParameters;
    }

    /**
     * Sets whether to enable parameters.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnableParameters($enabled)
    {
        $this->enableParameters = (bool) $enabled;

        return $this;
    }

    /**
     * Tells whether services are enabled.
     *
     * @return bool
     */
    public function areServicesEnabled()
    {
        return $this->enableServices;
    }

    /**
     * Sets whether to enable services.
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function setEnableServices($enabled)
    {
        $this->enableServices = (bool) $enabled;

        return $this;
    }

    /**
     * Gets the imports key.
     *
     * @return string
     */
    public function getImportsKey()
    {
        return $this->importsKey;
    }

    /**
     * Sets the imports key.
     *
     * @param string $key
     *
     * @return self
     */
    public function setImportsKey($key)
    {
        $this->importsKey = (string) $key;

        return $this;
    }

    /**
     * Gets the parameters key.
     *
     * @return string
     */
    public function getParametersKey()
    {
        return $this->parametersKey;
    }

    /**
     * Sets the parameters key.
     *
     * @param string $key
     *
     * @return self
     */
    public function setParametersKey($key)
    {
        $this->parametersKey = (string) $key;

        return $this;
    }

    /**
     * Returns whether to use the ParameterBag or an array of parameters.
     *
     * @return bool
     */
    public function getUseParameterBag()
    {
        return $this->enableParameters && $this->useParameterBag;
    }

    /**
     * Sets whether to use the ParameterBag or an array of parameters.
     *
     * @param bool $boolean
     *
     * @return self
     */
    public function setUseParameterBag($boolean)
    {
        $this->useParameterBag = (bool) $boolean;

        return $this;
    }
}
