import os
import sys
import datetime
import csv
import codecs
import cStringIO
import errno

class SearchTermCleanser:
    def __init__ (self, fileName):
        self.inputFile = fileName
        self.outputFile =  open("Search_Term_Output.csv", 'wb')
        self.csvOutput = csv.writer(self.outputFile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        self.rowDict = {}
        self.header = []



    def run(self):
        """ Runs the program """
        self.parseCSV()
        self.csvOutput.writerow(self.header)
        self.processCSV()



    def parseCSV(self):
        """ Parses the original csv into a Dictionary"""
        with open(self.inputFile, 'rb') as f:
            readFile = csv.reader(f, delimiter=',', quotechar='"')
            self.header = readFile.next()

            #for each line in file, check if in dictionary
            #if not, add new key to dictionary
            #append value to key's list
            for row in readFile:
                if row[0] in self.rowDict:
                    self.rowDict[row[0]].append(row)

                else:
                    self.rowDict[row[0]] = []
                    self.rowDict[row[0]].append(row)



    def processCSV(self):
        """ Runs functions to process the input CSV into the Output CSV """
        for key in self.rowDict:
            lst = self.rowDict[key]
            row = []

            if (len(lst) == 1):
                row = lst[0]

            else:
                #Search Term (key)
                searchTerm = key

                #Total unique searches
                uniqueSearches = self.AddTotalUniqueSearches(lst)

                #results pageviews/search
                results = self.AverageResults(lst)

                #%search exits
                searchExits = ""
                for i in lst:
                    searchExits = searchExits + i[3] + ": "
                searchExits = searchExits[:-2]

                #%search refinements
                searchRefinements = ""
                for i in lst:
                    searchRefinements = searchRefinements + i[4] + ": "
                searchRefinements = searchRefinements[:-2]

                #time after search
                timeAfterSearch = self.AverageTimeAfterSearch(lst)

                #average search depth
                searchDepth = self.AverageSearchDepth(lst)

                row = [searchTerm, uniqueSearches, results, searchExits, searchRefinements, timeAfterSearch, searchDepth]

            self.csvOutput.writerow(row)



    def add(self, lst, index):
        total = 0
        for i in lst:
            num = i[index]
            convertedNum = 0
            try:
                convertedNum = int(num)
            except:
                convertedNum = 1
            total += convertedNum
        return total



    def AddTotalUniqueSearches(self, lst):
        """Adds up the Total Unique Searches for a row """
        return self.add(lst, 1)





    def AverageResults(self, lst):
        """ Calculates the average number of results per pageview """
        length = len(lst)
        total = self.add(lst, 2)
        return total / length



    def AverageTimeAfterSearch(self, lst):
        """ Calculates the average time after search """
        seconds = 0
        minutes = 0
        hours = 0
        length = len(lst)
        for i in lst:
            strSeconds = i[5][5:7]
            strMinutes = i[5][2:4]
            strHours = i[5][0:1]
            try:
                seconds += int(strSeconds)
            except:
                seconds += 0
            try:
                minutes += int(strMinutes)
            except:
                minutes += 0
            try:
                hours += int(strHours)
            except:
                hours += 0
        seconds = seconds / length
        minutes = minutes / length
        hours = hours / length
        while (seconds >= 60):
            seconds -= 60
            minutes += 1
        while (minutes >= 60):
            minutes -= 60
            hours += 1
        time = "%02d:%02d:%02d" % (hours, minutes, seconds,)
        return time



    def AverageSearchDepth(self, lst):
        """ Calculates the average search depth """
        length = len(lst)
        total = self.add(lst, 6)
        return total / length



def main():
    if (len(sys.argv) < 2) or (len(sys.argv) > 3):
        print "Invalid number of Arguments"
    else:
        stc = SearchTermCleanser(sys.argv[1])
        stc.run()

main()