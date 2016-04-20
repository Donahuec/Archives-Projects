"""
This is a program that uses exiftool to tag a batch of images with face-tag data, using a a .csv file with the information.
The photos must be a 250X250px image of a face.
The Format of the Data File is the following"
[ID Number, First Name, Last Name, First Name at Carleton,
 Last Name at Carleton, Graduation Year, Associated Filename]
 This program tags face-data in the format that picasa uses, so
 picasa can recognize the data. Keyword tags are added for both 
 the ID number of the person, and their graduation year.

 This program is run using Exiftool on its default settings. 
 Exiftool must be added to your path, and if you are using Windows
 you may need to rename your exiftool run-file to exiftool.pl
 you can test this by typing exiftool.pl into your command prompt
 and seeing if it produces a help message. If not, then you must rename the file.
 (Given you have already added it to your path. )

 Run the program using the following format:
 python AddMetaData.py [path to DataFile] [path to Picture Directory]

"""

import os
import sys
import platform
import ast
import csv
import errno
import shutil
from PIL import Image

class AddMetaData:
    def __init__(self,argFile, dataFile, pictureDirectory):
        self.dataFile = dataFile
        self.picDir = pictureDirectory
        self.baseArray = []
        self.tagData = {}
        self.copiesFolder = os.path.join(self.picDir, "copies")
        self.fileIndex = []
        self.copyText = "_original"
        self.argFile = argFile

    def run(self):
        self.readFile()
        self.createArgFile()
        self.extractData()
        self.writeMetaData()
        self.runExif()

    def createArgFile(self):
        with open(self.argFile, 'w') as f:
            print "--------------------------------------"
            print "ArgFile initialized"
            print "--------------------------------------"  


    def readFile(self):
        """ Reads data from file and adds it to an array """
        print "--------------------------------------"
        print "Reading Data File"
        print "--------------------------------------"
        with open(self.dataFile, 'rb') as csvfile:
            reader = csv.reader(csvfile, delimiter=',', quotechar='"')
            for row in reader:
                self.baseArray.append(row)

    def extractData(self):
        """ Takes the data from readFile() and turns it into usable tag data """
        print "--------------------------------------"
        print "Extracting Tag Data"
        print "--------------------------------------"
        for item in self.baseArray:
            idNum = item[0]
            firstName = item[1]
            lastName = item[2]
            firstAtCarl = item[3]
            lastAtCarl = item[4]
            year = item[5]
            fileName = item[6]
            tagFirst = ""
            tagLast = ""
            if firstAtCarl:
                firstName = firstAtCarl
            if lastAtCarl:
                lastName = lastAtCarl  
            name = firstName + " " + lastName + " " + idNum
            #try:
                #im = Image.open(os.path.join(self.picDir, fileName))
                #width, height = im.size
                #self.tagData[fileName] = [name, idNum, year, str(width), str(height)]
            #except:
            self.tagData[fileName] = [name, idNum, year]

    def writeMetaData(self):
        """ Use exiftool to write meta data to images """
        print "--------------------------------------"
        print "Writing Meta Data to Images"
        print "--------------------------------------"
        #save original location so that you can return to your starting location after 
        #running Exiftool
        original_location = os.getcwd()
        parent = self.picDir
        exifName = ""
        #check what os the user is running to account for terminal command differences
        if platform.system() == "Windows":
            exifName = "exiftool.pl"
        else:
            exifName = "./exiftool"
        #make sure the directories are in the correct format
        parent = parent.strip().strip("'").strip('"')
        #navigate to the file that the user's exif program is located in     
        #make a list of all of the folders in this directory
        path_list = [x for x in os.listdir(parent)]
        exifName + " -stay_open True -@ " + self.argFile
        for item in path_list:
            if self.copyText not in item:
                data = self.tagData[item]
                path = os.path.join(parent, item)
                with open(self.argFile, "a+") as f:
                    cmd ="-q\n-overwrite_original\n-RegionName=" + data[0] +  '\n' + path + '\n'
                    f.write(cmd)
                    #cmd = "-RegionType=Face"+  '\n' + path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAppliedToDimensionsW=" + data[3] + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAppliedToDimensionsH=" + data[4]  + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAppliedToDimensionsUnit=pixel" + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAreaX=0.5" + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAreaY=0.5" + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAreaW=1"+  '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAreaH=1" + '\n'+ path + '\n'
                    #f.write(cmd)
                    #cmd = "-RegionAreaUnit=normalized"+ '\n'+ path + '\n'
                    #f.write(cmd)
                    #Adds ID number and Class Year
                    cmd = "-Subject="+ data[1]+","+data[2] + '\n'+ path + '\n'
                    f.write(cmd)
                
                    f.write("-execute\n")
        print "--------------------------------------"
        print "ArgFile Made"
        print "--------------------------------------"


    def runExif(self):
        print "--------------------------------------"
        print "Running ExifTool"
        print "--------------------------------------"
        exifName = ""
        if platform.system() == "Windows":
            exifName = "exiftool.pl"
        else:
            exifName = "./exiftool"
        cmd = exifName + " -@ " + self.argFile
        os.system(cmd)
        print "--------------------------------------"
        print "Complete"
        print "--------------------------------------"



                
def usageMessage():
    print "--------------------------------------"
    print "Usage Message"
    print "--------------------------------------"
    print "This is a program that uses exiftool to tag a batch of images with face-tag data, using a a .csv file with the information."
    print "The photos must be a 250X250px image of a face."
    print "The Format of the Data File is the following"
    print "[ID Number, First Name, Last Name, First Name at Carleton,"
    print  "Last Name at Carleton, Graduation Year, Associated Filename]"
    print  "This program tags face-data in the format that picasa uses, so"
    print "picasa can recognize the data. Keyword tags are added for both" 
    print  "the ID number of the person, and their graduation year."
    print "--------------------------------------"
    print  "This program is run using Exiftool on its default settings." 
    print  "Exiftool must be added to your path, and if you are using Windows"
    print  "you may need to rename your exiftool run-file to exiftool.pl"
    print  "you can test this by typing exiftool.pl into your command prompt"
    print  "and seeing if it produces a help message. If not, then you must rename the file."
    print  "(Given you have already added it to your path. )"
    print "--------------------------------------"
    print  "Run the program using the following syntax:"
    print "--------------------------------------"
    print  "python AddMetaData.py [path to DataFile] [path to Picture Directory]"
    print "--------------------------------------"


def main():
    if len(sys.argv) <= 3:
        usageMessage()
    else:
        tag = AddMetaData(sys.argv[1], sys.argv[2], sys.argv[3])
        tag.run()

main()