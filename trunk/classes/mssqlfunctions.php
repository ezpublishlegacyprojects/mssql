<?php
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