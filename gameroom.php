<?php
$gameRoomNumber = (preg_match('~^420(69|70|71|72|73)$~', $_GET['room'])) ? $_GET['room'] : null; // 5 rooms are available
$castingEndpoint = "/:".$gameRoomNumber;
$currentPlayer = (preg_match('~^[12]$~', $_GET['p'])) ? $_GET['p'] : null; // 5 rooms are available;
if(!$currentPlayer) {exit('Invalid player');}
$otherPlayer = ($currentPlayer == "1") ? "2" : "1";
session_start();
if(empty($_SESSION['playerID'])) {
    $_SESSION['playerID'] = uniqid(); // Insecure random id;
}
$playerID = $_SESSION['playerID'];
if(!$gameRoomNumber) {exit('Invalid game room');}
// Initialize server room thread
//exec('api/python3 rps.py ' . $gameRoomNumber . ' > /dev/null &'); // Must redirect output otherwise will hang
$curl_handle=curl_init();
curl_setopt($curl_handle,CURLOPT_URL,'http://cpp3800.edwin-dev.com:'.$gameRoomNumber.'/?login='.$currentPlayer.'&playerid='.$playerID);
curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
$loginResult = json_decode(curl_exec($curl_handle), true);
curl_close($curl_handle);
$loggedIn = false;
$logInErr = "Unable to log in. ";
if (empty($loginResult)){
    $logInErr .= "No response from login server.";
}
else{
    if($loginResult['response'] == "login_ok") {
        $loggedIn = true;
    } elseif ($loggedIn['response'] == "login_fail") {
        $logInErr .= $loginResult['content'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Gameroom </title>
	<style type="text/css">
		body {
			font-family: arial;
			background-color: #333;
			color: #fff;
		}
		#shootContainer, #roundResults, #gameover {
            display: none;
			position: relative;
            text-align: center;
			margin: 0 auto;
			width: 720px;
		}
        #roundResults .result {
            display: inline-block;
            font-weight: bold;
            text-align: center;
        }
        #roundResults .result img {
            max-width: 200px;
        }
		#infoWindow {
			width: 700px;
			margin: 0 auto;
			text-align: center;
		}
        #scoreboard {
            display: none;
        }
        #victoryimg, #defeatimg {
            display: none;
            margin: 0 auto;
        }
		#scoreboard .score {
			font-size: 1.5em;
			font-weight: bold;
			margin: 0.5em 0;
		}
        #gamelog {
            display: none;
        }
        #loading {
            position: relative;
            height: 400px;
            border: 6px solid #000;
            text-align: center;
        }
		#loadingMsg, #loadingImg {
            font-size: 3em;
            color: yellow;
            text-shadow: 2px 2px purple;
            margin: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
		}
        #tie {
            position: absolute;
            left: 315px;
            top: 10px;
            width: 100px;
            display: none;
        }
		.p1 {
			color: mediumspringgreen;
		}
		.p2 {
			color: dodgerblue;
		}
		.shoot {
			float: left;
			width: 33%;
			text-align: center;
			font-size: 1.5em;
			font-weight: bold;
		}
		.shoot img {
			max-width: 200px;
		}
		.shoot a:hover {
			cursor: pointer;
		}
		.clearme {
			clear: both;
		}
		.rock {
			color: #ef5480;
		}
		.paper {
			color: #ffde55;
		}
		.scissors {
			color: #74dae2;
		}
        .loser {
            opacity: 0.4;
        }
        .error {
            color: red;
        }
	</style>
	<script type="text/javascript">
        window.onload = function(event) {
            let currentRound = 1;
            let currentPlayer = '<?=$currentPlayer;?>';
            let playerID = '<?=$playerID;?>';
            let login = [false, false];
            let loadingWindow = document.getElementById('loading');
            let loadingMsg = document.getElementById('loadingMsg');
            let shootWindow = document.getElementById('shootContainer');
            let scoreboard = document.getElementById('scoreboard');
            let roundResults = document.getElementById('roundResults');
            let rock = document.getElementById('cast_rock');
            let paper = document.getElementById('cast_paper');
            let scissors = document.getElementById('cast_scissors');
            let p1score = document.getElementById('p1score');
            let p2score = document.getElementById('p2score');
            let resultp1 = document.getElementById('resultp1');
            let resultp2 = document.getElementById('resultp2');
            let resultp1win = document.getElementById('p1rwin');
            let resultp2win = document.getElementById('p2rwin');
            let resultp1lose = document.getElementById('p1rlose');
            let resultp2lose = document.getElementById('p2rlose');
            let nextRoundh2 = document.getElementById('nextRound');
            let roundResultsTitle = document.getElementById('roundResultsTitle');
            let tieimg = document.getElementById('tie');
            let round = document.getElementById('round');
            let resultsTimer = document.getElementById('resultsTimer');
            let gameOver = document.getElementById('gameover');
            let victoryimg = document.getElementById('victoryimg');
            let defeatimg = document.getElementById('defeatimg');
            let p1rock = document.getElementById('p1rock');
            let p1paper = document.getElementById('p1paper');
            let p1scissors = document.getElementById('p1scissors');
            let p2rock = document.getElementById('p2rock');
            let p2paper = document.getElementById('p2paper');
            let p2scissors = document.getElementById('p2scissors');
            let initialized = false;
            let dirtyBit = false;
            let winner = null;
            let gamelog = [[null, null], [null, null], [null, null], [null, null], [null, null]];
            let eventCounter = 0;
            //check for browser support
            if (typeof (EventSource) !== "undefined") {
                //var eSource = new EventSource("http://cpp3800.edwin-dev.com:<?=$gameRoomNumber;?>/sse");
                var eSource = new EventSource("http://cpp3800.edwin-dev.com/api/sse_buffer.php?room=<?=$gameRoomNumber;?>");
                //detect message receipt
                console.log("SSE load ok");
                eSource.addEventListener("login_ok", (event) => {
                    let loggedInPlayer = JSON.parse(event.data).context;
                    if (loggedInPlayer === "1") {
                        login[0] = true;
                    }
                    if (loggedInPlayer === "2") {
                        login[1] = true;
                    }
                    if(initialized && !dirtyBit) {
                        otherPlayerLogin();
                    }
                });
                eSource.addEventListener("cast_ok", (event) => {
                    let player = JSON.parse(event.data).context;
                    if(initialized) {
                        if(player === "<?=$currentPlayer;?>") {
                            waitingForOtherPlayer();
                        }
                    }
                });
                eSource.addEventListener("round_winner", (event) => {
                    let w = JSON.parse(event.data).context;
                    let p1 = JSON.parse(event.data).p1score;
                    let p2 = JSON.parse(event.data).p2score;
                    let p1cast = JSON.parse(event.data).p1cast;
                    let p2cast = JSON.parse(event.data).p2cast;
                    gamelog = JSON.parse(JSON.parse(event.data).gamelog);
                    if(p1 < 3 && p2 < 3 && w !== "0") {
                        currentRound++;
                    }
                    p1score.innerHTML = p1;
                    p2score.innerHTML = p2;
                    round.innerHTML = currentRound.toString();
                    if(initialized) {
                        displayResults(w,p1, p2, p1cast, p2cast);
                    }
                });
                eSource.addEventListener("game_winner", (event) => {
                    winner = JSON.parse(event.data).context;
                    show(gameOver);
                    hide(shootWindow);
                    if(currentPlayer === winner) {
                        show(victoryimg);
                    } else {
                        show(defeatimg)
                    }
                    let gameOverTime = 10;
                    let goTimer = setInterval(function(){
                        gameOverTime--;
                        if(gameOverTime < 1) {
                            window.location.replace("/gameover.php");
                        }
                    },1000);
                });
                eSource.addEventListener("current_id", (event) => {
                    eventCounter = event.lastEventId;
                    init();
                });
            } else {
                document.getElementById("serverData").innerHTML = "Whoops! Your browser doesn't receive server-sent events.";
            }

            function hide(elem) {
                elem.style.display = 'none';
            }

            function show(elem) {
                elem.style.display = 'block';
            }
            function waitingForOtherPlayer() {
                loadingMsg.innerHTML = "Waiting for Player <?=$otherPlayer;?>";
                show(loadingWindow);
                hide(shootWindow);
            }
            function cast(round, player, shoot) {
                if (login[0] && login[1]) {
                    fetch('http://cpp3800.edwin-dev.com:<?=$gameRoomNumber;?>/?p' + player + '=' + shoot + '&playerid=' + playerID, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
                        .then(response => response.text())
                        .then(text => console.log(text))
                }
            }
            function otherPlayerLogin(){
                hide(loadingWindow);
                hide(tieimg);
                show(shootWindow);
                show(scoreboard);
                dirtyBit = true;
            }
            function displayResults(w, p1, p2, p1cast, p2cast) {
                resultp1.className = "result";
                resultp2.className = "result";
                console.log('Winner is ' + w + " and p1cast: " + p1cast + " and p2cast: " + p2cast);
                hide(resultp1win);
                hide(resultp2win);
                hide(resultp1lose);
                hide(resultp2lose);
                hide(shootWindow);
                hide(tieimg);
                hide(p1rock);
                hide(p1paper);
                hide(p1scissors);
                hide(p2rock);
                hide(p2paper);
                hide(p2scissors);

                if(p1cast === "rock") {show(p1rock);}
                if(p1cast === "paper") {show(p1paper);}
                if(p1cast === "scissors") {show(p1scissors);}
                if(p2cast === "rock") {show(p2rock);}
                if(p2cast === "paper") {show(p2paper);}
                if(p2cast === "scissors") {show(p2scissors);}
                if (parseInt(p1) > 2 || parseInt(p2) > 2) {
                    hide(nextRoundh2);
                }
                roundResultsTitle.innerHTML = "Round Results";
                if (w.toString() === "1") {
                    resultp2.classList.add("loser");
                    show(resultp1win);
                    show(resultp2lose);
                }
                if (w.toString() === "2") {
                    resultp1.classList.add("loser");
                    show(resultp2win);
                    show(resultp1lose);
                }
                if (w.toString() === "0") {
                    show(tieimg);
                    roundResultsTitle.innerHTML = "TIE: REPEAT ROUND";
                    resultp1.classList.add("loser");
                    resultp2.classList.add("loser");
                }
                hide(loadingWindow);
                show(roundResults);
                console.log(w);
                if(w !== "0") {
                    round.innerHTML = (currentRound-1).toString();
                }
                let timeleft = 5;
                let resultTimer = setInterval(function(){
                    timeleft--;
                    resultsTimer.innerHTML = timeleft;
                    if(timeleft < 1) {
                        clearInterval(resultTimer);
                        hide(roundResults);
                        if(parseInt(p1) < 3 && parseInt(p2) < 3) {
                            show(shootWindow);
                        }
                        round.innerHTML = currentRound.toString();
                        resultsTimer.innerHTML = "5";
                    }
                },1000);
            }
            rock.onclick = function () {
                cast(currentRound, currentPlayer, 'rock');
            }
            paper.onclick = function () {
                cast(currentRound, currentPlayer, 'paper');
            }
            scissors.onclick = function () {
                cast(currentRound, currentPlayer, 'scissors');
            }
            /////////////////////////////////////////////
            /// Game Routine (Move to separate file
            /////////////////////////////////////////////
            function init(){
                if (login[0] && login[1]) {
                    loadingMsg.innerHTML = "Player <?=$otherPlayer;?> connected";
                    otherPlayerLogin();
                } else {
                    waitingForOtherPlayer();
                }
                if(winner) {
                    hide(shootWindow);
                }
                initialized = true;
            }
            /////////////////////////////////////////////
            /// End Game Routine
            /////////////////////////////////////////////
        }

	</script>
</head>
<body>
<?php
if(!$loggedIn) {
    echo "<h2>" . $logInErr . "</h2></body></html>";
    exit;
}
?>
<div id="infoWindow">
    <h2 id="loggedInAs" class="<?=($loggedIn) ? "p" . $currentPlayer : "error";?>">
        <?=($loggedIn) ? "Logged in as Player " . $currentPlayer : $logInErr;?>
    </h2>
    <div id="loading">
        <img id="loadingImg" src="api/img/loading.png" />
        <div id="loadingMsg"><?=($loggedIn) ? "Waiting for Player " . $otherPlayer : "Waiting for Server";?></div>
    </div>
    <div id="roundResults">
        <h2 id="roundResultsTitle">Round Results</h2>
        <img src="api/img/tie.png" id="tie" />
        <div id="resultp1" class="result">
            <img id="p1rock" src="api/img/rock.png" />
            <img id="p1paper" src="api/img/paper.png" />
            <img id="p1scissors" src="api/img/scissors.png" />
            <br /><span class="p1">PLAYER 1<br/><span id="p1rwin">WINNER</span><span id="p1rlose">LOSER</span></span></div>
        <div id="resultp2" class="result">
            <img id="p2rock" src="api/img/rock.png" />
            <img id="p2paper" src="api/img/paper.png" />
            <img id="p2scissors" src="api/img/scissors.png" />
            <br /><span class="p2">PLAYER 2<br /><span id="p2rwin">WINNER</span><span id="p2rlose">LOSER</span></span></div>
        <h2 id="nextRound">Next round starts in <span id="resultsTimer">5</span></h2>
    </div>
    <div id="gameover">
        <img id="victoryimg" src="api/img/victory.gif" />
        <img id="defeatimg" src="api/img/defeat.gif" />
    </div>
	<div id="scoreboard">
        <h2>Round <span id="round">1</span> </h2>
		<p class="score">Player One: <span id="p1score" class="p1">0</span></p>
		<p class="score">Player Two: <span id="p2score" class="p2">0</span></p>
		<div id="gamelog">
			[ <span class="p1">Rock</span> | <span class="p2">Paper</span> ] &nbsp;
		</div>
	</div>
</div>
	<div id="shootContainer">
		<div class="shoot rock"><a id="cast_rock"><img src="api/img/rock.png" /></a><br />ROCK</div>
		<div class="shoot paper"><a id="cast_paper"><img src="api/img/paper.png" /></a><br />PAPER</div>
		<div class="shoot scissors"><a id="cast_scissors"><img src="api/img/scissors.png" /></a><br />SCISSORS</div>
		<div class="clearme"></div>
	</div>
</body>
</html>