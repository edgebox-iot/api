<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RunMiddleware
{
    private array $extras;

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
