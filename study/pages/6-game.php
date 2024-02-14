<div class="page" id="6-game">

	<div class="title" id="game-title"></div>

	<div class="content center fade fastfade" id="sl-pane">
		<div class="title" id="txt-game-title"></div>
		<p id="txt-game"></p>
		<p>
			<span id="img-chosen"></span>
		</p>
		<p>
			<span id="group-btns"></span>
		</p>
	</div>

	<div class="content center fade fastfade" id="il-pane">
		<div class="title">Use the rabbit-finding machine?</div>
		<p>Costs 2 points. The machine is correct 2/3 of the time, but wrong 1/3 of the time.</p>
		<p>
			<span><button onclick="useIL()" id="btn-use"><p>Use</p></button>
			<button onclick="skipIL()" id="btn-skip"><p>Skip</p></button></span>
		</p>
	</div>

	<div class="content center fade fastfade" id="game-pane">
		<div class="game-row">
			<div class="game-left"></div>
			<div class="game-center title"><b>Nest A</b></div>
			<div class="game-right title"><b>Nest B</b></div>
		</div>
		<div class="game-row" id="sl-row">
			<div class="game-left"><p>Other people guessed:</p></div>
			<div class="game-center light" id="sl-a"></div>
			<div class="game-right light" id="sl-b"></div>
		</div>
		<div class="game-row machine-row" id="machine-row">
			<div class="game-left">
				<p>Rabbit-finding machine:</p>
			</div>
			<div class="game-center light" id="il-a">
				<img src="images/machine.png" class="machine" id="img-il-a">
			</div>
			<div class="game-right light" id="il-b">
				<img src="images/machine.png" class="machine" id="img-il-b">
			</div>
		</div>
		<div class="game-row guess">
			<div class="game-left"><p>Where is the rabbit?<br>(+5 points)</p></div>
			<div class="game-center"><button onclick="guessA()">Guess A</button></div>
			<div class="game-right"><button onclick="guessB()">Guess B</button></div>
		</div>
	</div>

</div>
