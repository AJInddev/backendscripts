<?php
use TorrentCheck\TrackerInfo;
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<title>Hash</title>
<body>
<h3 class='text-center'>Check torrent tracker</h3>
<div class='col-lg-12 col-md-12 col-sm-12'>
<form method='get' class='form-horizontal'>
<div class="form-group">
<label class="control-label col-lg-3 col-sm-3 col-md-3">Tracker</label>
<div class=" col-lg-6 col-sm-6 col-md-6"><input type='text' value='<?php echo @$_GET["tracker"]?>' name='tracker' id='value_name' class='form-control'>
</div>
<div class=" col-lg-3 col-sm-3 col-md-3"></div>
</div>
<div class="form-group">
<label class="control-label col-lg-3 col-sm-3 col-md-3">Hash</label>
<div class=" col-lg-6 col-sm-6 col-md-6"><input type='text' name='hash' value='<?php echo @$_GET["hash"]?>' class='form-control'>
</div>
<div class=" col-lg-3 col-sm-3 col-md-3"></div>
</div>
<div class="form-group">
<label class="control-label col-lg-3 col-sm-3 col-md-3"></label><div class=" col-lg-9 col-sm-9 col-md-9"><button class='btn btn-default' type='submit' value='check' name='save'>Check</button>
</div>
</div>
</body>
<?php
require_once 'lib/tracker_info_class.php';
if(isset($_GET["tracker"]))
{
	if($_GET["tracker"]!="" and $_GET["hash"]!="")
	{
		$tracker=$_GET["tracker"];
		$hash=$_GET["hash"];
		$tracker_class=new TrackerInfo;
		$hash_data=$tracker_class->GetHashInfo($tracker, $hash);
		echo "<h2>Information about $tracker</h2>";
		echo "<h3>Tracker statistics</h3>";
		if($hash_data=="")
		{
			echo $tracker_class::$error."<br>";
		}
		else
		{
			foreach($hash_data as $param=>$value)
			{
				echo "<b>$param:</b> $value<br>";
			}
		}
		$tracker_data=$tracker_class->GetTrackerInfo($tracker);
		if($tracker_data=="")
		{
			echo $tracker_class::$error."<br>";
		}
		else
		{
			echo "<h2>Tracker info</h2>";
			echo "<div class='col-lg-1'><b>Response time</b></div><div class='col-lg-11'> {$tracker_data["response_time"]} ms</div>";
			foreach($tracker_data["ips"] as $ip=>$ip_data)
			{
				if(!is_array($ip_data))
				{
					echo "$ip_data<br>";
					continue;
				}
				echo "<div class='col-lg-1'><b>IP</b></div><div class='col-lg-11'> $ip</div>";
				echo "<div class='col-lg-1'><b>Country</b></div><div class='col-lg-11'> {$ip_data["country_name"]}&nbsp;</div>";
				echo "<div class='col-lg-1'><b>Network</b></div><div class='col-lg-11'> {$ip_data["netname"]}</div><br>&nbsp;";
			}
		}
	}
	$tracker_class->UpdateDB();
}