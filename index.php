<?php
$numRooms = 5;
?>
<html>
<head>
	<title>Socket-To-Me RPS</title>
	<style type="text/css">
		body {
			font-family: arial;
		}
		.gameroom {
			padding: 10px;
			border: 1px solid #000;
			margin-bottom: 10px;
		}
		.gameroom h2 {
			margin: 0;
		}
		.g1 {
			background-color: darkgreen;
		}
		.g2 {
			background-color: darkgray;
		}
		.g3 {
			background-color: darkred;
		}
		.g4 {
			background-color: darkcyan;
		}
		.g5 {
			background-color: purple;
		}
	</style>
</head>
<body>
	<?php
		for ($i=1; $i <= $numRooms; $i++) { 
			?>
			<div class="gameroom g<?=$i;?>">
				<h2>Room <?=$i;?></h2>
				<p>
					<?php 
						if(file_exists("./api/buffer_$i")) {
							$json_data = file("./api/buffer_$i");
							$result = "";
							$players = 0;
							foreach ($json_data as $row) {
								$event = json_decode($row, true);
								if($event["event"] == "login_ok" && $event["context"] == "1") {
									$result = "Room available for P2 to join";
									$players++;
								}
								elseif($event["event"] == "login_ok" && $event["context"] == "2") {
									$result = "Room available for P1 to join";
									$players++;
								} elseif($players >= 2) {
									$result = "Room Unavailable";
									break;
								}
							}
							echo $result;
						} else {
							echo 'Room Available for P1 and P2 to join';
						}
					?>
				</p>
				<button type="button" onclick="location.href='http://cpp3800.edwin-dev.com/gameroom.php?room<?=42068+$i?>&p=1'">Join as P1</button>
				<button type="button" onclick="location.href='http://cpp3800.edwin-dev.com/gameroom.php?room<?=42068+$i?>&p=2'">Join as P2</button>
			</div>
			<?php
		}
	?>
</body></html>