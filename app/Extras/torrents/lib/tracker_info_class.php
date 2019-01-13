<?php
namespace TorrentCheck;
class TrackerInfo
{
	static $error;
	static $timeout;
	function __construct($path=__DIR__)
	{
		$this->path=$path;
		self::$timeout=2;
		require_once "$this->path/bencode.php";
		require_once "$this->path/lbencode.php";
	}
	function GetTorrentInfo($torrent_file)
	{
		self::$error="";
		include_once "$this->path/torrent.php";
		$torrent_data=new TorrentData;
		return $torrent_data->GetTorrentInfo($torrent_file);
	}
	function GetHashInfo($tracker, $hash)
	{
		$this->set_db();
		if(isset($this->trackers_db["$tracker"]) and $this->trackers_db["$tracker"]["status"]=="Down")
		{
			self::$error="Tracker is in blacklist.";
			return;
		}
		if(isset($this->trackers_db["$tracker"]) and $this->trackers_db["$tracker"]["seeders"]==0)
		{
			self::$error="Tracker is in blacklist: no seeders";
			return;
		}
		self::$error="";
		$ip=$this->GetIp($tracker);
		if(strtolower(substr($tracker, 0, 4))=="http")
		{
			include_once "$this->path/http_tracker.php";
			$tracker_class=new HTTPTracker;
		}
		if(strtolower(substr($tracker, 0, 3))=="udp")
		{
			include_once "$this->path/udp_tracker.php";
			$tracker_class=new UDPTracker;
		}
		$data=$tracker_class->GetHashInfo($tracker, $hash);
		$this->add_to_db($tracker, "seeders", $data["seeders"]);
		return $data;
	}
	private function TraceLocation($ipaddress)
    {

        $host = gethostbyaddr($ipaddress);

        $locationstr = "http://ip-api.com/json/";
        $locationstr = $locationstr . $ipaddress;
        //loading the xml file directly from the website
        $xml = json_decode($this->curl($locationstr));
        echo "\n********************************************************************";
        print_r($xml);
        echo "\n********************************************************************";
        if (!empty($xml) && isset($xml->countryCode)) {
            $countrycode = $xml->countryCode; //country code
            $countryname = $xml->country; //country name
            $isp         = $xml->org; //city latitude
        }
        else{
        	$countrycode = ""; //country code
            $countryname = ""; //country name
            $isp         = ""; //city latitude
        }
        return array('ccode' => $countrycode, 'cname' => $countryname, 'isp' => $isp);
    }
	function GetTrackerInfo($tracker)
	{
		$this->set_db();
		if(isset($this->trackers_db["$tracker"]) and $this->trackers_db["$tracker"]["status"]=="Down")
		{
			self::$error="Tracker is in blacklist.";
			return;
		}
		self::$error="";
		$ips=$this->GetIp($tracker);
		foreach($ips as $ip)
		{
			if(!filter_var($ip, FILTER_VALIDATE_IP))
			{
				TrackerInfo::$error="Could not fetch IP address for $tracker.";
				return;
			}
			// $json=json_decode(file_get_contents("https://rest.db.ripe.net/search.json?query-string=$ip&flags=no-filtering&source=RIPE"), true);
			// if($json=="")
			// {
			// 	TrackerInfo::$error="Could not fetch data for $tracker.";
			// 	return;
			// }
			// $values=$json["objects"]["object"][0]["attributes"]["attribute"];
			// foreach($values as $block)
			// {
			// 	$ip_data["{$block["name"]}"]=$block["value"];
			// }
			// include "$this->path/country_codes.php";
			// if(isset($countries["{$ip_data["country"]}"]))
			// 	$ip_data["country_name"]=$countries["{$ip_data["country"]}"];
			// else
			$ip_data["country_name"]= $this->TraceLocation($ip);
			$ip_data["country_name"]=$ip_data["country_name"]['cname'];
			$out["ips"]["$ip"]=$ip_data;
			$out["response_time"]=$this->Ping($tracker);
		}
		$value=(is_numeric($out["response_time"])) ? "Good" : "Down";
		$this->add_to_db($tracker, "status", $value);
		return $out;
	}
	function Ping($tracker)
	{
		self::$error="";
		$url= parse_url($tracker, PHP_URL_HOST);
		$port= parse_url($tracker, PHP_URL_PORT);
		$protocol=parse_url($tracker, PHP_URL_SCHEME);
		$start=microtime(true);
		if($protocol!="udp")
			$protocol="tcp";
		$file = @fsockopen ("$protocol://$url", $port, $errno, $errstr, self::$timeout);
		$res=(microtime(true)-$start)*1000;
		if(!$file)
			return "-";
		return round($res, 0);
	}
	function GetIp($tracker)
	{
		self::$error="";
		echo "\nHOST => ".$url=self::GetHost($tracker);
		$locationstr = "http://ip-api.com/json/";
        $locationstr = $locationstr .$url;
		$xml = json_decode($this->curl($locationstr));
        echo "\n********************************************************************";
        print_r($xml);
        echo "\n********************************************************************";
        if (!empty($xml) && isset($xml->countryCode)) {
            $ips = array($xml->query);
        }
        else{
			$url=self::GetHost($tracker);
			$ips=gethostbynamel($url);
		}
		return $ips;
// 		echo "$ip\n";
	}
	function SetTimeOut($val)
	{
		self::$error="";
		ini_set('default_socket_timeout',$val);
		self::$timeout=$val;
	}
	static function GetHost($tracker)
	{
		self::$error="";
		$host= parse_url($tracker, PHP_URL_HOST);
		return $host;
	}
	function GetError()
	{
		return self::$error;
	}
	function UpdateDB()
	{
		if(!isset($this->trackers_db))
			return;
		$out_file=fopen("$this->path/trackers.json", "w");
		fwrite($out_file, json_encode($this->trackers_db));
	}
	function set_db()
	{
		if(!isset($this->trackers_db))
			$this->trackers_db=json_decode(file_get_contents("$this->path/trackers.json"), true);
	}
	function add_to_db($tracker, $type, $value)
	{
		$this->set_db();
		$this->trackers_db["$tracker"]["$type"]=$value;
	}
	private function curl($url)
    {
    	$resolve = array(sprintf(
		    "%s:%d:%s", 
		    'ip-api.com',
		    '80',
		    '139.99.8.58'
		));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.98 Safari/537.36');
        $data = curl_exec($ch);
        if (curl_error($ch)) {
            print_r(curl_errno($ch) . ' ' . curl_error($ch));
        }
        curl_close($ch);
        sleep(2);
        // var_dump($data);
        return $data;
    }
}
