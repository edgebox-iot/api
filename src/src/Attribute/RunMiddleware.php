<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RunMiddleware
{
    public function __construct(
        public string $name
    ) {}
}