<?php

require("./DapNetPaging.Class.php");

$demo = new DapNetPaging("your-callsign", "your-password");
$demo->page_users(array("dl1ne", "do4bz"), "testruf via script");
print($demo->result);

?>
