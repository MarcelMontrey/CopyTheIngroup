<?php
require_once("params.php");
require_once("Util.php");

class User {

	// Create the directory and file structure for handling our data if it doesn't already exist.
	public static function createStructure() {
		global $N_CONDITIONS;

		// Is there a users directory?
		if(!file_exists(dirname(__FILE__) . "/../users")) {
			mkdir(dirname(__FILE__) . "/../users");
		}

		// Is there a directory for dropouts?
		if(!file_exists(dirname(__FILE__) . "/../users/dropouts")) {
			mkdir(dirname(__FILE__) . "/../users/dropouts");
		}

		// Is there a directory for each condition?
		for($condition = 0; $condition < $N_CONDITIONS; $condition++) {
			if(!file_exists(dirname(__FILE__) . "/../users/condition" . $condition)) {
				mkdir(dirname(__FILE__) . "/../users/condition" . $condition);
			}
		}

		// Is there a rabbit.txt file?
		if(!file_exists(dirname(__FILE__) . "/../users/rabbit.txt")) {
			fopen(dirname(__FILE__) . "/../users/rabbit.txt", "w");
		}
	}

	// Create a file storing a new user's information.
	public static function create($condition, $chain, $generation, $number, $startTime, $assignmentId, $hitId, $workerId, $ip, $age, $sex, $country, $group, $rabbit, $machine, $scores, $input) {
		// Output we're going to write.
		$str = "";

		// User's slot in the experiment.
		$str .= "[slot]";
		$str .= "\n" . "condition=" . $condition;
		$str .= "\n" . "chain=" . $chain;
		$str .= "\n" . "generation=" . $generation;
		$str .= "\n" . "number=" . $number;

		// User's identifying information.
		$str .= "\n\n" . "[identity]";
		$str .= "\n" . "startTime=" . $startTime;
		$str .= "\n" . "assignmentId=" . $assignmentId;
		$str .= "\n" . "hitId=" . $hitId;
		$str .= "\n" . "workerId=" . $workerId;
		$str .= "\n" . "ip=" . $ip;

		// User's demographic information.
		$str .= "\n\n" . "[demographic]";
		$str .= "\n" . "age=" . $age;
		$str .= "\n" . "sex=" . $sex;
		$str .= "\n" . "country=" . $country;

		// Input the user received from previous participants, if any.
		$str .= "\n\n" . "[input]";
		$str .= "\n" . "group=" . $group;
		$str .= "\n" . "rabbit=" . $rabbit;
		$str .= "\n" . "machine=" . $machine;
		$str .= "\n" . "scores=" . $scores;
		$str .= "\n" . "input=" . $input;

		// Open a file pointer for writing.
		$fpOut = fopen(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt", "w");

		// Write the output to the file.
		fwrite($fpOut, $str);

		// Close the file pointer.
		fclose($fpOut);
	}

	// Append the submitted data to the user's file.
	public static function submit($condition, $chain, $generation, $number, $pre, $post, $chosen, $chosenInput, $useMachine, $output, $score, $totalTime, $gameTime, $surveyTime, $surveyCode) {
		// Output we're going to write.
		$str = "";

		// User's output. Already parsed into a string, since we're getting it from the client.
		$str .= "\n\n" . "[output]";
		$str .= "\n" . "pre=" . $pre;
		$str .= "\n" . "post=" . $post;
		$str .= "\n" . "chosen=" . $chosen;
		$str .= "\n" . "chosenInput=" . $chosenInput;
		$str .= "\n" . "useMachine=" . $useMachine;
		$str .= "\n" . "output=" . $output;
		$str .= "\n" . "score=" . $score;

		// Details about the user's performance, the survey code they received, time they spent, etc.
		$str .= "\n\n" . "[details]";
		$str .= "\n" . "totalTime=" . $totalTime;
		$str .= "\n" . "gameTime=" . $gameTime;
		$str .= "\n" . "surveyTime=" . $surveyTime;
		$str .= "\n" . "surveyCode=" . $surveyCode;

		// Append everything to the correct file.
		Util::appendToFile(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt", $str);
	}

	// Load all user records.
	public static function loadAll() {
		global $N_USERS; // In case we have gaps where earlier numbers end up empty.

		$users = array();

		for($condition = 0; User::isCondition($condition); $condition++) {
			// Create an array for each condition.
			array_push($users, array());

			for($chain = 0; User::isChain($condition, $chain); $chain++) {
				// Create an array for each chain, in each condition.
				array_push($users[$condition], array());

				for($generation = 0; User::isGeneration($condition, $chain, $generation); $generation++) {
					// Create an array for each generation, in each chain in each condition.
					array_push($users[$condition][$chain], array());

					for($number = 0; $number < $N_USERS; $number++) {
						// Load the user's record if they exist.
						if(User::isUser($condition, $chain, $generation, $number)) {
							$users[$condition][$chain][$generation][$number] = User::load($condition, $chain, $generation, $number);
						}
					}
				}
			}
		}

		return $users;
	}

	// Load a particular user's record.
	public static function load($condition, $chain, $generation, $number) {
		return User::loadFromFile(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt");
	}

	// Load all dropouts' user records.
	public static function loadDropouts() {
		$users = array();

		// Create a directory iterator over the dropouts folder.
		$dir = new DirectoryIterator(dirname(__FILE__) . "/../users/dropouts");

		// Load each user's record that's saved in the dropouts folder.
		foreach ($dir as $fileinfo) {
			// Make sure it's a file, not a directory.
			if($fileinfo->isFile()) {
				array_push($users, User::loadFromFile(dirname(__FILE__) . "/../users/dropouts/" . $fileinfo->getFilename()));
			}
		}

		return $users;
	}

	// Return an array containing a particular user's information, loading the file from a path.
	public static function loadFromFile($file) {
		// Store the user's record in an array.
		$user = array();

		// Make sure the file exists.
		if(file_exists($file)) {
			// Open a file pointer for reading.
			$fpIn = fopen($file, "r");

			// Loop over every line of the file.
			while(!feof($fpIn)) {
				// Trim whitespace when grabbing the next line.
				$line = trim(fgets($fpIn));

				// Only bother with lines that store a value.
				if(substr_count($line, "=") > 0) {
					$pos = strpos($line, "="); // Generation of the delimiter between the variable name and value.
					$var = substr($line, 0, $pos); // Variable name.
					$value = substr($line, $pos + 1); // Variable value.

					// Load the variable, parsing it into a one or two-dimensional array, depending on whether the semicolon and comma delimiters are used.
					$user[$var] = Util::str2Array($value);
				}
			}

			// Close the file pointer.
			fclose($fpIn);
		}

		return $user;
	}

	// Prune any dropouts from among the users.
	public static function prune() {
		global $TIME_LIMIT, $DONE_KEY, $N_USERS;

		// Load all our users.
		$users = User::loadAll();

		for($condition = 0; User::isCondition($condition); $condition++) {
			for($chain = 0; User::isChain($condition, $chain); $chain++) {
				for($generation = 0; User::isGeneration($condition, $chain, $generation); $generation++) {
					for($number = 0; $number < $N_USERS; $number++) {
						// Make sure the user exists.
						if(User::isUser($condition, $chain, $generation, $number)) {
							$user = $users[$condition][$chain][$generation][$number];

							// If the user has gone past their time limit and hasn't returned any data yet, remove them.
							if(time() - $TIME_LIMIT > $user["startTime"] && !array_key_exists($DONE_KEY, $user)) {
								// Move the user record to the dropouts folder, adding start time, condition and generation to the filename.
								rename(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt", dirname(__FILE__) . "/../users/dropouts/" . $user["startTime"] . "-" . $condition . "-" . $chain . "-" . $generation . "-" . $number . ".txt");
							}
						}
					}

					// If the generation directory is empty, remove it.
					if(Util::isDirEmpty(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation)) {
						rmdir(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation);
					}
				}

				// If the chain directory is empty, remove it.
				if(Util::isDirEmpty(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain)) {
					rmdir(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain);
				}
			}
		}
	}

	// Does the folder for a condition exist?
	public static function isCondition($condition) {
		return file_exists(dirname(__FILE__) . "/../users/condition" . $condition);
	}

	// Does the folder for a chain (within a condition) exist?
	public static function isChain($condition, $chain) {
		return file_exists(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain);
	}

	// Does the folder for a generation (within a chain, within a condition) exist?
	public static function isGeneration($condition, $chain, $generation) {
		return file_exists(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation);
	}

	// Does the record for a user (within a generation, within a chain, within a condition) exist?
	public static function isUser($condition, $chain, $generation, $number) {
		return file_exists(dirname(__FILE__) . "/../users/condition" . $condition . "/chain" . $chain . "/generation" . $generation . "/" . $number . ".txt");
	}

	// Is this user done the experiment?
	public static function isUserDone($condition, $chain, $generation, $number) {
		global $DONE_KEY;

		// The user can't be done if they don't exist.
		if(!User::isUser($condition, $chain, $generation, $number)) {
			return false;
		}
		// If the user exists, return whether or not they submitted output.
		else {
			$user = User::load($condition, $chain, $generation, $number);
			return array_key_exists($DONE_KEY, $user);
		}
	}

	// Is every user in this generation done?
	public static function isGenerationDone($condition, $chain, $generation) {
		global $N_USERS;

		// If the generation doesn't exist, it can't be done.
		if(!User::isGeneration($condition, $chain, $generation)) {
			return false;
		}

		// If one of the users isn't done, the generation isn't done.
		for($number = 0; $number < $N_USERS; $number++) {
			if(!User::isUserDone($condition, $chain, $generation, $number)) {
				return false;
			}
		}

		// If the generation exists and all the users are done, the generation is done.
		return true;
	}

	// Is this generation open for new users?
	public static function isGenerationOpen($condition, $chain, $generation) {
		global $N_USERS;

		// If the generation doesn't exist, it's open by definition.
		if(!User::isGeneration($condition, $chain, $generation)) {
			return true;
		}

		// Try to find an empty slot. If one exists, then the generation is open.
		for($number = 0; $number < $N_USERS; $number++) {
			if(!User::isUser($condition, $chain, $generation, $number)) {
				return true;
			}
		}

		// Otherwise, the generation is closed.
		return false;
	}

	// Is this chain open for new users?
	public static function isChainOpen($condition, $chain) {
		global $N_USERS;

		// If the chain doesn't exist yet, it's open by definition.
		if(!User::isChain($condition, $chain)) {
			return true;
		}

		// Find the current generation (i.e. the next generation doesn't exist yet).
		for($generation = 0; User::isGeneration($condition, $chain, $generation + 1); $generation++) {
			continue;
		}

		// If the current generation is done, then the chain is open.
		if(User::isGenerationDone($condition, $chain, $generation)) {
			return true;
		}

		// If the current generation isn't done, then whether the chain is open depends on whether the generation is open.
		return User::isGenerationOpen($condition, $chain, $generation);
	}
}
?>
