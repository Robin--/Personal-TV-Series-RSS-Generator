<?php

include("class.fetcher.php");
include("../include/common.php");
include("../include/class.db.php");
include("../include/class.db_mysql.php");

$fetcher = new fetcher;
$db = new db_mysql();
$db->connect($config);

class fetchSeries
{
	function __construct ()
	{
		$this->seriesList = "http://epguides.com/common/allshows.txt";
	}

	function fetchList()
	{
		global $fetcher;
		$key = "_list";
		if (!($rows = $fetcher->fetchCache($key))) {
			$rows = $fetcher->fetchContents($this->seriesList);
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

echo "Fetching series started ... \n";
$fetchSeries = new fetchSeries();
$data = $fetchSeries->fetchList();

$inactive_ids = array();
$inactive = $db->query("select tvrage from tv_series where active=0");
while (list($tvrage) = $db->fetch_row($inactive)) {
	$inactive_ids[$tvrage] = 1;
}

$active_ids = array();
$active = $db->query("select tvrage from tv_series where active=1");
while (list($tvrage) = $db->fetch_row($active)) {
	$active_ids[$tvrage] = 1;
}

foreach ($data as $serie) {
	if (!empty($serie['tvrage']) && !isset($inactive_ids[$serie['tvrage']])) {
		if (isset($active_ids[$serie['tvrage']])) {
			echo "Updating ".$serie['title']. " with rage id ".$serie['tvrage'] .".\n";
			$db->update("tv_series", $serie, "tvrage");
		} else {
			echo "Inserting ".$serie['title']. " with rage id ".$serie['tvrage'] .".\n";
			$db->insert("tv_series", $serie);
		}
	}
}