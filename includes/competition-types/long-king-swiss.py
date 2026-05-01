#!/usr/bin/env python

import json, argparse, random, itertools

parser = argparse.ArgumentParser(description = "Derives the rounds for a long king swiss")
parser.add_argument('file', nargs=1, help = "file to use")
parser.add_argument('--noshuffling', action="store_true", help = "remove the random behaviour")
parser.add_argument("--verbose", "-v", nargs = 1, help = "string to control output:p(layers), i(llegal swaps), s(waps)")
parser.add_argument('--rounds','-r', default = 4, type = int, help = "Number of rounds (4)")
parser.add_argument('--tries', '-t', default = 40, type = int, help ="Number of tries to find minimum 40)")
args = parser.parse_args()
shuffle = not args.noshuffling
verbosity = args.verbose[0]
verbosePlayers = verbosity and 'p' in verbosity
verboseIllegal = verbosity and'i' in verbosity
verboseSwaps = verbosity and's' in verbosity

rounds = args.rounds


# Run the whole process
best = None
for run in range(args.tries):
    
    # Distribute the players to give everyone the same number of games
    with open(args.file[0], "r") as jsonfile: 
        players = json.load(jsonfile)
    size = len(players)
    while(True):
        games = {}
        for player in players: games[player['name']] = 0

        if shuffle: random.shuffle(players)
        for player in players:
            randomPlayers = players[:]
            randomPlayers.remove(player)
            if shuffle:random.shuffle(randomPlayers)
            player['opp'] = []
            for p in randomPlayers:
                pName = p['name']
                if len(player['opp']) >= rounds: break
                if games[pName] < rounds:
                    player['opp'].append(pName)
                    games[pName] += 1
                    
        ok = True
        for player in players:
            if games[player['name']] != rounds:
                ok = False
                break;
        if ok: break

    # Put players into dictionary

    playerD = {}
    for player in players:
        pName = player['name']
        playerD[pName] = player
        del player['name']

    # Add the array of idsx and the sumidx for each player
      
    for pName, player in playerD.items():
        idxs = []
        sumidx = 0
        for p in player['opp']:
            idx = playerD[p]['idx']
            idxs.append(idx)
            sumidx += idx
        player['idxs'] = idxs
        player['sumidx'] = sumidx

    # Now start swapping to make inprovements

    # First find highest and lowest
    for iter in range(30):
        if verbosePlayers:
            for pName, player in playerD.items():
                print(pName, player)
        
        high = None
        averageSumidx = 0
        for pName, player in playerD.items():
            averageSumidx += player['sumidx']
            if high == None:
                high = pName
                low = pName
            elif player['sumidx'] > playerD[high]['sumidx']:
                high = pName
            elif player['sumidx'] < playerD[low]['sumidx']:
                low = pName
        averageSumidx = averageSumidx/size
        if verbosePlayers: print ("averageSumidx:", averageSumidx, ", low and high:", low, high)

        # for those which can be swopped work out the effect
        mind2 =  None
        for lowerIndex in range(rounds):
            lowerEle = playerD[low]['opp'][lowerIndex]
            for higherIndex in range(rounds):
                higherEle = playerD[high]['opp'][higherIndex]
                if verboseIllegal: print ("Consider indices", lowerIndex, higherIndex)
                if lowerEle == higherEle:
                    if verboseIllegal: print ("Same contents", lowerEle, higherEle)
                elif low == higherEle:
                    if verboseIllegal: print ("low", low, "higherEle", higherEle)
                elif high == lowerEle:
                    if verboseIllegal: print("high", high, "lowerEle", lowerEle)
                elif lowerEle in playerD[high]['opp']:
                    if verboseIllegal: print("lowerEle", lowerEle, "in", playerD[high]['opp'], "so can't swap",  lowerEle, "at", low, "with", higherEle, "at",  high)
                elif higherEle in playerD[low]['opp']:
                    if verboseIllegal: print("higherEle", higherEle, "in", playerD[low]['opp'], "so can't swap",  lowerEle, "at", low, "with", higherEle, "at",  high)
                else:
                    sumidxLow = playerD[low]["sumidx"] - playerD[lowerEle]['idx'] + playerD[higherEle]['idx']
                    sumidxHigh = playerD[high]["sumidx"] - playerD[higherEle]['idx'] + playerD[lowerEle]['idx']
                    d2 = (sumidxLow - averageSumidx) ** 2 + (sumidxHigh - averageSumidx) **2
                    if mind2 == None or d2 < mind2:
                        mind2 = d2
                        eles = (lowerIndex, low, higherIndex, high)
                        if verboseSwaps: print(d2,sumidxLow,sumidxHigh)
        lowerIndex, low, higherIndex, high = eles
        if verboseSwaps: print("For mind2 of", mind2, "swap", lowerIndex, "at", low, "with", higherIndex, "at",  high, "lowerops, higherops are:",playerD[low]['opp'] ,playerD[high]['opp'])
        pLow = playerD[low]
        pHigh = playerD[high]

        if verboseSwaps: print("pLow", pLow, "pHigh", pHigh,  pLow['opp'][lowerIndex],  pHigh['opp'][higherIndex] )
        newLow = pHigh['opp'][higherIndex]
        newHigh = pLow['opp'][lowerIndex]

        pLow['opp'][lowerIndex] = newLow
        pHigh['opp'][higherIndex] = newHigh

        newidxLow = pHigh['idxs'][higherIndex]
        newidxHigh = pLow['idxs'][lowerIndex]

        pLow['idxs'][lowerIndex] = newidxLow
        pHigh['idxs'][higherIndex] = newidxHigh

        lowsumidx = 0
        highsumidx = 0
        for index in range(rounds):
            lowsumidx += pLow['idxs'][index]
            highsumidx += pHigh['idxs'][index]
        pLow['sumidx'] = lowsumidx/rounds
        pHigh['sumidx'] = highsumidx/rounds
        
    for pName, player in playerD.items():
        print(pName, player)

    sumidx2 = 0
    for player in players:
        sumidx2 += player['sumidx']**2
    print ("sumidx2:", sumidx2)

    if not best or sumidx2 < best:
        best = sumidx2
        bestGames = dict(playerD)

print(best)
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
for pname, player in bestGames.items():
    left = numbers[pname]
    opp = player['opp']
    for i in range(rounds):
        right = numbers[opp[i]]
        print(left, right, end = " ")
print()
            
 
        

    
    

    

    

