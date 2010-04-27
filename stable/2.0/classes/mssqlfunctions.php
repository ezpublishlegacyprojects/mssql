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

if ( eZMssqlIsUnicode() )
    define( "EZMSSQL_UNICODE_SUPPORT", true );
else
    define( "EZMSSQL_UNICODE_SUPPORT", false );

function eZMssqlIsUnicode()
{
    $ini = eZINI::instance( "dbschema.ini" );
    if ( extension_loaded( 'odbtp' ) and $ini->variable( "SchemaSettings", "Unicode" ) == 'true' )
       return true;
    else
       return false;
}
?>