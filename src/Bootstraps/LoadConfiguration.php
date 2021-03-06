<?php

namespace Core\Bootstraps;

use Core\Services\DI;
use Core\Bootstraps\Contracts\Bootable;
use Dotenv\Dotenv;

/**
 * Configuration Loader.
 */
class LoadConfiguration implements Bootable
{
    /**
     * Environment file.
     */
    private $envFile;

    /**
     * Configuration path.
     *
     * @var string
     */
    private $configPath;

    /**
     * Cached configuration file.
     *
     * @var string
     */
    private $cachedFile;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->envFile    = environment_file();
        $this->configPath = config_path();
        $this->cachedFile = storage_path('cache/config.php');
    }

    /**
     * Bootstrap
     */
    public function boot()
    {
        /*
         * Load environment file.
         */
        //$this->loadEnvironment(); // todo prüfen, wieviel Zeit dies kostet (ENV kann so nur in der Config verwenden werden)

        /*
         * Load configuration files.
         */
        if ($this->isCacheUpToDate()) {
            $config = $this->loadConfigFromCache();
        }
        else {
            $this->loadEnvironment();
            $config = $this->loadConfigFromFiles();
            $this->saveConfigToCache($config);
        }

        /*
         * Sets the default timezone used by all date/time functions in a script.
         */
        date_default_timezone_set($config->get('app.timezone'));

        /*
         * Use internally the UTF-8 Encoding
         */
        mb_internal_encoding('UTF-8');
    }

    /**
     * Load environment file.
     */
    private function loadEnvironment()
    {
        (new Dotenv(dirname($this->envFile), basename($this->envFile)))->load();
    }

    /**
     * Load the configuration files.
     *
     * @return \Core\Services\Contracts\Config
     */
    private function loadConfigFromFiles()
    {
        /** @var \Core\Services\Contracts\Config $config */
        $config = DI::getInstance()->get('config');

        foreach (scandir($this->configPath) as $file) {
            if (is_file($this->configPath . DIRECTORY_SEPARATOR . $file) && $file[0] != '.') {
                /** @noinspection PhpIncludeInspection */
                $config->set(basename($file, '.php'), require $this->configPath . DIRECTORY_SEPARATOR . $file);
            }
        }

        return $config;
    }

    /**
     * Determine if the cached file is up to date with the configuration path.
     *
     * @return bool
     */
    private function isCacheUpToDate()
    {
        if (!file_exists($this->cachedFile)) {
            return false;
        }

        $cacheTime  = filemtime($this->cachedFile);
        $configTime = max(filemtime($this->configPath), filemtime($this->envFile));

        return $cacheTime == $configTime;
    }

    /**
     * Load the configuration from cache.
     *
     * @return \Core\Services\Contracts\Config
     */
    private function loadConfigFromCache()
    {
        /** @var \Core\Services\Contracts\Config $config */
        $config = DI::getInstance()->get('config');

        /** @noinspection PhpIncludeInspection */
        $config->set(null, require $this->cachedFile);

        return $config;
    }

    /**
     * Save the configuration to the cache.
     *
     * @param \Core\Services\Contracts\Config $config
     */
    private function saveConfigToCache($config)
    {
        if (!is_dir($cacheDir = dirname($this->cachedFile))) {
            if (!@mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf('Configuration Loader was not able to create directory "%s"', $cacheDir));
            }
        }

        if (file_exists($this->cachedFile)) {
            unlink($this->cachedFile); // so we will to be the owner at the new file
        }

        if (file_put_contents($this->cachedFile, '<?php return ' . var_export($config->get(), true) . ';' . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Configuration Loader was not able to save cached file "%s"', $this->cachedFile));
        }

        @chmod($this->cachedFile, 0664);

        $time = max(filemtime($this->configPath), filemtime($this->envFile));
        if (!touch($this->cachedFile, $time)) {
            throw new \RuntimeException(sprintf('Configuration Loader was not able to modify time of cached file "%s"', $this->cachedFile));
        }
    }
}