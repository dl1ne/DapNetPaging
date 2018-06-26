<?php

class DapNetPagingCli {

	var $DapNetPaging;
	var $usercall;
	var $mycall = "DL1NE-11";
	var $prompt;

	function __construct($dapnet) {
		$this->DapNetPaging = $dapnet;
	}

	function cli_page($attr) {
		$a = explode(" ", $attr);
		if(count($a)<3) {
			$this->displayhelp_page(false);
			return;
		}
		$callsign = $a[1];
		$message = "";
		for($i=2; $i<count($a); $i++) { $message.=$a[$i]." "; }
		$message = trim($message);
                $this->DapNetPaging->page_users(array($callsign), $this->usercall.": ".trim($message));
                print($this->DapNetPaging->result."\r\r");
	}

	function displayhelp_page($short = true) {
		printf("%-16s %s", "Page", "Sends a Message to Pager\r");
		if(!$short) {
			print("\rSyntax:\r");
			print("page <callsign> <message>\r");
			print("Example:\r");
			print("page dl1ne dies ist nur eine testnachricht\r\r");
		}
	}

	function cli_rubrics($attr) {
		$a = explode(" ", $attr);
		$arr = $this->DapNetPaging->get_rubriclist();
		printf("%-2s : %-16.16s : %-14.14s : %s\r", "Nr", "Name", "Label", "TransmitterGroups");
		foreach($arr as $rubric) {
			printf("%02s : %-16.16s : %-14.14s : %s\r", $rubric["number"], $rubric["name"], $rubric["label"], implode(",", $rubric["transmitterGroupNames"]));
		}
		print("\r");
	}

	function displayhelp_rubrics($short = true) {
		printf("%-16s %s", "Rubrics", "List all configured rubrics\r");
	}

	function cli_transmitters($attr) {
		$a = explode(" ", $attr);
		$filter = "";
		if(count($a)>1) $filter = $a[1];
		$arr = $this->DapNetPaging->get_transmitterlist();
		printf("%-6.6s : %-6.6s : %-24.24s : %s\r", "Call", "Node", "Type", "Status");
		foreach($arr as $trans) {
			if($filter == "" || strpos($trans["name"], $filter) !== false || strpos($trans["nodeName"], $filter) !== false) {
				printf("%-6.6s : %-6.6s : %-24.24s : %s\r", $trans["name"], $trans["nodeName"], $trans["deviceType"], $trans["status"]);
			}
		}
		print("\r");
	}

	function displayhelp_transmitters($short = true) {
		printf("%-16s %s", "Transmitters", "List all Transmitters on DapNet\r");
	}

        function cli_userlist($attr) {
                $a = explode(" ", $attr);
                $filter = "";
                if(count($a)>1) $filter = $a[1];
                $arr = $this->DapNetPaging->get_userlist();
                $col_max = 8;
                $col_cur = 1;
                foreach($arr as $user) {
                        if($filter == "" || strpos(strtolower($user), strtolower($filter)) !== false) {
                                if($col_cur > $col_max) {
                                        print("\r");
                                        $col_cur = 1;
                                } elseif($col_cur > 1) {
                                        //print("\t");
                                }
                                printf("%-8.8s", $user);
                                $col_cur++;
                        }
                }
                print("\r");
        }

	function displayhelp_userlist($short = true) {
		printf("%-16s %s", "Userlist", "Shows a list of registered users\r");
	}

	function cli_nodelist($attr) {
		$nodes = $this->DapNetPaging->get_nodelist();
		for($i=0; $i<count($nodes); $i++) {
			printf("%-16s %s", $nodes[$i]["name"], "Status: ".$nodes[$i]["status"]."\r");
		}
		print("\r");
	}

	function displayhelp_nodelist($short = true) {
		printf("%-16s %s", "Nodelist", "Shows a list of all registeres nodes/cores\r");
	}

	function displayhelp_help($short = true) {
		printf("%-16s %s", "Help", "Display help context\r");
		if(!$short) {
			print("\rYou can also try help for a specified command\r");
			print("Example:\r");
			print("help page\r\r");
		}
	}

	function displayhelp_exit($short = true) {
		printf("%-16s %s", "Exit", "Close the connection\r");
	}

	function run($callsign) {
		$this->usercall = $callsign;
		$this->prompt = $this->usercall . " de " . $this->mycall . "> ";
		$abort = false;
		while(!$abort) {
		        //$input = readline($this->prompt);
			$input = "";
			print($this->prompt);
			while(true) {
			        $char = fgetc(STDIN);
			        if($char == "\r") break;
			        $input .= $char;
			}
			//print("\r");
			if(trim($input)!="") $abort = $this->execute($input);
			$input = "";
		}
        }

	function execute($input) {
		$abort = false;
		$options = get_class_methods('DapNetPagingCli');
		$commands = explode(" ", $input);
		$command = strtolower($commands[0]);
		$last = -1;
		if($command == "exit" || $command == "quit") {
			$abort = true;
			return $abort;
		}

		for($i=0; $i<count($options); $i++) {
			if(strpos($options[$i], "cli_") === 0 && strpos(str_replace("cli_", "", $options[$i]), $command) === 0) {
				if($last>-1) {
					print("Ambiguous command, try help for more information.\r\r");
					return $abort;
				} else {
					$last = $i;
				}
			}
		}
		if($last>-1) {
			eval("\$this->$options[$last](\"$input\");");
			return $abort;
		}

                if($command == "help") {
                        if(count($commands)>1) {
                                for($i=0; $i<count($options); $i++) {
                                        if(strpos($options[$i], "displayhelp_") === 0 && strpos(str_replace("displayhelp_", "", $options[$i]), strtolower($commands[1])) === 0) {
                                                if($last>-1) {
                                                        print("Ambiguous command, try help for more information.\r\r");
                                                        return $abort;
                                                } else {
                                                        $last = $i;
                                                }
                                        }
                                }
                                if($last>-1) {
                                        eval("\$this->$options[$last](false);");
                                        return $abort;
                                }
                        }
		}
                for($i=0; $i<count($options); $i++) {
         	       if(strpos($options[$i], "displayhelp_") === 0) {
                	       eval("\$this->$options[$i]();");
                       }
                }
                return $abort;

	}

}

?>
