<?php
use TorrentCheck\TrackerInfo;
// error_reporting(0);
?>
<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<title>Trackers</title>
<body>

<form method="post">
Enter trackers list<br>
<textarea class='form-control' name='trackers' rows='10'><?php echo @$_POST["trackers"]?></textarea><br>
    <button class='btn btn-primary'>Get trackers info</button>
</form>

</body>
</html>
<?php
require_once 'lib/tracker_info_class.php';
if(isset($_POST["trackers"]))
{
	$tracker_class=new TrackerInfo;
	$pts=explode("\n", $_POST["trackers"]);
	foreach($pts as $tracker)
	{
		$tracker=trim($tracker);
		if($tracker=="")
			continue;
		$tracker_data=$tracker_class->GetTrackerInfo($tracker);
		if($tracker_data=="")
		{
			echo $tracker_class::$error."<br>";
		}
		else
		{
			echo "<b>$tracker</b><br>";
			echo "<b>Response time</b>: {$tracker_data["response_time"]} ms<br>";
			foreach($tracker_data["ips"] as $ip=>$ip_data)
			{
				echo "<b>IP</b>: $ip<br>";
				echo "<b>Country</b>: {$ip_data["country_name"]}<br>";
				echo "<b>Network</b>: {$ip_data["netname"]}<br><br>";
			}
		}
	}
	$tracker_class->UpdateDB();
}