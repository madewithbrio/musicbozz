<?php

namespace Musicbozz;
use Musicbozz\Catalog\Service as CatalogService;

class Question {
	private static $catalogServiceInstance;

	public $trackPreview;
	public $image;
	public $type;
	public $number;
	public $solutions;
	public $correct;

	public function __construct() {}

	private static function getCatalogInstance() {
		if (null === self::$catalogServiceInstance) {
			self::$catalogServiceInstance = new CatalogService;
		}
		return self::$catalogServiceInstance;
	}

	public static function factory($type = Question_Type::ARTIST, $number = 1, $retry = 0) {
		$track = self::getCatalogInstance()->getRandomTrack();

		$question = new static;
		$question->type = $type;
		$question->number = $number;

		$question->trackPreview = $track->PreviewUrl;
		$question->image = $track->LargeAlbumCover;

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
		 	return self::factory($type, $number, ++$retry); 
		}
		return $question;
	}

	public function isCorrectAnswer($answer) { 
		var_dump($answer);
		var_dump($this->correct);
		if ($answer === null) return true;
		return $answer === $this->correct; 
	}

	public function toWs() {
		return array(
			'number' 	=> $this->number,
			'url' 		=> $this->trackPreview,
			'solutions' => $this->solutions,
			'query' 	=> Question_Type::getQuery($this->type)
			);
	}
}

class Question_Type {
	const ARTIST = 1;
	const TRACK = 2;

	public static function getRandom() {
		return rand(1,2);
	}

	public static function getQuery($type) {
		switch ($type) {
			case self::ARTIST:
				return "Qual o nome do artista?";
			
			case self::TRACK:
				return "Qual o nome da musica?";
			default:
				# code...
				break;
		}
	}
}