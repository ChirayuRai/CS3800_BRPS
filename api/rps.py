import datetime
import time
import os
from socket import *
import sys  # In order to terminate the program
import json
import re
from datetime import datetime

timeoutLimit = 45
serverSocket = socket(AF_INET, SOCK_STREAM)
serverPort = int(sys.argv[1])  # Port number passed as argument
serverSocket.bind(("", serverPort))
serverSocket.listen(5)  # listen for X max connections at a time
serverSocket.settimeout(timeoutLimit)
startTime = time.time()
playerID = [None, None]
gameRound = 0
winner = None
p_input = [[None, None], [None, None], [None, None], [None, None], [None, None]]
score = [0, 0]
eventID = 0


def sendmessage(response, content):
    output = "HTTP/1.1 200 OK\nContent-Type: text/html\nAccess-Control-Allow-Origin: *\n\n"
    output += '{"response": "' + response + '", "content": "' + content + '"}'
    connectionSocket.send(output.encode())
    connectionSocket.send('\n'.encode())  # Only 1 \n for JSON, add another if HTML


def bufferWrite(event_type, msg, context=None, p1cast=None, p2cast=None):
    global eventID, score, p_input
    if event_type == "status":
        context = eventID
    timestamp = datetime.now().strftime("%d/%m/%Y %H:%M:%S")
    buffernumber = 5-(42073-serverPort)  # NumRooms-(LastRoom-CurrentRoom)
    writemode = 'w' if event_type == "init" else 'a'
    data = '{"event": "' + event_type + '", "msg": "' + msg + '", "time": "' + str(timestamp) + '", "context": "'
    data += str(context) + '", "p1score": "' + str(score[0]) + '", "p2score": "' + str(score[1]) + '"'
    if event_type == "round_winner":
        data += ', "p1cast": "' + str(p1cast) + '", "p2cast": "' + str(p2cast) + '"'
    data += ', "gamelog": "' + json.dumps(p_input) + '"'
    data += '}'
    line = data if event_type == "init" else '\n' + data
    with open('api/buffer_' + str(buffernumber), writemode) as f:
        f.write(line)
    eventID += 1
    if event_type == "game_winner" or event_type == "timeout":
        time.sleep(10)
        os.remove('api/buffer_' + str(buffernumber))


def loginplayer(p, playerid, response):
    global playerID, currentPlayer
    login_response = "login_ok"
    err = 0
    if playerID[int(p) - 1] is None:
        playerID[int(p) - 1] = playerid
    elif playerID[int(p) - 1] == playerid:
        err = 1
    else:
        err = 2
    if err == 1:
        login_msg = "Welcome back p" + p
    elif err == 2:
        login_response = "login_fail"
        login_msg = "Player " + p + " is already logged in"
    else:
        currentPlayer = int(p) - 1  # P1 is 0, P2 is 1 for easier array writing
        login_msg = "Player " + p + " logged in with id " + playerid
    if response:
        sendmessage(login_response, login_msg)
        bufferWrite(login_response, login_msg, p)
        if err == 1:
            bufferWrite("status", json.dumps(p_input))
    return True if err < 2 else False


def game(p1, p2):
    rules = {
        "rock": {"rock": 2, "paper": 0, "scissors": 1},
        "paper": {"rock": 1, "paper": 2, "scissors": 0},
        "scissors": {"rock": 0, "paper": 1, "scissors": 2},
    }
    outcome = [2, 1, 0]  # [P2 Wins, P1 Wins, Tie]
    result = outcome[rules[p1][p2]]
    return result


bufferWrite("init", "Game instance initialized")
while winner is None:
    # Establish the connection
    print("Session " + str(serverPort) + " listening...")
    connectionSocket = None
    try:
        connectionSocket, addr = serverSocket.accept()
        message = connectionSocket.recv(4096)
        filename = message.split()[1].decode()
        action = ""
        matchResult = None
        playerCast = re.search(r'p(1|2)=(rock|paper|scissors)&playerid=([a-z0-9]{13})', filename)  # Check if current URL is a cast
        playerLogin = re.search(r'login=(1|2)&playerid=([a-z0-9]{13})', filename)  # Check if current URL is a login
        currentPlayer = None
        match = False
        castResponse = [None, None]
        p1cast = None
        p2cast = None
        if playerLogin is not None:
            loginResults = loginplayer(playerLogin[1], playerLogin[2], True)  # Login and send response
        elif playerCast is not None and playerID[0] is not None and playerID[1] is not None:
            loginResults = loginplayer(playerCast[1], playerCast[3], False)  # Login and don't send response
            if loginResults:
                currentPlayer = playerID.index(playerCast[3])
                if p_input[gameRound][currentPlayer] is None:
                    p_input[gameRound][currentPlayer] = playerCast[2]
                else:
                    castResponse[0] = "cast_fail"
                    castResponse[1] = "P" + str(currentPlayer + 1) + "already cast " + str(p_input[gameRound][currentPlayer])
                for p in range(2):
                    if p_input[gameRound][p] is not None:
                        action = "Round " + str(gameRound + 1) + ": P" + str(currentPlayer + 1) + " cast " + str(
                            p_input[gameRound][p])
                        castResponse[0] = "cast_ok"
                        castResponse[1] = action
                    else:
                        castResponse[0] = "cast_fail"
                        castResponse[1] = "Empty cast"
                if p_input[gameRound][0] and p_input[gameRound][1]:
                    p1cast = p_input[gameRound][0]
                    p2cast = p_input[gameRound][1]
                    match = game(str(p_input[gameRound][0]), str(p_input[gameRound][1]))  # 0 = Tie, 1 = P1Win, 2 = P2Win
                    if match == 0:
                        p_input[gameRound][0] = p_input[gameRound][1] = None  # Reset round values for do-over
                        matchResult = "Tie"
                    else:
                        gameRound += 1  # Round concluded.
                        score[match - 1] += 1  # Plus one point to the winner
                        matchResult = "Player " + str(match) + " wins round "
                        matchResult += str(gameRound) + " (" + str(score[0]) + " - " + str(score[1]) + ")"
                print(action)
                bufferWrite("cast_ok", action, str(currentPlayer + 1))
                sendmessage("cast_ok", action)
                bufferWrite("status", "ping")
                if matchResult:
                    print(matchResult)
                    print(p_input)
                    bufferWrite("round_winner", matchResult, str(match), p1cast, p2cast)
                if score[0] > 2:
                    winner = 1
                if score[1] > 2:
                    winner = 2
                if winner is not None:
                    bufferWrite("game_winner", "Player " + str(winner) + " wins the game!", str(winner))
            else:
                castError = "Bad cast (invalid player ID.)"
                bufferWrite("cast_fail", castError, currentPlayer)
        else:
            bufferWrite("status", "ping")
    except TimeoutError:
        print("Socket timeout")
        bufferWrite("timeout", "No input received for " + str(timeoutLimit) + " seconds. Shutting down...")
        break
    except IOError:
        connectionSocket.send("HTTP/ 1.1 404 Not Found\r\n".encode())
        connectionSocket.send("Content-Type: text/html\r\n".encode())
        connectionSocket.send("\r\n".encode())
        connectionSocket.send("<html><head></head><body><h1>404 Not Found</h1></body></html>\r\n".encode())
        connectionSocket.close()
    connectionSocket.close()
serverSocket.close()
print("Player " + str(winner) + " wins the game!")
print("Session " + str(serverPort) + " closed.")
sys.exit()
