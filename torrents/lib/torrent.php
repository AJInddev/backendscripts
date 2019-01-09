<?php
namespace TorrentCheck;
class TorrentData
{
	function GetTorrentInfo($torrent_file)
	{
		$content = file_get_contents($torrent_file);
		if($content=="")
		{
			TrackerInfo::$error="Could not open torrent file ($torrent_file)";
			return;
		}
		return $this->GetTorrentStrInfo($content);
	}
	function GetTorrentStrInfo($content)
	{
		$bencode_class=new BEncode;
		$pos=0;
		$data=$bencode_class->Decode($content, $pos);
		if(!is_array($data))
		{
			TrackerInfo::$error="Could not parse torrent file.";
			return;
		}
		$data["info_hash"]=bin2hex(sha1($bencode_class->Encode($data['info']), true));
		$data["info"]["pieces"]="";
		return $data;
	}
}