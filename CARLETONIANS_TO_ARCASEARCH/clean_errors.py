import sys
import os
import re
import errno
import codecs
import cStringIO
import string

def main():
    errorFile = open(sys.argv[1], "rb")
    renameFile = open(sys.argv[2], "rb")
    cleanRename = open("cleanRename.txt", "ab")
    renames = []
    for line in renameFile:
        renames.append(string.split(line, "\t"))
    errors = []
    for line in errorFile:
        errors.append(string.split(line, "\t"))
    errorPaths = []
    for item in errors:
        errorPaths.append(item[0])
    renamePaths = []
    for item in renames:
        renamePaths.append(item[0])

    for i in range(len(errors)):
        curError = errorPaths[i]
        if ".pdf" not in curError:
            curError += ".pdf"
        if curError not in renamePaths:
            cleanRename.write(errors[i][0] + "\t" + errors[i][1])
main()