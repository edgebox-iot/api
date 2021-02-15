<?php

$f3->route('GET /', 'App\Controllers\App->index');
$f3->route('GET /docs', 'App\Controllers\App->docs');
$f3->route('GET /setup', 'App\Controllers\App->setup');
$f3->route('GET /setup/access/logout', 'App\Controllers\App->setup_access_logout');
$f3->route('GET /setup/applications', 'App\Controllers\App->setup_applications');
$f3->route('GET|POST /setup/access', 'App\Controllers\App->setup_access');


