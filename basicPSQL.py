#!/usr/bin/python2.7
# -*- coding: utf-8 -*-
#
# descPSQL.py - Six useful, basic functions to access a postgreSQL database.
#            1. to connect to the given db
#            2. to list table names for a given psql database
#            3. to list columns names for a given table
#            4. to get the data in a given table; a select *
#            5. to get the data from a table associated with a given column

# Note: make this a class!

import psycopg2
import psycopg2.extras
import pprint


   	
def PSQLconnect (dataBase, user):
        ''' Connect to a given database and open a cursor for it, which is
            passed back to the caller.
        '''
        conn_string = 'host=localhost  dbname=' + dataBase
        conn_string = conn_string + ' user=' + user
        
        try:
            conn = psycopg2.connect(conn_string)
            print "Connected to -> %s" % (conn_string)
        except:
            print "Database Failed [", dataBase, "]"
        
        return conn

def getPSQLdbs (conn):
    ''' Gets the names of postgreSQL databases on the system. '''
    psqlist = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor.execute("SELECT datname FROM pg_database")
    
    psqlDBs = cursor.fetchall()
    for db in psqlDBs:
        psqlist = psqlist + db
    
    return psqlist

def getTableNames (conn):
	''' Gets table names list from information schema for a given database.'''
	tablist = []
	cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
	cursor.execute("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ")
	
	psqlTabs = cursor.fetchall()
	for table in psqlTabs:
	    tablist = tablist + table
	
	return tablist

def getColumnNames (conn, table_name):
    ''' Gets a list of the column names for a given table. '''
    colist = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor.execute(" SELECT column_name FROM information_schema.columns WHERE table_name=" + repr(table_name))
    psqlCols = cursor.fetchall()
    for col in psqlCols:
        colist = colist + col
    
    return colist

def getTableData (conn, table_name):
    ''' Gets a list of all the data for a given table. '''
    selectStr = "SELECT * FROM " + table_name;
    recList = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor.execute(selectStr)
    records = cursor.fetchall()
    for record in records:
        recList = recList + record
    
    return recList

def getColData (conn, table_name, col):
    ''' Gets s list of column data, across rows, for a given table.'''
    selectStr = "SELECT " + col + " FROM " + table_name
    colList = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor.execute(selectStr)
    records = cursor.fetchall()
    for record in records:
        colList = colList + record
    
    return colList

def getPrimaryKey (conn, table):
    ''' Returnes primary key for the given table '''     
    selectStr = "SELECT pg_attribute.attname, \
                        format_type(pg_attribute.atttypid, \
                        pg_attribute.atttypmod) \
    	         FROM pg_index, pg_class, pg_attribute  \
    	         WHERE pg_class.oid='TABLENAME'::regclass \
    	         AND indrelid = pg_class.oid \
    	         AND pg_attribute.attrelid = pg_class.oid \
    	         AND pg_attribute.attnum = any(pg_index.indkey) \
    	         AND pg_index.indisprimary IS TRUE"

    selStmt = selectStr.replace('TABLENAME', str(table)) 
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cursor.execute(selStmt)
    primaryKeyList = cursor.fetchall()
    if not primaryKeyList: return 'None'
    primaryKey = primaryKeyList[0]

    return str(primaryKey[0])

if __name__  ==  "__main__":
   
   # Get connection
   psql_db = 'bahai03db'; user = 'donfox1'
   conn = PSQLconnect(psql_db, user)

   # Get a list of avaliable psql dbs on the system.
   dbList = getPSQLdbs(conn); #pprint.pprint(dbList)
   
   # Get a list of tables for the pdql db that is connected.
#   tableList = getTableNames(conn); #pprint.pprint(tableList)
   
   # Get column names list for a given table in the connected psql db.
   columnList = getColumnNames(conn, 'person'); #pprint.pprint(columnList)
   
   # Get table data
   tableData = getTableData(conn, 'person'); #sprint tableData
   
   # Get data for a given column of a given table
   colData = getColData(conn, 'country', 'display_order' ); 
   #print "COL Data for country, country_code",  colData

   pkey = getPrimaryKey(conn, 'country'); print pkey

   conn.close()