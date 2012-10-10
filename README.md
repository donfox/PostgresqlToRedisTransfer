PostgresqlToRedisTransfer
=========================

Transfer of data from  postgresql database(s) to redis using python.

The intention is to just transfer relational data from a database, initially in postgtresql,  to redis using python.

First pass does two actions; writing the data content of all the tables to redis in terns of the columns and storing the 
database schema as XML.

TODO:
  1. Refine and extend the XML using python's extensive capabilities at doing that.
  2. Explore the different ways of storing the data in Redis. There are five variations of data types that could be 
     used that are all types of 'dictionaries'. Initially i've just used a simple associative array.
  3. Add a Django front end. 

This initial stage is just to show that such data transfer between relational and nonrelational databases is simple. The 
intention here is to eventually have a prototype that could become a comoponent of an application like a claim's 
processing application.

Of course redis and postgres should be installed! If a Mac is being used there is a very nice app avaliable for your 
dock that will start and stop postgresql at  http://postgresapp.com.

Using the psql shell the correctness of the data transfer can be verified as the application prints out the key/value 
pairs as it runs.

There are three  files: basicPLSQ.py and infoSchema which are modules and plsqlToRedisTransfer.py which is the application.
I've included the postgres 'bahai' database ands my redis dump.