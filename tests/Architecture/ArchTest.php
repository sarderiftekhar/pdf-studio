<?php

arch()->preset()->php();

arch('contracts are interfaces')
    ->expect('PdfStudio\Laravel\Contracts')
    ->toBeInterfaces();

arch('DTOs have no dependencies on framework')
    ->expect('PdfStudio\Laravel\DTOs')
    ->toUseNothing();

arch('exceptions extend RuntimeException')
    ->expect('PdfStudio\Laravel\Exceptions')
    ->toExtend(RuntimeException::class);

arch('pipeline stages have handle method')
    ->expect('PdfStudio\Laravel\Pipeline')
    ->toHaveMethod('handle')
    ->ignoring('PdfStudio\Laravel\Pipeline\RenderPipeline');

arch('drivers implement RendererContract')
    ->expect('PdfStudio\Laravel\Drivers')
    ->toImplement(\PdfStudio\Laravel\Contracts\RendererContract::class)
    ->ignoring('PdfStudio\Laravel\Drivers\DriverManager')
    ->ignoring('PdfStudio\Laravel\Drivers\CapabilityValidator');

arch('cache classes have flush method')
    ->expect('PdfStudio\Laravel\Cache')
    ->toHaveMethod('flush');

arch('commands extend Illuminate Command')
    ->expect('PdfStudio\Laravel\Commands')
    ->toExtend(\Illuminate\Console\Command::class);

arch('events are simple DTOs')
    ->expect('PdfStudio\Laravel\Events')
    ->toBeFinal()
    ->toHaveConstructor();

arch('preview data provider contract is interface')
    ->expect('PdfStudio\Laravel\Contracts\PreviewDataProviderContract')
    ->toBeInterface();
