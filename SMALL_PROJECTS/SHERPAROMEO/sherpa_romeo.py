"""
	sherpa_romeo.py
	
	Made to find publisher copyright policies for self-archiving articles 
	written by Carleton faculty and staff using the SHERPA/RoMEO API.
	http://www.sherpa.ac.uk/romeo/api.html

	Input: CSV file of citations + info (articles.csv) in the format:
		Faculty/Staff Name,Category,Citation,Students,Journal Name,Publisher Name
	Output: CSV file (archivingPermissions.csv)

	The SHERPA/RoMEO API key has a limit of 500 requests per day. To get a new key, go
	to http://www.sherpa.ac.uk/romeo/apiregistry.php
	Otherwise: Sahree's API key: AVIRGTSihmI. Liam's API key: T7WN1iQRAPk.
	Last updated July 2014. Written by Liam Everett, edited by Sahree Kasper.
"""

import csv, requests, re
import xml.etree.ElementTree as ET

def addHeaders(csvfile):
	""" Given the path of a CSV file, appends the given column headers to the original 
	file. Returns a CSV file. """
	headers = ['Notes','Color','PDFarchiving','PDFrestrictions','Postarchiving',
			   'Postrestrictions','Prearchiving','Prerestrictions']
	for header in headers:
		csvfile.fieldnames.append(header)
	return csvfile

def sendRequest(requestFor,requestType='jtitle'):
	""" For querying the SHERPA/RoMEO API. Send the request with parameters formatted 
	as a dictionary. Encode and parse the resulting XML string. Return the root. """
	# root URL for requests sent to the sherpa/romeo API
	root_url = 'http://www.sherpa.ac.uk/romeo/api29.php'
	# info about requests module: http://docs.python-requests.org/en/latest/
	r = requests.get(root_url, params = {requestType:requestFor, 'ak':'T7WN1iQRAPk'})
	content = r.text.encode('raw_unicode_escape').decode('utf-8', 'ignore')
	return ET.fromstring(content)	

def printDetails(pdf,pre,post,color):
	""" Given the four pieces of data, prints them in a readable format. """
	print '\tcolor code:',color
	print '\tpdf:',pdf
	print '\tpre:',pre
	print '\tpost:',post

def findRestrictions(root,node1,node2):
	""" Given the root and the last two nodes, returns a list of restrictions. """
	listOfRs = []
	try: 
		restrictions = root.find('publishers').find('publisher').find(node1).find(node2)
		for restriction in pdfrestrictions:
			listOfRs.append(restriction.text)
		prs = '; '.join((listOfRs))
		return re.sub('<[^<]+>','',prs)		
	except:
		return "no details given"

def main():
	articles_file = addHeaders(csv.DictReader(open('articles.csv')))
	index = 0

	# Instantiate a new csv file with the proper headers
	return_file = csv.DictWriter(open("archivingPermissions.csv", "wb"), fieldnames=articles_file.fieldnames)
	return_file.writeheader()

	for row in articles_file:
		index+=1
		jname = row['Journal Name']
		root = sendRequest(jname)
		row['Notes'] = ''

		# using ET to parse the XML structure of the API's query responses
		numJournals = len(root.find('journals'))
		numPublishers = len(root.find('publishers'))

		print str(index) + " " + jname

		# if multiple journal title results, try: match publisher name and query with ISSN
		if numPublishers == 0 and numJournals > 1:
			# print root.find('publishers').text
			row['Notes'] = 'multiple journals found '
			journals = root.find('journals')
			for journal in journals:
				issn = journal.find('issn')
				print "\tUsing ISSN instead of journal title."
				root = sendRequest(issn.text,'issn')
				numJournals = len(root.find('journals'))
				numPublishers = len(root.find('publishers'))
			# if the ISSN search is successful, continue with the try block
			if numJournals >= 1 or numPublishers >= 1:
				pass
			# if it's not successful or has no publishers, make a note
			else:
				row['Notes'] += "no publishers given "
				return_file.writerow(row)
				print '\tNot enough information for this journal: No publisher found.'
				continue 
		# the following two write the row and skips the rest of the current loop iteration
		elif numPublishers == 0 and numJournals == 0:
			row['Notes'] = "no journals found "
			print '\tNo journal found'
			return_file.writerow(row)
			continue

		# added a try-except block to catch possible future errors
		try:
			# get info from XML results for the following four columns:
			row['Color'] = root.find('publishers').find('publisher').find('romeocolour').text
			row['PDFarchiving'] = root.find('publishers').find('publisher').find('pdfversion').find('pdfarchiving').text
			row['Prearchiving'] = root.find('publishers').find('publisher').find('preprints').find('prearchiving').text
			row['Postarchiving'] = root.find('publishers').find('publisher').find('postprints').find('postarchiving').text
			printDetails(row['PDFarchiving'],row['Postarchiving'],row['Prearchiving'],row['Color'])

			# if restricted, add restrictions to a new column in the output document
			if row['PDFarchiving'] == 'restricted':
				row['PDFrestrictions'] = findRestrictions(root,'version','pdfrestrictions')
			if row['Postarchiving'] == 'restricted':
				row['Postrestrictions'] = findRestrictions(root,'postprints','postrestrictions')
			if row['Prearchiving'] == 'restricted':
					row['Prerestrictions'] = findRestrictions(root,'preprints','prerestrictions')
		except:
			row['Notes'] += 'error looking up journal'
			print '\tError with',str(index) + '. No publisher(s) in journal results.'

		return_file.writerow(row)
		
main()