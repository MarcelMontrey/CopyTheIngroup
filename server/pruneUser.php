<?php
require_once("User.php");

// Figure out which user's data we're handling.
$condition = $_REQUEST["condition"];
$chain = $_REQUEST["chain"];
$generation = $_REQUEST["generation"];
$number = $_REQUEST["number"];

// Collect the workerId as well, just so we can be absolutely sure we're pruning the correct user's record.
$workerId = $_REQUEST["workerId"];

// Make sure the pruned user exists.
if(!User::isUser($condition, $chain, $generation, $number)) {
	return;
}

// Load the pruned user and check their workerId against the one we were sent.
$user = User::load($condition, $chain, $generation, $number);

if($user["workerId"] == $workerId) {
	// Prune the user.
	rename(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt", dirname(__FILE__) . "/../users/dropouts/" . $user["startTime"] . "-" . $condition . "-" . $chain . "-" . $generation . "-" . $number . ".txt");
}

// Prune anyone else who has timed out while we're at it, and clean up any now-empty directories.
User::prune();
?>
