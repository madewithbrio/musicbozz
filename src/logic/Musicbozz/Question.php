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
		$source = $number > 15 ? 'RecommendedTracks' : 'TopTracks';
		if ($number < 5) { $slice = array(1,30); }
		else if ($number < 10) { $slice = array(31, 60); }
		else { $slice = array(61,100); }

		$track = self::getCatalogInstance()->getRandomTrack($source, $slice);

		$question = new static;
		$question->type = $type;
		$question->number = $number;

		$question->trackPreview = str_replace ('streamer.nmusic.sapo.pt', '62.28.238.103', $track->PreviewUrl).".mp3";
		$question->image = str_replace ('streamer.nmusic.sapo.pt', '62.28.238.103', $track->LargeAlbumCover);

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
		if (sizeof($question->solutions) != 4) {
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