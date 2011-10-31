<?php

include("class.epguides.php");
include("include/common.php");
include("include/class.db.php");
include("include/class.db_mysql.php");

$db = new db_mysql();
$db->connect($config);

$debug = isset($argv[1]) && $argv[1] == "debug";

class fetchEpisodes extends epguides
{
	function __construct ()
	{
		$this->episodeLink = "http://epguides.com/common/exportToCSV.asp?rage=%s";
		$this->getData();
	}

	function fetchShow($rage_id)
	{
		global $debug, $db;
		$key = "_show_".$rage_id;
		if (!($rows = $this->fetchCache($key))) {
			$rows = $this->fetchContents(sprintf($this->episodesLink, $rage_id));
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

$episodes = new fetchEpisodes();