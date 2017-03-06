<?php

namespace DmKnvk\LaravelImageOptimizer;

use Illuminate\Console\Scheduling\Schedule;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const BASE_CONFIG_PATH = __DIR__ . '/../config/image-optimizer.php';

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // merge config
        $this->mergeConfigFrom(self::BASE_CONFIG_PATH, 'image-optimizer');

        // register command
        $this->app['image-optimizer.run'] = $this->app->share(
            function ($app) {
                return new Console\RunCommand();
            }
        );
        $this->commands(['image-optimizer.run']);
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig(self::BASE_CONFIG_PATH);
    }


    /**
     * Get the config path
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return config_path('image-optimizer.php');
    }

    /**
     * Publish the config file
     *
     * @param  string $configPath
     */
    protected function publishConfig($configPath)
    {
        $this->publishes([$configPath => $this->getConfigPath()], 'config');
    }
}
