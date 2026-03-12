<?php

namespace PdfStudio\Laravel\Drivers;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\Contracts\RendererContract;
use PdfStudio\Laravel\Exceptions\DriverException;

class DriverManager
{
    /** @var array<string, RendererContract> */
    protected array $drivers = [];

    public function __construct(
        protected Application $app,
    ) {}

    public function driver(?string $name = null): RendererContract
    {
        $name ??= $this->getDefaultDriver();

        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        $driver = $this->resolve($name);
        $this->drivers[$name] = $driver;

        return $driver;
    }

    protected function resolve(string $name): RendererContract
    {
        $config = $this->app['config']->get("pdf-studio.drivers.{$name}");

        if ($config === null) {
            throw new DriverException("Driver [{$name}] is not configured.");
        }

        $method = 'create'.ucfirst($name).'Driver';

        if (method_exists($this, $method)) {
            return $this->{$method}($config);
        }

        throw new DriverException("Driver [{$name}] is not supported.");
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createFakeDriver(array $config = []): FakeDriver
    {
        return new FakeDriver;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createChromiumDriver(array $config = []): ChromiumDriver
    {
        return new ChromiumDriver($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createGotenbergDriver(array $config = []): GotenbergDriver
    {
        return new GotenbergDriver($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createDompdfDriver(array $config = []): DompdfDriver
    {
        return new DompdfDriver($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createWkhtmltopdfDriver(array $config = []): WkhtmlDriver
    {
        return new WkhtmlDriver($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function createWeasyprintDriver(array $config = []): WeasyPrintDriver
    {
        return new WeasyPrintDriver($config);
    }

    public function getDefaultDriver(): string
    {
        return $this->app['config']->get('pdf-studio.default_driver', 'fake');
    }
}
