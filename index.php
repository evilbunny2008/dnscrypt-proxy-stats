<?php
	require_once('mysql.php');
	$link = new mysqli($hostname, $username, $password);

	if(mysqli_connect_errno())
	{
		echo('mysqli connection error: ' . mysqli_connect_error());
		die;
	}

	mysqli_select_db($link, "dnsstats");
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNSCrypt-Proxy Stats</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.3.2/chart.min.js"></script>
	<link rel="stylesheet" href="css/styles.css">
  </head>
  <body>
<?php
	$period = 600;
	$timeSpan = 86400;
	$startTime = time() - $timeSpan;
	$delem = '"'.date("H:i", $startTime).'"';

	$query = "SELECT count(`time`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $startTime + $period";
	$res = mysqli_query($link, $query);
	$total = mysqli_fetch_assoc($res)['count'];

	$query = "SELECT count(`time`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $startTime + $period and `cached`=1";
	$res = mysqli_query($link, $query);
	$cached = mysqli_fetch_assoc($res)['count'];

	for($i = 1; $i < $timeSpan / $period; $i++)
	{
		$now = $i * $period + $startTime;
		$delem .= ', "'.date("H:i", $now).'"';

		$query = "SELECT count(`time`) as `count` FROM `ltsv` where `time` >= $now and `time` < $now + $period";
		$res = mysqli_query($link, $query);
		$total .= ", ".mysqli_fetch_assoc($res)['count'];

		$query = "SELECT count(`time`) as `count` FROM `ltsv` where `time` >= $now and `time` < $now + $period and `cached`=1";
		$res = mysqli_query($link, $query);
		$cached .= ", ".mysqli_fetch_assoc($res)['count'];
	}
?>
<div class="grid-container">
  <div class="DNS-Hits" style='width:100%;height:100%'><canvas id="myChart" style='width:100%;height:100%'></canvas></div>
  <div class="Total-Queries-by-Return-Code" style="position: relative; height:23vh; width:23vw"><canvas id="rcChart"></canvas></div>
<?php
	$rclabels = $rcdata = "";
	$query = "SELECT `return_value`, count(`return_value`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `return_value` ORDER BY count(`return_value`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($rclabels != "")
			$rclabels .= ", ";
		if($rcdata != "")
			$rcdata .= ", ";

		$rclabels .= '"'.$row['return_value'].'"';
		$rcdata .= $row['count'];
	}
?>
  <div class="Total-Queries-by-Server">
  <div class="Total-Queries-by-Server" style="position: relative; height:23vh; width:23vw"><canvas id="serverChart"></canvas></div>
<?php
	$serverlabels = $serverdata = "";
	$query = "SELECT `server`, count(`server`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `server` ORDER BY count(`server`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($serverlabels != "")
			$serverlabels .= ", ";
		if($serverdata != "")
			$serverdata .= ", ";

		if($row['server'] == '-')
			$row['server'] = "From Cache";

		$serverlabels .= '"'.$row['server'].'"';
		$serverdata .= $row['count'];
	}
?>
  </div>
  <div class="Total-Queries-by-Src=IP" style="position: relative; height:23vh; width:23vw"><canvas id="srcIPChart"></canvas></div>
<?php
	$srciplabels = $srcipdata = "";
	$query = "SELECT `host`, count(`host`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `host` ORDER BY count(`host`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($srciplabels != "")
			$srciplabels .= ", ";
		if($srcipdata != "")
			$srcipdata .= ", ";

		$srciplabels .= '"'.$row['host'].'"';
		$srcipdata .= $row['count'];
	}
?>
  <div class="Total-Queries-by-Type" style="position: relative; height:23vh; width:23vw"><canvas id="typeChart"></canvas></div>
<?php
	$typelabels = $typedata = "";
	$query = "SELECT `type`, count(`type`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `type` ORDER BY count(`type`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($typelabels != "")
			$typelabels .= ", ";
		if($typedata != "")
			$typedata .= ", ";

		$typelabels .= '"'.$row['type'].'"';
		$typedata .= $row['count'];
	}
?>
</div>

    <script>
      var rcData = {
	labels: [ <?=$rclabels?> ],
	datasets: [{
	    data: [ <?=$rcdata?> ],
	    backgroundColor: [
                "#039741",
                "#3498db",
                "#e83e8c",
                "#375a7f",
                "#00bc8c",
		"#fae372",
		"#333333",
		"#00aabb",
            ],
	}],
      };
      var rc = document.getElementById('rcChart').getContext('2d');
      var myrc = new Chart(rc, {
	type: 'doughnut',
	data: rcData,
	options: {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Return Codes',
                    },
                },
	    responsive: true,
	},
      });
      myrc.resize(100, 100);

      var serverData = {
	labels: [ <?=$serverlabels?> ],
	datasets: [{
	    data: [ <?=$serverdata?> ],
	    backgroundColor: [
                "#039741",
                "#3498db",
                "#e83e8c",
                "#375a7f",
                "#00bc8c",
		"#fae372",
		"#333333",
		"#00aabb",
            ],
	}],
      };
      var server = document.getElementById('serverChart').getContext('2d');
      var myserver = new Chart(server, {
	type: 'doughnut',
	data: serverData,
	options: {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Requests by Server',
                    },
                },
	    responsive: true,
	},
      });
      myserver.resize(100, 100);

      var srcipData = {
	labels: [ <?=$srciplabels?> ],
	datasets: [{
	    data: [ <?=$srcipdata?> ],
	    backgroundColor: [
                "#039741",
                "#3498db",
                "#e83e8c",
                "#375a7f",
                "#00bc8c",
		"#fae372",
		"#333333",
		"#00aabb",
            ],
	}],
      };
      var srcip = document.getElementById('srcIPChart').getContext('2d');
      var mysrcip = new Chart(srcip, {
	type: 'doughnut',
	data: srcipData,
	options: {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Src IP Requests',
                    },
                },
	    responsive: true,
	},
      });
      mysrcip.resize(100, 100);

      var typeData = {
	labels: [ <?=$typelabels?> ],
	datasets: [{
	    data: [ <?=$typedata?> ],
	    backgroundColor: [
                "#039741",
                "#3498db",
                "#e83e8c",
                "#375a7f",
                "#00bc8c",
		"#fae372",
		"#333333",
		"#00aabb",
            ],
	}],
      };
      var typeid = document.getElementById('typeChart').getContext('2d');
      var mytype = new Chart(typeid, {
	type: 'doughnut',
	data: typeData,
	options: {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'DNS Type Requests',
                    },
                },
	    responsive: true,
	},
      });
      mytype.resize(100, 100);

      var ctx = document.getElementById('myChart').getContext('2d');
      var myChart = new Chart(ctx, {
          type: 'line',
	  options: {
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: '<?php
	$query = "SELECT count(`time`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period";
	$res = mysqli_query($link, $query);
	$queries = mysqli_fetch_assoc($res)['count'];
	echo "Total Queries Served in the past 24 hours: $queries";
?>',
                    },
                },
		elements: {
                    point:{
                        radius: 2
		    }
		},
		responsive: true,
          },
          data: {
            labels: [<?=$delem?>],
            datasets: [{
                data: [<?=$total?>],
                label: "Total",
		borderColor: 'rgb(255, 99, 132)',
		backgroundColor: 'rgb(255, 99, 132)',
		borderWidth: 1,
                fill: false,
              }, {
                data: [<?=$cached?>],
                label: "Cached",
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgb(54, 162, 235)',
		borderWidth: 1,
                fill: false,
              }
            ]
          },
        });
    </script>
  </body>
</html>
