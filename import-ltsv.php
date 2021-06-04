#!/usr/bin/php -q
<?php
	require_once('mysql.php');
	$link = new mysqli($hostname, $username, $password);

	if(mysqli_connect_errno())
	{
		echo('mysqli connection error: ' . mysqli_connect_error());
		die;
	}

	mysqli_select_db($link, "dnsstats");

	$query = "SELECT `time` FROM `ltsv` ORDER BY `time` DESC LIMIT 1";
	$res = mysqli_query($link, $query);
	$lasttime = mysqli_fetch_assoc($res)['time'];

	$fp = fopen("/var/log/dnscrypt-proxy/query.log", "r");
	while (($buffer = fgets($fp, 4096)) !== false)
	{
		$arr = array();
		$line = trim($buffer);
		$bits = explode("\t", $line);
		foreach($bits as $val)
		{
			list($key, $val) = explode(":", $val, 2);
			$key = trim($key);
			$val = trim($val);

			if($key == "return")
				$key = "return_value";

			$key = mysqli_real_escape_string($link, $key);

			if($key == "time" || $key == "cached" || $key == "duration")
				$val = intval($val);
			else if($val != "-")
				$val = "'".mysqli_real_escape_string($link, $val)."'";
			else
				$val = "NULL";

			$arr[$key] = $val;
		}

		if(intval($arr['time']) < $lasttime)
			continue;

		$query = "SELECT 1 FROM `ltsv` WHERE `time`=${arr['time']} AND `host`=${arr['host']} AND `message`=${arr['message']} AND `type`=${arr['type']}";
		$res = mysqli_query($link, $query);
		if(mysqli_num_rows($res) > 0)
			continue;

		$query = "INSERT INTO `ltsv` SET";
		foreach($arr as $key => $val)
			$query .= " `$key`=$val,";

		$query = substr($query, 0, -1);
		if(mysqli_query($link, $query) === FALSE)
		{
			echo $query."\n";
			echo mysqli_error($link)."\n";
			die;
		}
	}
	fclose($fp);
