import sys
import csv
import urllib2
from BeautifulSoup import BeautifulSoup

# This class returns the names of all the webpages given in the pages dataset.
# It requires installing BeautifulSoup to run.
class PageLookup:
    def __init__ (self, fileName):
        self.inputFile = fileName

    def readFile():
        with open(self.inputFile, 'rb') as f:
            readFile = csv.reader(f, delimiter=',', quotechar='"')
            header = readFile.next()

            # Test data
            for i in [  "/?p=digitallibrary/digitalcontent&id=76626",
                        "/?p=digitallibrary/digitalcontent&id=76877",
                        "/?p=digitallibrary/digitalcontent&id=77068",
                        "/?p=digitallibrary/digitalcontent&id=77070",
                        "/?p=digitallibrary/digitalcontent&id=77084",
                        "/?p=digitallibrary/digitalcontent&id=77183",
                        "/?p=digitallibrary/digitalcontent&id=77217",
                        "/?p=digitallibrary/digitalcontent&id=77522",
                        "/?p=digitallibrary/digitalcontent&id=77936",
                        "/?p=digitallibrary/digitalcontent&id=78092",
                        "/?p=digitallibrary/digitalcontent&id=78305",
                        "/?p=digitallibrary/digitalcontent&id=78374",
                        "/?p=digitallibrary/digitalcontent&id=78434",
                        "/?p=digitallibrary/digitalcontent&id=78463",
                        "/?p=digitallibrary/digitalcontent&id=78476",
                        "/?p=digitallibrary/digitalcontent&id=78982",
                        "/?p=digitallibrary/digitalcontent&id=79093",
                        "/?p=digitallibrary/digitalcontent&id=79731"]:

                # searchURL = baseURL + readFile.next()[0]

                print fetchHTML(i)

    def formatTitle(rawTitle):
        pageTitle = rawTitle
        if len(rawTitle) > 25 and rawTitle[-25:] == "Carleton College Archives":
            pageTitle, excess = rawTitle.split('|')
        pageTitle = pageTitle.replace("&nbsp", "")
        pageTitle = pageTitle.replace("&#039;", "\'")
        return pageTitle

    def fetchHTML(link):
        baseURL = "https://archivedb.carleton.edu"
        searchURL = baseURL + link
        soup = BeautifulSoup(urllib2.urlopen(searchURL))
        return formatTitle(soup.title.string)


def main():
    if (len(sys.argv) < 2) or (len(sys.argv) > 3):
        print "Running on file \"Analytics_Carleton_College_Archives_Pages_20140620-20150720.csv\""
        lookerUpper = PageLookup("Analytics_Carleton_College_Archives_Pages_20140620-20150720.csv")
    else:
        lookerUpper = PageLookup(sys.argv[1])
    lookerUpper.readFile()
