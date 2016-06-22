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
		print("Start")
		self.readCSV()
		for item in self.fileData:
			self.addToDictionary(item)
		self.finalData.append(["ID", "Title", "Description", "Architect",
				"Date", "Type", "Notes"])
		for row in self.idDictionary:
			self.writeFinalList(row)
		#print(self.finalData)
		self.writeCSV()	

	""" Reads the CSV from collapseFile,
	 returns Error if file does not exist.
	 Puts rows into self.fileData, as a list of lists """
	def readCSV(self):
		try:
			with open(self.originalFile, "r") as file:
				dataReader = csv.reader(file, delimiter = ',', quotechar = '"')
				next(dataReader, None)
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
		data = self.idDictionary[row]
		arr = [row, self.concatValue(data['Title']), self.concatValue(data['Description']),
				self.concatValue(data['Architect']), self.concatValue(data['Date']),
				self.concatValue(data['Type']), self.concatValue(data['Notes'])]
		self.finalData.append(arr)


	""" Takes a list of values and concatenates them in the format
		'value 1. value 2. value 3.' --> always end in period unless one already exists"""
	def concatValue(self, values):
		final = ""
		for item in values:
			if item.endswith("."):
				final += item + " "
			else:
				final += item + ". "

		while final.endswith(" "):
			final = final[:-1]

		return final


	""" Writes a CSV file from the data in self.idDictionary unless otherwise specified
		the name of the new file will be the same as the old with '_collapsed' attached 
		+ a number if it already exists."""
	def writeCSV(self):
		outfile = "TAP_collapsed.csv"
		try:
			with open(outfile, "w", newline = '') as f:
				writer = csv.writer(f, delimiter=",", quotechar='"', quoting=csv.QUOTE_ALL)
				for row in self.finalData:
					#print(row)
					writer.writerow(row)
		except:
			print("Failed to write file")
			sys.exit()

		print("Completed")

	

def main():
	path = "TelecommArchPlanscsv.csv"

	r = CSVCollapse(path)
	r.run()

main()

