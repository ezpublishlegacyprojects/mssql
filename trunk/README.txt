MSSQL for eZ publish 3.x and SQL Server 2005

Developed by

Doro Eils
Björn Dieding
Sören Meyer

Includes
 - database driver
 - database schema definition

1. INSTALLATION

1.0 Configure PHP

Configure PHP to use mssql. 

WINDOWS:
You have two options to use mssql. 

    * First option is to run it with php_mssql.dll. This driver doesn't support uft8.
      The the proper mssql client libraries can be found under dll/ntwdblib.dll.
      They have to be placed in your windows directory.
      
      ** php.ini **
      ;extension=php_mssql.dll
      
      Configure PHP to use Windows AUTH or not.
      mssql.secure_connection = On / Off

    * Second option is to run the odbtp driver php_odbtp_mssql.dll and service.
      The php library will connect to the odbtp server over tcp/ip. This driver supports utf8.
      
      ** php.ini **
      extension=php_odbtp_mssql.dll

      [odbtp]
      odbtp.interface_file = "C:\odbtp\odbtp.conf"
      odbtp.datetime_format = mdyhmsf
      odbtp.detach_default_queries = yes
      
      ** odbtp.conf **
      [global]
      type = mssql
      odbtp host = localhost
      use row cache = yes
      right trim text = yes
      unicode sql = yes
      
LINUX:  
    
      Under Linux the client libs should be build from source.
      http://odbtp.sourceforge.net/

      ** php.ini **
      extension=php_odbtp_mssql.so

      [odbtp]
      odbtp.interface_file = "C:\odbtp\odbtp.conf"
      odbtp.datetime_format = mdyhmsf
      odbtp.detach_default_queries = yes
      
      ** odbtp.conf **
      [global]
      type = mssql
      odbtp host = localhost
      use row cache = yes
      right trim text = yes
      unicode sql = yes

Turning Active Directory AUTH is off leads to less complications.

1.1 Patching

MSSQL works at some extend different as other databases. 

- copy and replace all patched files in your shipped distribution

  You find the patched files for your distribution under:
  extension/mssql/patch/(your version number)

Certain bugs prevent us from having a unpatched version (see doc/BUGS.txt). They have been resolved in eZ publish 3.10.

1.2 Export of current data [optional]

This step only applies if you plan to use option 1.3.3.
A similar approach can be also used for moving from oracle or postgres to mssql.

export mysql:
- run the following command from the command line
php bin\php\ezsqldumpschema.php --output-array --output-types=data --type=mysql --host=[databasehostname] --port=[databaseport] --user=[databaseusername] --password=[databaseuserpassword] [databasename] temp.dba


1.3 Configuration

- If you have choosen to install either mysql or odbtp wihtout unicode support change setttings/override/dbschema.ini.append.php to

[SchemaSettings]
Unicode=false

- register the mssql extension by adding following lines to
  setttings/override/site.ini.append.php

[DatabaseSettings]
Server=[databasehostname]
User=[databaseusername]
Password=[databaseuserpassword]
Database=[databasename]
Port=[databaseport] #default is 1433
SQLOutput=disabled

[ExtensionSettings]
ActiveExtensions[]=mssql

1.3 Data injection / Fill database with data

First you will need to create a new database in SQL Server 

1.3.1 Stored procedures
    
Pleaes execute PROCEDURE.sql from the extensions root
in your Microsoft SQL Server Management Studio Express.

    
1.3.2 Insert fresh new data [optional]

- run the following command from the command line

php bin\php\ezsqlinsertschema.php -d --clean-existing \
--schema-file=share/db_schema.dba \
--insert-types=all --type=mssql --host=[databasehostname] --port=[databaseport] \
--user=[databaseusername] --password=[databaseuserpassword] \
share/db_data.dba [databasename]

1.3.3 Move from any other DB to MSSQL [optional]

import mssql:
- run the following command from the command line
php bin\php\ezsqlinsertschema.php --clean-existing --schema-file=share/db_schema.dba --insert-types=all --type=mssql --host=[databasehostname] --port=[databaseport] --user=sa --port=1433 --password=openpass temp.dba nextgen


2.0 Support

For support contact:

xrow GbR
Am Lindener Berge 22 
30449 Hannover
Germany

Phone: +49 (511) 5904576
Email: service@xrow.de