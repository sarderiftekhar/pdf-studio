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

arch('template registry has register and get methods')
    ->expect('PdfStudio\Laravel\Templates\TemplateRegistry')
    ->toHaveMethod('register')
    ->toHaveMethod('get');

arch('template exceptions extend RuntimeException')
    ->expect('PdfStudio\Laravel\Exceptions\TemplateNotFoundException')
    ->toExtend(RuntimeException::class);

arch('jobs implement ShouldQueue')
    ->expect('PdfStudio\Laravel\Jobs')
    ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);

arch('listeners have handle methods')
    ->expect('PdfStudio\Laravel\Listeners')
    ->toHaveMethod('handleStarting');

arch('models extend Eloquent Model')
    ->expect('PdfStudio\Laravel\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

arch('services implement their contracts')
    ->expect('PdfStudio\Laravel\Services\TemplateVersionService')
    ->toImplement(\PdfStudio\Laravel\Contracts\TemplateVersionServiceContract::class);

arch('access control service implements contract')
    ->expect('PdfStudio\Laravel\Services\AccessControl')
    ->toImplement(\PdfStudio\Laravel\Contracts\AccessControlContract::class);

arch('middleware has handle method')
    ->expect('PdfStudio\Laravel\Http\Middleware')
    ->toHaveMethod('handle');

arch('builder schema blocks extend Block')
    ->expect('PdfStudio\Laravel\Builder\Schema')
    ->toExtend(\PdfStudio\Laravel\Builder\Schema\Block::class)
    ->ignoring('PdfStudio\Laravel\Builder\Schema\Block')
    ->ignoring('PdfStudio\Laravel\Builder\Schema\DocumentSchema')
    ->ignoring('PdfStudio\Laravel\Builder\Schema\StyleTokens')
    ->ignoring('PdfStudio\Laravel\Builder\Schema\DataBinding')
    ->ignoring('PdfStudio\Laravel\Builder\Schema\SchemaVersion');

arch('builder compiler has compile method')
    ->expect('PdfStudio\Laravel\Builder\Compiler')
    ->toHaveMethod('compile');

arch('builder exporter has export method')
    ->expect('PdfStudio\Laravel\Builder\Exporter')
    ->toHaveMethod('export');
