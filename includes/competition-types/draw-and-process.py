#!/usr/bin/env python

import math, random, argparse,sys

def seedclux(i, n): # match_nr, log2(participants)
    #decompose i = 2^k + r where 0 <= r < 2^k
    k = int(math.floor(math.log(i,2)))
    r = i - (1 << k)

    if (r == 0): return 1 << n-k

    nr = bin(i - 2*r)[2:][::-1]
    return int(nr,2) << n-len(nr) | 1 << n-k-1
    
def seedpos(i,n):
    if i%2 == 0: return (seedclux(2*n-i,5))
    else:  return (seedclux(i+1,5))
    
def allocate_byes(byes,minv,count,numbyes):
    halfc = count//2
    halfn = numbyes//2
    if numbyes == 0: return None
    if count == 1:
        byes[minv] = '-'
        return None
    
    if (numbyes % 2 == 1):
        if (random.randint(0,1) == 0):
            allocate_byes(byes,minv,halfc,halfn)
            allocate_byes(byes,minv+halfc,halfc,halfn+1)
        else:
            allocate_byes(byes,minv,halfc,halfn+1)
            allocate_byes(byes,minv+halfc,halfc,halfn)
    else:
        allocate_byes(byes,minv,halfc,halfn)
        allocate_byes(byes,minv+halfc,halfc,halfn)



parser = argparse.ArgumentParser(description = "Derives the input lists for the two halves of the competition")
parser.add_argument('-s', '--seed', action="store_true", help="Take the input list as being in seeded order" )
parser.add_argument('-r', '--random', action = "store_true", help="Randomise the input list - e.g. for handicap games")
parser.add_argument('-f', '--file', help = "file to use rathen than stdin")
args = parser.parse_args()
shuffle = args.random
seed = args.seed
inputfile = args.file

if shuffle & seed:
	exit("Only specify at most one of random and seed")

contents = []
file = open(inputfile,'r') if inputfile else sys.stdin
for line in file:
	line = line.strip()
	if line: contents.append(line.strip())
file.close()

num = len(contents)
power = 1
n = 0
while (power < num):
    power = power*2
    n +=1

numbyes = power - num
	
if seed:
	seeded = []
	for i in range(power):  
	    pos = seedpos(i,power)-1
	    if pos < num : seeded.append(contents[pos])
	contents = seeded

if shuffle:	
	random.shuffle(contents)

draw=[None]*power
allocate_byes(draw,0,power,numbyes)

pos = 0
for value in contents:
	pos = draw.index(None,pos)
	draw[pos] = value
  
print ("Draw")
for i in range(len(draw)): print(i+1, ": ", draw[i])

process = [None]*power
fmt="0" + str(n) + "b"
for key in range(power) :
	pkey = int(format(key,fmt)[::-1],2)
	process[pkey] = draw[key]

print ("Process")
for i in range(len(process)): print(i+1, ": ", process[i])

