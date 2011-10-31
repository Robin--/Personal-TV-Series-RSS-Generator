#!/usr/bin/php
<?php

include("../include/common.php");
include("../include/class.db.php");
include("../include/class.db_mysql.php");

$db = new db_mysql();
$db->connect($config);

$debug = isset($argv[1]) && $argv[1] == "debug";

/**
*	Retrieve torrents list based on user defined episodes
*/

class fetchTorrents {

	function __construct()
	{
		$this->torrentLink = "http://torrentz.eu/feed_verifiedP?q=";
		$this->getData();
	}

	function getData()
	{
		global $db, $debug;

		$subscriptions = $db->get_all("select * from user_subscriptions");
		foreach ($subscriptions as $row) {
			$torrent = $this->fetchTorrent($row);
		}
	}

	private function fetchTorrent($row)
	{
		global $db;

		$serie = $db->get("select * from tv_series where tvrage=?", $row['tvrage']);
		$rss_contents = simplexml_load_file($this->torrentLink.$this->buildQueryString($serie['title']));
		
		// rss items
		foreach ($rss_contents->channel->item as $item) {
			var_dump($item);
		}

	}

	private function buildQueryString($str)
	{
		return strtolower(str_replace(" ", "+", $str));
	}
}

$torrents = new fetchTorrents();