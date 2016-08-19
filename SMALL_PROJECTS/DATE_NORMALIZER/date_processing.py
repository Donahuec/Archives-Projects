'''
This file contains some methods for normalizing dates in a csv file. Useful
for quickly changing a lot of dates, and can be modified to fit the data you
are working with.
'''

import os
import csv
import re

filename = 'Peps_Media_Database20160815.csv'

def circaFixer():
    with open(filename, 'rU') as readfile:
        with open('tmp.csv', 'wb') as writefile:
            reader = csv.reader(readfile, delimiter = ',', dialect=csv.excel_tab)
            writer = csv.writer(writefile)

            regexSpace = re.compile('\\s')
            regexQ = re.compile('(\?|\(\?\))')
            regexCa = re.compile('(ca\\.|c\\.|circa)')

            for row in reader:
                # First row exception
                if (row[0] == "ItemID"):
                    writer.writerow([row[0],row[1],"NewValue"])
                else:
                    # If the value contains 'c.' or 'ca.', remove all whitespace
                    # and the 'c.', 'ca.' and place 'Circa' at the beginning
                    value = row[1]
                    newValue = value
                    #newValue = regexSpace.sub('', value)
                    newValue = re.sub(r"(.*)(ca\.\s*|c\.\s*|circa\s*)(.*)", r"Circa \1\3", newValue)

                    # Find all instances of Value with "?" and add "Circa " Value in NewValue
                    if ('?' in newValue):
                        if not re.match(r"Circa", newValue):
                            newValue = regexQ.sub("", newValue)
                            newValue = "Circa " + newValue
                            #print newValue

                    newValue = re.sub(r"(\?|\(\?\))", "", newValue)
                    writer.writerow([row[0],row[1],newValue])

    readfile.close()
    writefile.close()
    os.rename("tmp.csv", filename)

# Changes yyyy-yy to yyyy-yyyy
def dateFixer():
    with open(filename, 'rU') as readfile:
        with open('tmp.csv', 'wb') as writefile:
            reader = csv.reader(readfile, delimiter = ',', dialect=csv.excel_tab)
            writer = csv.writer(writefile)
            dateColumn = 9
            print "Irregular dates: "
            for row in reader:
                newDate = row[dateColumn]
                # s = re.search(r"(\d{2})(\d{2})(-|/)(\d{2})([^-]\D)", newDate)
                s = re.search(r"(\d{4})/(\d{1,2})/(\d{2})(.*)", newDate)
                s2 = re.search(r"(\d{1,2})/(\d{1,2})/(\d{2})", newDate)
                if s:
                    month = s.group(2)
                    if len(month) < 2:
                        month = '0' + month
                    newDate = s.group(1) + '-' + month + '-' + s.group(3) + s.group(4)
                elif s2:
                    day = s2.group(2)
                    month = s2.group(1)
                    if len(day) < 2:
                        day = '0' + day
                    if len(month) < 2:
                        month = '0' + month
                    newDate = '20' + s2.group(3) + '-' + month + '-' + day
                elif any(c.isdigit() for c in newDate):
                    print newDate
                elif newDate == 'Date':
                    newDate = 'Normalized Date'
                else:
                    newDate = ''

                row.insert(dateColumn + 1, newDate)
                writer.writerow(row)

    readfile.close()
    writefile.close()
    # os.rename("tmp.csv", filename)


# This method is incomplete
def monthFixer():
    with open(filename, 'rU') as readfile:
        with open('tmp.csv', 'wb') as writefile:
            reader = csv.reader(readfile, delimiter = ',', dialect=csv.excel_tab)
            writer = csv.writer(writefile)
            for row in reader:
                value = row[1]
                s = None
                for month in ("Jan", "Feb", "Mar"):
                    #s = re.search("(%s)(\\.)?" % (month), value)
                    s = re.search(r"(" + "%s" % (month) + r")(\.)?", value)
                    if s:
                        if s.group(1) == "Jan":
                            print 1
                        elif s.group(1) == "Feb":
                            print 2
                        elif s.group(1) == "Mar":
                            print 3

# circaFixer()
dateFixer()
# monthFixer()
