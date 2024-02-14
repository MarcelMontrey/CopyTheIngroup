<?php
require_once("User.php"); // Used to save user's output to their file.

// Figure out which user's data we're handling.
$condition = $_REQUEST["condition"];
$chain = $_REQUEST["chain"];
$generation = $_REQUEST["generation"];
$number = $_REQUEST["number"];

// Retrieve the data they submitted.
$pre = $_REQUEST["pre"];
$post = $_REQUEST["post"];
$chosen = $_REQUEST["chosen"];
$chosenInput = $_REQUEST["chosenInput"];
$useMachine = $_REQUEST["useMachine"];
$output = $_REQUEST["output"];
$score = $_REQUEST["score"];
$totalTime = $_REQUEST["totalTime"];
$gameTime = $_REQUEST["gameTime"];
$surveyTime = $_REQUEST["surveyTime"];
$surveyCode = $_REQUEST["surveyCode"];

// Append the submitted data to the user's file.
User::submit($condition, $chain, $generation, $number, $pre, $post, $chosen, $chosenInput, $useMachine, $output, $score, $totalTime, $gameTime, $surveyTime, $surveyCode);
?>
