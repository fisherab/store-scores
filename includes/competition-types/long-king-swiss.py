#!/usr/bin/env python

import json, argparse, random, itertools

parser = argparse.ArgumentParser(description = "Derives the rounds for a long king swiss")
parser.add_argument('file', nargs=1, help = "file to use")
parser.add_argument('--games','-g', default = 4, type = int, help = "Number of games per person (4)")
parser.add_argument('--tries', '-t', default = 10000, type = int, help ="Number of tries to find minimum (100000)")
args = parser.parse_args()

gamesPerPerson = args.games

# Run the whole process
best = None
for run in range(args.tries):

    """
    Basic algorithm

    Consider all possible games - i.e each player could plays
    everybody with lexically higher name. There are (n-1)! - so not
    many.

    There are no rounds - just a flat list of games to find.

    Each person needs to play m (probably 4) games so a total of m*n/2
    games

    Choose a game at random

    Compute quality metric

    Repeat to get the best quaility metric
    """

    # Do initial game allocation - restart if it gets stuck
    while True:
        with open(args.file[0], "r") as jsonfile: 
            players = json.load(jsonfile)
        playerDict = {}
        for player in players:
            playerDict[player['name']] = player
            player['opps'] = set()
            games = set()
            fullyScheduledPlayerNames = set()
            for player1 in players:
                for player2 in players:
                    if player1['name'] < player2['name']:
                        games.add((player1['name'],player2['name']))

        while len(fullyScheduledPlayerNames) != len(players):
            if len(games) == 0: break
            game = random.choice(list(games))
            games.remove(game)
            pName1, pName2 = game
            if pName1 in fullyScheduledPlayerNames or  pName2 in fullyScheduledPlayerNames:
                continue
            player1 = playerDict[pName1]
            player2 = playerDict[pName2]
            player1['opps'].add(pName2)
            player2['opps'].add(pName1)
            
            for player in (player1, player2):
                if len(player['opps']) == gamesPerPerson:
                    fullyScheduledPlayerNames.add(player['name'])   

        if len(fullyScheduledPlayerNames) == len(players): break
        
#   Check for uniqueness
    for player in players:
        for opp in player['opps']:
            assert player['name'] in playerDict[opp]['opps']

#   Add sumidx for each player
    sumidx2 = 0
    for player in players:
        sumidx = 0
        for p in player['opps']:
            sumidx += playerDict[p]['idx']
        player['sumidx'] = sumidx
        sumidx2 += sumidx**2
 #   print ("Run", run, "Sumidx2", sumidx2)

    if not best or sumidx2 < best:
        best = sumidx2
        bestGames = players[:]

print("Best", best)
for player in bestGames: print (player)

# Name to numbers
with open(args.file[0], "r") as jsonfile: 
    players = json.load(jsonfile)
numbers = {}
names = {}
i = 0
for p in players:
    names[i] = p['name']
    numbers[p['name']] = i
    i += 1

print ("games=", end="")
for player in bestGames:
    left = numbers[player['name']]
    opp = sorted(list(player['opps']))
    for i in range(gamesPerPerson):
        right = numbers[opp[i]]
        if left < right:
            print(left, right, end = " ")
print()
print("Games each", gamesPerPerson, " tries:", args.tries)

        

    
    

    

    

