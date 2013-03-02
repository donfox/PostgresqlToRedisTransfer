#!/usr/bin/python2.7
# -*- coding: utf-8 -*-
#
# basicPSQL.py - Six basic functions to access a postgreSQL database.
#            1. to connect to the given db
#            2. to list table names for a given psql database
#            3. to list columns names for a given table
#            4. to get the data in a given table; a select *
#            5. to get the data from a table associated with a given column

# Note: make this a class!
import sys 
reload(sys) 
sys.setdefaultencoding("utf-8")
import basicPSQL
import psycopg2
import psycopg2.extras
import pprint

def useCursor(conn, sqlStmt):
    ''' Use connection and SQL statement to load data into a cursor, then
        pass that data back to caller in a list.
    '''
    cList = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    result = cursor.execute(sqlStmt);
    elements = cursor.fetchall()
    for elm in elements:
        cList = cList + elm
    cursor.close()
    return cList

def PSQLconnect (dataBase, user):
        ''' Connect to database and open a cursor, which is returned.
        '''
        conn_string = 'host=localhost  dbname=' + dataBase
        conn_string = conn_string + ' user=' + user
        
        try:
            conn = psycopg2.connect(conn_string)
            print "Connected to -> %s" % (conn_string)
        except:
            print "Database Failed [", dataBase, "]"
        
        return conn

def collectDB_Data (*args):
    '''  Returns a list of data items returned from a cursor based on the given
         SQL statement. 
    '''
    whatToDo = args[0]
    conn = args[1]

    if (whatToDo == 'listTableNames'):
        if (args[1]): conn = args[1]
        ret = useCursor(conn, 'SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' ');
    elif (whatToDo == 'listColNames'):
        if (args[1]): conn = args[1]
        if (args[2]): table_name = args[2] 
        ret = useCursor(conn, " SELECT column_name FROM information_schema.columns WHERE table_name=" + repr(table_name))
    elif (whatToDo == 'listPSQLdbs'):
        conn = args[1]
        ret = useCursor(conn, "SELECT datname FROM pg_database")
    elif (whatToDo == 'listTableData'):
        if (args[2]): table_name = args[2]
        ret = useCursor(conn, "SELECT * FROM " + table_name)
    elif (whatToDo == 'listColdata'):
        if (args[2]): table_name = args[2]
        if (args[3]): col = args[3]
        ret = useCursor(conn, "SELECT " + col + " FROM " + table_name)
    elif (whatToDo == 'listPrimaryKey'):
        table_name = args[2]
        selectStr = "SELECT pg_attribute.attname, \
	                     format_type(pg_attribute.atttypid, \
	                     pg_attribute.atttypmod) \
	                 FROM pg_index, pg_class, pg_attribute  \
	                 WHERE pg_class.oid='TABLENAME'::regclass \
	                 AND indrelid = pg_class.oid \
	                 AND pg_attribute.attrelid = pg_class.oid \
	                 AND pg_attribute.attnum = any(pg_index.indkey) \
	                 AND pg_index.indisprimary IS TRUE"
        queryStr = selectStr.replace('TABLENAME', str(table_name))
        primaryKeyList = useCursor(conn, queryStr)
        if not primaryKeyList: return 'None'
        primaryKey = primaryKeyList[0]
        return str(primaryKey) 
    else:
       ret = "Choice Not Avaliable"

    return ret

if __name__  ==  "__main__":
   
    # Get connection
    #
    psql_db = 'bahai03db'; user = 'donfox1'
    conn = PSQLconnect(psql_db, user)
    if (conn):
        # Get a list of psql dbs.
        #
        dbList = collectDB_Data('listPSQLdbs', conn); 
    #    pprint.pprint(dbList)

        # Get a list of tables for the db.
        #
        tableList = collectDB_Data('listTableNames', conn ); 
        print tableList

        # Get column names for the given table.
        #
        colData = collectDB_Data('listColNames', conn, 'country'); 
    #    print colData

        # Get table data for the given table
        #
        tableData = collectDB_Data('listTableData' , conn, 'country'); 
    #    print(tableData)

        # Get display order of the given table
        #    
        colData = collectDB_Data('listColdata' , conn, 'country', 'display_order'); 
    #    print(colData)

        pKey = collectDB_Data('listPrimaryKey', conn, 'country'); 
    #    print pKey

        conn.close()
    else:
	    print "Could not connect to database!"
	