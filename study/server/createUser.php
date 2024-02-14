<?php
require_once("params.php");
require_once("User.php");

// Recover all of the user's identifying information from the POST request.
$startTime = $_REQUEST["startTime"];
$assignmentId = $_REQUEST["assignmentId"];
$hitId = $_REQUEST["hitId"];
$workerId = $_REQUEST["workerId"];
$ip = $_REQUEST["ip"];

// Recover the user's demographic information.
$age = $_REQUEST["age"];
$sex = $_REQUEST["sex"];
$country = $_REQUEST["country"];

// Make sure to prune any dropouts before we assign the user a spot.
User::prune();

// Assign the user a spot in the experiment.
$condition = getCondition();
$chain = getChain($condition);
$generation = getGeneration($condition, $chain);
$number = getNumber($condition, $chain, $generation);

// Determine the user's group from their number.
$group = getGroup($number);

// Get this chain's rabbit data, then randomize it a bit to generate the rabbit-finding machine's hints.
$rabbit = Util::array2Str(getRabbit($chain));
$machine = Util::array2Str(getMachine(Util::str2Array($rabbit)));

// Get the user's input from previous users (if this isn't the first generation).
$scores = Util::array2Str(getScores($condition, $chain, $generation));
$input = Util::array2Str(getInput($condition, $chain, $generation));

// Create the user.
User::create($condition, $chain, $generation, $number, $startTime, $assignmentId, $hitId, $workerId, $ip, $age, $sex, $country, $group, $rabbit, $machine, $scores, $input);

// Return the user's assigned condition, chain, generation, number, group and input.
echo(implode("\t", array($condition, $chain, $generation, $number, $group, $rabbit, $machine, $scores, $input)));

// Return the condition with the lowest open chain.
function getCondition() {
	// Store the first open chain for each condition.
	$open = array();
	
	// Get the first open chain for each condition. Suppress creating a new one if none exists yet.
	for($condition = 0; User::isCondition($condition); $condition++) {
		$open[$condition] = getChain($condition, true);
	}
	
	// Identify the first condition with the lowest open chain and lowest open generation.
	$minChain = min($open);
	$minGen = NULL;
	$result = NULL;
	for($condition = 0; $condition < count($open); $condition++) {
		// If this condition is tied for the lowest chain, find the lowest generation.
		if($open[$condition] == $minChain) {
			// This chain's lowest open generation.
			$gen = getGeneration($condition, $open[$condition], true);
			
			// Is this the lowest generation we've seen so far?
			if(is_null($minGen) || $gen < $minGen) {
				$minGen = $gen;
				$result = $condition;
			}
		}
	}
	
	return $result;
}

// Assign the user to a chain. Select the first open one, or create a new one if none are open.
function getChain($condition, $suppress=false) {
	global $MAX_GENS;

	// Get the first open chain.
	for($chain = 0; User::isChain($condition, $chain); $chain++) {
		if(User::isChainOpen($condition, $chain) && ($MAX_GENS == 0 || getGeneration($condition, $chain, true) < $MAX_GENS)) {
			return $chain;
		}
	}

	// If none of the existing chains are open, create one (unless this action is suppressed).
	if(!$suppress && !file_exists(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain)) {
		mkdir(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain);
	}

	return $chain;
}

// Assign the user to a generation. Select the first open one, or create a new one is none are open.
function getGeneration($condition, $chain, $suppress=false) {
	// Find the first open generation (has an open slot or doesn't exist).
	for($generation = 0; !User::isGenerationOpen($condition, $chain, $generation); $generation++) {
		continue;
	}

	// If the generation doesn't exist yet, create it (unless this action is suppressed).
	if(!$suppress && !file_exists(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation)) {
		mkdir(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation);
	}

	return $generation;
}

// Assign the user to a number. Select the first open one.
function getNumber($condition, $chain, $generation) {
	// Return the first open number assignment.
	for($number = 0; User::isUser($condition, $chain, $generation, $number); $number++) {
		continue;
	}

	return $number;
}

// Assign the first three subjects in a generation to the first group, and the last three to the second group.
function getGroup($number) {
	if($number > 2) {
		return 1;
	}
	else {
		return 0;
	}
}

// Load the rabbit data. If one doesn't exist for this chain yet, create it.
function getRabbit($chain) {
	// Load all the rabbit data.
	$rabbits = loadRabbits();

	// If the chain doesn't have a rabbit yet, make one for them.
	for($i = 0; $i <= $chain; $i++) {
		if(!array_key_exists($i, $rabbits)) {
			$rabbits[$i] = createRabbit();

			$fpOut = fopen(dirname(__FILE__) . "/../users/rabbit.txt", "a");
			fwrite($fpOut, Util::array2Str($rabbits[$i]) . "\n");
			fclose($fpOut);
		}
	}

	return $rabbits[$chain];
}

// Load existing rabbit data from the rabbit.txt file.
function loadRabbits() {
	$rabbits = array();

	// Open a file pointer for reading.
	$fpIn = fopen(dirname(__FILE__) . "/../users/rabbit.txt", "r");

	// Loop over every line of the file.
	while(!feof($fpIn)) {
		$line = trim(fgets($fpIn));
		// Trim whitespace when grabbing the next line.
		if(strpos($line, ",") !== false) {
			array_push($rabbits, Util::str2Array($line));
		}
	}

	// Close the file pointer.
	fclose($fpIn);

	return $rabbits;
}

// Create a new set of rabbit data.
function createRabbit() {
	global $N_ROUNDS;

	// Start the rabbit off in a random nest. Switch its generation with 10% probability each round.
	$rabbit = array();
	$rabbit[0] = rand(0, 1);
	for($i = 1; $i < $N_ROUNDS; $i++) {
		$rabbit[$i] = (rand(0, 9) > 0) ? $rabbit[$i - 1] : 1 - $rabbit[$i - 1];
	}

	return $rabbit;
}

// Generate rabbit-finding machine data by randomly flipping some of the bits in the rabbit data.
function getMachine($rabbit) {
	global $N_ROUNDS;

	// With 2/3 probability the machine is accurate. With 1/3 probability, it's wrong.
	$machine = array();
	for($i = 0; $i < $N_ROUNDS; $i++) {
		$machine[$i] = (rand(0, 2) > 0) ? $rabbit[$i] : 1 - $rabbit[$i];
	}

	return $machine;
}

// Get the score for each round for every user in the previous generation.
function getScores($condition, $chain, $generation) {
	global $N_USERS, $N_ROUNDS;

	$scores = array();

	// Make sure there are users with data to pull from.
	if($generation > 0) {
		// Load the users in the previous generation.
		$users = array();
		for($i = 0; $i < $N_USERS; $i++) {
			$users[$i] = User::load($condition, $chain, $generation - 1, $i);
		}

		// Get each user's score on every round.
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$scores[$i] = array();
			for($j = 0; $j < $N_USERS; $j++) {
				$scores[$i][$j] = $users[$j]["score"][$i];
			}
		}
	}
	else {
		// If there are no users with data, create a 2D array filled with "-1".
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$scores[$i] = array();
			for($j = 0; $j < $N_USERS; $j++) {
				$scores[$i][$j] = -1;
			}
		}
	}

	return $scores;
}

// Get previous users' guesses for each round.
function getInput($condition, $chain, $generation) {
	global $N_USERS, $N_ROUNDS;

	$input = array();

	// Make sure there are users with data to pull from.
	if($generation > 0) {
		// Load the users in the previous generation.
		$users = array();
		for($i = 0; $i < $N_USERS; $i++) {
			$users[$i] = User::load($condition, $chain, $generation - 1, $i);
		}

		// Get each user's guess on every round.
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$input[$i] = array();
			for($j = 0; $j < $N_USERS; $j++) {
				$input[$i][$j] = $users[$j]["output"][$i];
			}
		}
	}
	else {
		// If there are no users with data, create a 2D array filled with "-1".
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$input[$i] = array();
			for($j = 0; $j < $N_USERS; $j++) {
				$input[$i][$j] = -1;
			}
		}
	}

	return $input;
}
?>
