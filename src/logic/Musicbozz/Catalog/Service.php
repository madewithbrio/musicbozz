<?php

namespace Musicbozz\Catalog;

use Musicbozz\Catalog\Musicbox\Proxy as MusicBoxService;
use Musicbozz\Catalog\LastFM\PublicApi as LastFMService;

class Service {
	private $musicBoxInstance;

	private function getMusicBoxInstance() {
		if (null === $this->musicBoxInstance) {
			$this->musicBoxInstance = new MusicBoxService;
		}
		return $this->musicBoxInstance;
	}

	public function getRandomTrack($source = 'RecommendedTracks') {
		$track = null;
		switch ($source) {

			default:
				$track = $this->_findTrackInRecommendedTracks();
				break;
		}
		return $track;
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
	private function _findTrackInRecommendedTracks() {
		$result = $this->getMusicBoxInstance()->GetRecommendedTracks();
		$tracks = $result->TrackList->Track;
		$trackIdx = rand(0, sizeof($tracks));
		return $tracks[$trackIdx];
	}
}