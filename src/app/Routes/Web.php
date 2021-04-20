<?php

$f3->route('GET /', 'App\Controllers\App->setup');
$f3->route('GET /docs', 'App\Controllers\App->docs');
$f3->route('GET /setup', 'App\Controllers\App->setup');
$f3->route('GET /storage', 'App\Controllers\App->setup_storage');
$f3->route('GET /backups', 'App\Controllers\App->setup_backups');
$f3->route('GET /sources', 'App\Controllers\App->setup_sources');
$f3->route('GET /edgeapps', 'App\Controllers\App->setup_edgeapps');
$f3->route('GET /edgeapps/@action/@edgeapp', 'App\Controllers\App->setup_edgeapps_action');
$f3->route('GET|POST /settings', 'App\Controllers\App->setup_settings');
$f3->route('GET /settings/logout', 'App\Controllers\App->setup_settings_logout');



