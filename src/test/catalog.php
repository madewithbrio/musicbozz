<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../logic/');
$loader->add('Sapo',  __DIR__.'/../logic/');
$loader->register();

/**
use Musicbozz\Catalog\Service as CatalogService;

$service = new CatalogService;
$track = $service->getRandomTrack();
//var_dump($track);
printf( "TrackName: %s\n", $track->TrackName);
printf( "ArtistName: %s\n", $track->ArtistName);
printf( "Preview: %s\n", $track->PreviewUrl);
print "\nSimilar Artist:\n";
$solutions = $service->getSimilarArtists($track);
var_dump($solutions);
print "\nSimilar Track:\n";
$solutions = $service->getSimilarTrack($track);
var_dump($solutions);
**/

use Musicbozz\Question;
use Musicbozz\Question_Type;
var_dump(Question::factory(Question_Type::getRandom()));