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
