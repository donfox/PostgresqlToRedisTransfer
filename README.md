PostgresqlToRedisTransfer
=========================

Transfer of data from  postgresql database(s) to redis.

First pass does two actions; (1) writing data content of all tables to redis 
in terms of the columns and (2) storing the database schema as XML.

TODO:
  1. Refine and extend the XML.
  2. Explore different ways of storing the data in Redis. There are five data 
     structures that are all variations of 'dictionaries'. Initially i've just
     used a simple associative array.
  3. Add a Django front end. 

This initial stage is just to show a data transfer between relational and 
nonrelational databases is simple. The intention here is to have a prototype 
component for a larger application.

Of course redis and postgres should be installed! 
http://postgresapp.com for Mac

Using the psql shell the correctness of the data transfer can be verified as 
the application prints out the key/value pairs as it runs.

There are three  files: basicPLSQ.py and infoSchema which are modules and 
plsqlToRedisTransfer.py which is the application.

I've included the postgres 'bahai' database and my redis dump.