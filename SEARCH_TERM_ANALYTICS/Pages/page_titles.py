import sys
import csv
import re
import urllib2
from BeautifulSoup import BeautifulSoup

# This class returns the names of all the webpages given in the pages dataset.
# It requires installing BeautifulSoup to run the html fetcher.
class PageLookup:
    def __init__ (self, fileName):
        self.sourceURLFile =  open(fileName, 'rU')

        # Helpful if datafiles are sorted in ascending order with a header row
        self.collectionsFile = open("tables/tblCollections_Collections.csv", 'rU')
        self.accessionsFile = open("tables/tblAccessions_Accessions.csv", 'rU')
        self.outFile = open("Pages_Output.csv", 'wb')

    def readFile(self):
        # Initialize readers and writers
        writeData = csv.writer(self.outFile, delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        readFile = csv.reader(self.sourceURLFile, delimiter=',', quotechar='"')
        collectionsData = csv.reader(self.collectionsFile, delimiter=',', quotechar='"')
        accessionsData = csv.reader(self.accessionsFile, delimiter=',', quotechar='"')
        h = readFile.next()
        d = collectionsData.next()
        accessionsData.next()

        # d[5] = title, d[7] = date, d[14] = scope
        header = [h[0], h[1], h[2], d[5], d[7], d[14], h[3], h[4], h[6]]
        writeData.writerow(header)

        #Create dictionaries of collectionsData and accessionsData (key = ID)
        collections = {}
        accessions = {}
        for row in collectionsData:
            if row[0].isdigit():
                collections[row[0]] = row
        for row in accessionsData:
            if row[0].isdigit():
                accessions[row[0]] = row

        for row in readFile:
            if "collections/controlcard" in row[0]:
                idNum = re.search('id=(\d*)', row[0]).group(1)
                print idNum
                try:
                    title = collections[idNum][5]
                    date = collections[idNum][7]
                    scope = collections[idNum][14]
                except:
                    print "ERROR with ID " + idNum
                    title = "COLLECTION ID NOT FOUND"
                    date = "Not Found"
                    scope = "Not Found"
                writeData.writerow([row[0], row[1], row[2], title, date, scope, row[3], row[4], row[6]])
            elif "accessions/accession" in row[0]:
                idNum = re.search('id=(\d*)', row[0]).group(1)
                print idNum
                try:
                    title = accessions[idNum][3]
                    date = accessions[idNum][5]
                    scope = accessions[idNum][17]
                except:
                    print "ERROR with ID " + idNum
                    title = "ID NOT FOUND"
                    date = "Not Found"
                    scope = "Not Found"
                writeData.writerow([row[0], row[1], row[2], title, date, scope, row[3], row[4], row[6]])


def main():
    if (len(sys.argv) < 2) or (len(sys.argv) > 3):
        f = "Analytics_Carleton_College_Archives_Pages_20140620-20150720.csv"
        print "Running on file \"%s\"" %(f)
        lookerUpper = PageLookup(f)
    else:
        lookerUpper = PageLookup(sys.argv[1])
    lookerUpper.readFile()

main()
