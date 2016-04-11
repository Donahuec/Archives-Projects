import os
import sys
import csv
import re
import shutil
import ast
import codecs
import cStringIO
import errno



class MapsContent:
    def __init__(self):
        self.ID = ""
        self.ItemNum = ""
        self.Date = ""
        self.Title = ""
        self.Description = ""
        self.Series = ""
        self.Originals = ""
        self.PrevID = ""
        self.Creator = ""
        self.Note = ""
        self.AdditionalDate = ""
        self.Physical = ""
        self.Additional = ""
        self.UnitID = ""
        self.Creators = ""

    def addRow(self,arr):
        self.ID = arr[0]
        self.ItemNum = arr[1]
        self.Date = arr[2]
        self.Title = arr[3]
        self.Description = arr[4]
        self.Series = arr[5]

    def getID(self):
        return self.ID

    def getCreators(self):
        return self.Creators

    def addCreator(self, creator):
        if self.Creators == "":
            self.Creators = creator
        else:
            self.Creators = self.Creators + "; " + creator

    def addUserField(self, title, value):
        if title == "Originals or Copies Note":
            if self.Originals == "":
                self.Originals = value
            else:
                self.Originals = self.Originals + "; " + value
        elif title == "PreviousUnitID":
            if self.PrevID == "":
                self.PrevID = value
            else:
                self.PrevID = self.PrevID + "; " + value
        elif title == "Creator":
            if self.Creator == "":
                self.Creator = value
            else:
                slef.Creator = self.Creator + "; " + value
        elif title == "Note":
            if self.Note == "":
                self.Note = value
            else:
                self.Note = self.Note + ", " + value
        elif title == "Additional date Information" or title == "Additional date information" or title == "Additional Date Information":
            if self.AdditionalDate == "":
                self.AdditionalDate = value
            else:
                self.AdditionalDate = self.AdditionalDate + "; " + value
        elif title == "Physical Description" or title == "Physical description":
            if self.Physical == "":
                self.Physical = value
            else:
                self.Physical = self.Physical + "; " + value 
        elif title == "Additional Information" or title == "Additional information":
            if self.Additional == "":
                self.Additional = value
            else:
                self.Additional = self.Additional + "; "+  value
        elif title == "UnitID":
            if self.UnitID == "":
                self.UnitID = value
            else:
                self.UnitID = self.UnitID + "; " + value
        else: 
            print "Error, could not match field " + title + " for ID " + self.ID

    def getRow(self):
        return [self.ID, self.ItemNum, self.Date, self.Title, self.Description, self.Series, self.Originals, self.PrevID, self.Creator,
         self.Note, self.AdditionalDate, self.Physical, self.Additional, self.UnitID, self.Creators] 



class UserFields:
    def __init__(self):
        self.ID = ""
        self.ContentID = ""
        self.Title = ""
        self.Value = ""
        self.EAD = ""

    def getID(self):
        return self.ContentID
    def getTitle(self):
        return self.Title
    def getValue(self):
        return self.Value
    def addFields(self, arr):
        self.ID = arr[0]
        self.ContentID = arr[1]
        self.Title = arr[2]
        self.Value = arr[3]
        self.EAD = arr[4]

class Creators:
    def __init__(self):
        self.ID = ""
        self.creator = ""
    def getID(self):
        return self.ID
    def getCreator(self):
        return self.creator
    def addData(self, arr):
        self.ID = arr[0]
        self.creator = arr[1]

class Run:
    def __init__(self, contentFile, userFieldFile, creatorsFile):
        self.content = contentFile
        self.userField = userFieldFile
        self.creators = creatorsFile
        self.contentList = []
        self.fieldList = []
        self.creatorsList = []
        self.IDs = []
        self.header = ['Number ID','ItemNum','Date','Title','Description','Series or Sub-series', 'Originals or Copies Note',
        'Previous Unit ID', 'Creator', 'Note', 'Additional Date Information', 'Physical Description', 'Additional Information',
        'UnitID', 'Creators']
        
    def do(self):
        self.readFiles()
        self.getUserFields()
        self.getCreators()
        self.writeFile()

    def readFiles(self):
        with open(self.content, 'rU') as cf:
            reader = csv.reader(cf, delimiter=',', quotechar='"')
            for row in reader:
                cont = MapsContent()
                cont.addRow(row)
                self.contentList.append(cont)
                self.IDs.append(row[0])
        with open(self.userField, 'rb') as uf:
            reader = csv.reader(uf, delimiter=',', quotechar='"')
            for row in reader:
                fields = UserFields()
                fields.addFields(row)
                self.fieldList.append(fields)
        with open(self.creators, 'rb') as cr:
            reader = csv.reader(cr, delimiter=',', quotechar='"')
            for row in reader:
                creator = Creators()
                creator.addData(row)
                self.creatorsList.append(creator)

    def getUserFields(self):
        test = self.fieldList.pop(0)
        print test.getID()
        for item in self.fieldList:
            title = item.getTitle()
            value = item.getValue()
            ID = item.getID()
            index = self.IDs.index(ID)
            self.contentList[index].addUserField(title, value) 

    def getCreators(self):
        test = self.creatorsList.pop(0)
        print test.getID()
        for item in self.creatorsList:
            ID = item.getID()
            try:
                creator = item.getCreator()
                index = self.IDs.index(ID)
                self.contentList[index].addCreator(creator)
            except:
                print "Could not find match for ID " + ID


    def writeFile(self):
        test = self.contentList.pop(0)
        print test.getID()
        with open("MapsComplete.csv", 'wb') as writeFile:
            writer = csv.writer(writeFile, delimiter=',', quotechar='"')
            writer.writerow(self.header)
            for item in self.contentList:
                row = item.getRow()
                writer.writerow(row)

def main():
    content = sys.argv[1]
    fields = sys.argv[2]
    creators = sys.argv[3]
    run = Run(content, fields, creators)
    run.do()
    print "Complete!"

main()

