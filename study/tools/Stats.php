<?php
require_once("../server/params.php");
require_once("../server/Util.php");

class Stats {
	
	// Derive and append some statistics about the user's behavior.
	public static function appendStats($users) {
		global $N_USERS, $N_SOURCES, $N_ROUNDS;
		
		$subject = 0;
		for($condition = 0; $condition < count($users); $condition++) {
			for($chain = 0; $chain < count($users[$condition]); $chain++) {
				for($generation = 0; $generation < count($users[$condition][$chain]); $generation++) {
					for($number = 0; $number < count($users[$condition][$chain][$generation]); $number++) {
						// Assign $user as a reference to the correct record in the $users array.
						$user = &$users[$condition][$chain][$generation][$number];
						
						if(is_null($user["output"])) {
							continue;
						}
						
						$user["models"] = ($user["condition"] + 1);
						$user["totalSurveyTime"] = array_sum($user["surveyTime"]);
						$user["totalGameTime"] = array_sum($user["gameTime"]);
						$user["finalScore"] = $user["score"][$N_ROUNDS - 1];
						$user["il"] = array_sum($user["machine"]);
						$user["preCompIG"] = ($user["pre"][0] + $user["pre"][1]) / 2;
						$user["preCompOG"] = ($user["pre"][4] + $user["pre"][5]) / 2;
						$user["preCompDelta"] = $user["preCompIG"] - $user["preCompOG"];
						$user["preCompBias"] = $user["preCompDelta"] != 0 ? ($user["preCompDelta"] > 0 ? "IG" : "OG") : "Unbiased";
						$user["preWarmIG"] = ($user["pre"][2] + $user["pre"][3]) / 2;
						$user["preWarmOG"] = ($user["pre"][6] + $user["pre"][7]) / 2;
						$user["preWarmDelta"] = $user["preWarmIG"] - $user["preWarmOG"];
						$user["preWarmBias"] = $user["preWarmDelta"] != 0 ? ($user["preWarmDelta"] > 0 ? "IG" : "OG") : "Unbiased";
						$user["postCompIG"] = ($user["post"][0] + $user["post"][1]) / 2;
						$user["postCompOG"] = ($user["post"][4] + $user["post"][5]) / 2;
						$user["postCompDelta"] = $user["postCompIG"] - $user["postCompOG"];
						$user["postCompBias"] = $user["postCompDelta"] != 0 ? ($user["postCompDelta"] > 0 ? "IG" : "OG") : "Unbiased";
						$user["postWarmIG"] = ($user["post"][2] + $user["post"][3]) / 2;
						$user["postWarmOG"] = ($user["post"][6] + $user["post"][7]) / 2;
						$user["postWarmDelta"] = $user["postWarmIG"] - $user["postWarmOG"];
						$user["postWarmBias"] = $user["postWarmDelta"] != 0 ? ($user["postWarmDelta"] > 0 ? "IG" : "OG") : "Unbiased";
						$user["compDelta"] = $user["postCompDelta"] - $user["preCompDelta"];
						$user["warmDelta"] = $user["postWarmDelta"] - $user["preWarmDelta"];
						
						if($generation == 0 || !User::isUserDone($condition, $chain, $generation, $number)) {
							$user["subject"] = "";
							$user["agreeIG"] = "";
							$user["agreeOG"] = "";
							$user["agreeTotal"] = "";
							$user["agreePropIG"] = "";
							$user["agreeBias"] = "";
							$user["agreeMaxIG"] = "";
							$user["agreeMaxOG"] = "";
							$user["obsIG"] = "";
							$user["obsOG"] = "";
							$user["obsTotal"] = "";
							$user["obsPropIG"] = "";
							$user["obsBias"] = "";
							$user["hammingIG"] = "";
							$user["hammingOG"] = "";
							$user["hammingDelta"] = "";
						}
						else if(!is_null($user)) {
							$agree = Stats::getAgree($user);
							$agreeMax = Stats::getAgreeMax($user, $users);
							$agreeMaxObs = Stats::getAgreeMaxObs($user);
							$obs = Stats::getObs($user);
							$hamming = Stats::getHamming($user, $users);
							
							$user["subject"] = $subject++;
							$user["agreeIG"] = $agree[0];
							$user["agreeOG"] = $agree[1];
							$user["agreeTotal"] = $agree[0] + $agree[1];
							$user["agreePropIG"] = $user["agreeTotal"] > 0 ? $agree[0] / ($agree[0] + $agree[1]) : "0.5";
							$user["agreeBias"] = $user["agreePropIG"] != 0.5 ? ($user["agreePropIG"] > 0.5 ? "IG" : "OG") : "Unbiased";
							$user["agreeMaxIG"] = $agreeMaxObs[0];
							$user["agreeMaxOG"] = $agreeMaxObs[1];
							$user["obsIG"] = $obs[0];
							$user["obsOG"] = $obs[1];
							$user["obsTotal"] = $obs[0] + $obs[1];
							$user["obsPropIG"] = $obs[0] / ($obs[0] + $obs[1]);
							$user["obsBias"] = $user["obsPropIG"] != 0.5 ? ($user["obsPropIG"] > 0.5 ? "IG" : "OG") : "Unbiased";
							$user["hammingIG"] = $hamming[0];
							$user["hammingOG"] = $hamming[1];
							$user["hammingDelta"] = $hamming[0] - $hamming[1];
						}
					}
				}
			}
		}
		
		return $users;
	}
	
	// Get the number of agreements between the user and the ingroup/outgroup.
	public static function getAgree($user, $round=null, $partner=null) {
		global $N_ROUNDS;
		
		$totalIG = 0;
		$totalOG = 0;
		
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$i = !is_null($round) ? $round : $i;
			
			$roundIG = 0;
			$roundOG = 0;
			
			for($j = 0; $j < $user["models"]; $j++) {
				$j = !is_null($partner) ? $partner : $j;
				
				$partnerIG = 0;
				$partnerOG = 0;
				
				$chosenGroup = ($user["chosen"][$i][$j] < 3) ? 0 : 1;
				if($user["chosenInput"][$i][$j] == $user["output"][$i]){
					if($user["group"] == $chosenGroup) {
						$totalIG++;
						$roundIG++;
						$partnerIG++;
					}
					else {
						$totalOG++;
						$roundOG++;
						$partnerOG++;
					}
				}
				
				if(!is_null($partner) && $i == $round && $j == $partner) {
					return array($partnerIG, $partnerOG);
				}
			}
			
			if(!is_null($round) && is_null($partner) && $i == $round) {
				return array($roundIG, $roundOG);
			}
		}
		
		return array($totalIG, $totalOG);
	}
	
	// Get the number of possible agreements between the user and the ingroup/outgroup, given their sometimes contradictory choices.
	public static function getAgreeMax($user, $users, $round=null) {
		global $N_ROUNDS, $N_USERS;
		
		$maxIG = 0;
		$maxOG = 0;
		
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$i = !is_null($round) ? $round : $i;
			
			$ig = array(0, 0);
			$og = array(0, 0);
			
			for($number = 0; $number < $N_USERS; $number++) {
				$partner = $users[$user["condition"]][$user["chain"]][$user["generation"] - 1][$number];
				$output = $partner["output"][$i];
				
				if($user["group"] == $partner["group"]) {
					$ig[$output]++;
				}
				else {
					$og[$output]++;
				}
			}
			
			$roundIG = min(max($ig), $user["models"]);
			$roundOG = min(max($og), $user["models"]);
			
			if(!is_null($round) && $i == $round) {
				return array($roundIG, $roundOG);
			}
			
			$maxIG += $roundIG;
			$maxOG += $roundOG;
		}
		
		return array($maxIG, $maxOG);
	}
	
	// Get the total number of possible agreements between the user and the ingroup/outgroup, given their sometimes contradictory choices and who was actually observed.
	public static function getAgreeMaxObs($user, $round=null) {
		global $N_ROUNDS, $N_USERS;
		
		$maxIG = 0;
		$maxOG = 0;
		
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$i = !is_null($round) ? $round : $i;
			
			$ig = array(0, 0);
			$og = array(0, 0);
			
			for($j = 0; $j < $user["models"]; $j++) {
				$chosenGroup = ($user["chosen"][$i][$j] < 3) ? 0 : 1;
				$chosenInput = $user["chosenInput"][$i][$j];
				
				if($user["group"] == $chosenGroup) {
					$ig[$chosenInput]++;
				}
				else {
					$og[$chosenInput]++;
				}
			}
			
			$roundIG = max($ig);
			$roundOG = max($og);
			
			if(!is_null($round) && $i == $round) {
				return array($roundIG, $roundOG);
			}
			
			$maxIG += $roundIG;
			$maxOG += $roundOG;
		}
		
		return array($maxIG, $maxOG);
	}
	
	// Get the number of times the user observed ingroup/outgroup.
	public static function getObs($user, $round=null, $partner=null) {
		global $N_ROUNDS;
		
		$totalIG = 0;
		$totalOG = 0;
		
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$i = !is_null($round) ? $round : $i;
			
			$roundIG = 0;
			$roundOG = 0;
			
			for($j = 0; $j < $user["models"]; $j++) {
				$j = !is_null($partner) ? $partner : $j;
				
				$partnerIG = 0;
				$partnerOG = 0;
				
				$chosenGroup = ($user["chosen"][$i][$j] < 3) ? 0 : 1;
				if($chosenGroup == $user["group"]){
					$totalIG++;
					$roundIG++;
					$partnerIG++;
				}
				else {
					$totalOG++;
					$roundOG++;
					$partnerOG++;
				}
				
				if(!is_null($partner) && $i == $round && $j == $partner) {
					return array($partnerIG, $partnerOG);
				}
			}
			
			if(!is_null($round) && is_null($partner) && $i == $round) {
				return array($roundIG, $roundOG);
			}
		}
		
		return array($totalIG, $totalOG);
	}
	
	// Get the total Hamming distance from ingroup/outgroup.
	public static function getHamming($user, $users, $round=null) {
		global $N_ROUNDS, $N_USERS;
		
		$totalIG = 0;
		$totalOG = 0;
		
		for($i = 0; $i < $N_ROUNDS; $i++) {
			$i = !is_null($round) ? $round : $i;
			
			$roundIG = 0;
			$roundOG = 0;
			
			for($j = 0; $j < $N_USERS; $j++) {
				$partner = $users[$user["condition"]][$user["chain"]][$user["generation"] - 1][$j];
				
				if($user["output"][$i] != $partner["output"][$i]) {
					if($user["group"] == $partner["group"]) {
						$totalIG++;
						$roundIG++;
					}
					else {
						$totalOG++;
						$roundOG++;
					}
				}
			}
			
			if(!is_null($round) && $i == $round) {
				return array($roundIG, $roundOG);
			}
		}
		
		return array($totalIG, $totalOG);
	}
	
}
?>