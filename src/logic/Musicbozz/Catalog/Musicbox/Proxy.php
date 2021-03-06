<?php
namespace Musicbozz\Catalog\Musicbox;

use Musicbozz\Catalog\Musicbox\PublicApi as ServiceClient;
use \Sapo\Redis;
class Proxy
{
	private $soapClient;
	private static $instance;

	public function __construct(){}
	
	private static function getInstance()
	{
		if(null === self::$instance)
			self::$instance = new self();
	
		return self::$instance;
	}
	
	protected static function getSOAPClient()
	{
		if(!isset(self::getInstance()->soapClient))
			self::getInstance()->soapClient = new ServiceClient();
			
		return self::getInstance()->soapClient;
	}

	private static function getMBCredentials() {
		$credentials = new \stdClass;
		$credentials->MBUsername = 'hellc0re';
		$credentials->MBPassword = 'sgventil';
		return $credentials;
	}

	private function doRequest($method, $requestParameters)
	{
		//$key = sha1(serialize($requestParameters));
		//$result = unserialize(Redis::getInstance()->hget($method, $key));
		//if (empty($result)) {
			$response = self::getSOAPClient()->$method($requestParameters);
			$returnProp = $method . "Result";
	
			// @todo process type of exception
			if (empty($response) || !isset($response->{$returnProp})) {
				throw new \Exception("Error Processing Request", 1);
			}
			$result = $response->{$returnProp};
			//Redis::getInstance()->set($key, serialize($result), 'EX', '43200'); // save for 12 hours
			//Redis::getInstance()->hset($method, serialize($result));
		//}
		return $result;
	}

	public function AddAlbumToFavorites($AlbumId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('AddAlbumToFavorites', $request);
	}

	public function AddAlbumTracksToPlaylist($PlaylistId, $AlbumId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		$request->AlbumId = $AlbumId;
		return $this->doRequest('AddAlbumTracksToPlaylist', $request);
	}

	public function AddArtistToFavorites($ArtistId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->ArtistId = $ArtistId;
		return $this->doRequest('AddArtistToFavorites', $request);
	}

	public function AddPlaylist($PlaylistTitle)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistTitle = $PlaylistTitle;
		return $this->doRequest('AddPlaylist', $request);
	}

	public function AddPlaylistToFavorites($PlaylistId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		return $this->doRequest('AddPlaylistToFavorites', $request);
	}

	public function AddTracksToPlaylist($PlaylistId, array $TrackIdList)
	{
		if (sizeof($TrackIdList) < 1) throw new Exception("need at less 1 track id");

		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		$request->TrackIdList = array();
		for($j = sizeof($TrackIdList); $j >= 0; --$j) {
			$track = new \stdClass;
			$track->TrackId = $TrackIdList[$j];
			$request->TrackIdList[] = $track;
		}
		return $this->doRequest('AddTracksToPlaylist', $request);
	}
	
	public function AddTrackToFavorites($TrackId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->TrackId = $TrackId;
		return $this->doRequest('AddTrackToFavorites', $request);
	}

	public function GetNewAlbums()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetNewAlbums', $request);
	}

	public function GetRecommendedAlbums()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetRecommendedAlbums', $request);
	}
	
	public function GetAlbumsByArtistId($ArtistId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->ArtistId = $ArtistId;
		return $this->doRequest('GetAlbumsByArtistId', $request);
	}

	public function GetAlbumsByCategoryId($CategoryId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CategoryId = $CategoryId;
		return $this->doRequest('GetAlbumsByCategoryId', $request);
	}

	public function GetAlbumsByCollectionId($CollectionId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CollectionId = $CollectionId;
		return $this->doRequest('GetAlbumsByCollectionId', $request);		
	}

	public function GetAlbumById($AlbumId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('GetAlbumById', $request);
	}

	public function GetEditorialPlaylists()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetEditorialPlaylists', $request);
	}

	public function GetFavoriteAlbums()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetFavoriteAlbums', $request);
	}

	public function GetTrackById($TrackId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->TrackId = $TrackId;
		return $this->doRequest('GetTrackById', $request);
	}

	public function GetTracksByAlbumId($AlbumId)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('GetTracksByAlbumId', $request);
	}

	public function GetRecommendedTracks()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetRecommendedTracks', $request);		
	}

	//GetTopTracks
	public function GetTopTracks()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetTopTracks', $request);		
	}

	public function GetNewTracks()
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetNewTracks', $request);		
	}
	
	public function GetPopularTracksByArtistId($artistId)
	{
		$request = new \stdClass;
		$request->ArtistId = $artistId;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetPopularTracksByArtistId', $request);		
	}

	public function GetTracksByCategoryId($categoryId)
	{
		$request = new \stdClass;
		$request->CategoryId = $categoryId;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetTracksByCategoryId', $request);		
	}


	public function Search($searchTerm)
	{
		$request = new \stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->SearchTerm = $searchTerm;
		return $this->doRequest('Search', $request);
	}
}
