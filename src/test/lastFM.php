<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../logic/');
$loader->add('Sapo',  __DIR__.'/../logic/');
$loader->register();

use Musicbozz\Catalog\LastFM\PublicApi as LastFM;

$result = LastFM::getSimilarTrackBySearch('The xx','Chained');
var_dump($result);