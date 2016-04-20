#A Script to remove nul characters from the end of file names
#Usage: Run with the top-directory as an argument

import os
import sys
import datetime
import time
import csv
import re
import shutil
import ast
import codecs
import cStringIO
import errno


class Cleanup:
    def __init__(self, top_dir):
        self.now = datetime.datetime.now()
        #a counter for how many files have been looked at (just extra info for testing)
        self.datestamp = str(self.now.year) + "_" + str(self.now.month) + "_" + str(self.now.day)
        self.top_dir = top_dir
        self.log_name = os.path.join(self.top_dir, "change_log" + self.datestamp)
        self.log_file = open(os.path.join(self.top_dir,self.log_name +  '.csv'),'wb')
        self.writer = UnicodeWriter(self.log_file, dialect=csv.excel, encoding="utf-8", delimiter=",", quotechar='"', quoting=csv.QUOTE_ALL)
        self.writer.writerow(["Old Path", "New Path"])
    
    def run(self):
        paths = os.listdir(ast.literal_eval("u'" + (self.top_dir.replace("\\", "\\\\")) + "'"))
        for i in paths:
            if "change_log" not in i:
                replacement = i.replace("?","").replace(" ","").replace("\0", "").replace("\n", "").replace("\r", "").replace("\t", "").encode("cp850", errors="ignore")
                print replacement
                if (i != replacement):
                    path_to_file = os.path.join(self.top_dir, i)
                    replacement_path = os.path.join(self.top_dir, replacement)
                    os.rename(path_to_file, replacement_path)
                    self.writer.writerow([path_to_file, replacement_path])
class UnicodeWriter:
    """
    A CSV writer which will write rows to CSV file "f",
    which is encoded in the given encoding.
    """
    def __init__(self, f, dialect=csv.excel, encoding="utf-8", **kwds):
        # Redirect output to a queue
        self.queue = cStringIO.StringIO()
        self.writer = csv.writer(self.queue, dialect=dialect, delimiter=",", quotechar='"', quoting=csv.QUOTE_ALL)
        self.stream = f
        self.encoder = codecs.getincrementalencoder(encoding)()

    def writerow(self, row):
        self.writer.writerow([s.encode("utf-8") for s in row])
        # Fetch utf-8 output from the queue ...
        data = self.queue.getvalue()
        data = data.decode("utf-8")
        # ... and reencode it into the target encoding
        data = self.encoder.encode(data)
        # write to the target stream
        self.stream.write(data)
        # empty queue
        self.queue.truncate(0)

    def writerows(self, rows):
        for row in rows:
            self.writerow(row)


def main():
    clean = Cleanup(sys.argv[1])
    clean.run()
main()






