<?php
use TorrentCheck\TrackerInfo;
?>
<!DOCTYPE html>
<html>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<title>Torrent info</title>
<body>

<form method="post" enctype="multipart/form-data">
    Select torrent file to upload:<br>
<label class="btn btn-default btn-file">
    Select torrent file <input type="file" style="display: none;" name='torrent_file'>
</label> 
    <button class='btn btn-primary'>Upload</button>
</form>

</body>
</html>
<?php
// error_reporting(0);
require_once 'lib/tracker_info_class.php';
if(isset($_FILES["torrent_file"]))
{
	$tracker_class=new TrackerInfo;
	$tracker_class->SetTimeOut(2);
	$data=$tracker_class->GetTorrentInfo($_FILES["torrent_file"]["tmp_name"]);
	if($data=="")
	{
		echo $tracker_class::$error;
		exit;
	}
	echo "<h2>Torrent data</h2>";
	echo "<div class='col-lg-1'><b>File name</b></div><div class='col-lg-11'> {$_FILES["torrent_file"]["name"]}</div>";
	echo "<div class='col-lg-1'><b>Announce</b></div><div class='col-lg-11'> {$data["announce"]}</div>";
	echo "<div class='col-lg-1'><b>Comment</b></div><div class='col-lg-11'> {$data["comment"]}</div>";
	echo "<div class='col-lg-1'><b>Creation date</b></div><div class='col-lg-11'> ".date("Y-m-d h:i:s", $data["creation date"])."</div>";
	echo "<div class='col-lg-1'><b>Hash</b></div><div class='col-lg-11'> {$data["info_hash"]}</div>";
	$hash=$data["info_hash"];
	foreach($data["announce-list"] as $trackers)
	{
		foreach($trackers as $tracker)
		{
			echo "<h2>Information about $tracker</h2>";
			$hash_data=$tracker_class->GetHashInfo($tracker, $hash);
			echo "<h3>Tracker statistics</h3>";
			if($hash_data=="")
			{
				echo $tracker_class::$error."<br>";
			}
			else
			{
				foreach($hash_data as $pram=>$value)
				{
					echo "<b>$pram:</b> $value<br>";
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
					echo "<div class='col-lg-1'><b>Country</b></div><div class='col-lg-11'> {$ip_data["country_name"]}</div>";
					echo "<div class='col-lg-1'><b>Network</b></div><div class='col-lg-11'> {$ip_data["netname"]}</div><br>&nbsp;";
				}
			}
		}
	}
	$tracker_class->UpdateDB();
}