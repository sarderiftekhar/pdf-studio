<?php

namespace PdfStudio\Laravel\Events;

final class BillableEvent
{
    public function __construct(
        public int $workspaceId,
        public string $eventType,
        public int $quantity,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}
}
