#!/usr/bin/php -q
<?php
	// This script is for using a named pipe with dnscrypt-proxy, not a query.log
	$verbose = false;
	if($argc > 1 && $argv['1'] == '-v')
		$verbose = true;

	require_once('mysql.php');
	$link = new mysqli($hostname, $username, $password);

	if(mysqli_connect_errno())
	{
		echo('mysqli connection error: ' . mysqli_connect_error());
		die;
	}

	mysqli_select_db($link, "dnsstats");

	if(!file_exists("/var/run/query.log.pipe"))
	{
		if($verbose)
			echo "/var/run/query.log.pipe doesn't exist, creating it...\n";

		posix_mkfifo("/var/run/query.log.pipe", 0600);
	}


	if($verbose)
		echo "chmoding /var/run/query.log.pipe to 0600\n";

	chmod("/var/run/query.log.pipe", 0600);

	if($verbose)
		echo "chowning /var/run/query.log.pipe to dnscrypt\n";

	chown("/var/run/query.log.pipe", "dnscrypt");

	if($verbose)
		echo "chgrping /var/run/query.log.pipe to dnscrypt\n";

	chgrp("/var/run/query.log.pipe", "dnscrypt");

	if($verbose)
		echo "Opening /var/run/query.log.pipe to read\n";

	$fp = fopen("/var/run/query.log.pipe", "r");

	if($verbose)
		echo "Dropping from root to dnscrypt\n";

	$user = posix_getpwnam("dnscrypt");
	posix_setuid($user['uid']);

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
			else
				$val = "'".mysqli_real_escape_string($link, $val)."'";

			$arr[$key] = $val;
		}

		$query = "INSERT INTO `ltsv` SET";
		foreach($arr as $key => $val)
			$query .= " `$key`=$val,";

		$query = substr($query, 0, -1);

		if($verbose)
			echo $query."\n";

		if(mysqli_query($link, $query) === FALSE)
		{
			echo mysqli_error($link)."\n";
			die;
		}
	}

	fclose($fp);
