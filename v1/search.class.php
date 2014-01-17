<?php
require_once("common.php");

class Search
{
	private $searchFor;
	public  $query;
	public  $isFinal;

	function __construct($query, $isFinal)
	{
		$this->query = ucwords($query);
		$this->isFinal = $isFinal;
	}

	public function albums()
	{
		$this->searchFor = "albums";
		$dhingana = $this->search("dhingana");
		$songspk = $this->search("songspk");
		$results = Search::uniqueMerge($songspk, $dhingana);
		if (!$this->isFinal)
		{
			$this->isFinal = true;
			$newResults = $this->albums();
			$results = Search::uniqueMerge($results, $newResults);
		}
		$this->sanitizeResults($results);
		return $results;
	}

	public function songs()
	{
		$this->searchFor = "songs";
		$dhingana = $this->search("dhingana");
		$songspk = $this->search("songspk");
		$results = Search::uniqueMerge($songspk, $dhingana);
		
		if (!$this->isFinal)
		{
			$this->isFinal = true;
			$newResults = $this->songs();
			$results = Search::uniqueMerge($results, $newResults);
		}
		$this->sanitizeResults($results);
		return $results;
	}
	

	public function sanitizeResults(&$results)
	{
		if ($this->isFinal == false)
			return;
		foreach ($results as $key => $result)
		{
			$lev = levenshtein($this->query, $result->Name);
			if ($lev > 3)
				unset($results[$key]);
		}
		$results = array_values($results);
	}

	public static function uniqueMerge(&$objs1, &$objs2)
	{
		$removeIndexes = array();
		foreach ($objs1 as $key => $pk)
		{
			foreach ($objs2 as $dh)
			{
				if ($pk->isEqualTo($dh))
					unset($objs1[$key]);
			}
		}
		$objs1 = array_values($objs1);
		return array_merge($objs1, $objs2);
	}

	private function search($table)
	{
		global $link;
	
		$fuzzy = "match(Name) against (?)";
	
		if ($this->searchFor == "albums")
			$idField = "AlbumID";
		else if($this->searchFor == "songs")
			$idField = "SongID";

		if ($this->isFinal)
		{
			$query = "SELECT $idField FROM ".$table."_".$this->searchFor." WHERE $fuzzy ORDER BY $fuzzy DESC LIMIT 10";
			$search = $link->prepare($query);
			$search->bind_param("ss", $this->query, $this->query);
		}
		else
		{
			$startWith = $this->query."%";
			$query = "SELECT $idField FROM ".$table."_".$this->searchFor." WHERE Name Like ? LIMIT 10";
			$search = $link->prepare($query);
			$search->bind_param("s", $startWith);
		}
		$search->execute();
		$search->bind_result($id);
		$ids = array();
		while($search->fetch())
			array_push($ids, Utility::getExternalID($id, $table));
		$search->close();
		
		if ($this->searchFor == "albums")
			return $this->processAlbumResults($ids);
		else
			return $this->processSongResults($ids);
	}
	
	private function processSongResults(&$ids)
	{
		$songs = Song::songsFromArray($ids);
		foreach($songs as $song)
			$song->setAlbum();
		return $songs;
	}

	private function processAlbumResults(&$ids)
	{
		$albums = (array) Album::albumsFromArray($ids);
		foreach($albums as $key => $album)
		{
			$album->setSongs();
			if (count($album->Songs) == 0)
				unset($albums[$key]);
		}
		return array_values($albums);	
	}
}
	
?>
