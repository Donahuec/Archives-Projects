import csv
import os
import sys

class CSVCollapse:
	def __init__(self, originalFile):
		self.originalFile = originalFile
		self.idDictionary = {}
		self.fileData = []
		self.finalData = []

	""" Run the program """
	def run(self):
		readCSV()
		for item in self.fileData:
			addToDictionary(row)
		self.finalData.append(["ID", "Title", "Description", "Architect",
				"Date", "Type", "Notes"])
		for row in self.idDictionary:
			writeFinalList(row)
		writeCSV()	

	""" Reads the CSV from collapseFile,
	 returns Error if file does not exist.
	 Puts rows into self.fileData, as a list of lists """
	def readCSV(self):
		try:
			with open(self.originalFile, "rb") as file:
				dataReader = csv.reader(file, delimiter = ',', quotechar = '"')
				for row in dataReader:
					self.fileData.append(row)
		except IOError:
			print(self.originalFile + " failed to open properly.")
			print("Exiting program")
			sys.exit()
		

	""" Takes a list 'row' and adds its data to self.idDictionary.
		Every key in self.idDictionary contains a dictionary with
		keys equvalent to the required table headers. If a key with the
		ID of row does not exist, call AddNewKey, if it does exist, add the
		Items to the lists connected to the table-value keys """
	def addToDictionary(self, row):
		newID = row[0]
		title = row[2]
		description = row[3]
		architect = row[4]
		date = row[5]
		data_type = row[6]
		notes = row[7]

		if newID not in self.idDictionary:
			self.idDictionary[newID] = {'Title' : [], 'Description': [], 
					'Architect' : [], 'Date' : [], 'Type' : [], 'Notes' : []}


		if title not in self.idDictionary[newID]['Title'] and title != "":
			self.idDictionary[newID]['Title'].append(title)

		if description not in self.idDictionary[newID]['Description'] and description != "":
			self.idDictionary[newID]['Description'].append(description)

		if architect not in self.idDictionary[newID]['Architect'] and architect != "":
			self.idDictionary[newID]['Architect'].append(architect)

		if date not in self.idDictionary[newID]['Date'] and date != "":
			self.idDictionary[newID]['Date'].append(date)

		if data_type not in self.idDictionary[newID]['Type'] and data_type != "":
			self.idDictionary[newID]['Type'].append(data_type)

		if notes not in self.idDictionary[newID]['Notes'] and notes != "":
			self.idDictionary[newID]['Notes'].append(notes)


	""" For all of the items in self.idDictionary, make the array for each 
		item so that is can be put in the csv """
	def writeFinalList(self, row):
		#stuff 

	""" Takes a list of values and concatenates them in the format
		'value 1. value 2. value 3.' --> always end in period unless one already exists"""
	def concatValue(self, values):
		#stuff


	""" Writes a CSV file from the data in self.idDictionary unless otherwise specified
		the name of the new file will be the same as the old with '_collapsed' attached 
		+ a number if it already exists."""
	def writeCSV(self):
		#stuff

	

def main():
	#stuff

