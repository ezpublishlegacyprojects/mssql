<?php

include_once( "lib/ezutils/classes/ezdebug.php" );
include_once( "lib/ezutils/classes/ezini.php" );
include_once( "lib/ezdb/classes/ezdbinterface.php" );

class eZMSSQLDB extends eZDBInterface
{
    /*!
      Create a new eZMySQLDB object and connects to the database backend.
    */
    function eZMSSQLDB( $parameters )
    {
        $this->eZDBInterface( $parameters );

        $this->CharsetMapping = array( 'iso-8859-1' => 'latin1',
                                       'iso-8859-2' => 'latin2',
                                       'iso-8859-8' => 'hebrew',
                                       'iso-8859-7' => 'greek',
                                       'iso-8859-9' => 'latin5',
                                       'iso-8859-13' => 'latin7',
                                       'windows-1250' => 'cp1250',
                                       'windows-1251' => 'cp1251',
                                       'windows-1256' => 'cp1256',
                                       'windows-1257' => 'cp1257',
                                       'utf-8' => 'utf8',
                                       'koi8-r' => 'koi8r',
                                       'koi8-u' => 'koi8u' );

        if ( !extension_loaded( 'mssql' ) )
        {
            if ( function_exists( 'eZAppendWarningItem' ) )
            {
                eZAppendWarningItem( array( 'error' => array( 'type' => 'ezdb',
                                                              'number' => EZ_DB_ERROR_MISSING_EXTENSION ),
                                            'text' => 'MSSQL extension was not found, the DB handler will not be initialized.' ) );
                $this->IsConnected = false;
            }
            eZDebug::writeError( 'MSSQL extension was not found, the DB handler will not be initialized' );
            return;
        }

        /// Connect to master server
        if ( $this->DBWriteConnection == false )
        {
            $connection = $this->connect( $this->Server, $this->DB, $this->User, $this->Password, $this->Charset );
            if ( $this->IsConnected )
            {
                $this->DBWriteConnection = $connection;
            }
        }

        eZDebug::createAccumulatorGroup( 'mssql_total', 'MS SQL Total' );
    }

    /*!
     \private
     Opens a new connection to a MS SQL database and returns the connection
    */
    function connect( $server, $db, $user, $password, $charset = null )
    {
        $connection = false;

        /*eZDebug::writeDebug( $server );
        eZDebug::writeDebug( $db );
        eZDebug::writeDebug( $user );
        eZDebug::writeDebug( $password );*/
        if ( $this->UsePersistentConnection == true )
        {
            $connection = @mssql_pconnect( $server, $user, $password );
        }
        else
        {
            $connection = mssql_connect( $server, $user, $password );
        }
        $dbErrorText = mssql_get_last_message();
        $maxAttempts = $this->connectRetryCount();
        $waitTime = $this->connectRetryWaitTime();
        $numAttempts = 1;
        while ( $connection == false and $numAttempts <= $maxAttempts )
        {
            sleep( $waitTime );
            if ( $this->UsePersistentConnection == true )
            {
                $connection = @mssql_pconnect( $this->Server, $this->User, $this->Password );
            }
            else
            {
                $connection = @mssql_connect( $this->Server, $this->User, $this->Password );
            }
            $numAttempts++;
        }
        $this->setError();

        $this->IsConnected = true;

        if ( $connection == false )
        {
            eZDebug::writeError( "Connection error: Couldn't connect to database. Please try again later or inform the system administrator.\n$dbErrorText", "eZMSSQLDB" );
            $this->IsConnected = false;
        }

        if ( $this->IsConnected && $db != null )
        {
            $ret = @mssql_select_db( $db, $connection );
            $this->setError();
            if ( !$ret )
            {
                eZDebug::writeError( "Connection error: " . @mssql_get_last_message(), "eZMSSQLDB" );
                $this->IsConnected = false;
            }
        }

        if ( $charset !== null )
        {
            $originalCharset = $charset;
            include_once( 'lib/ezi18n/classes/ezcharsetinfo.php' );
            $charset = eZCharsetInfo::realCharsetCode( $charset );
            // Convert charset names into something MySQL will understand
            if ( isset( $this->CharsetMapping[ $charset ] ) )
                $charset = $this->CharsetMapping[ $charset ];
        }

        /*
        if ( $this->IsConnected and $charset !== null and $this->isCharsetSupported( $charset ) )
        {
            $versionInfo = $this->databaseServerVersion();

            // We require MySQL 4.1.1 to use the new character set functionality,
            // MySQL 4.1.0 does not have a full implementation of this, see:
            // http://dev.mysql.com/doc/mysql/en/Charset.html
            if ( version_compare( $versionInfo['string'], '4.1.1' ) >= 0 )
            {
                $query = "SET NAMES '" . $charset . "'";
                $status = @mysql_query( $query, $connection );
                $this->reportQuery( 'eZMySQLDB', $query, false, false );
                if ( !$status )
                {
                    $this->setError();
                    eZDebug::writeWarning( "Connection warning: " . @mysql_errno( $connection ) . ": " . @mysql_error( $connection ), "eZMySQLDB" );
                }
            }
        }
        */

        return $connection;
    }

    /*!
     \reimp
    */
    function databaseName()
    {
        return 'mssql';
    }

    function query( $sql, $params = array() )
    {
        if ( $this->IsConnected )
        {
            eZDebug::accumulatorStart( 'mssql_query', 'mssql_total', 'Mssql_queries' );
            $orig_sql = $sql;
            // The converted sql should not be output
            if ( $this->InputTextCodec )
            {
                eZDebug::accumulatorStart( 'mssql_conversion', 'mssql_total', 'String conversion in mssql' );
                $sql = $this->InputTextCodec->convertString( $sql );
                eZDebug::accumulatorStop( 'mssql_conversion' );
            }

            $limit = false;
            $offset = 0;
            if ( is_array( $params ) )
            {
                if ( isset( $params["limit"] ) and is_numeric( $params["limit"] ) )
                    $limit = $params["limit"];
                if ( isset( $params["offset"] ) and is_numeric( $params["offset"] ) )
                    $offset = $params["offset"];
            }

            if ( $this->OutputSQL )
            {
                $this->startTimer();
            }
            // Check if it's a write or read sql query
            $sql = trim( $sql );

            $isWriteQuery = true;
            if ( strncasecmp( $sql, 'select', 6 ) === 0 )
            {
                $isWriteQuery = false;
            }

            // Send temporary create queries to slave server
            if ( preg_match( "/create\s+temporary/i", $sql ) )
            {
                $isWriteQuery = false;
            }

            /*if ( $isWriteQuery )
            {*/
                $connection = $this->DBWriteConnection;
            /*}
            else
            {
                $connection = $this->DBConnection;
            }*/

            $analysisText = false;
            // If query analysis is enable we need to run the query
            // with an EXPLAIN in front of it
            // Then we build a human-readable table out of the result
            if ( $this->QueryAnalysisOutput )
            {
                $analysisResult = mssql_query( 'EXPLAIN ' . $sql, $connection );
                if ( $analysisResult )
                {
                    $numRows = mssql_num_rows( $analysisResult );
                    $rows = array();
                    if ( $numRows > 0 )
                    {
                        for ( $i = 0; $i < $numRows; ++$i )
                        {
                            if ( $this->InputTextCodec )
                            {
                                $tmpRow = mssql_fetch_array( $analysisResult, MSSQL_ASSOC );
                                $convRow = array();
                                foreach( $tmpRow as $key => $row )
                                {
                                    $convRow[$key] = $this->OutputTextCodec->convertString( $row );
                                }
                                $rows[$i] = $convRow;
                            }
                            else
                                $rows[$i] = mssql_fetch_array( $analysisResult, MSSQL_ASSOC );
                        }
                    }

                    // Figure out all columns and their maximum display size
                    $columns = array();
                    foreach ( $rows as $row )
                    {
                        foreach ( $row as $col => $data )
                        {
                            if ( !isset( $columns[$col] ) )
                                $columns[$col] = array( 'name' => $col,
                                                        'size' => strlen( $col ) );
                            $columns[$col]['size'] = max( $columns[$col]['size'], strlen( $data ) );
                        }
                    }

                    $analysisText = '';
                    $delimiterLine = array();
                    // Generate the column line and the vertical delimiter
                    // The look of the table is taken from the MySQL CLI client
                    // It looks like this:
                    // +-------+-------+
                    // | col_a | col_b |
                    // +-------+-------+
                    // | txt   |    42 |
                    // +-------+-------+
                    foreach ( $columns as $col )
                    {
                        $delimiterLine[] = str_repeat( '-', $col['size'] + 2 );
                        $colLine[] = ' ' . str_pad( $col['name'], $col['size'], ' ', STR_PAD_RIGHT ) . ' ';
                    }
                    $delimiterLine = '+' . join( '+', $delimiterLine ) . "+\n";
                    $analysisText = $delimiterLine;
                    $analysisText .= '|' . join( '|', $colLine ) . "|\n";
                    $analysisText .= $delimiterLine;

                    // Go trough all data and pad them to create the table correctly
                    foreach ( $rows as $row )
                    {
                        $rowLine = array();
                        foreach ( $columns as $col )
                        {
                            $name = $col['name'];
                            $size = $col['size'];
                            $data = isset( $row[$name] ) ? $row[$name] : '';
                            // Align numerical values to the right (ie. pad left)
                            $rowLine[] = ' ' . str_pad( $row[$name], $size, ' ',
                                                        is_numeric( $row[$name] ) ? STR_PAD_LEFT : STR_PAD_RIGHT ) . ' ';
                        }
                        $analysisText .= '|' . join( '|', $rowLine ) . "|\n";
                        $analysisText .= $delimiterLine;
                    }

                    // Reduce memory usage
                    unset( $rows, $delimiterLine, $colLine, $columns );
                }
            }

            $batchSize = 50;
            if ( $limit !== false && is_numeric( $limit ) && $limit < $batchSize )
            {
                $batchSize = $limit;
            }
            eZDebug::writeDebug( $batchSize, 'batch size' );

            $result = mssql_query( $sql, $connection, $batchSize );

            if ( $this->RecordError and !$result )
                $this->setError();

            if ( $this->OutputSQL )
            {
                $this->endTimer();

                if ($this->timeTaken() > $this->SlowSQLTimeout)
                {
                    $num_rows = mssql_rows_affected( $connection );
                    $text = $sql;

                    // If we have some analysis text we append this to the SQL output
                    if ( $analysisText !== false )
                        $text = "EXPLAIN\n" . $text . "\n\nANALYSIS:\n" . $analysisText;

                    $this->reportQuery( 'eZMSSQLDB', $text, $num_rows, $this->timeTaken() );
                }
            }
            eZDebug::accumulatorStop( 'mssql_query' );
            if ( $result )
            {
                return $result;
            }
            else
            {
                eZDebug::writeError( "Query error: " . mssql_get_last_message() . ". Query: ". $sql, "eZMSSQLDB"  );
                $oldRecordError = $this->RecordError;
                // Turn off error handling while we unlock
                $this->RecordError = false;
                $this->unlock();
                $this->RecordError = $oldRecordError;

                $this->reportError();

                return false;
            }
        }
        else
        {
            eZDebug::writeError( "Trying to do a query without being connected to a database!", "eZMSSQLDB"  );
        }
    }

    /*!
     \reimp
    */
    function arrayQuery( $sql, $params = array() )
    {
        //eZDebug::writeDebug( $params );
        $retArray = array();
        if ( $this->IsConnected )
        {
            $limit = false;
            $offset = 0;
            $column = false;
            // check for array parameters
            if ( is_array( $params ) )
            {
                if ( isset( $params["limit"] ) and is_numeric( $params["limit"] ) )
                    $limit = $params["limit"];

                if ( isset( $params["offset"] ) and is_numeric( $params["offset"] ) )
                    $offset = $params["offset"];

                if ( isset( $params["column"] ) and is_numeric( $params["column"] ) )
                    $column = $params["column"];
            }

            // Not supported by MS SQL server, work with batch retrieving instead
            /*
            if ( $limit !== false and is_numeric( $limit ) )
            {
                $sql .= "\nLIMIT $offset, $limit ";
            }
            else if ( $offset !== false and is_numeric( $offset ) and $offset > 0 )
            {
                $sql .= "\nLIMIT $offset, 18446744073709551615"; // 2^64-1
            }
            */

            $result = $this->query( $sql, $params );

            if ( $result == false )
            {
                $this->reportQuery( 'eZMSSQLDB', $sql, false, false );
                return false;
            }

            $numRows = mssql_num_rows( $result );

            $previousTotalNumRows = 0;

            if ( $numRows > 0 )
            {
                eZDebug::accumulatorStart( 'mssql_loop', 'mssql_total', 'Looping result' );
                while ( $numRows > 0 )
                {
                    if ( ( $previousTotalNumRows + $numRows ) < $offset )
                    {
                        eZDebug::writeDebug( 'skipping one batch of the result set' );
                        $previousTotalNumRows += $numRows;
                        $numRows = mssql_fetch_batch( $result );
                        continue;
                    }

                    for ( $i=0; $i < $numRows; $i++ )
                    {
                        $currentRowNumber = $previousTotalNumRows + $i;
                        //eZDebug::writeDebug( $currentRowNumber, 'current row number' );
                        $tmpRow = mssql_fetch_array( $result, MSSQL_ASSOC );

                        if ( $currentRowNumber < $offset )
                        {
                            continue;
                        }

                        if ( !is_string( $column ) )
                        {
                            if ( $this->InputTextCodec )
                            {
                                $convRow = array();
                                foreach( $tmpRow as $key => $row )
                                {
                                    eZDebug::accumulatorStart( 'mssql_conversion', 'mysql_total', 'String conversion in mysql' );
                                    $convRow[$key] = $this->OutputTextCodec->convertString( $row );
                                    eZDebug::accumulatorStop( 'mssql_conversion' );
                                }
                                $retArray[$currentRowNumber] = $convRow;
                            }
                            else
                            {
                                $retArray[$currentRowNumber] = $tmpRow;
                            }
                        }
                        else
                        {
                            if ( $this->InputTextCodec )
                            {
                                eZDebug::accumulatorStart( 'mssql_conversion', 'mssql_total', 'String conversion in mssql' );
                                $retArray[$i + $offset] = $this->OutputTextCodec->convertString( $tmp_row[$column] );
                                eZDebug::accumulatorStop( 'mssql_conversion' );
                            }
                            else
                            {
                                $retArray[$i + $offset] =& $tmp_row[$column];
                            }
                        }

                        // apply limit (example: we want only 10 results)
                        if ( $limit !== false && is_numeric( $limit ) && $limit == count( $retArray ) )
                        {
                            break 2;
                        }
                    }

                    $previousTotalNumRows += $numRows;
                    $numRows = mssql_fetch_batch( $result );
                }
                eZDebug::accumulatorStop( 'mssql_loop' );

                mssql_free_result( $result );
            }
        }
        return $retArray;
    }

    /*!
     \reimp
    */
    function availableDatabases()
    {
        $databaseArray = $this->arrayQuery( "EXEC sp_databases" );

        if ( $this->errorNumber() != 0 )
        {
            return null;
        }

        $databases = array();
        $i = 0;

        $numRows = count( $databaseArray );
        if ( count( $numRows ) == 0 )
        {
            return false;
        }

        foreach ( $databaseArray as $dbInfo )
        {
            // we don't allow system databases to be shown anywhere
            $curDB = $dbInfo['DATABASE_NAME'];
            if ( strcasecmp( $curDB, 'master' ) != 0 &&
                 strcasecmp( $curDB, 'model' ) != 0 &&
                 strcasecmp( $curDB, 'msdb' ) != 0 &&
                 strcasecmp( $curDB, 'tempdb' ) != 0 )
            {
                $databases[] = $curDB;
            }
        }
        return $databases;
    }

    /*!
     \reimp
    */
    function escapeString( $str )
    {
        return str_replace("'", "''", $str);
    }

    /*!
     \reimp
    */
    function close()
    {
        if ( $this->IsConnected )
        {
            @mssql_close( $this->DBWriteConnection );
        }
    }


}

?>