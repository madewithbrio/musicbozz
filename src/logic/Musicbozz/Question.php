<?php

namespace Musicbozz;
use Musicbozz\Catalog\Service as CatalogService;

class Question {
	private static $catalogServiceInstance;

	public $trackPreview;
	public $type;
	public $solutions;
	public $correct;

	public function __construct() {}

	private static function getCatalogInstance() {
		if (null === self::$catalogServiceInstance) {
			self::$catalogServiceInstance = new CatalogService;
		}
		return self::$catalogServiceInstance;
	}

	public static function factory($type = Question_Type::ARTIST, $retry = 0) {
		$track = self::getCatalogInstance()->getRandomTrack();

		$question = new static;
		$question->type = $type;
		$question->trackPreview = $track->PreviewUrl;

		switch ($type) {
			case Question_Type::TRACK:
				$solutions = self::getCatalogInstance()->getSimilarTrack($track);
				$solutions[] = $track->TrackName;
				shuffle($solutions);
				$question->solutions = $solutions;
				$question->correct = array_search($track->TrackName, $solutions);
				break;
			
			default:
				$solutions = self::getCatalogInstance()->getSimilarArtists($track);
				$solutions[] = $track->ArtistName;
				shuffle($solutions);
				$question->solutions = $solutions;
				$question->correct = array_search($track->ArtistName, $solutions);
				break;
		}
		
		// it's a good question, if not retry get other
		if (sizeof($question->solutions) < 2) {
			if ($retry >= 2) throw new \Exception("dont have solutions");
		 	return self::factory($type, ++$retry); 
		}

		var_dump($question);
		return $question;
	}

	public function toWs() {
		return array(
			'url' 		=> $this->trackPreview,
			'solutions' => $this->solutions,
			'type' 		=> $this->type
			);
	}
}

class Question_Type {
	const ARTIST = 1;
	const TRACK = 2;

	public static function getRandom() {
		return rand(1,2);
	}
}