# This program takes a CSV file with a list of archive URLs in the first
# column. It fetches the titles of the pages accessed and prints them out. A
# more efficient way to get this data is with page_titles.py however this
# program is here for reference and an alternative way to get these titles.

import sys
import csv
import re
import urllib2
from BeautifulSoup import BeautifulSoup

# This class returns the names of all the webpages given in the pages dataset.
# It requires installing BeautifulSoup to run the html fetcher.
class TitleFetcher:
    def __init__ (self, inFile):
        self.sourceURLFile =  open(inFile, 'rU')

    def readFile(self):
        readFile = csv.reader(self.sourceURLFile, delimiter=',', quotechar='"')
        header = readFile.next()

        for row in readFile:
            # Instead of printing this could also be written to a file
            print fetchPageTitle(row[0])


    # Formats page title. For example:
    # "Women&#039;s Rugby | Carleton College Archives" becomes "Women's Rugby"
    def formatTitle(self, rawTitle):
        pageTitle = rawTitle
        if len(rawTitle) > 25 and rawTitle[-25:] == "Carleton College Archives":
            pageTitle, excess = rawTitle.split('|')
        pageTitle = pageTitle.replace("&nbsp", "")
        pageTitle = pageTitle.replace("&#039;", "\'")
        return pageTitle


    # Fetches page's raw html and returns the formatted title
    def fetchPageTitle(self, link):
        baseURL = "https://archivedb.carleton.edu"
        searchURL = baseURL + link
        rawHTML = BeautifulSoup(urllib2.urlopen(searchURL))
        pageTitle = rawHTML.title.string
        return self.formatTitle(pageTitle)


def main():
    if (len(sys.argv) < 2) or (len(sys.argv) > 3):
        print "Please enter a filename."
        exit(0)
    else:
        tf = TitleFetcher(sys.argv[1])
        tf.readFile()

main()
