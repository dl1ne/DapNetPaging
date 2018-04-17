<?php
/**
##########################################################################
#                    DapNetPaging Class for PHP
##########################################################################
#
# Filename: DapNet.Class.php
#
# Author:   Simon Brecht, simon@brecht.email
#
##########################################################################
#
# Manages Paging and Connections between custom Scripts and DapNet Core
# API. For a simple Example, please look below.
#
##########################################################################
#
# Versions:
#           0.1 , 17.04.2018: Script created
#	    0.2 , 17.04.2018: Debug added
#
#
##########################################################################
**/

class DapNetPaging {

	/*
		Variables for Class
	*/
	var $dapnet_api = "http://dapnet.di0han.as64636.de.ampr.org/api/";
	var $dapnet_user;
	var $dapnet_pass;

	var $callsign;
	var $transmitter = array("dl-all");
	var $result;

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
		Customized cUrl Function for different requests.
		If $_postdata is empty, function will call a GET request; otherwise
		a POST Request with JSON String in $_postdata will be executed.
		Result of cUrl will be saved in $this->result for later operations...
	*/
	private function curlme($json_path, $_postdata = "") {
		$this->debugme("Creating new cUrl Instance");
		$ch      = curl_init( $this->dapnet_api . $json_path );
		$this->debugme("Options:");
		$this->debugme(" url    = " . $this->dapnet_api . $json_path);
		$this->debugme(" user   = " . $this->dapnet_user);
		$this->debugme(" pass   = " . $this->dapnet_pass);
		$this->debugme(" header = " . "Accept: application/json");
		$options = array(
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERPWD        => "{$this->dapnet_user}:{$this->dapnet_pass}",
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
		if($body->name != "Not Found") {
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
	function page_users($callsigns, $text, $emergency = false) {
		foreach($callsigns as $callsign) {
			if(!$this->isUserExisting($callsign)) {
				die("Error: Aborting, during ".$callsign." does not exist!");
			}
		}
		$request = array("text" => $text, "emergency" => $emergency, "callSignNames" => $callsigns, "transmitterGroupNames" => $this->transmitter);
		$this->curlme("calls", $request);
	}

}

?>
