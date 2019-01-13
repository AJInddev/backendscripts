<?php
namespace TorrentCheck;
class HTTPTracker
{
	function GetHashInfo($tracker, $hash)
	{
		$bencode_class=new BEncode;
		$url=str_ireplace("announce", "scrape", $tracker);
		$url=str_ireplace("ann", "scrape", $url);
		$sep = preg_match ('/\?.{1,}?/i', $url) ? '&' : '?';
		$requesturl = $url."?info_hash=".rawurlencode(pack('H*', $hash));
		$res = file_get_contents($requesturl);
		if($res=="")
		{
			TrackerInfo::$error="Tracker is down.";
			return;
		}
		$decode_class=new lightbenc;
		$data = $decode_class->bdecode($res);
		if($data=="")
		{
			TrackerInfo::$error="Tracker response is: $res";
			return;
		}
		if(!isset($data["files"]["$hash"]))
		{
			TrackerInfo::$error="Tracker response is:";
			foreach($data as $param=>$value)
			{
				if(is_array($value))
				{
					foreach($value as $v1=>$v2)
					{
						TrackerInfo::$error.="$param: $v1: $v2; ";
					}
				}
				else
					TrackerInfo::$error.="$param: $value; ";
				return;
			}
		}
		list($files, $data)=each($data);
		list($hash, $data)=each($data);
		$data["seeders"]=$data["complete"];
		$data["completed"]=$data["downloaded"];
		$data["leechers"]=$data["incomplete"];
		return $data;
	}
}