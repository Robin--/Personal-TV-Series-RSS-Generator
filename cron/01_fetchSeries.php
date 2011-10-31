#!/usr/bin/php
<?php

include("class.epguides.php");
include("../include/common.php");
include("../include/class.db.php");
include("../include/class.db_mysql.php");

$db = new db_mysql();
$db->connect($config);

$debug = isset($argv[1]) && $argv[1] == "debug";

class fetchSeries extends epguides
{
	function __construct ()
	{
		$this->seriesList = "http://epguides.com/common/allshows.txt";
		$this->getData();
	}

	function getData()
	{
		global $debug, $db;
		if ($debug) echo "Fetching series started ... \n";

		$data = $this->fetchList();
		$existing_ids = array();
		// gather existing data
		$row = $db->query("select tvrage from tv_series");
		while (list($tvrage) = $db->fetch_row($row)) {
			$existing_ids[$tvrage] = 1;
		}

		foreach ($data as $serie) {
			if (!empty($serie['tvrage'])) {
				if (isset($existing_ids[$serie['tvrage']])) {
					if ($debug) echo "Updating ".$serie['title']. " with rage id ".$serie['tvrage'] .".\n";
					$db->update("tv_series", $serie, "tvrage");
				} else {
					if ($debug) echo "Inserting ".$serie['title']. " with rage id ".$serie['tvrage'] .".\n";
					$db->insert("tv_series", $serie);
				}
			}
		}
	}

	private function fetchList()
	{
		$key = "_list";
		if (!($rows = $this->fetchCache($key))) {
			$rows = $this->fetchContents($this->seriesList);
			$this->setCache($key, $rows);
		}
		foreach ($rows as $k=>$row) {
			$rows[$k] = $this->prepareSerie($row);
		}
		return $rows;
	}

	private function prepareSerie($row)
	{
		$row['title'] = @iconv("UTF-8", "ISO-8859-1//IGNORE", $row['title']);
		$row['start_date'] = date("Y-m-d", strtotime($row['start_date']));
		$endDate = strtotime($row['end_date']);
		if (is_numeric($endDate)) {
			$row['end_date'] = date("Y-m-d", strtotime($row['end_date']));
			$row['active'] = 0;
		} else {
			$row['end_date'] = "0000-00-00";
			$row['active'] = 1;
		}
		return $row;
	}
}

$series = new fetchSeries();