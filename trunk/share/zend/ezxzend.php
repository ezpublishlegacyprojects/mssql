<?
/**
 * Zend Encoder Header

include_once( 'lib/ezutils/classes/ezextension.php' );
$include = eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/share/zend/ezxzend.php';
include_once( $include );
ezxZend::header();
 
 */
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'lib/ezutils/classes/ezmodule.php' );
include_once( 'lib/ezutils/classes/ezexecution.php' );
class ezxZend
{
    function ezxZend()
    {
    	
    }
    function is_encoded( $file )
    {
        if ( !file_exists( $file ) )
            $file = __FILE__;
        include_once( 'lib/ezfile/classes/ezfile.php' );
        $lines = eZFile::splitLines( $file );
        if ( "<?php @Zend;" == trim( $lines[0] ) )
            return true;
        else
            return false;
    }
    function hasDecoder()
    {
        if ( function_exists('extension_loaded') && extension_loaded('Zend Optimizer') )
            return true;
        else
            false;
    }
    function header()
    {
        $extension = nameFromPath( __FILE__ );
        if ( eZSys::isShellExecution() )
        {
            include_once( 'lib/ezutils/classes/ezcli.php' );
            $cli = eZCLI::instance();
            if ( ezxZend::hasDecoder() )
                $cli->output( "Error: Zend Optimizer not installed" );
            else    
                $cli->output( "Error: Licence not loaded" );
            $cli->output( "" );
            $cli->output( "This extension '".$extension."' is encoded. In order to run it, please install the freely available Zend Optimizer, version 2.1.0 or later and apply a valid licence." );
            $cli->output( "http://www.zend.com/store/products/zend-optimizer.php" );
        }

        else
        {
            if ( ezxZend::hasDecoder() )
                echo ( "Error: Zend Optimizer not installed or licence not loaded<br />" );
            else    
                echo ( "Error: Licence not loaded<br />" );
            echo( "<br />" );
            echo( "This extension '".$extension."' is encoded. In order to run it, please install the freely available Zend Optimizer, version 2.1.0 or later and apply a valid licence.<br />" );
            echo( "http://www.zend.com/store/products/zend-optimizer.php<br />" );
        }
        eZExecution::cleanExit();
    }
    function loadLicense( $file )
    {
        if ( !file_exists( $file ) )
            $file = __FILE__;
    	zend_loader_install_license( eZExtension::baseDirectory() .'/'. eZExtension::nameFromPath( __FILE__ ) . '/'. eZExtension::nameFromPath( __FILE__ ) . ".lic" );
    }
    function getMD5Hash( $file )
    {
        if ( !file_exists( $file ) )
            $file = __FILE__;
        return md5_file( $file );
    }
}

?>