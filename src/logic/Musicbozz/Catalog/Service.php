<?php

namespace Musicbozz\Catalog;

use Musicbozz\Catalog\Musicbox\Proxy as MusicBoxService;
use Musicbozz\Catalog\LastFM\PublicApi as LastFMService;

class Service {
	private $musicBoxInstance;
	private static $preselectArtists;


	private function getMusicBoxInstance() {
		if (null === $this->musicBoxInstance) {
			$this->musicBoxInstance = new MusicBoxService;
		}
		return $this->musicBoxInstance;
	}

	public function getRandomTrack($source = 'TrackFromArtistList', $slice = null) 
	{
		$track = null;
		switch ($source) {
			case 'TopTracks':
				$track = $this->_findTrackInTopTracks($slice);
				break;

			case 'TrackFromArtistList':
				$track = $this->_findTrackFromArtistList($slice);
				break;

			case 'RecommendedTracks':
			default:
				$track = $this->_findTrackInRecommendedTracks($slice);
				break;
		}
		return $track;
	}

	private function addArtistToPreselectesByTrackList($list) 
	{
		foreach ($list->Track as $track) {
			if (!in_array($track->ArtistId, self::$preselectArtists))
			{
				self::$preselectArtists[] = $track->ArtistId;
			}
		}
	}

	public function getPreSelectedArtistList()
	{
		if (null == self::$preselectArtists) {
			print "load artists catalog\n";
			self::$preselectArtists = array();

			// via top tracks
			try {
				print "from top tracks\n";
				$result = $this->getMusicBoxInstance()->GetTopTracks();
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from new tracks\n";
				$result = $this->getMusicBoxInstance()->GetNewTracks();
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 12\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(12);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}
/*
			try {
				print "from category 61\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(61);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 28\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(28);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 29\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(29);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 30\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(30);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 31\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(31);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}

			try {
				print "from category 39\n";
				$result = $this->getMusicBoxInstance()->GetTracksByCategoryId(39);
				$this->addArtistToPreselectesByTrackList($result->TrackList);
			} catch (\Exception $e) {}
**/
		}
		return self::$preselectArtists;
	}



	public function getRandomTrackFromArtist($artistId)
	{
		$tracks = $this->getMusicBoxInstance()->GetPopularTracksByArtistId($artistId);
		$tracksList = $tracks->TrackList->Track;
		$max = sizeof($tracksList)-1;
		if ($max > 4) $max = 4;
		return $tracksList[rand(0,$max)];
	}

	public function getSimilarArtists($track, $nrResult = 3) {
		try {
			$artists = LastFMService::getSimilarArtist($track->ArtistName);
		} catch (\Exception $e) {
			try {
				$artists = LastFMService::getSimilarArtistBySearch($track->ArtistName, $track->TrackName);
			} catch (\Exception $e) {
				return null;
			}	
		}

		$response = array();
		$maxRand = sizeof($artists);
		$i = 0;
		do {
			$idx = rand(0, $maxRand);
			$artistName = $artists[$idx];
			if (!empty($artistName) && !in_array($artistName, $response)) {
				$response[] = $artistName;
			}
		} while(sizeof($response) < $nrResult && ++$i < $maxRand);
		return $response;
	}

	public function getSimilarTrack($track, $nrResult = 3) {
		try {
			$tracks = LastFMService::getSimilarTrackByArtist($track->ArtistName, $track->TrackName);
		} catch (\Exception $e) {
			try {
				$tracks = LastFMService::getSimilarTrackBySearch($track->ArtistName, $track->TrackName);
			} catch (\Exception $e) {
				return null;
			}
		}

		$response = array();
		$maxRand = sizeof($tracks);
		$i = 0;
		do {
			$idx = rand(0, $maxRand);
			$trackName = $tracks[$idx];
			if (!empty($trackName) && !in_array($trackName, $response)) {
				$response[] = $trackName;
			}
		} while(sizeof($response) < $nrResult && ++$i < $maxRand);
		return $response;
	}

	/** **/
	private function _findTrackInRecommendedTracks($slice) {
		$result = $this->getMusicBoxInstance()->GetRecommendedTracks();
		$tracks = $result->TrackList->Track;
		$max = sizeof($tracks)-1;
		if (null !== $slice && $max >= $slice[1]) {
			$trackIdx = rand($slice[0], $slice[1]);
		} else {
			$trackIdx = rand(0, $max);
		}
		
		return $tracks[$trackIdx];
	}

	private function _findTrackInTopTracks($slice){
		$result = $this->getMusicBoxInstance()->GetTopTracks();
		$tracks = $result->TrackList->Track;
		$max = sizeof($tracks)-1;
		if (null !== $slice && $max >= $slice[1]) {
			$trackIdx = rand($slice[0], $slice[1]);
		} else {
			$trackIdx = rand(0, $max);
		}
		
		return $tracks[$trackIdx];
	}

	private function _findTrackFromArtistList($slice) {
		$artistsList = $this->getPreSelectedArtistList();
		$max = sizeof($artistsList)-1;
		if (null !== $slice && $max >= $slice[1]) {
			$idx = rand($slice[0], $slice[1]);
		} else {
			$idx = rand(0, $max);
		}

		$artistId = $artistsList[$idx];
		return $this->getRandomTrackFromArtist($artistId);
	}
}