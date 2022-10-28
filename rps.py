from socket import *
import sys  # In order to terminate the program
import re

serverSocket = socket(AF_INET, SOCK_STREAM)
serverPort = int(sys.argv[1])  # Port number passed as argument
serverSocket.bind(("", serverPort))
serverSocket.listen(5)  # listen for X max connections at a time
gameRound = 0
cast = 0
winner = None
p_input = [[None, None], [None, None], [None, None], [None, None], [None, None]]
score = [0, 0]


def sendmessage(code, msg, content):
    output = "HTTP/1.1 " + code + " " + msg + "\r\nContent-Type: text/html\r\n\r\n"
    output += content
    connectionSocket.send(output.encode())
    connectionSocket.close()


def game(p1, p2):
    rules = {
        "rock": {
            "rock": 2,
            "paper": 0,
            "scissors": 1
        },
        "paper": {
            "rock": 1,
            "paper": 2,
            "scissors": 0
        },
        "scissors": {
            "rock": 0,
            "paper": 1,
            "scissors": 2
        },
    }
    outcome = [2, 1, 0]  # [P2 Wins, P1 Wins, Tie]
    result = outcome[rules[p1][p2]]
    return result


while winner is None:
    # Establish the connection
    print("Session " + str(serverPort) + " listening...")
    connectionSocket, addr = serverSocket.accept()
    try:
        message = connectionSocket.recv(4096)
        filename = message.split()[1].decode()
        action = ""
        matchResult = None
        playerCast = re.search(r'p(1|2)=(rock|paper|scissors)', filename)
        currentPlayer = None
        if playerCast is not None:
            currentPlayer = int(playerCast[1]) - 1
            p_input[gameRound][currentPlayer] = playerCast[2]
            cast = 0
            for p in range(2):
                if p_input[gameRound][p] is not None:
                    cast += 1
                    action = "Round " + str(gameRound + 1) + ": P" + str(currentPlayer + 1) + " casted " + str(p_input[gameRound][p])
            if p_input[gameRound][0] and p_input[gameRound][1]:
                match = game(str(p_input[gameRound][0]), str(p_input[gameRound][1]))  # 0 = Tie, 1 = P1Win, 2 = P2Win
                if match == 0:
                    p_input[gameRound][0] = p_input[gameRound][1] = None  # Reset round values for do-over
                    matchResult = "Tie"
                else:
                    gameRound += 1  # Round concluded.
                    score[match-1] += 1  # Plus one point to the winner
                    matchResult = "Player " + str(match) + " wins round "
                    matchResult += str(gameRound) + " (" + str(score[0]) + " - " + str(score[1]) + ")"
            print(action)
            if matchResult:
                print(matchResult)
                print(p_input)
            if score[0] > 2:
                winner = 1
            if score[1] > 2:
                winner = 2
            sendmessage("200", "OK", action)
    except IOError:
        connectionSocket.send("HTTP/ 1.1 404 Not Found\r\n".encode())
        connectionSocket.send("Content-Type: text/html\r\n".encode())
        connectionSocket.send("\r\n".encode())
        connectionSocket.send("<html><head></head><body><h1>404 Not Found</h1></body></html>\r\n".encode())
        connectionSocket.close()
serverSocket.close()
print("Player " + str(winner) + " wins the game!")
print("Session " + str(serverPort) + " closed.")
sys.exit()
