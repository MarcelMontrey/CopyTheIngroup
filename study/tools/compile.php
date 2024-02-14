<?php
require_once("../server/User.php"); // Used to load users.
require_once("../server/Util.php"); // Used for string and array handling.
require_once("../server/params.php"); // Study parameters. 
require_once("Stats.php"); // Used to derive and append some statistics about users' behavior.

User::prune();
$users = User::loadAll();
$users = Stats::appendStats($users);

// Compile data (for generations > 0) in wide format.
$fp = fopen("../data/wide.tsv", "w");
$header = false;
for($condition = 0; $condition < count($users); $condition++) {
	for($chain = 0; $chain < count($users[$condition]); $chain++) {
		for($generation = 1; $generation < count($users[$condition][$chain]); $generation++) {
			for($number = 0; $number < count($users[$condition][$chain][$generation]); $number++) {
				if(User::isUserDone($condition, $chain, $generation, $number)) {
					$user = $users[$condition][$chain][$generation][$number];
					$user = array("subject" => $user["subject"]) + $user;
					unset($user["pre"], $user["post"], $user["startTime"], $user["assignmentId"], $user["hitId"], $user["workerId"], $user["rabbit"], $user["machine"], $user["scores"], $user["input"], $user["chosen"], $user["chosenInput"], $user["useMachine"], $user["output"], $user["score"], $user["surveyTime"], $user["gameTime"], $user["surveyCode"], $user["ip"], $user["country"]);
					unset($user["agreePropIG"], $user["obsPropIG"], $user["chain"], $user["totalTime"], $user["number"]);
					
					$keys = array();
					$values = array();
					foreach ($user as $key => $value) {
						if(!$header) {
							array_push($keys, $key);
						}
						array_push($values, Util::array2Str($value));
					}
					if(!$header) {
						fwrite($fp, implode("\t", $keys) . "\n");
						$header = true;
					}
					fwrite($fp, implode("\t", $values) . "\n");
				}
			}
		}
	}
}
fclose($fp);

// Compile data (for generations > 0) in long format.
$fp = fopen("../data/long.tsv", "w");
$vars = array("subject", "models", "generation", "group", "preCompDelta", "preCompBias", "preWarmDelta", "preWarmBias", "postCompDelta", "postCompBias", "postWarmDelta", "postWarmBias", "agree", "agreeTotal", "agreeBias", "agreeMax", "obs", "obsTotal", "obsBias", "hamming");
fwrite($fp, implode("\t", $vars) . "\n");
for($condition = 0; $condition < count($users); $condition++) {
	for($chain = 0; $chain < count($users[$condition]); $chain++) {
		for($generation = 1; $generation < count($users[$condition][$chain]); $generation++) {
			for($number = 0; $number < count($users[$condition][$chain][$generation]); $number++) {
				$user = $users[$condition][$chain][$generation][$number];
				
				$ig = array();
				$og = array();
				for($i = 0; $i < count($vars); $i++) {
					if($vars[$i] == "group") {
						array_push($ig, "IG");
						array_push($og, "OG");
					}
					else if(!is_null($user[$vars[$i] . "IG"])) {
						array_push($ig, $user[$vars[$i] . "IG"]);
						array_push($og, $user[$vars[$i] . "OG"]);
					}
					else {
						array_push($ig, $user[$vars[$i]]);
						array_push($og, $user[$vars[$i]]);
					}
				}
				
				fwrite($fp, implode("\t", $ig) . "\n");
				fwrite($fp, implode("\t", $og) . "\n");
			}
		}
	}
}
fclose($fp);
?>