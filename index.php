<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="group.css">
</head>
<body>

<?php include("server/launch.php"); ?>

<div class="page" id="error"></div>
<?php include("pages/1-consent.php"); ?>
<?php include("pages/2-demo.php"); ?>
<?php include("pages/3-assign.php"); ?>
<?php include("pages/4-pre.php"); ?>
<?php include("pages/5-instruct.php"); ?>
<?php include("pages/6-game.php"); ?>
<?php include("pages/7-outcome.php"); ?>
<?php include("pages/8-post.php"); ?>
<?php include("pages/9-code.php"); ?>

<script type="text/javascript">
const TIME_LIMIT = 20 * 60 * 1000; // In milliseconds.
const WAIT_BUTTON = 750; // Delay (in ms) before the continue buttons appear on the assign, game and outcome pages.
const WAIT_ASSIGN = 2500; // Delay (in ms) before being assigned to a group, to help ensure that the participant reads the text.
const COOKIE = "GSL"; // The name of the variable placed into local storage, so the participant can't restart the experiment.
const ROUNDS = 20; // Number of rounds.
const USERS = 6; // Number of users per generation.
const BENEFIT = 5; // Points gained by guessing the correct nest.
const COST = 2; // Points lost by using the rabbit-finding machine.

// Information collected as soon as the experiment is loaded.
var startTime = "<?php echo($startTime); ?>"; // Start time via POST (server-side).
var assignmentId = "<?php echo($assignmentId); ?>"; // assignmentId via POST.
var hitId = "<?php echo($hitId); ?>"; // hitId via POST.
var workerId = "<?php echo($workerId); ?>"; // workerId via POST.
var ip = "<?php echo($ip); ?>"; // IP address.
var valid = <?php echo($valid); ?>; // -1 if the above weren't passed by POST. 0 if the workerId belongs to an existing participant or a dropout. 1 otherwise.

var pages = ["1-consent", "2-demo", "3-assign", "4-pre", "5-instruct", "6-game", "7-outcome", "8-post", "9-code"]; // All the pages.
var checkMarks = ["check-demo", "check-pre"]; // Check marks showing whether the user can proceed.
var selDemo = ["demo-age", "demo-sex", "demo-country"]; // Select boxes for demographic questions.
var radPre = ["pre-ig-competent", "pre-ig-capable", "pre-ig-warm", "pre-ig-friendly", "pre-og-competent", "pre-og-capable", "pre-og-warm", "pre-og-friendly"]; // Radio buttons for the pre-game Likert scale.
var radPost = ["post-ig-competent", "post-ig-capable", "post-ig-warm", "post-ig-friendly", "post-og-competent", "post-og-capable", "post-og-warm", "post-og-friendly"]; // Radio buttons for the post-game Likert scale.
var groups = ["red", "blue"]; // Group colors.
var adjectives = ["competent", "capable", "warm", "friendly"]; // Adjectives used in the stereotype content model.
var likert = ["Not at all", "Slightly", "Moderately", "Very", "Extremely"]; // 5-point Likert scale.

var startingPage = "1-consent"; // Start here.
var condition; // User's condition (affects the number of behavioral models).
var chain; // User's chain.
var generation; // User's generation within that chain.
var number; // User's index in that chain and generation.
var group; // User's group.
var outgroup; // User's outgroup.

var rabbit = []; // Nests the rabbit visits for each round.
var machine = []; // Rabbit-finding machine's guess for each round.
var scores = []; // Previous users' scores for each round.
var input = []; // Previous users' guesses for each round.

var demoAns = []; // Demographic question answers.
var preAns = []; // Pre-experiment confidence in ingroup.
var postAns = []; // Post-experiment confidence in ingroup.
var chosen = []; // Which previous users' input the user has chosen to see for each round.
var chosenInput = []; // What input the user received from the chosen individuals for each round.
var useMachine = []; // Whether the user chose to use the machine for each round.
var output = []; // The user's guess for each round.
var score = []; // The user's total (cumulative) score for each round.

var round = 0; // Current round.
var sources; // Number of users in the previous generation available for social learning.
var availableIngroup; // Remaining ingroup members to learn from (this round).
var availableOutgroup; // Remaining outgroup members to learn from (this round).

var totalTime = new Date().getTime(); // Total time spent. Stores the (client-side) starting time until the experiment is done (the user's clock could be off, unlike the server's).
var gameTime = []; // Total time spent on each of the games.
var surveyTime = []; // Total time spent on each survey.
var surveyCode; // Survey code generated to verify completion on MTurk.

var timeout; // A timer for when the HIT expires. If this runs out, kill the experiment.

// Initialize the experiment.
init();

// Initialize the experiment.
function init() {
	//
	// To simplify running the experiment offline, the code that handled IP verification and MTurk IDs has been removed.
	//
	
	// Set a timeout so that if someone spends more time on this page than the limit, they are kicked out.
	timeout = setTimeout(function() {
		window.onbeforeunload = null; // Remove the warning when navigating away from the page, since we are about to do just that.
		window.alert("This HIT has expired.");
		document.location.href = "https://www.mturk.com";
	}, TIME_LIMIT);

	// Toggle all the check marks off.
	for(var i = 0; i < checkMarks.length; i++) {
		toggleCheck(checkMarks[i], false);
	}

	// Shuffle the colors into a random order.
	groups = shuffle(groups);
	
	// Used in creating the pre- and post-game surveys.
	var list = ["pre", "post"];
	var order = ["ingroup", "outgroup"];
	
	// Randomize the order in which we ask about the two groups, as well as the order of the questions.
	order = shuffle(order);
	adjectives = shuffle(adjectives);
	
	// Create the pre- and post-game surveys.
	for(var i = 0; i < list.length; i++) {
		var str = ""; // HTML string we're embedding in the pre- and post-game survey page.
		var fname = "check" + list[i].charAt(0).toUpperCase() + list[i].slice(1) + "()"; // Function called when clicking one of the radio buttons.
		
		// Create the questions for the ingroup and the outgroup (group order is random).
		for(var j = 0; j < order.length; j++) {
			// Create the questions for the current group (questions are random).
			for(var k = 0; k < adjectives.length; k++) {
				// Create the question box.
				str += "<div class=\"content center\">";
				str += "\n<p>How <u>" + adjectives[k] + "</u> is <span class=\"" + order[j] + "\"></span>?</p>";
				str += "\n<p><form>";
				
				// Create each Likert-scale response, along with its label.
				for(var l = 0; l < likert.length; l++) {
					if(l > 0) {
						str += "&nbsp;&nbsp;";
					}
					str += "\n<label><input type=\"radio\" id=\"" + list[i] + "-" + (order[j] == "ingroup" ? "ig-" : "og-") + adjectives[k] + "-" + l + "\" name=\"" + list[i] + "-" + adjectives[k] + "\" onclick=\"" + fname + "\">" + likert[l] + "</label>";
				}
				
				// End the question box.
				str += "\n</form></p>";
				str += "\n</div>";
			}
		}
		
		// Embed the HTML string in the current survey page.
		document.getElementById(list[i] + "-content").innerHTML = str;
	}

	// Show the starting page.
	document.getElementById(startingPage).style.display = "block";
}

// Once the user is assigned to a group, initialize it.
function initGroup() {
	// Replace all references to and images of the user's group with the correct color.
	replaceText("group", "<span class=\"" + groups[group] + "\">" + groups[group] + "</span>");
	replaceImg("group-img", "images/" + groups[group] + ".png");

	// Replace all references to the generic group with the correct color.
	replaceText("ingroup", "<span class=\"" + groups[group] + "\">your group</span>");
	replaceText("outgroup", "<span class=\"" + groups[outgroup] + "\">the other group</span>");

	// Replace all images of a generic outgroup with the correct color.
	replaceImg("ingroup-img", "images/" + groups[group] + ".png");
	replaceImg("outgroup-img", "images/" + groups[outgroup] + ".png");

	// Randomly place the "choose ingroup" and "choose outgroup" buttons on the left or right.
	var ingroupBtn = "<button onclick=\"chooseIngroup()\" class=\"choose\" id=\"btn-ingroup\"><p>\n<img class=\"ingroup-img\">\n<img class=\"ingroup-img\">\n<img class=\"ingroup-img\">\n<br>My group's\n</p></button>";
	var outgroupBtn = "<button onclick=\"chooseOutgroup()\" class=\"choose\" id=\"btn-outgroup\"><p>\n<img class=\"outgroup-img\">\n<img class=\"outgroup-img\">\n<img class=\"outgroup-img\">\n<br>Other group's\n</p></button>";
	document.getElementById("group-btns").innerHTML = (Math.random() < 0.5) ? ingroupBtn + "\n" + outgroupBtn : outgroupBtn + "\n" + ingroupBtn;
}

// Once the user is assigned to a condition, initialize it.
function initCondition() {
	// Set the number of sources based on the condition.
	sources = condition + 1;
	
	// If there is no previous generation of users, we can't show social information.
	if(generation == 0) {
		document.getElementById("txt-instruct").style.display = 'none';
		document.getElementById("sl-pane").style.display = 'none';
		document.getElementById("sl-row").style.display = 'none';
	}
	else {
		// Create the string for how many behavioral models are available.
		var numbers = ["one", "two", "three", "four"];
		var strSources = numbers[condition];
		
		// Pluralize the number of sources if its more than one.
		if(sources == 1) {
			strSources += " other person's guess";
		}
		else {
			strSources += " other people's guesses";
		}
		
		// Update the text on the intruction and game pages.
		document.getElementById("txt-instruct").innerHTML = "<li>You get to see " + strSources + " <b>(free)</b></li>";
		document.getElementById("txt-game-title").innerHTML = "You get to see " + strSources;
		document.getElementById("txt-game").innerHTML = (sources == 1) ? "Whose guess would you like to see?" : "Whose guesses would you like to see?";
	}

	// Create the survey code. First 3 characters are random. Last two are K and the condition number.
	surveyCode = "";
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	for (var i = 0; i < 3; i++) {
		surveyCode += possible.charAt(Math.floor(Math.random() * possible.length));
	}
	surveyCode += "C" + condition + "C" + chain + "G" + generation + "N" + number;
}

// Once the user has started the experiment, make sure they are pruned if they navigate away from the page or close it.
function initPrune() {
	window.onbeforeunload = function(event) {
		var xhttp = new XMLHttpRequest();
		var str = "";

		// Location information so that we know which user to prune.
		str += "&condition" + condition;
		str += "&chain=" + chain;
		str += "&generation=" + generation;
		str += "&number=" + number;

		// WorkerId so that we're absolutely certain we're pruning the correct user.
		str += "&workerId=" + workerId;

		// Make sure it's a synchronous request (the send method does not return until a response is received).
		xhttp.open("POST", "server/pruneUser.php", false);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send(str);
	};

	// Use the browser's local storage to note that the user has started the experiment. This prevents them from leaving, getting pruned, but then backing into the page.
	window.localStorage.setItem(COOKIE, "started");
}

// Initialize the current round's game page.
function initGame() {
	// Set the current round in the title.
	document.getElementById("game-title").innerHTML = "Round " + (round + 1) + "/" + ROUNDS;
	document.getElementById("outcome-title").innerHTML = "Round " + (round + 1) + "/" + ROUNDS + " complete";

	// The user hasn't chosen who to learn from yet.
	chosen[round] = [];
	chosenInput[round] = [];

	// Shuffle their ingroup and outgroup options of who to choose from.
	if(group == 0) {
		availableIngroup = shuffle([0, 1, 2]);
		availableOutgroup = shuffle([3, 4, 5]);
	}
	else {
		availableIngroup = shuffle([3, 4, 5]);
		availableOutgroup = shuffle([0, 1, 2]);
	}

	// Enable all of the buttons, as these may have been disabled on previous rounds.
	document.getElementById("btn-ingroup").disabled = false;
	document.getElementById("btn-outgroup").disabled = false;
	document.getElementById("btn-use").disabled = false;
	document.getElementById("btn-skip").disabled = false;

	// If the user is in the first generation, we have no social information to show.
	if(generation > 0) {
		redrawSL();
		revealElement("sl-pane");
		hideElement("il-pane");
		hideElement("game-pane");
	}
	else {
		chosen[round] = -1;
		chosenInput[round] = [];
		for(var i = 0; i < sources; i++) {
			chosenInput[round].push(-1);
		}
		revealElement("il-pane");
		hideElement("game-pane");
	}
}

// Assign the user to a group.
function assignGroup() {
	// Create a request to the server.
	var xhttp = new XMLHttpRequest();

	// Be ready for the server's response.
	xhttp.onreadystatechange = function() {
		// Response is ready.
		if (this.readyState == 4 && this.status == 200) {
			// Assign the condition, chain, generation and number according to the server response.
			var response = xhttp.responseText.split("\t");
			condition = parseInt(response[0]);
			chain = parseInt(response[1]);
			generation = parseInt(response[2]);
			number = parseInt(response[3]);

			// Determine the user's group and outgroup based on their number.
			group = parseInt(response[4]);
			outgroup = 1 - group;

			// Parse the rabbit and machine data.
			var strRabbit = response[5].split(",");
			var strMachine = response[6].split(",");
			for(var i = 0; i < ROUNDS; i++) {
				rabbit[i] = parseInt(strRabbit[i]);
				machine[i] = parseInt(strMachine[i]);
			}

			// Parse previous users' score and input data (2D arrays).
			var strScores = response[7].split(";");
			var strInput = response[8].split(";");
			for(var i = 0; i < ROUNDS; i++) {
				var strScores2 = strScores[i].split(",");
				var strInput2 = strInput[i].split(",");

				scores[i] = [];
				input[i] = [];
				for(var j = 0; j < USERS; j++) {
					scores[i][j] = strScores2[j];
					input[i][j] = strInput2[j];
				}
			}

			// Initialize everything contingent on group and condition.
			initCondition();
			initGroup();
			initPrune();
			initGame();

			// Wait a bit before replacing the loader with the user's group.
			setTimeout(function() {
				// Replace the loader with the result.
				document.getElementById("assign-loader").style.display = "none";
				document.getElementById("assign-result").style.display = "block";

				// Trigger a reflow, so that the opacity is properly set and the result can fade in.
				document.getElementById("assign-result").offsetHeight;

				// Fade the result in.
				revealElement("assign-result");

				// Wait a bit before enabling the continue button.
				setTimeout(function() {
					document.getElementById("assign-btn").disabled = false;
					revealElement("assign-btn");
				}, WAIT_BUTTON);
			}, WAIT_ASSIGN);
		}
	};

	// Prepare the data we want to send when creating the user.
	var str = "";
	str += "startTime=" + startTime;
	str += "&assignmentId=" + assignmentId;
	str += "&hitId=" + hitId;
	str += "&workerId=" + workerId;
	str += "&ip=" + ip;
	str += "&age=" + demoAns[0];
	str += "&sex=" + demoAns[1];
	str += "&country=" + demoAns[2];

	// Send this data to the server, so it can create the user and send us back our condition, chain, generation and group.
	xhttp.open("POST", "server/createUser.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send(str);
}

// Redraw the panel where the user picks their demonstrators.
function redrawSL() {
	// Fill the slots showing the user's selected demonstrators.
	var strChosen = "";
	for(var i = 0; i < sources; i++) {
		if(i < chosen[round].length) {
			strChosen += "<img src=\"images/" + groups[(chosen[round][i] < 3) ? 0 : 1] + ".png\">\n";
		}
		else {
			strChosen += "<img src=\"images/empty.png\">\n";
		}
	}
	document.getElementById("img-chosen").innerHTML = strChosen;

	// Fill the buttons with the remaining possible demonstrators.
	var strIG = "";
	var strOG = "";
	for(var i = 0; i < availableIngroup.length; i++) {
		strIG += "\n<img src=\"images/" + groups[group] + ".png\">\n";
	}
	for(var i = 0; i < availableOutgroup.length; i++) {
		strOG += "\n<img src=\"images/" + groups[outgroup] + ".png\">\n";
	}
	if(availableIngroup.length == 0) {
		strIG += "\n<img src=\"images/null.png\">\n";
	}
	if(availableOutgroup.length == 0) {
		strOG += "\n<img src=\"images/null.png\">\n";
	}
	document.getElementById("btn-ingroup").innerHTML = "<p>\n" + strIG + "<br>My group's</p>";
	document.getElementById("btn-outgroup").innerHTML = "<p>\n" + strOG + "<br>Other group's</p>";
}

// Check if the user has picked all of their demonstrators. If so fade that window, disable it and move on.
function checkDoneSL() {
	// If the user has exhausted the ingroup, disable that button.
	if(availableIngroup.length == 0) {
		document.getElementById("btn-ingroup").disabled = true;
	}
	
	// If the user has exhausted the outgroup, disable that button.
	if(availableOutgroup.length == 0) {
		document.getElementById("btn-outgroup").disabled = true;
	}
	
	// Is the user done picking demonstrators?
	if(chosen[round].length == sources) {
		// Once the user has selected demonstrators, place their guesses in the game window.
		var strA = "";
		var strB = "";
		for(var i = 0; i < sources; i++) {
			var user = chosen[round][i];
			var userGroup = (chosen[round][i] < 3) ? 0 : 1;
			if(input[round][user] == 0) {
				strA += "\n<img src=\"images/" + groups[userGroup] + ".png\">\n";
				chosenInput[round].push(0);
			}
			else {
				strB += "\n<img src=\"images/" + groups[userGroup] + ".png\">\n";
				chosenInput[round].push(1);
			}
		}
		document.getElementById("sl-a").innerHTML = "<p>" + strA + "</p>";
		document.getElementById("sl-b").innerHTML = "<p>" + strB + "</p>";

		// Move on to the individual learning panel. Fade and disable the social learning one.
		revealElement("il-pane");
		document.getElementById("sl-pane").style.opacity = 0.5;
		document.getElementById("btn-ingroup").disabled = true;
		document.getElementById("btn-outgroup").disabled = true;
	}
}

// The user chose an ingroup demonstrator.
function chooseIngroup() {
	// Make the user's selection and redraw the social learning window. Also check if they're done.
	if(chosen[round].length < sources && availableIngroup.length > 0) {
		chosen[round].push(availableIngroup.shift());
		redrawSL();
	}
	checkDoneSL();
}

// The user chose an outgroup demonstrator.
function chooseOutgroup() {
	// Make the user's selection and redraw the social learning window. Also check if they're done.
	if(chosen[round].length < sources && availableOutgroup.length > 0) {
		chosen[round].push(availableOutgroup.shift());
		redrawSL();
	}
	checkDoneSL();
}

// The user has either chosen to use the rabbit-finding machine or opted not to. Move on to the game itself.
function endIL() {
	// Has a choice been made?
	if(useMachine[round] != null) {
		// If the user chose not to use the machine, hide this row in the game panel.
		if(useMachine[round] == 0) {
			document.getElementById("machine-row").style.display = "none";
			document.getElementById("il-a").innerHTML = "";
			document.getElementById("il-b").innerHTML = "";
		}
		else{
			// Otherwise show the machine's prediction.
			document.getElementById("machine-row").style.display = "flex";
			if(machine[round] == 0) {
				document.getElementById("il-a").innerHTML = "<img src=\"images/machine.png\" class=\"machine\">";
				document.getElementById("il-b").innerHTML = "";
			}
			else if(machine[round] == 1) {
				document.getElementById("il-a").innerHTML = "";
				document.getElementById("il-b").innerHTML = "<img src=\"images/machine.png\" class=\"machine\">";
			}
		}

		// Move on to the game panel. Fade out the individual learning panel and disable its buttons.
		document.getElementById("il-pane").style.opacity = 0.5;
		revealElement("game-pane");
		document.getElementById("btn-use").disabled = true;
		document.getElementById("btn-skip").disabled = true;
	}
}

// The user chose to use the rabbit-finding machine.
function useIL() {
	if(useMachine[round] == null) {
		useMachine[round] = 1;
	}
	endIL();
}

// The user chose to skip the rabbit-finding machine.
function skipIL() {
	if(useMachine[round] == null) {
		useMachine[round] = 0;
	}
	endIL();
}

// The user guessed nest A.
function guessA() {
	output[round] = 0;
	endRound();
}

// The user guessed nest B.
function guessB() {
	output[round] = 1;
	endRound();
}

// End the current round, because a guess has been submitted.
function endRound() {
	// Calculate the user's score on this round.
	prevScore = (round == 0) ? 0 : score[round - 1];
	score[round] = prevScore + ((output[round] == rabbit[round]) ? BENEFIT : 0) - ((useMachine[round] == 1) ? COST : 0);

	// After every 5th round, show the point rankings. Otherwise hide that panel.
	if((round + 1) % 5 == 0) {
		showRankings();
	}
	else {
		document.getElementById("rankings").style.display = 'none';
	}

	// Move on to the outcome page.
	nextPage("6-game");
}

// Show the point rankings on the output page.
function showRankings() {
	document.getElementById("rankings").style.display = 'block';

	order = ["user", 0, 1, 2, 3, 4, 5];

	// If there are previous users, sort their order of appearance by point total.
	if(generation > 0) {
		order.sort(function(a, b) {
			var scoreA = (a == "user") ? score[round] : parseInt(scores[round][a]);
			var scoreB = (b == "user") ? score[round] : parseInt(scores[round][b]);
			if(scoreA > scoreB) {
				return -1;
			}
			else if(scoreA < scoreB) {
				return 1;
			}
			else {
				return 0;
			}
		});
	}

	// Draw the table with each user's rank, ID and point total.
	var strRank = "";
	var strId = "";
	var strPoints = "";
	for(var i = 0; i < USERS + 1; i++) {
		if(order[i] == "user") {
			strRank += "<p><b>" + (i + 1) + "</b></p>\n";
			strId += "<p><b>You</b></p>\n";
			strPoints += "<p><b>" + score[round] + "</b></p>\n";
		}
		else if(generation > 0) {
			strRank += "<p>" + (i + 1) + "</p>\n";
			strId += "<p>PARTICIPANT " + (order[i] + 1) + "</p>\n";
			strPoints += "<p>" + scores[round][order[i]] + "</p>\n";
		}
		else {
			strRank += "<p>" + (i + 1) + "</p>\n";
			strId += "<p>(Not yet recruited)</p>\n";
			strPoints += "<p>?</p>\n";
		}
	}
	document.getElementById("rank").innerHTML = strRank;
	document.getElementById("id").innerHTML = strId;
	document.getElementById("points").innerHTML = strPoints;
}

// Move to the previous page.
function prevPage(current) {
	for(var i = 0; i < pages.length; i++) {
		if(current == pages[i]) {
			document.getElementById(pages[i]).style.display = 'none';
			document.getElementById(pages[i - 1]).style.display = 'block';

			break;
		}
	}

	window.scrollTo(0,0);
}

// Move to the next page.
function nextPage(current) {
	for(var i = 0; i < pages.length; i++) {
		if(current == pages[i]) {
			// Stop counting time if we're leaving the first survey.
			if(pages[i] == "4-pre") {
				surveyTime[0] = Math.round((new Date().getTime() - surveyTime[0]) / 1000);
			}
			// Progress or end the game if we're leaving the outcome page.
			else if(pages[i] == "7-outcome") {
				// Log the time spent on the current round.
				gameTime[round] = Math.round((new Date().getTime() - gameTime[round]) / 1000);
				round++;

				// Send the user back to the game page if they're not done all the rounds yet.
				if(round < ROUNDS) {
					gameTime[round] = new Date().getTime();
					initGame();
					prevPage(current);
					break;
				}
			}
			// Stop counting time if we're leaving the final survey.
			else if(pages[i] == "8-post") {
				surveyTime[1] = Math.round((new Date().getTime() - surveyTime[1]) / 1000);
			}

			// To assign the user to a group, contact the server to create a record and assign them a slot.
			if(pages[i + 1] == "3-assign") {
				assignGroup();
			}
			// Start counting time if we're starting the first survey.
			else if(pages[i + 1] == "4-pre") {
				surveyTime[0] = new Date().getTime();
			}
			// Start counting time if we're starting the first round.
			else if(pages[i + 1] == "6-game" && gameTime.length == 0) {
				gameTime[0] = new Date().getTime();
			}
			// Start counting time if we're starting the final survey.
			else if(pages[i + 1] == "8-post") {
				surveyTime[1] = new Date().getTime();
			}
			// Submit the user's data if they're done.
			else if(pages[i + 1] == "9-code") {
				totalTime = Math.round((new Date().getTime() - totalTime) / 1000);
				submitData();
				document.getElementById("bonus").innerHTML = "You earned " + score[ROUNDS - 1] + " points.";
			}

			// Hide the current page and show the next one.
			document.getElementById(pages[i]).style.display = 'none';
			document.getElementById(pages[i + 1]).style.display = 'block';

			break;
		}
	}

	// Put us back at the top of the page.
	window.scrollTo(0,0);
}

// Submit the user's data.
function submitData() {
	// Prepare the data we want to send.
	var str = "";

	// Location information so that we append to the correct record.
	str += "condition=" + condition;
	str += "&chain=" + chain;
	str += "&generation=" + generation;
	str += "&number=" + number;

	// User's output.
	str += "&pre=" + preAns;
	str += "&post=" + postAns;
	str += "&chosen=" + array2Str(chosen);
	str += "&chosenInput=" + array2Str(chosenInput);
	str += "&useMachine=" + useMachine.toString();
	str += "&output=" + output.toString();
	str += "&score=" + score.toString();

	// Other information about the user's behavior.
	str += "&totalTime=" + totalTime;
	str += "&gameTime=" + gameTime.toString();
	str += "&surveyTime=" + surveyTime.toString();
	str += "&surveyCode=" + surveyCode;

	// Create a request to the server.
	var xhttp = new XMLHttpRequest();

	// Submit our data to the server.
	xhttp.open("POST", "server/submitData.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send(str);

	// The user is done, so remove the warning when navigating away from the page, stop preventing them from backing into the page, and clear the HIT timeout.
	window.onbeforeunload = null;
	window.localStorage.clear();
	clearTimeout(timeout);
}

// Check if the user has completed the demographic questions.
function checkDemo() {
	checkSelForm(selDemo, demoAns, "check-demo", "btn-demo");
}

// Check if the user has answered the pre-experiment question.
function checkPre() {
	checkLikertForm(radPre, preAns, "check-pre", "btn-pre");
}

// Check if the user has answered the post-experiment question.
function checkPost() {

	checkLikertForm(radPost, postAns, "check-post", "btn-post"); 
}

// Check if a select-box form has been filled out.
function checkSelForm(sel, ans, check, btn) {
	// Assign the answers to the correct array.
	for(var i = 0; i < sel.length; i++) {
		ans[i] = document.getElementById(sel[i]).value;
	}

	// If any of the answers are incomplete, toggle the checkmark off and disable the button.
	for(var i = 0; i < ans.length; i++) {
		if(ans[i] == "none") {
			document.getElementById(btn).disabled = true;
			toggleCheck(check, false);
			return;
		}
	}

	// If the answers are all complete, toggle the checkmark on and enable the button.
	document.getElementById(btn).disabled = false;
	toggleCheck(check, true);
}

// Check if a Likert form has been filled out.
function checkLikertForm(rad, ans, check, btn) {
	for(var i = 0; i < rad.length; i++) {
		ans[i] = 0;
		
		for(var j = 0; j < likert.length; j++) {
			if(document.getElementById(rad[i] + "-" + j).checked) {
				ans[i] = j + 1;
				break;
			}
		}
	}
	
	for(var i = 0; i < ans.length; i++) {
		if(ans[i] == 0) {
			document.getElementById(btn).disabled = true;
			toggleCheck(check, false);
			return;
		}
	}
	
	document.getElementById(btn).disabled = false;
	toggleCheck(check, true);
}

// Shuffle an array.
function shuffle(array) {
  var current = array.length;

	// Keep going until we run out of elements to shuffle.
  while (0 !== current) {
    // Pick an element at random.
    var ind = Math.floor(Math.random() * current);
    current -= 1;

    // Swap it with the current element.
    var temp = array[current];
    array[current] = array[ind];
    array[ind] = temp;
  }

  return array;
}

// Convert a 1D or 2D array to a string. If it's a 2D array, join the subarrays with a colon.
function array2Str(array) {
	if(!Array.isArray(array[0])) {
		return array.toString();
	}
	else {
		var str = array[0].toString();
		for(var i = 1; i < array.length; i++) {
			str += ";" + array[i].toString();
		}
		return str;
	}
}

// Remove a matching element from an array.
function arrayRemove(array, element) {
	var i;
	for(i = 0; array[i] != element && i < array.length; i++) {
		continue;
	}

	if(i == array.length) {
		return;
	}
	else {
		array.splice(i, 1);
	}
}

// Replace the text in an entire class of HTML elements.
function replaceText(className, text) {
	var elements = document.getElementsByClassName(className);
	for(var i = 0; i < elements.length; i++) {
		elements[i].innerHTML = text;
	}
}

// Replace the image src in an entire class of HTML images.
function replaceImg(className, src) {
	var elements = document.getElementsByClassName(className);
	for(var i = 0; i < elements.length; i++) {
		elements[i].src = src;
	}
}

// Replace the className of an entire class of HTML elements.
function replaceClass(className, newClass) {
	var elements = document.getElementsByClassName(className);
	if(elements.length > 0) {
		elements[0].className = newClass;
		replaceClass(className, newClass);
	}
}

// Replace the display settings of an entire class of HTML elements.
function replaceDisplay(className, display) {
	var elements = document.getElementsByClassName(className);
	for(var i = 0; i < elements.length; i++) {
		elements[i].style.display = display;
	}
}

// Hide an HTML element, setting its visibility to hidden and its opacity to 0 (so we can fade it back in).
function hideElement() {
	for(var i = 0; i < arguments.length; i++) {
		document.getElementById(arguments[i]).style.visibility = 'hidden';
		document.getElementById(arguments[i]).style.opacity = 0;
	}
}

// Reveal an HTML element, setting its visibility to visible and its opacity to 1 (so it fades back in if it has a transition.)
function revealElement() {
	for(var i = 0; i < arguments.length; i++) {
		document.getElementById(arguments[i]).style.visibility = 'visible';
		document.getElementById(arguments[i]).style.opacity = 1;
	}
}

// Hide all of the pages, then embed an error message in the previously hidden div at the top.
function displayError(message) {
	for(var i = 0; i < pages.length; i++) {
		document.getElementById(pages[i]).style.display = 'none';
	}
	document.getElementById("error").innerHTML = message;
	document.getElementById("error").style.display = 'block';
}

// Toggle check marks on (green check) or off (red x).
function toggleCheck(name, checked) {
	if(checked == true) {
		document.getElementById(name).innerHTML = "<b>&#10003;</b>";
		document.getElementById(name).style.color = "#00ff00";
	}
	else {
		document.getElementById(name).innerHTML = "<b>X</b>";
		document.getElementById(name).style.color = "#ff0000";
	}
}

</script>
</body>
</html>
