<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RunMiddleware
{
    protected array $args;

    public function __construct(
        public string $name,
        ...$extras
    ) {
        $this->extras = $extras;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }
}