<?php
namespace TorrentCheck;
class UDPTracker
{
	function GetHashInfo($tracker, $hash)
	{
		if(!preg_match('%udp://([^:/]*)(?::([0-9]*))?(?:/)?%si', $tracker, $m))
		{
			TrackerInfo::$error='Invalid tracker url.';
			return;
		}
		$tracker = 'udp://' . $m[1];
		$port = isset($m[2]) ? $m[2] : 80;		
		$transaction_id = mt_rand(0,65535);
		$fp = fsockopen($tracker, $port, $errno, $errstr);
// 		echo "get $tracker stst<br>";
		if(!$fp)
		{
			TrackerInfo::$error='Could not open UDP connection: ' . $errno . ' - ' . $errstr;
			return;
		}
// 		stream_set_timeout($fp, $this->timeout);
			
		$current_connid = "\x00\x00\x04\x17\x27\x10\x19\x80";
			
		//Connection request
		$packet = $current_connid . pack("N", 0) . pack("N", $transaction_id);
		fwrite($fp,$packet);
			
		//Connection response
		$ret = fread($fp, 16);
		if(strlen($ret) < 1)		
		{
			TrackerInfo::$error='No connection response.';
			return;
		}
		if(strlen($ret) < 16)
		{
			TrackerInfo::$error='Too short connection response.';
			return;
		}
		$retd = unpack("Naction/Ntransid",$ret);
		if($retd['action'] != 0 || $retd['transid'] != $transaction_id)
		{
			TrackerInfo::$error='Invalid connection response.';
			return;
		}
		$current_connid = substr($ret,8,8);
			
		//Scrape request
		$p_hash = pack('H*', $hash);
		$packet = $current_connid . pack("N", 2) . pack("N", $transaction_id) . $p_hash;
		fwrite($fp,$packet);
			
		//Scrape response
		$readlength = 8 + (12 * count($hash));
		$ret = fread($fp, $readlength);
		$retd = unpack("Naction/Ntransid",$ret);
		// Todo check for error string if response = 3
		if($retd['action'] != 2 || $retd['transid'] != $transaction_id)
		{
			TrackerInfo::$error='Invalid scrape response.';
			return;
		}
		if(strlen($ret) < $readlength)
		{
			TrackerInfo::$error='Too short scrape response.';
			return;
		}
		$torrents = array();
		$index = 8;
		$retd = unpack("Nseeders/Ncompleted/Nleechers",substr($ret,$index,12));
		$retd['infohash'] = $hash;
		$torrents = $retd;
		$index = $index + 12;
		return($torrents);
	}
}