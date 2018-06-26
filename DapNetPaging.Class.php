<?php
/**
**************************************************************************
*                    DapNetPaging Class for PHP
**************************************************************************
*
* Filename: DapNetPaging.Class.php
*
* Author:   Simon Brecht, simon@brecht.email
*
**************************************************************************
*
* Manages Paging and Connections between custom Scripts and DapNet Core
* API. For a simple Example, please look below.
*
**************************************************************************
*
* Versions:
*           0.1 , 17.04.2018: Script created
*	    0.2 , 17.04.2018: Debug added
*           0.3 , 20.04.2018: Added Caching for DapNet Core Servers and
*                             auto failover, if one Core is not reachable
*           0.4 , 25.06.2018: Fix Unknown Property in isUserExisting function
*           0.5 , 25.06.2018: Retrieve User List from Core
*           0.6 , 25.06.2018: Fixes on $this->result from Cache
*           0.7 , 25.06.2018: Retrieve Node List from Core
*           0.8 , 26.06.2018: Retrieve Rubrics and Transmitters from Core
*
*
**************************************************************************
**/

class DapNetPaging {

	/*
		Variables for Class
	*/
	var $dapnet_api = "http://dapnet.di0han.as64636.de.ampr.org/api/";
	var $dapnet_user;
	var $dapnet_pass;
	var $dapnet_default = ":8080/";

	var $dapnet_ini = "./DapNetPaging.ini";

	var $callsign;
	var $transmitter = array("dl-all");
	var $result;
	var $http_code;
	var $cache_once = false;

	var $debug = false;


	/*
		Construct Class with Default Options, example:
		$mydapnet = new DapNetPaging("callsign", "password", "http://dapnet.core/api/");
	*/
	function __construct($dapnet_user = "", $dapnet_pass = "", $dapnet_api = "") {
		$this->dapnet_user = $dapnet_user;
		$this->dapnet_pass = $dapnet_pass;
		if($dapnet_api != "") $this->dapnet_api  = $dapnet_api;
	}

	/*
		Small and simple Debug Function to Print executions
	*/
	private function debugme($str) {
		if($this->debug) {
			print("DEBUG:   " . $str. "\n");
		}
	}

	/*
		Save cUrl Method; executes cUrl, checks response and if request
		failed, move to next available DapNet Core Server
	*/
	private function curlme($json_path, $_postdata = "") {
		$this->makecurl($json_path, $_postdata);
		if(substr($this->http_code,0,1) != "2") {
			$this->debugme("Doesn't get http code 200, get: ".$this->http_code);
			$this->debugme("Looping through all available DapNet Core Servers to finish request...");
			$nodes = $this->file_ini_read("nodes");
			foreach($nodes as $key => $val) {
				$this->dapnet_api = "http://" . $val . $this->dapnet_default;
				$this->debugme("Trying Core [".$key."] with URL: ".$this->dapnet_api);
				$this->makecurl($json_path, $_postdata);
				if(substr($this->http_code,0,1) == "2") break;
			}
		} else {
			$this->build_cache();
		}
	}

	/*
		Customized cUrl Function for different requests.
		If $_postdata is empty, function will call a GET request; otherwise
		a POST Request with JSON String in $_postdata will be executed.
		Result of cUrl will be saved in $this->result for later operations...
	*/
	private function makecurl($json_path, $_postdata = "") {
		$this->debugme("Creating new cUrl Instance");
		$ch      = curl_init( $this->dapnet_api . $json_path );
		$this->debugme("Options:");
		$this->debugme(" url    = " . $this->dapnet_api . $json_path);
		$this->debugme(" user   = " . $this->dapnet_user);
		$this->debugme(" pass   = " . $this->dapnet_pass);
		$this->debugme(" header = " . "Accept: application/json");
		$options = array(
			CURLOPT_SSL_VERIFYPEER    => false,
			CURLOPT_RETURNTRANSFER    => true,
			CURLOPT_USERPWD           => "{$this->dapnet_user}:{$this->dapnet_pass}",
			CURLOPT_TIMEOUT	          => 60,
			CURLOPT_CONNECTTIMEOUT    => 5,
		);
		$this->debugme("Setting cUrl Options to Instance");
		curl_setopt_array( $ch, $options );
		if($_postdata != "") {
			$this->debugme("POST Data is SET, Adding JSON Post Fields");
			$_postdata_json = json_encode($_postdata);
			$this->debugme(" JSON = " . $_postdata_json);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $_postdata_json);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		} else {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
		}
		$this->debugme("Executing curl with options...");
		$result = curl_exec( $ch );
		$this->debugme("cUrl Result: \n ".$result);
		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->result = $result;
	}

	/*
		Before Paging, check if all listed Callsigns are available.
		$callsign is only ONE callsign for checking. Example:
		if($mydapnet->isUserExisting("dl1ne")) {
			[...]
		}
	*/
	public function isUserExisting($callsign) {
		$retval = false;
		$this->debugme("Checking if callsign '".$callsign."' existing...");
		$this->curlme("users/" . $callsign);
		$body   = json_decode($this->result);
		if(!property_exists('body', 'name')) {
			$retval = true;
			$this->debugme("...unknown Response!");
		} elseif($body->name != "Not Found") {
			$retval = true;
			$this->debugme("...existing");
		} else {
			$this->debugme("...NOT existing!");
		}
		return $retval;
	}

	/*
		Option to set transmitter groups; $groupname must be an array.
		ToDo: Implement check if given group is an array and exists...
	*/
	public function transmitter($groupname) {
		$this->transmitter = $groupname;
	}

	/*
		Lets do an PAGE Request for array of users. Per Default, emergency is disabled but
		can be overwritten. An example for paging:
		$mydapnet->page_users(array("dl1ne", "do4bz", "dg4oae"), $this->dapnet_user . ": Just a simple test from my script");
		Result of paging is $this->result / fetched via cUrl
		ToDo: Implement check if Variables, if $callsigns is really an array...
	*/
	public function page_users($callsigns, $text, $emergency = false) {
		foreach($callsigns as $callsign) {
			if(!$this->isUserExisting($callsign)) {
				die("Error: Aborting, during ".$callsign." does not exist!");
			}
		}
		$request = array("text" => $text, "emergency" => $emergency, "callSignNames" => $callsigns, "transmitterGroupNames" => $this->transmitter);
		$this->curlme("calls", $request);
	}

	/*
		Returns an array of all registered users to the system
	*/
	public function get_userlist() {
		$this->curlme("users/");
		$result = json_decode($this->result, true);
		$users = "";
		foreach($result as $user) {
			$users .= " ".$user["name"];
		}
		$users = trim($users);
		return explode(" ", $users);
	}

	/*
		Returns an array of all nodes with status, like:
		$array[0]["name"] = di0han
		$array[0]["status"] = online
	*/
	public function get_nodelist() {
                $this->curlme("nodes");
                $result = json_decode($this->result, true);
                $i = 0;
                foreach($result as $node) {
                        $nodes[$i]["name"] = $node["name"];
			$nodes[$i]["status"] = $node["status"];
			$i++;
                }
                return $nodes;
	}

	/*
		Returns an array of all rubrics listed on core, like:
		$array[0]["number"] = 1
		$array[0]["name"] = dx-kw
		$array[0]["label"] = DX KW
		$array[0]["transmitterGroups"] = array(dxclusters)
	*/
	public function get_rubriclist() {
		$this->curlme("rubrics");
		$result = json_decode($this->result, true);
		return $result;
	}

	/*
		Returns an array of all transmitters, like:
		$array[0]["name"] = db0luh
		$array[0]["nodeName"] = di0han
		$array[0]["deviceType"] = UniPager-Audio
		$array[0]["status"] = ONLINE
	*/
	public function get_transmitterlist() {
		$this->curlme("transmitters");
		$result = json_decode($this->result, true);
		return $result;
	}

	/*
		Tries to fetch all available DapNet Core Servers and build a local cache
		for some unreachable reasons. The Cache File is build in ini structure, so 
		it is easy the read those information again.
	*/
	private function build_cache() {
		if(!$this->cache_once) {
			$old_result = $this->result;
			$this->debugme("Trying to build a local Cache for DapNet Core Servers...");
			$this->makecurl("nodes");
			$nodes_fetch = json_decode($this->result, true);
			$nodes_cache = array();
			foreach($nodes_fetch as $node) {
				$nodes_cache[$node["name"]] = $node["address"]["ip_addr"];
			}
			$this->debugme("Cache build, write down ini file..");
			$this->file_ini_write("nodes", $nodes_cache);
			$this->debugme("Disable further caching in this script!");
			$this->cache_once = true;
			$this->result = $old_result;
		}
	}

	/*
		Writes Cache/INI File for Class.
		To execute, please follow example below:
		$this->file_ini_write("demo-key", array("MyKey" => "MyValue"));
	*/
	private function file_ini_write($key, $vals) {
		if(file_exists($this->dapnet_ini)) {
			$old_ini = parse_ini_file($this->dapnet_ini, TRUE);
		} else {
			$old_ini = array();
		}
		$old_ini[$key] = $vals;
		$ini_content = "";
		foreach($old_ini as $inikey => $inival) {
			$ini_content .= "[".$inikey."] \n";
			foreach($inival as $inisubkey => $inisubval) {
				if(!empty($inisubval) && $inisubval != "") {
					if(is_numeric($inisubval)) {
						$ini_content .= $inisubkey." = ".$inisubval." \n";
					} else {
						$ini_content .= $inisubkey." = \"".$inisubval."\" \n";
					}
				}
			}
			$ini_content .= "\n";
		}
		$new_ini = fopen($this->dapnet_ini, 'w');
		fwrite($new_ini, $ini_content);
		fclose($new_ini);
	}

	/*
		Returns an array of keys and values from DapNetPaging.ini,
		an Example:
		$nodes = $this->file_ini_read("nodes");
		
		Returns in:
		$nodes["abc"] = "1.2.3.4"
		[...]
	*/
	private function file_ini_read($key) {
                if(file_exists($this->dapnet_ini)) {
                        $content = parse_ini_file($this->dapnet_ini, TRUE);
                } else {
                        return false;
                }
		return $content[$key];
	}
}

?>

