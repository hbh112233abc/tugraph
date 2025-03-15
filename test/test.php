<?php

require __DIR__ . '/../vendor/autoload.php';
use bingher\tugraph\TuGraph;

$env     = __DIR__ . '/../.env';
$content = file_get_contents($env);
$config  = json_decode($content, true);
$tu      = new TuGraph($config);
$res     = $tu->graph('责任关系')->all();
var_dump($res);
