# API key: T7WN1iQRAPk
# information about the requests module: http://docs.python-requests.org/en/latest/

import csv, requests
import xml.etree.ElementTree as ET

articles_file = csv.DictReader(open('Articles.csv'))

# Add additional headers to original file
articles_file.fieldnames.append('Publisher Name')
articles_file.fieldnames.append('Color Code')
articles_file.fieldnames.append('No Journals Found?')
articles_file.fieldnames.append('Multiple Journals Found?')

# Instantiate a new csv file with the proper headers
return_file = csv.DictWriter(open("ArticlesWithColors2.csv", "wb"), fieldnames=articles_file.fieldnames)
return_file.writeheader()

# Base URL for requests sent to the sherpa/romeo API
root_url = 'http://www.sherpa.ac.uk/romeo/api29.php'

i = 1
singlePubs = 0
noPubs = 0
multiplePubs = 0

# Parse original file to construct/send queries to the API
for row in articles_file:
	jname = row['Journal Name']

	# Send the request here with the parameters formatted as a dictionary
	r = requests.get(root_url, params = {'jtitle':jname, 'ak':'T7WN1iQRAPk'})

	# Encode and parse the resulting XML string
	content = r.text.encode('raw_unicode_escape').decode('utf-8', 'ignore')
	root = ET.fromstring(content)

	# XML parsing based on the structure of	the API's responses
	numJournals = len(root.find('journals'))
	numPublishers = len(root.find('publishers'))
	print str(i) + " " + jname + "\n\t" + str(numPublishers) + " publishers",
	if numPublishers == 1:
		singlePubs += 1
		row['Publisher Name'] = root.find('publishers').find('publisher').find('name').text
		row['Color Code'] = root.find('publishers').find('publisher').find('romeocolour').text
		print row['Color Code'],
		if numJournals > 1:
			print "***",
		print
	elif numPublishers == 0:
		noPubs += 1
		if numJournals > 1:
			row['Multiple Journals Found?'] = 'X'
			print "+++",
		print
	else:
		multiplePubs += 1
		print
	if numJournals == 0:
		row['No Journals Found?'] = "X"
	return_file.writerow(row)
	i+=1
print "PUBLISHERS: single, none, multiple:", singlePubs, noPubs, multiplePubs