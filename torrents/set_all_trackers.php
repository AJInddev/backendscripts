<?php
require_once "lib/tracker_info_class.php";
use TorrentCheck\TrackerInfo;
$tracker_class=new TrackerInfo;
$data=file("https://raw.githubusercontent.com/ngosang/trackerslist/master/trackers_all.txt");
$trackers_db=json_decode(file_get_contents("trackers.json"), true);
foreach($data as $str)
{
	$tracker=trim($str);
	if($tracker=="")
		continue;
	$res=$tracker_class->GetTrackerInfo($tracker, true);
// 		print_r($res);
	if(@is_numeric($res["response_time"]))
	{
		if(isset($trackers_db["$tracker"]) and $trackers_db["$tracker"]["status"]=="Down")
			echo "$tracker is was down but now is online.<br>\n";
		$out["$tracker"]["status"]="Good";
// 		echo "$tracker - good\n";
	}
	else
	{
		if(isset($trackers_db["$tracker"]) and $trackers_db["$tracker"]["status"]=="Down")
			echo "$tracker is still down.<br>\n";
		$out["$tracker"]["status"]="Down";
// 		echo "$tracker Down\n";
	}
}
$out_file=fopen("trackers.json", "w");
fwrite($out_file, json_encode($out));