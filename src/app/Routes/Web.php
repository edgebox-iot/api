<?php

$f3->route('GET /', 'App\Controllers\App->index');
$f3->route('GET /docs', 'App\Controllers\App->docs');
$f3->route('GET /setup', 'App\Controllers\App->setup');
