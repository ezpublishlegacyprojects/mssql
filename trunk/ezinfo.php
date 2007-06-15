<?php

class ezmssqlInfo
{
    function info()
    {
        include_once( 'extension/mssql/classes/version.php' );
        return array( 'Name' => "MSSQL database extension for SQL Server 2005",
                      'Version' => "2.0 (rev " . EZ_MSSQL_VERSION_REVISION . ")",
                      'Copyright' => "Copyright (C) 2003-2006 xrow GbR",
                      'License' => "eZ Proprietary Partner license"
                     );
    }
}
?>
