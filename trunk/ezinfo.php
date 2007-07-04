<?php
/*
    eZ Publish MSSQL extension
    Copyright (C) 2007  xrow GbR, Hannover, Germany

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

class ezmssqlInfo
{
    function info()
    {
        include_once( 'extension/mssql/classes/version.php' );
        return array( 'Name' => "MSSQL database extension for SQL Server 2005",
                      'Version' => "2.0 (rev " . EZ_MSSQL_VERSION_REVISION . ")",
                      'Copyright' => "Copyright (C) 2003-2007 xrow GbR",
                      'License' => "GPL Version 3 and higher"
                     );
    }
}
?>
