<?php
require_once("User.php");
require_once("Util.php");
require_once("params.php");

// Create the user data directory and file structure if it doesn't already exist.
User::createStructure();

// To simplify running the experiment offline, the code that handled IP verification and MTurk IDs has been removed. These variables now take placeholder values.
$startTime = time();
$assignmentId = "AID";
$hitId = "HID";
$workerId = "WID";
$ip = "127.0.0.1";
$valid = 1;
?>
