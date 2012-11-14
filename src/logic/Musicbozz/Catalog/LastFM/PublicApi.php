<?php

namespace Musicbozz\Catalog\LastFM;

class PublicApi {
	const KEY = "e3203ea51f5b7c61c33eed3a623367d6";
	const ENPOINT = "http://ws.audioscrobbler.com/2.0/";

	private static function request($uri) {
		$string = file_get_contents($uri);
		return json_decode($string);
	}

	public static function getSimilarArtist($artist) {
		$uri = sprintf("%s?method=artist.getsimilar&artist=%s&api_key=%s&limit=50&format=json", self::ENPOINT, urlencode($artist), self::KEY);
		$data = self::request($uri);
		$artists = array();
		foreach ($data->similarartists->artist as $_artist) {
			$artists[] = $_artist->name;
		}
		if (sizeof($artists) == 0) throw new \Exception("no similar artists found", 1);
		return $artists;
	}

	public static function getSimilarTrackByArtist($artist, $track) {
		$uri = sprintf("%s?method=track.getsimilar&artist=%s&track=%s&api_key=%s&limit=50&autocorrect=1&format=json", 
				self::ENPOINT, urlencode($artist), urlencode($track), self::KEY);
		$data = self::request($uri);
		$tracks = array();
		foreach ($data->similartracks->track as $_track) {
			$tracks[] = $_track->name;
		}
		if (sizeof($tracks) == 0) throw new \Exception("no similar tracks found", 1);
		return $tracks;		
	}

	// http://ws.audioscrobbler.com/2.0/?method=track.search&track=Chained&api_key=e3203ea51f5b7c61c33eed3a623367d6&format=json
	// http://ws.audioscrobbler.com/2.0/?method=track.search&track=Chained&artist=The%20xx&api_key=e3203ea51f5b7c61c33eed3a623367d6&format=json

	public static function getSimilarArtistBySearch($artist, $track) {
		$uri = sprintf("%s?method=track.search&track=%s&artist=%s&api_key=%s&limit=50&format=json", 
						self::ENPOINT, urlencode($track), urlencode($artist), self::KEY);
		$data = self::request($uri);
		$artists = array();
		foreach ($data->results->trackmatches->track as $_track) {
			if (strtolower($_track->artist) != strtolower($artist)) {
				$artists[] = $_track->artist;
			}
		}
		if (sizeof($artists) == 0) throw new \Exception("no similar artists found", 1);
		return $artists;		
	}

	public static function getSimilarTrackBySearch($artist, $track) {
		$uri = sprintf("%s?method=track.search&track=%s&artist=%s&api_key=%s&limit=50&format=json", 
						self::ENPOINT, urlencode($track), urlencode($artist), self::KEY);
		$data = self::request($uri);
		$tracks = array();
		foreach ($data->results->trackmatches->track as $_track) {
			if (strtolower($_track->name) != strtolower($track)) {
				$tracks[] = $_track->name;
			}
		}
		if (sizeof($tracks) == 0) throw new \Exception("no similar tracks found", 1);
		return $tracks;		
	}
}