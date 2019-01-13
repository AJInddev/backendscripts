<?php

namespace App\Extras;
require_once "torrents/lib/tracker_info_class.php";
use TorrentCheck\TrackerInfo;
use Storage;

/**
 * Trackers manager - Updates new working trackers/trackers pending for validation/Blacklisted Trackers
 */
class TrackersManager
{
    private $BlacklistedTrackersJson;
    private $PendingTrackersJson;
    private $WorkingTrackersJson;
    private $AllTrackersJson;
    private $BlacklistedTrackersDB;
    private $PendingTrackersDB;
    private $WorkingTrackersDB;
    private $AllTrackersDB;
    private $DetailedTrackersJson;
    public function __construct()
    {
        // Detailed Trackers Info Json
        $this->DetailedTrackersJson = 'DeatiledTrackers.json';
        // Initialize Resources
        $this->BlacklistedTrackersJson = 'Blacklisted.json';
        // This Queue would be used by other script to check on the tracker on daily basis for next 30 days
        $this->PendingTrackersJson = 'TrackersQueue.json';
        // Get Working Trackers
        $this->WorkingTrackersJson = 'WorkingTrackers.json';
        // Get ConsolidatedTrackers
        $this->AllTrackersJson = 'AllTrackers.json';
        // Get BlackList DB
        $this->BlacklistedTrackersDB = $this->getBlacklistedDatabase();

    }

    public function getBlacklistedDatabase()
    {

        if (file_exists("tlist/" . $this->BlacklistedTrackersJson)) {
            $tmp = json_decode(Storage::disk('public')->get($this->BlacklistedTrackersJson), true);
        } else {
            $tmp = json_decode(Storage::disk('public')->get($this->BlacklistedTrackersJson), true);
        }

        return $tmp;
    }

    public function getGitHubBlacklistTrackersUpdate()
    {

        $tmp  = array();
        $data = explode("\n", $this->curl("https://raw.githubusercontent.com/ngosang/trackerslist/master/blacklist.txt"));
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $stat = $this->checkIfBlacklisted($value);
                if (!$stat[0]) {
                    echo "\nURL => " . $value;
                    $value                                             = explode(" # ", $value);
                    $this->BlacklistedTrackersDB[]['url']              = trim($value[0]);
                    $cnt                                               = count($this->BlacklistedTrackersDB) - 1;
                    $this->BlacklistedTrackersDB[$cnt]['reason']       = trim($value[1]);
                    $this->BlacklistedTrackersDB[$cnt]['last_checked'] = date('d-m-Y h:i:s');
                    $this->BlacklistedTrackersDB[$cnt]['date_added']   = date('d-m-Y h:i:s');
                }
            }
        }

        Storage::disk('public')->put($this->BlacklistedTrackersJson, json_encode($this->BlacklistedTrackersDB, JSON_PRETTY_PRINT));
    }

    public function checkIfBlacklisted($url)
    {

        echo "\n" . $domain = parse_url($url, PHP_URL_HOST);
        $blk                = array(0, '');
        foreach ($this->BlacklistedTrackersDB as $key => $value) {
            if ($value['url'] == $domain) {
                $blk = array(1, $value);
            }
            if (is_array($value) && strpos($value['url'], $domain)) {
                $blk = array(1, $value);
            }
        }

        return $blk;
    }

    public function getGitHubTrackersUpdate()
    {

        $tracker_class           = new TrackerInfo;
        $this->AllTrackersDB     = @json_decode(Storage::disk('public')->get($this->AllTrackersJson), true);
        $this->WorkingTrackersDB = @json_decode(Storage::disk('public')->get($this->WorkingTrackersJson), true);
        $this->PendingTrackersDB = @json_decode(Storage::disk('public')->get($this->PendingTrackersJson), true);
        $data                    = explode("\n", $this->curl("https://raw.githubusercontent.com/ngosang/trackerslist/master/trackers_all.txt"));
        foreach ($data as $key => $value) {
            // Check if the value is not empty string
            if (!empty($value)) {
                echo "\n\n\n|" . str_repeat('-', 50) . "|";
                echo "\n" . $value;
                // Check if the URL is already in black list if yes skip checking
                $stat = $this->checkIfBlacklisted($value);
                if (!$stat[0]) {

                    $res = $tracker_class->GetTrackerInfo($value, true);
                    print_r($res);
                    if (@is_numeric($res["response_time"])) {
                        if (isset($trackers_db["$value"]) and $trackers_db["$value"]["status"] == "Down") {
                            echo "$value is was down but now is online.\n";
                        }

                        $this->AllTrackersDB["$value"]["status"]            = "Good";
                        $this->AllTrackersDB["$value"]["response_time"]     = $res["response_time"];
                        $this->AllTrackersDB["$value"]["last_checked"]      = date('d-m-Y h:i:s');
                        $this->AllTrackersDB["$value"]["date_added"]        = date('d-m-Y h:i:s');
                        $this->WorkingTrackersDB["$value"]["last_checked"]  = date('d-m-Y h:i:s');
                        $this->WorkingTrackersDB["$value"]["status"]        = "Good";
                        $this->WorkingTrackersDB["$value"]["response_time"] = $res["response_time"];
                        $this->WorkingTrackersDB["$value"]["date_added"]    = date('d-m-Y h:i:s');
                    } else {
                        if (isset($trackers_db["$value"]) and $trackers_db["$value"]["status"] == "Down") {
                            echo "$value is still down.\n";
                        }

                        $this->AllTrackersDB["$value"]["status"]            = "Down";
                        $this->AllTrackersDB["$value"]["response_time"]     = $res["response_time"];
                        $this->AllTrackersDB["$value"]["last_checked"]      = date('d-m-Y h:i:s');
                        $this->AllTrackersDB["$value"]["date_added"]        = date('d-m-Y h:i:s');
                        $this->PendingTrackersDB["$value"]["status"]        = "Down";
                        $this->PendingTrackersDB["$value"]["response_time"] = $res["response_time"];
                        $this->PendingTrackersDB["$value"]["last_checked"]  = date('d-m-Y h:i:s');
                        $this->PendingTrackersDB["$value"]["date_added"]    = date('d-m-Y h:i:s');
                    }
                } else {
                    $this->BlacklistedTrackersDB[]['url']              = $value;
                    $cnt                                               = count($this->BlacklistedTrackersDB) - 1;
                    $this->BlacklistedTrackersDB[$cnt]['reason']       = "Possible duplicate of " . $stat[1]['url'];
                    $this->BlacklistedTrackersDB[$cnt]['last_checked'] = date('d-m-Y h:i:s');
                    echo "\nDuplicate of " . $stat[1]['url'] . "\n";
                }
                echo "\n|" . str_repeat('-', 50) . "|";
            }
        }
        $this->BlacklistedTrackersDB = $this->super_unique($this->BlacklistedTrackersDB, 'url');
        Storage::disk('public')->put($this->BlacklistedTrackersJson, json_encode($this->BlacklistedTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->AllTrackersJson, json_encode($this->AllTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->WorkingTrackersJson, json_encode($this->WorkingTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->PendingTrackersJson, json_encode($this->PendingTrackersDB, JSON_PRETTY_PRINT));
    }
    // Function to be worked on
    public function getSubmittedTrackersUpdate()
    {

        $tracker_class               = new TrackerInfo;
        $this->AllTrackersDB         = @json_decode(Storage::disk('public')->get($this->AllTrackersJson), true);
        $this->WorkingTrackersDB     = @json_decode(Storage::disk('public')->get($this->WorkingTrackersJson), true);
        $this->PendingTrackersDB     = @json_decode(Storage::disk('public')->get($this->PendingTrackersJson), true);
        $this->BlacklistedTrackersDB = @json_decode(Storage::disk('public')->get($this->BlacklistedTrackersJson), true);
        $data                        = @json_decode(Storage::disk('public')->get("submit.json"));
        foreach ($data as $key => $value) {
            // Check if the value is not empty string
            if (!empty($value)) {
                echo "\n\n\n|" . str_repeat('-', 50) . "|";
                echo "\n" . $value;
                // Check if the URL is already in black list if yes skip checking
                $stat = $this->checkIfBlacklisted($value);
                if (!$stat[0]) {

                    $res = $tracker_class->GetTrackerInfo($value, true);
                    print_r($res);
                    if (@is_numeric($res["response_time"])) {
                        if (isset($trackers_db["$value"]) and $trackers_db["$value"]["status"] == "Down") {
                            echo "$value is was down but now is online.\n";
                        }

                        $this->AllTrackersDB["$value"]["status"]            = "Good";
                        $this->AllTrackersDB["$value"]["response_time"]     = $res["response_time"];
                        $this->AllTrackersDB["$value"]["last_checked"]      = date('d-m-Y h:i:s');
                        $this->WorkingTrackersDB["$value"]["status"]        = "Good";
                        $this->WorkingTrackersDB["$value"]["response_time"] = $res["response_time"];
                        $this->WorkingTrackersDB["$value"]["last_checked"]  = date('d-m-Y h:i:s');
                    } else {
                        if (isset($trackers_db["$value"]) and $trackers_db["$value"]["status"] == "Down") {
                            echo "$value is still down.\n";
                        }

                        $this->AllTrackersDB["$value"]["status"]            = "Down";
                        $this->AllTrackersDB["$value"]["response_time"]     = $res["response_time"];
                        $this->AllTrackersDB["$value"]["last_checked"]      = date('d-m-Y h:i:s');
                        $this->PendingTrackersDB["$value"]["status"]        = "Down";
                        $this->PendingTrackersDB["$value"]["response_time"] = $res["response_time"];
                        $this->PendingTrackersDB["$value"]["last_checked"]  = date('d-m-Y h:i:s');
                    }
                } else {
                    $this->BlacklistedTrackersDB[]['url']              = $value;
                    $cnt                                               = count($this->BlacklistedTrackersDB) - 1;
                    $this->BlacklistedTrackersDB[$cnt]['reason']       = "Possible duplicate of " . $stat[1]['url'];
                    $this->BlacklistedTrackersDB[$cnt]['last_checked'] = date('d-m-Y h:i:s');
                    echo "\nDuplicate of " . $stat[1]['url'] . "\n";
                }
                echo "\n|" . str_repeat('-', 50) . "|";
            }
        }
        // print_r($this->WorkingTrackersDB);
        $this->BlacklistedTrackersDB = $this->super_unique($this->BlacklistedTrackersDB, 'url');
        Storage::disk('public')->put($this->BlacklistedTrackersJson, json_encode($this->BlacklistedTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->AllTrackersJson, json_encode($this->AllTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->WorkingTrackersJson, json_encode($this->WorkingTrackersDB, JSON_PRETTY_PRINT));
        Storage::disk('public')->put($this->PendingTrackersJson, json_encode($this->PendingTrackersDB, JSON_PRETTY_PRINT));
        foreach ($this->WorkingTrackersDB as $key => $value) {
            $host                                    = $this->getHost($key);
            $ip                                      = $this->getAssociatedIP($host);
            $this->WorkingTrackersDB[$key]['ipinfo'] = $ip;
            // print_r($this->WorkingTrackersDB);
        }
        Storage::disk('public')->put($this->DetailedTrackersJson, json_encode($this->WorkingTrackersDB, JSON_PRETTY_PRINT));
    }

    public function getTrackerIpLocationInfo()
    {

        if (Storage::disk('public')->exists($this->BlacklistedTrackersJson)) {
            $this->WorkingTrackersDB = json_decode(Storage::disk('public')->get($this->WorkingTrackersJson), true);
        } else {
            $this->WorkingTrackersDB = json_decode(Storage::disk('public')->get($this->WorkingTrackersJson), true);
        }

        foreach ($this->WorkingTrackersDB as $key => $value) {
            $host                                    = $this->getHost($key);
            $ip                                      = $this->getAssociatedIP($host);
            $this->WorkingTrackersDB[$key]['ipinfo'] = $ip;
            // print_r($this->WorkingTrackersDB);
        }
        Storage::disk('public')->put($this->DetailedTrackersJson, json_encode($this->WorkingTrackersDB, JSON_PRETTY_PRINT));

    }

    private function getHost($url)
    {

        $info = parse_url($url);
        return $info['host'];

    }

    // Get associated IP address
    private function getAssociatedIP($host)
    {
        $aip         = array();
        $locationstr = "http://ip-api.com/json/";
        $locationstr = $locationstr . $host;
        $xml         = json_decode($this->curl($locationstr, 'ip-api'));
        echo "\n********************************************************************";
        print_r($xml);
        echo "\n********************************************************************";
        if (!empty($xml) && isset($xml->countryCode)) {
            $aip = array($xml->query => array('ccode' => $xml->countryCode, 'cname' => $xml->country, 'isp' => $xml->isp));
        } else {
            $dip = @dns_get_record($host, DNS_A);
            if (!$dip) {
                return $aip;
            }

            foreach ($dip as $key => $value) {
                $hip = gethostbyname($host);
                if (!isset($value['ipv6']) && !isset($value['ip'])) {
                    $aip[$hip] = $this->TraceLocation(gethostbyname($hip));
                }

                if (isset($value['ipv6'])) {
                    $aip[$value['ipv6']] = $this->TraceLocation($value['ipv6']);
                }

                if (isset($value['ip'])) {
                    $aip[$value['ip']] = $this->TraceLocation($value['ip']);
                }

            }
        }
        print_r($aip);
        return $aip;
    }

    // Trace ISP and Country Info
    public function TraceLocation($ipaddress)
    {

        $host = gethostbyaddr($ipaddress);

        $locationstr = "http://ip-api.com/json/";
        $locationstr = $locationstr . $ipaddress;
        //loading the xml file directly from the website
        $xml = json_decode($this->curl($locationstr, 'ip-api'));
        echo "\n********************************************************************";
        print_r($xml);
        echo "\n********************************************************************";
        if (!empty($xml) && isset($xml->countryCode)) {
            $countrycode = $xml->countryCode; //country code
            $countryname = $xml->country; //country name
            $isp         = $xml->org; //city latitude
        } else {
            $countrycode = ""; //country code
            $countryname = ""; //country name
            $isp         = ""; //city latitude
        }
        return array('ccode' => $countrycode, 'cname' => $countryname, 'isp' => $isp);
    }

    public function super_unique($array, $key)
    {
        $temp_array = [];
        foreach ($array as &$v) {
            if (!isset($temp_array[$v[$key]])) {
                $temp_array[$v[$key]] = &$v;
            }

        }
        $array = array_values($temp_array);
        return $array;

    }

    public function curl($url, $post = false)
    {
        $resolve = array(sprintf(
            "%s:%d:%s",
            'ip-api.com',
            '80',
            '72.11.140.50'
        ));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        // if ($post) {
        //     curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
        // }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
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

