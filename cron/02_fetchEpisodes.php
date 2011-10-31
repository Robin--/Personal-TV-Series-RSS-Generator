#!/usr/bin/php
<?php

include("class.epguides.php");
include("../include/common.php");
include("../include/class.db.php");
include("../include/class.db_mysql.php");

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

	function getData()
	{
		global $debug, $db;
		if ($debug) echo "Fetching episodes ... \n";

		$series = array();
		if ($series = $db->get_all("select * from tv_series where active=1")) {
			foreach ($series as $row) {
				$db->query("delete from tv_episodes where tvrage=?", $row['tvrage']);
				$episodes = $this->fetchShow($row['tvrage']);
				if ($debug) echo "Inserting ".count($episodes). " episodes for ".$row['title']." with rage id ".$row['tvrage'] .".\n";
				foreach ($episodes as $ep) {
					$ep['tvrage'] = $row['tvrage'];
					$db->insert("tv_episodes", $ep);
				}
			}
		}
	}

	function fetchShow($rage_id)
	{
		global $debug, $db;
		$key = "_show_".$rage_id;
		if (!($rows = $this->fetchCache($key))) {
			if ($debug) echo "Fetching rage id ".$rage_id." and setting cache ...\n";
			$rows = $this->fetchContents(sprintf($this->episodeLink, $rage_id));
			$this->setCache($key, $rows);
		} else {
			if ($debug) echo "Rage id ".$rage_id." fetched from cache ...\n";
		}
		foreach ($rows as $k=>$row) {
			$rows[$k] = $this->prepareShow($row);
		}
		return $rows;
	}

	private function prepareShow($row)
	{
		$airdate = strtotime($row['airdate']);
		if (!is_numeric($airdate)) {
			$row['airdate'] = $this->parseDate($row['airdate']);
		} else {
			$row['airdate'] = date("Y-m-d", $airdate);
		}
		return $row;
	}

	private function parseDate($date) 
	{
		$date = explode("/", $date);
		if (count($date)!=3) {
			return "0000-00-00";
		}
		$year = "19".$date[2];
		$last_two_digits = substr(date("Y"), 2);
		if ($date[2] <= $last_two_digits ) {
			$year = "20".$date[2];
		
		}
		return date("Y-m-d", strtotime($year."-".$date[1]."-".$date[0]));
	}
}

$episodes = new fetchEpisodes();