<?php

include("class.fetcher.php");
include("include/common.php");
include("include/class.db.php");
include("include/class.db_mysql.php");

$fetcher = new fetcher;
$db = new db_mysql();
$db->connect($config);

class fetchEpisodes
{
	function __construct ()
	{
		$this->episodeLink = "http://epguides.com/common/exportToCSV.asp?rage=%s";
	}

	function fetchShow($rage_id)
	{
		global $fetcher;
		$key = "_show_".$rage_id;
		if (!($rows = $fetcher->fetchCache($key))) {
			$rows = $fetcher->fetchContents(sprintf($this->episodesLink, $rage_id));
			$this->setCache($key, $rows);
		}
		foreach ($rows as $k=>$row) {
			$rows[$k] = $this->prepareShow($row);
		}
		return $rows;
	}

	private function prepareShow($row)
	{
		$airdate = strtotime($row['airdate']);
		if (is_numeric($airdate)) {
			$row['airdate'] = $this->parseDate($row['airdate']);
		} else {
			$row['airdate'] = "0000-00-00";
		}
		return $row;
	}

	private function parseDate( $date ) 
	{
		$date = explode("/", $date);
		$year = "19".$date[2];
		$last_two_digits = substr(date("Y"), 2);
		if ($date[2] <= $last_two_digits ) {
			$year = "20".$date[2];
		
		}
		return date("Y-m-d", strtotime($year."-".$date[1]."-".$date[0]));
	}
}