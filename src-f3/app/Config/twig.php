<?php

$options = [
    'debug' => getenv('DEBUG'),
    'auto_reload' => getenv('DEBUG'),
    'strict_variables' => true,
    'cache' => $f3->get('FULLPATH') . '/app/Storage/Cache'
];
$loader = new Twig\Loader\FilesystemLoader('app/Views');
$twig = new Twig\Environment($loader, $options);

return $twig;
