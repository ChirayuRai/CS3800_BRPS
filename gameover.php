<html>
<head>
    <title>Game Over!</title>
    <script>
        window.onload = function(event) {
            let timerdiv = document.getElementById("countdown");
            console.log(timerdiv)
            let timeleft = 20;
            let halt = false;
            let resultTimer = setInterval(function(){
                if(!halt) {
                    timeleft--;
                    timerdiv.innerHTML = timeleft;
                }
                if(timeleft < 1) {
                    halt = true;
                    window.location.href = '/';
                }
            },1000);
        }
    </script>
    <style>
        body {
            background: #000;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        #container {
            width: 100%;
            position: relative;
            text-align: center;
            font-size: 2em;
        }
        #countdown {
            color: red;
        }
    </style>
</head>
<body>
<div id="container">
    <img src="api/img/game_over.jpg" style="display: inline-block; margin: 0 auto;" />
    <p>Returning to lobby in <span id="countdown">20</span> seconds</p>
</div>
</body>
</html>