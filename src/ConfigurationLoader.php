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

use AWurth\Silex\Config\Loader\JsonFileLoader;
use AWurth\Silex\Config\Loader\PhpFileLoader;
use AWurth\Silex\Config\Loader\YamlFileLoader;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Configuration Loader.
 *
 * @author Alexis Wurth <awurth.dev@gmail.com>
 */
class ConfigurationLoader
{
    /**
     * @var ConfigCacheInterface
     */
    protected $cache;

    /**
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * @var LoaderInterface[]
     */
    protected $loaders;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var FileResource[]
     */
    protected $resources;

    /**
     * Constructor.
     *
     * @param string $cachePath
     * @param bool   $debug
     */
    public function __construct($cachePath = null, $debug = false)
    {
        $this->resources     = [];
        $this->options       = new Options();
        $this->parameterBag  = new ParameterBag();

        if (null !== $cachePath) {
            $this->cache = new ConfigCache($cachePath, $debug);
        }
    }

    /**
     * Loads the configuration from a cache file if it exists, or parses a configuration file if not.
     *
     * @param string $file
     *
     * @return array
     */
    public function load($file)
    {
        $this->resources = [];

        if (null !== $this->cache) {
            if (!$this->cache->isFresh()) {
                $this->export($this->loadFile($file));
            }

            return self::requireFile($this->cache->getPath());
        }

        return $this->loadFile($file);
    }

    /**
     * Exports the configuration to a cache file.
     *
     * @param array $configuration
     */
    public function export(array $configuration)
    {
        $content = '<?php'.PHP_EOL.PHP_EOL.'return '.var_export($configuration, true).';'.PHP_EOL;

        $this->cache->write($content, $this->resources);
    }

    /**
     * Gets the configuration cache.
     *
     * @return ConfigCacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Sets the configuration cache.
     *
     * @param ConfigCacheInterface $cache
     */
    public function setCache(ConfigCacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Gets the file loaders.
     *
     * @return LoaderInterface[]
     */
    public function getLoaders()
    {
        return $this->loaders;
    }

    /**
     * Adds a file loader.
     *
     * @param LoaderInterface $loader
     *
     * @return self
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;

        return $this;
    }

    /**
     * Sets the file loaders.
     *
     * @param LoaderInterface[] $loaders
     */
    public function setLoaders(array $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * Gets the parameter bag.
     *
     * @return ParameterBag
     */
    public function getParameterBag()
    {
        return $this->parameterBag;
    }

    /**
     * Gets the options.
     *
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the options.
     *
     * @param Options $options
     */
    public function setOptions(Options $options)
    {
        $this->options = $options;
    }

    /**
     * Initializes the file loader.
     */
    protected function initLoader()
    {
        if (null === $this->loader) {
            $this->addLoader(new PhpFileLoader());
            $this->addLoader(new YamlFileLoader());
            $this->addLoader(new JsonFileLoader());

            $this->loader = new DelegatingLoader(new LoaderResolver($this->loaders));
        }
    }

    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file
     *
     * @return bool
     */
    protected function isAbsolutePath($file)
    {
        if ('/' === $file[0] || '\\' === $file[0]
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && ':' === $file[1]
                && ('\\' === $file[2] || '/' === $file[2])
            )
            || null !== parse_url($file, PHP_URL_SCHEME)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Loads the configuration from a file.
     *
     * @param string $file
     *
     * @return array
     */
    protected function loadFile($file)
    {
        $this->initLoader();

        $configuration = $this->parseFile($file);

        if ($this->options->areParametersEnabled()) {
            if (isset($configuration[$this->options->getParametersKey()])) {
                $this->parameterBag->add($configuration[$this->options->getParametersKey()]);
                $this->parameterBag->resolve();
            }

            $configuration = $this->resolve($configuration);
        }

        return $configuration;
    }

    /**
     * Loads an imported file.
     *
     * @param string $path
     * @param string $originalFile
     *
     * @return array
     */
    protected function loadImport($path, $originalFile)
    {
        if ($this->isAbsolutePath($path) && file_exists($path)) {
            return $this->parseFile($path);
        }

        return $this->parseFile(dirname($originalFile).DIRECTORY_SEPARATOR.$path);
    }

    /**
     * Loads file imports recursively.
     *
     * @param array  $values
     * @param string $originalFile
     *
     * @return array
     */
    protected function loadImports(array $values, $originalFile)
    {
        $importsKey = $this->options->getImportsKey();

        if (isset($values[$importsKey])) {
            $imports = $values[$importsKey];

            if (is_string($imports)) {
                $values = $this->merge($values, $this->loadImport($imports, $originalFile));
            } elseif (is_array($imports)) {
                foreach ($imports as $key => $file) {
                    $import = $this->loadImport($file, $originalFile);

                    $values = $this->merge($values, is_string($key) ? [$key => $import] : $import);
                }
            }
        }

        unset($values[$importsKey]);

        return $values;
    }

    /**
     * Merges values into the configuration.
     *
     * @param array $configuration
     * @param array $values
     *
     * @return array
     */
    protected function merge(array $configuration, array $values)
    {
        return array_replace_recursive($values, $configuration);
    }

    /**
     * Parses a configuration file.
     *
     * @param string $file
     *
     * @return array
     */
    protected function parseFile($file)
    {
        $values = (array) $this->loader->load($file);

        if (!empty($values) && $this->options->areImportsEnabled()) {
            $values = $this->loadImports($values, $file);
        }

        $this->resources[] = new FileResource($file);

        return $values;
    }

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     *
     * @param array $configuration
     *
     * @return array
     */
    protected function resolve(array $configuration)
    {
        $values = [];
        foreach ($configuration as $key => $value) {
            $value = $this->parameterBag->resolveValue($value);
            $values[$key] = $this->parameterBag->unescapeValue($value);
        }

        return $values;
    }

    /**
     * Includes a PHP file.
     *
     * @param string $file
     *
     * @return array
     */
    private static function requireFile($file)
    {
        return require $file;
    }
}
