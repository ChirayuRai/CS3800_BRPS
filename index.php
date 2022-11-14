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
				<p>Available</p>
				<button>Join</button>
			</div>
			<?php
		}
	?>
</body></html>