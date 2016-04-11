"""
Written by Caitlin Donahue
Program to format file names for upload to arcasearch
"""

import sys
import os
import re
import errno
import codecs
import cStringIO
import string


class ArcaUpload:
    def __init__(self, location):
        self.fileLocation = location
        self.errorFile = os.path.join(self.fileLocation, "errors.txt")
        self.renameFile = ""
        self.errors = []
        self.curDay = ""
        self.curMonth = ""
        self.curYear = ""

    def run(self):
        """ runs the program"""
        self.makeErrorFile()
        self.createRenameFile()
        self.loopThroughDirs(self.fileLocation)

    def createRenameFile(self):
        """ Creates a file that stores the renames"""
        renameFile = "renames.txt"
        i = 0
        while os.path.isfile(os.path.join(self.fileLocation, renameFile)):
            i += 1
            renameFile = "renames" + "_" + str(i) + ".txt"
        self.renameFile = os.path.join(self.fileLocation, renameFile)
        with open(self.renameFile, "ab") as f:
            f.write("old\tnew\n")


    def loopThroughDirs(self, curdir):
        """loops through directories, will loop through top level and Issue Level, exit when finding a folder with PDF in the name""" 
        dirList = os.listdir(curdir)
        for item in dirList:
            if "._" not in item and ".DS_Store" not in item and "Thumbs.db" not in item and os.path.isdir(os.path.join(curdir, item)):
                if "pdf" in item.lower() and "final" in item.lower():
                    self.curDay = item[13:15]
                    self.curMonth = item[10:12]
                    self.curYear = item[16:20]
                    self.handlePDFSDir(os.path.join(curdir, item))
                else:
                    self.loopThroughDirs(os.path.join(curdir, item))


    def handlePDFSDir(self, curdir):
        """loops through list of pdfs"""
        print "----",curdir,"-----"
        pdfList = os.listdir(curdir)
        for pdf in pdfList:
            if "._" not in pdf and ".DS_Store" not in pdf and "Thumbs.db" not in pdf and ".pdf" in pdf:
                page, error = self.grabPDFInfo(pdf, curdir)
                if not error:
                    self.tryRenamePDF(page, curdir, pdf)


    def grabPDFInfo(self, pdf, curdir):
        """extracts information about the pdf using the file name"""
        pageRE = re.compile(r"(P|p)[0-9]*(\.|_)")
        pageMatch = pageRE.search(pdf)
        page = ""
        if not pageMatch:
            self.addToErrorFile(pdf, curdir, "Improper page Format")
            return page, True
        
        page = pageMatch.group()[1:-1]
        try:
            page = "{:0>3}".format(int(page))
        except: 
            self.addToErrorFile(pdf, curdir, "Improper page Format")
            return page, True
        return page, False

    def tryRenamePDF(self, page, curdir, pdf):
        """Renames PDF into the proper format car_YYYY_MM_DD_PPP.pdf"""
        newName = "car" + "_" + self.curYear +"_"+ self.curMonth +"_"+ self.curDay +"_"+ page + ".pdf"
        old = os.path.join(curdir, pdf)
        new = os.path.join(curdir, newName)
        if not os.path.isfile(new):
            self.renamePDF(old, new, pdf, curdir)
        else:
            self.addToErrorFile(pdf, curdir, "Rename already exists")

    def renamePDF(self, old, new, pdf, curdir):
        try:
            os.rename(old, new)
            with open(self.renameFile, "ab") as f:
                f.write(os.path.relpath(old, self.fileLocation) + "\t" + os.path.relpath(new, self.fileLocation) +"\n")
        except:
            if not retry:
                print "failed"
                print "adding to error file..."
                self.addToErrorFile(pdf, curdir, "Failed to rename")

    def makeErrorFile(self):
        """makes an error file if necessary""" 
        try:
            with open(self.errorFile, "a+") as f:
                print "writing to error file"
            with open(self.errorFile, "r+b") as f:
                for line in f:
                    arr = string.split(line, "\t")
                    if arr[0] not in self.errors:
                        self.errors.append(arr[0])
        except OSError as exception:
            if exception.errno != errno.EEXIST:
                raise


    def addToErrorFile(self, pdf, curdir, errorType):
        """adds to an error file if the rename failed for any reason"""
        if pdf not in self.errors:
            with open(self.errorFile, "a") as f:
                f.write(os.path.join(os.path.relpath(curdir, self.fileLocation), pdf) + "\t" + errorType + "\n")


def usageMessage():
    print "This is a program meant to prepare carletonian files for upload to Arcasearch"
    print "use this program in with the following syntax:"
    print "<< python arca_upload.py FILES-TO-RUN-ON FILEFORMAT DELIMETER >>"
    print "the delimiter is the character that separates different fields in the filenames."
    print "I have noticed that most of the time the delimiter is '.' "
    print "The date format should have spaces for PAGE, DAY, MONTH, YEAR"
    print "so if the files are int the format P1.1.23.12.pdf, the date format"
    print "would be 'PAGE.MONTH.DAY.YEAR' and the delimiter would be '.'"
    print "keep in mind it is case sensitive, so use all capitals"
    print "with tha example date the fie would be renamed to car2012_01_23_001.pdf"


def main():
    if len(sys.argv) < 2:
        usageMessage()

    else:
        a = ArcaUpload(sys.argv[1])
        a.run()
main()