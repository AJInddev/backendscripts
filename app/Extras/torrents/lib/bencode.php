<?php
namespace TorrentCheck;
class BEncode
{
	function Encode($str)
	{
		return $this->bencode($str);
	}
	function Decode($str)
	{
		$pos=0;
		return $this->bdecode($str, $pos);
	}
	function bdecode($str, &$pos) {
		$strlen = strlen($str);
		if (($pos < 0) || ($pos >= $strlen)) {
			return null;
		}
		else if ($str{$pos} == 'i') {
			$pos++;
			$numlen = strspn($str, '-0123456789', $pos);
			$spos = $pos;
			$pos += $numlen;
			if (($pos >= $strlen) || ($str{$pos} != 'e')) {
				return null;
			}
			else {
				$pos++;
				return intval(substr($str, $spos, $numlen));
			}
		}
		else if ($str{$pos} == 'd') {
			$pos++;
			$ret = array();
			while ($pos < $strlen) {
				if ($str{$pos} == 'e') {
					$pos++;
					return $ret;
				}
				else {
					$key = $this->bdecode($str, $pos);
					if ($key == null) {
						return null;
					}
					else {
						$val = $this->bdecode($str, $pos);
						if ($val == null) {
							return null;
						}
						else if (!is_array($key)) {
							$ret[$key] = $val;
						}
					}
				}
			}
			return null;
		}
		else if ($str{$pos} == 'l') {
			$pos++;
			$ret = array();
			while ($pos < $strlen) {
				if ($str{$pos} == 'e') {
					$pos++;
					return $ret;
				}
				else {
					$val = $this->bdecode($str, $pos);
					if ($val == null) {
						return null;
					}
					else {
						$ret[] = $val;
					}
				}
			}
			return null;
		}
		else {
			$numlen = strspn($str, '0123456789', $pos);
			$spos = $pos;
			$pos += $numlen;
			if (($pos >= $strlen) || ($str{$pos} != ':')) {
				return null;
			}
			else {
				$vallen = intval(substr($str, $spos, $numlen));
				$pos++;
				$val = substr($str, $pos, $vallen);
				if (strlen($val) != $vallen) {
					return null;
				}
				else {
					$pos += $vallen;
					return $val;
				}
			}
		}
	}
	function lbdecode($s, &$pos=0) {
		if($pos>=strlen($s)) {
			return null;
		}
		switch($s[$pos]){
			case 'd':
				$pos++;
				$retval=array();
				while ($s[$pos]!='e'){
					$key=self::bdecode($s, $pos);
					$val=self::bdecode($s, $pos);
					if ($key===null || $val===null)
						break;
						$retval[$key]=$val;
				}
				$retval["isDct"]=true;
				$pos++;
				return $retval;
	
			case 'l':
				$pos++;
				$retval=array();
				while ($s[$pos]!='e'){
					$val=self::bdecode($s, $pos);
					if ($val===null)
						break;
						$retval[]=$val;
				}
				$pos++;
				return $retval;
	
			case 'i':
				$pos++;
				$digits=strpos($s, 'e', $pos)-$pos;
				$val=(int)substr($s, $pos, $digits);
				$pos+=$digits+1;
				return $val;
	
				//	case "0": case "1": case "2": case "3": case "4":
				//	case "5": case "6": case "7": case "8": case "9":
			default:
				$digits=strpos($s, ':', $pos)-$pos;
				if ($digits<0 || $digits >20)
					return null;
					$len=(int)substr($s, $pos, $digits);
					$pos+=$digits+1;
					$str=substr($s, $pos, $len);
					$pos+=$len;
					//echo "pos: $pos str: [$str] len: $len digits: $digits\n";
					return (string)$str;
		}
		return null;
	}
	function bencode($var) {
		if (is_int($var)) {
			return 'i' . $var . 'e';
		}
		else if (is_array($var)) {
			if (count($var) == 0) {
				return 'de';
			}
			else {
				$assoc = false;
				foreach ($var as $key => $val) {
					if (!is_int($key)) {
						$assoc = true;
						break;
					}
				}
				if ($assoc) {
					ksort($var, SORT_REGULAR);
					$ret = 'd';
					foreach ($var as $key => $val) {
						$ret .= $this->bencode($key) . $this->bencode($val);
					}
					return $ret . 'e';
				}
				else {
					$ret = 'l';
					foreach ($var as $val) {
						$ret .= $this->bencode($val);
					}
					return $ret . 'e';
				}
			}
		}
		else {
			return strlen($var) . ':' . $var;
		}
	}
}