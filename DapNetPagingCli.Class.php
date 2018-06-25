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
                print($this->DapNetPaging->result."\n\n");		
	}

	function displayhelp_page($short = true) {
		print("Page\t\tSends a Message to Pager\n");
		if(!$short) {
			print("\nSyntax:\n");
			print("\tpage <callsign> <message>\n");
			print("Example:\n");
			print("\tpage dl1ne dies ist nur eine testnachricht\n\n");
		}
	}

	function displayhelp_help($short = true) {
		print("Help\t\tDisplay help context\n");
		if(!$short) {
			print("\nYou can also try help for a specified command\n");
			print("Example:\n");
			print("\thelp page\n\n");
		}
	}

	function displayhelp_exit($short = true) {
		print("Exit\t\tClose the connection\n");
	}

	function run($callsign) {
		$this->usercall = $callsign;
		$this->prompt = $this->usercall . " de " . $this->mycall . "> ";
		$abort = false;
		while(!$abort) {
		        $input = readline($this->prompt);
			if(trim($input)!="") $abort = $this->execute($input);
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
					print("Ambiguous command, try help for more information.\n\n");
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
                                                        print("Ambiguous command, try help for more information.\n\n");
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