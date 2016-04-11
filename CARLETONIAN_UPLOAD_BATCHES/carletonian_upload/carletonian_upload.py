"""
program to create import files for uploading the Carletonian files
written by Caitlin Donahue
"""

import os
import sys
import errno

class CarletonianUpload:
    def __init__(self, carletonianLocation, saveLocation):
        self.carletonianLocation = carletonianLocation
        self.saveLocation = os.path.join(saveLocation, "carletonian_import_files")
        self.directoryList = os.listdir(self.carletonianLocation)
        self.saveFileName = "carletonian_Import_File_"
        self.curImportFileNum = 0
        self.curImportFile = ""
        self.curIndex = 0
        self.maxIndex = 2000
        self.publisher = "Carleton College"
        self.addedBy = "nwilson"
        self.header = "TitleAndDate\tYear\tDate\tAlternate Title\tPublisher\tAdded by\tIdentifier\n"

    def run(self):
        """ Runs the program """
        self.createImportFileLocation()
        self.createImportFile()
        self.loopThroughFiles()


    def createImportFileLocation(self):
        """ creates a directory to hold the import files """
        created = False
        index = 0
        save = self.saveLocation
        while not created:
            try:
                os.makedirs(save)
                created = True
            except OSError as exception:
                if exception.errno != errno.EEXIST:
                    raise
                else:
                    index += 1
                    save = self.saveLocation + str(index)
        self.saveLocation = save

    def createImportFile(self):
        """ creates an import file"""
        self.curImportFileNum += 1
        importName = os.path.join(self.saveLocation, self.saveFileName + str(self.curImportFileNum) + ".txt")
        self.curImportFile = importName
        with open(self.curImportFile, "wb") as importFile:
            importFile.write(self.header)

    def loopThroughFiles(self):
        """ loops through the files in carletonianLocation"""
        for item in self.directoryList:
            self.updateCount(item)

    def updateCount(self, curdir):
        """ checks how many files are in the current directory and updates the count """
        toAdd = len(os.listdir(os.path.join(self.carletonianLocation, curdir)))
        self.curIndex += toAdd
        self.addToImportFile(curdir)
        if self.curIndex >= self.maxIndex:
            self.createImportFile()
            self.curIndex = 0

    def addToImportFile(self, curdir):
        """ adds a new row to the current import file """
        year, month, day = self.calculateDateFields(curdir)
        date = str(year) + "-" + str(month) + "-" + str(day)
        titleAndDate = "Carletonian " + date
        t = "\t"
        row = titleAndDate + t + year + t + date + t + t + self.publisher + t + self.addedBy + t + curdir + "\n"
        with open(self.curImportFile, "ab") as importFile:
            importFile.write(row)

    def calculateDateFields(self, curdir):
        """ Calclates what dates need to be entered into the date field"""
        year = curdir[4:8]
        month = curdir[9:11]
        day = curdir[12:14]
        return year, month, day

def usageMessage():
    """ Usage message here"""
    print "run with the syntax:"
    print "python carletonian_upload.py pathToCarletonianFiles pathToSaveLocation"
    print "will create a folder in the save location and save the import files there"

def main():
    if len(sys.argv) != 3:
        usageMessage()
    else:
        c = CarletonianUpload(sys.argv[1],sys.argv[2])
        c.run()

main()