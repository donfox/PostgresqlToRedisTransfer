#!/usr/bin/python2.7
# -*- coding: utf-8 -*-
#
# psqlToRedisTransfer.py - Reads postgreSQL database represents it in redis.
#
import sys 
reload(sys) 
sys.setdefaultencoding("utf-8")

import basicPSQL
import InfoSchema
from redis import Redis
import pprint

r = Redis()    # instantiate a global redis db

def testRedis(keyList):
    ''' Utility displays values for the given list of keys'''
    pCntr = 0
    for key in keyList:
        print pCntr, " KEY [", key, "] VALUE [", r.get(key), "]\n"
        pCntr += 1 


def generateElem (tag, text):
    ''' Generates a complete XML element'''                                                   
    l = len(tag)
    f = tag[0:1]; 
    end_slice = tag[1:l]; 
    f = f + '/';

    if text == 'None': 
        text = ''
    endTag = f + end_slice
    endTag = endTag.lstrip()
        
    element = tag + text + endTag;
    return element
                                                                              

def storeSchema (conn, db_name ):
    ''' Stores the information schema for a postgreSQL database to a redis
        database as a single XML tree.

        Information schema is here regarded as lists of:
            1. users
            2. tables
            3. table constraints
            4. table indices
            5. views
            6. column data types
            7. functions
            8. triggers

    see: http://aloksah.org/2011/04/23/extracting-information-schema-using-postgres-sql/
         http://www.alberton.info/postgresql_meta_info.html
         http://tharas.wordpress.com/2010/01/12/getting-meta-information-of-a-postgresql-database/
    '''
    tagTypes = [ 'users', 
                 'tables', 
                 'constraints',  
                 'indices', 
                 'views',  
                 'coltypes', 
                 'functions', 
                 'triggers' ]
    
    key = db_name;
    keyList = []
    charEncodeInfo_tag = "<?xml version='1.0' encoding='utf-8'?>"
    rootTag = '<' + db_name + ":" + 'schema' + '>'
    rootComment = '<!-- A tree to represent the postgres database ' + db_name + '-->' 
  
    # generate value
    value = rootTag + rootComment;
    for type in tagTypes:
        typeTag = '<' + type + '>';                                           
        if type == 'users':
           users = InfoSchema.collectSchemaData('listAllUsers', conn);
           key = key + ':' + type;             	
           Uelem = generateElem(typeTag, users);                              
           r.set(key, Uelem)
           keyList.append(key)
           key = db_name
        elif type == 'tables':
           tables = InfoSchema.collectSchemaData('listAllTables', conn);       
           key = key + ':' + type;    
           Telem = generateElem(typeTag, tables);                           
           r.set(key, Telem)
           keyList.append(key)
           key = db_name
        elif type == 'views':
           views = InfoSchema.collectSchemaData('listAllViews', conn);   
           key = key + ':' + type;        
           Velem = generateElem(typeTag, views);   
           r.set(key, Velem)
           keyList.append(key)
           key = db_name
        elif type == 'constraints':
           constraints = InfoSchema.collectSchemaData('listTabConstraints', conn); 
           key = key + ':' + type; 
           CONelem = generateElem(typeTag, constraints); 
           r.set(key, CONelem)
           keyList.append(key)
           key = db_name
        elif type == 'indices':
           indices = InfoSchema.collectSchemaData('listTabIndices', conn);   
           key = key + ':' + type; 
           Ielem = generateElem(typeTag, indices);     
           r.set(key, Ielem)
           keyList.append(key)
           key = db_name
        elif type == 'coltypes':
           coltypes = InfoSchema.collectSchemaData('listColnameDataType', conn);  
           key = key + ':' + type; 
           COLelem = generateElem(typeTag, coltypes);
           r.set(key, COLelem)
           keyList.append(key)
           key = db_name
        elif type == 'functions':
           functions = InfoSchema.collectSchemaData('listFunctions', conn);   
           key = key + ':' + type; 
           Felem = generateElem(typeTag, functions); 
           r.set(key, Felem)
           keyList.append(key)
           key = db_name
        elif type == 'triggers':
           triggers = InfoSchema.collectSchemaData('listTriggers', conn);   
           key = key + ':' + type; 
           Telem = generateElem(typeTag, triggers); print "TELEM: ",Telem
           r.set(key, Telem)
           keyList.append(key)
           key = db_name
        else:
	       print "Oops!"
	
    return keyList

def storeData(conn, db_name):
    ''' Stores data from postgreSQL databases ro redis as a dictionary 
        Keys   - of the form "database:table:id( primary key):column".
        Values - space separated data strings pertaining the column.   
    '''
    keyList = []
    tbl_cntr = 0;           # counts table names involved

    # iterates list of public tables of connected db
    # 
    tableList = basicPSQL.collectDB_Data('listTableNames', conn); 
    for table in tableList:
        tbl_cntr += 1
        colList = basicPSQL.collectDB_Data('listColNames', conn, table)
        colList = colList[::-1]  # reverse column list                        
    
        # get primary key per table
        #
        pKey = basicPSQL.collectDB_Data('listPrimaryKey', conn, 'country')
        # print pKey
        # get data for each column in the table
        for col in colList:
            dataList = basicPSQL.collectDB_Data('listColNames', conn, 'country')
     
            # make the key and make a list of keys 
            key = db_name + ':' + table + ':' + pKey + ':' + col;
            keyList.append(key)
    
            # construct the value string 
            value = ''  # new value string for each key
            for data in dataList: value += str(data) + ' '
     
            # write key-value pair to redis
            r.set(key, value) # Note: turn redis on
   
    print "\n\tTables Read:", tbl_cntr
    return keyList

if __name__  ==  "__main__":
    
    # Gets connection 
    psql_db = 'bahai03db'; user = 'donfox1'
    conn = basicPSQL.PSQLconnect(psql_db, user)
    if (conn):
       keys = storeData(conn, psql_db); 
       testRedis(keys)
       keys = storeSchema(conn, psql_db); 
       testRedis(keys)
    else:
         print "Connection to DB failed!"