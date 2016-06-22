# This file attempts to connect to a mysql database using mysql connector.
# More information can be found at:
# https://dev.mysql.com/doc/connector-python/en/connector-python-example-connecting.html

import mysql.connector
from mysql.connector import errorcode


def main():
    try:
        # 137.22.94.155 is archivedb.carleton.edu
        # Does not work. I suspect there is a problem with the user/pass
        cnx = mysql.connector.connect(  user = 'readuser',
                                        password = 'readonly',
                                        host = '137.22.94.155',
                                        port = 443)

        # This connects sucessfully to a public test database.
        # For more info go to: http://useast.ensembl.org/info/data/mysql.html
        # cnx = mysql.connector.connect(user='anonymous',
        #                               host='ensembldb.ensembl.org',
        #                               database = 'information_schema',
        #                               port=3337)

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print("Something is wrong with your user name or password")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            print("Database does not exist")
        else:
            print(err)
    else:
        print "Connected successfully!"
        cursor = cnx.cursor()

        # From test database
        query = ("DESCRIBE ENGINES;")
        cursor.execute(query)

        for row in cursor:
          print row

        cursor.close()
        cnx.close()


main()
