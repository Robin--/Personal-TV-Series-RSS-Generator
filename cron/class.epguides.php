<?php

class epguides
{
	function fetchContents($link)
	{
		$retval = array();
		$f = fopen($link, "r");
		$i = 0;
		do {
			$line = fgetcsv($f);
			if ($line!==false && count($line)>1) {
				$i++;
				if ($i==1) {
					$keys = $line;
					foreach ($keys as $k=>$v) {
						$keys[$k] = trim(str_replace(array("?"," "),"_",$v), "_");
					}
					continue;
				}
				$retval[] = array_map("trim", array_combine($keys, $line));
			}
		} while (!feof($f));
		return $retval;
	}

	function fetchCache($key)
	{
		$file_age = @filemtime("/tmp/".$key);
		$now = strtotime(date("Y-m-d H:i:s"));
		$diff = $now - $file_age;
		$day_diff = floor($diff / (60*60*24));
		if (intval($day_diff) > 7) {
			return false;
		}
		return unserialize(@file_get_contents("/tmp/".$key));
	}

	function setCache($key, $rows)
	{
		file_put_contents("/tmp/".$key, serialize($rows));
	}
}