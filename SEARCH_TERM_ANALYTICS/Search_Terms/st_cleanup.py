# ***************************************************************************
# st_cleanup.py
# Summer 2016
# Caleb Braun and Caitlin Donahue
#
# Takes in a CSV file of Google Analytics search term data and processes it.
# Removes internal searches (searches by ID) and combines duplicates.
#
# ***************************************************************************

import os
import sys
import csv

class SearchTermCleanser:
    def __init__ (self, fileName, fileOut = "default.csv"):
        try:
            self.inputFile = open(fileName, 'rU')
        except:
            print("Invalid file name!")
            exit(0)

        print("Creating output file " + fileOut)
        # Makes this program compatable with Python 2 or 3
        if sys.version_info[0] < 3:
            self.outputFile =  open(fileOut, 'wb')
        else:
            self.outputFile =  open(fileOut, 'w')

        self.csvOutput = csv.writer(self.outputFile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        self.rowDict = {}
        self.header = []



    def run(self):
        """ Runs the program """
        self.parseCSV()
        print("Writing header")
        self.csvOutput.writerow(self.header)
        print("Writing output...")
        self.processCSV()
        print("Finished!")


    def parseCSV(self):
        """ Parses the original csv into a Dictionary"""
        readFile = csv.reader(self.inputFile, delimiter=',', quotechar='"')
        self.header = next(readFile)
        if len(self.header) < 8: self.header.append("Alternate Spellings")

        # for each line in file, check if in dictionary
        # if not, add new key to dictionary
        # append value to key's list
        for row in readFile:
            # Have a consistent format for all search terms
            row[0] = self.homogenize(row[0])
            if len(row) < 8: row.append(0)

            if row[0] in self.rowDict:
                self.rowDict[row[0]].append(row)
            else:
                self.rowDict[row[0]] = [row]


    def processCSV(self):
        """ Runs functions to process the input CSV into the output CSV """
        for key in self.rowDict:
            lst = self.rowDict[key]
            valid = self.testSearchValidity(lst[0][0])
            row = []

            # If there are no double entries in the row dictionary
            if (len(lst) == 1):
                row = lst[0]
            else:
                searchTerm = key
                uniqueSearches = self.addResults(lst, 1)
                pageviews = self.averageResults(lst, 2)
                searchExits = str(self.averageResults(lst, 3)) + "%"
                searchRefinements = str(self.averageResults(lst, 4)) + "%"
                timeAfterSearch = self.averageTimeAfterSearch(lst)
                searchDepth = self.averageResults(lst, 6)
                altSpellings = self.addResults(lst, 7) + len(lst) - 1

                row = [searchTerm, uniqueSearches, pageviews, searchExits, searchRefinements, timeAfterSearch, searchDepth, altSpellings]

            if valid:
                self.csvOutput.writerow(row)



    def addResults(self, lst, index):
        total = 0
        for i in lst:
            num = i[index]
            try:
                total += int(num)
            except:
                print("Error parsing number: %s" % (i[index]))
        return total


    def averageResults(self, lst, index):
        """ Calculates the average number of results based on all searches """
        weightedAvg = 0.0
        numSearches = 0

        for i in lst:
            weight = int(i[1])
            value = float(i[index].replace("%", ""))
            numSearches += weight
            weightedAvg += value * weight

        weightedAvg /= numSearches
        weightedAvg = round(weightedAvg, 2)
        return weightedAvg


    def averageTimeAfterSearch(self, lst):
        """ Calculates the average time after search """
        totalSeconds = 0
        numSearches = 0

        for i in lst:
            weight = int(i[1])
            numSearches += weight
            strHours, strMinutes, strSeconds = i[5].split(":")
            totalSeconds += int(strSeconds) * weight
            totalSeconds += int(strMinutes) * weight * 60
            totalSeconds += int(strHours) * weight * 3600

        totalSeconds /= numSearches
        minutes, seconds = divmod(totalSeconds, 60)
        hours, minutes = divmod(minutes, 60)

        time = "%02d:%02d:%02d" % (hours, minutes, seconds)
        return time


    def homogenize(self, st):
        """ Standardizes capitalisation and removes surrounding quotes """
        st = st.title()
        if st.startswith('"') and st.endswith('"'):
            st = st[1:-1]
        if "'S" in st:
            st = st.replace("'S", "'s")
        st = st.replace("\\", "")   # A common search error
        return st


    def testSearchValidity(self, searchTerm):
        """ Determines whether search term was useful """
        if searchTerm.isdigit():            # Search term is an ID number
            return False
        elif searchTerm.count('/') > 1:     # Search term is a specific date
            return False
        elif '_' in searchTerm:             # Search term is an ID
            return False
        else:
            return True


def main():
    if len(sys.argv) == 2:
        stc = SearchTermCleanser(sys.argv[1])
    elif len(sys.argv) == 4 and sys.argv[2] == "-o":
        stc = SearchTermCleanser(sys.argv[1], sys.argv[3])
    else:
        print("Invalid arguments! Usage: ")
        print("st_cleanup.py <file> [-o <file>]")
        exit(0)

    print("Parsing " + sys.argv[1] + "...")
    stc.run()

main()
