<?php

class Util {
	
	// Parse a string into an array. If it's comma-separated (e.g. "0,1,2"), return a one-dimensional array. If it's both semicolon and comma-separated (e.g. "0,1,2;0,2,4"), return a two-dimensional array.
	public static function str2Array($str) {
		// Split the string using semicolon as a delimeter.
		$array = explode(";", $str);
		
		// For each element of the array, further split the string using comma as a delimeter.
		for($i = 0; $i < count($array); $i++) {
			$array[$i] = explode(",", $array[$i]);
		}
		
		// If we ended up with a two-dimensional array of length 1 (e.g. because there was no semi-colon), then transform it into a one-dimensional array. If our array contains a single value, return that value and not an array.
		while(is_array($array) && count($array) == 1) {
			$array = $array[0];
		}
		
		return $array;
	}

	// Convert an array to a string (e.g. two-dimensional array into "0,1,2;0,2,4");
	public static function array2Str($array) {
		// Is this a two-dimensional array?
		if(is_array($array[0])) {
			$joined = array(); // Stores the result of imploding each sub-array with commas.

			// Implode each sub-array with commas, adding the result to a new array.
			for($i = 0; $i < count($array); $i++) {
				array_push($joined, implode(",", $array[$i]));
			}

			// Implode the new array (of imploded sub-arrays) with a semicolon.
			$str = implode(";", $joined);
		}
		else if(is_array($array)) {
			// Simply implode with a comma if the array is one-dimensional.
			$str = implode(",", $array);
		}
		else {
			// If it's not an array at all, just return the input.
			$str = $array;
		}

		return $str;
	}

	// Append a line to a file.
	public static function appendToFile($file, $str) {
		$fpOut = fopen($file, "a");
		fwrite($fpOut, $str);
		fclose($fpOut);
	}
	
	// Is a directory empty?
	public static function isDirEmpty($dir) {
		// If we can't read from it, don't know either way.
		if(!is_readable($dir)) {
			return null;
		}
		// If there's only two files (pointers to parent directories "." and "..") then it's empty.
		else if(count(scandir($dir)) == 2) {
			return true;
		}
		// Otherwise it's not empty.
		else {
			return false;
		}
	}
}

?>
