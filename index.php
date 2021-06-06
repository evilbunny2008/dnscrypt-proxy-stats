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
  <div class="Total-Queries-by-Return-Code">
<br/>
<table class="blueTable">
<thead>
<tr>
<th>Return</th>
<th>Value</th>
</tr>
</thead>
<tbody>
<?php
	$query = "SELECT `return_value`, count(`return_value`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `return_value` ORDER BY count(`return_value`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
		echo "<tr><td>${row['return_value']}</td><td>${row['count']}</td></tr>\n";
?>
</tbody>
</tr>
</table>
  </div>
  <div class="Total-Queries-by-Server">
<br/>
<table class="blueTable">
<thead>
<tr>
<th>Server</th>
<th>Value</th>
</tr>
</thead>
<tbody>
<?php
	$query = "SELECT `server`, count(`server`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `server` ORDER BY count(`server`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
		echo "<tr><td>${row['server']}</td><td>${row['count']}</td></tr>\n";
?>
</tbody>
</tr>
</table>
  </div>
  <div class="Total-Queries-by-Src-IP">
<br/>
<table class="blueTable">
<thead>
<tr>
<th>Source IP</th>
<th>Value</th>
</tr>
</thead>
<tbody>
<?php
	$query = "SELECT `host`, count(`host`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `host` ORDER BY count(`host`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
		echo "<tr><td>${row['host']}</td><td>${row['count']}</td></tr>\n";
?>
</tbody>
</tr>
</table>
  </div>
  <div class="Total-Queries-by-Type"><canvas id="typeChart"></canvas></div>
<?php
	$donutlabels = $donutdata = "";
	$query = "SELECT `type`, count(`type`) as `count` FROM `ltsv` where `time` >= $startTime and `time` < $now + $period GROUP BY `type` ORDER BY count(`type`) DESC";
	$res = mysqli_query($link, $query);
	while($row = mysqli_fetch_assoc($res))
	{
		if($donutlabels != "")
			$donutlabels .= ", ";
		if($donutdata != "")
			$donutdata .= ", ";

		$donutlabels .= '"'.$row['type'].'"';
		$donutdata .= $row['count'];
	}
?>
</div>

    <script>
      var donutData = {
	labels: [ <?=$donutlabels?> ],
	datasets: [{
	    data: [ <?=$donutdata?> ],
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
      var donut = document.getElementById('typeChart').getContext('2d');
      var myDonut = new Chart(donut, {
	type: 'doughnut',
	data: donutData,
	options: {
	    responsive: true,
	},
      });

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
