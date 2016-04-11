import os
import sys
import re
import shutil
import codecs
import cStringIO
import errno

class LetterSorter:
    def __init__(self, location, dirLocation):
        self.location = location
        self.dirLocation = dirLocation
        self.currentFolder = 3000
        self.ignore = [".DS_Store", ".picasa.ini", "._.DS_Store"]

    def createStructure(self):
        pathList = [x for x in os.listdir(self.location)]
        for fileName in pathList:
            path= os.path.join(self.location, fileName)
            if fileName not in self.ignore:
                if "BLANK" not in fileName:
                    saveLocation = os.path.join(self.dirLocation, str(self.currentFolder))
                    if not os.path.isdir(saveLocation):
                        try:
                            os.makedirs(saveLocation)
                        except OSError as exception:
                            if exception.errno != errno.EEXIST:
                                raise
                    shutil.copyfile(path, os.path.join(saveLocation, fileName))
                else:
                    self.currentFolder += 1
def main():
    location = sys.argv[1]
    dirLocation = sys.argv[2]
    LS = LetterSorter(location, dirLocation)
    LS.createStructure()
    print "done"
main()

