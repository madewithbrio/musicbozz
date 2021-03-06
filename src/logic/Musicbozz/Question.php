<?php

namespace Musicbozz;
use Musicbozz\Catalog\Service as CatalogService;

class Question {
	
	private static $logger;
	private static $catalogServiceInstance;

	public $trackPreview;
	public $image;
	public $type;
	public $number;
	public $solutions;
	public $correct;
	public $hash;
	public $timer;

	public function __construct() {}

	private static function getCatalogInstance() {
		if (null === self::$catalogServiceInstance) {
			self::$catalogServiceInstance = new CatalogService;
		}
		return self::$catalogServiceInstance;
	}

	private static function getLogger() {
    	if (null === self::$logger) {
    		self::$logger = \Logger::getLogger(__CLASS__);
    	}
    	return self::$logger;
	}

	public static function factory($type = Question_Type::ARTIST, $number = 1, $retry = 0) {
		self::getLogger()->debug(sprintf("Load question #%s retry: %s",$number,$retry));
		
		//$source = 'TrackFromArtistList'; //$number > 15 ? 'RecommendedTracks' : 'TopTracks' : 
		$source = $number > 15 ? 'TrackFromArtistList' : 'RecommendedTracks';
		if ($number < 5) { $slice = array(1,100); }
		else if ($number < 10) { $slice = array(100, 300); }
		else { $slice = array(300,600); }

		$track = self::getCatalogInstance()->getRandomTrack($source, $slice);

		$question = new static;
		$question->type = $type;
		$question->number = $number;

		$question->trackPreview = str_replace ('streamer.nmusic.sapo.pt', 'vmdev-musicbozz.vmdev.bk.sapo.pt', $track->PreviewUrl).".mp3";
		$question->image = str_replace ('streamer.nmusic.sapo.pt', 'vmdev-musicbozz.vmdev.bk.sapo.pt', $track->LargeAlbumCover);
		$question->hash = md5($question->trackPreview . $question->type . $question->number);

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
			if ($retry >= 5) throw new \Exception("dont have solutions");
		 	return self::factory($type, $number, ++$retry); 
		}
		return $question;
	}

	public function isCorrectAnswer($answer) { 
		if ($answer === null) return true;
		return $answer === $this->correct; 
	}

	public function toWs() {
		return array(
			'hash' 		=> $this->hash,
			'number' 	=> $this->number,
			'url' 		=> $this->trackPreview,
			'solutions' => $this->solutions,
			'query' 	=> Question_Type::getQuery($this->type)
			);
	}

	public function getTimer() { return $this->timer; }
	public function setTimer($timer) { $this->timer = $timer; }
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