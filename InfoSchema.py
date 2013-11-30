#!/usr/bin/python2.7
# -*- coding: utf-8 -*-
#
# psqlToRedisTransfer.py - Reads postgreSQL database writes it in redis.
#
import sys 
reload(sys) 
sys.setdefaultencoding("utf-8")
import basicPSQL
import psycopg2
import psycopg2.extras

def runSQL_stmt(conn, sqlStmt):
    ''' Takes DB connection and SQL statement; returns result list.
    '''
    cList = []
    cursor = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    result = cursor.execute(sqlStmt);
    elements = cursor.fetchall()
    for elm in elements:
        cList = cList + elm
    cursor.close()
    return cList
    
def collectSchemaData (whatToDo,conn):
    '''  Returns data resuting from a given SQL statement. 
    '''
    if (whatToDo == 'listAllUsers'):
       ret = runSQL_stmt(conn, 'SELECT usename FROM pg_user');    
    elif (whatToDo == 'listAllTables'):
	   ret = runSQL_stmt(conn, "SELECT table_name FROM  information_schema.tables \
                               WHERE table_type = 'BASE TABLE' \
                               AND table_schema NOT IN ('pg_catalog', 'information_schema') ")
    elif (whatToDo == 'listAllViews'):
       ret = runSQL_stmt(conn, "SELECT table_name \
                               FROM information_schema.views \
                               WHERE table_schema NOT IN ('pg_catalog', 'information_schema') \
                               AND table_name !~ '^pg_'")
    elif (whatToDo == 'listColnameDataType'):
        ret = runSQL_stmt(conn, "SELECT column_name, data_type \
                                FROM information_schema.columns \
                                WHERE table_name = 'emp';")
    elif (whatToDo == 'listTabConstraints'):
        ret = runSQL_stmt(conn, "SELECT  constraint_name, constraint_type  \
                                FROM information_schema.table_constraints \
                                WHERE table_name = 'emp' \
                                AND constraint_type!='CHECK' ")  
    elif (whatToDo == 'listTabIndices'):
	    ret = runSQL_stmt(conn, "SELECT  relname FROM pg_class \
					            WHERE oid IN \
						                   (SELECT indexrelid FROM pg_index, pg_class \
						                    WHERE pg_class.relname='emp'  \
						                    AND pg_class.oid=pg_index.indrelid \
						                    AND indisunique != 't'  \
						                    AND indisprimary != 't')" )   
    elif (whatToDo == 'listFunctions'):
	    ret = runSQL_stmt(conn, "SELECT routine_name FROM information_schema.routines \
						        WHERE specific_schema NOT IN ('pg_catalog', 'information_schema')")   
    elif (whatToDo == 'listTriggers'):
	    ret = runSQL_stmt(conn, "SELECT DISTINCT trigger_name FROM information_schema.triggers \
		                        WHERE event_object_table = 'emp' \
		                        AND trigger_schema NOT IN ('pg_catalog', 'information_schema') ")
    else:
	   ret = "Choice Not Avaliable"
	
    if ret:
        ret = ', '.join(ret);   # convert list of strings to a csv string   
        return ret
    else:
        return 'None'

if __name__  ==  "__main__":
    # Get connection
    #
    psql_db = 'bahai03db'; user = 'donfox1'
    conn = basicPSQL.PSQLconnect(psql_db, user)
    
    #  Collect Data
    #
    rv = collectSchemaData('listAllTables', conn); 
    print rv

    # Close Connection
    #
    conn.close()