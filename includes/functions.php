<?php
/*********************************************************************
 * functions.php - Common functions
 ********************************************************************/

// Handle creating a lockfile and bailing out if lock file already exists (ie, an instance is already running)
function createLockFile($file=null){
    $lockFile = ($file!='') ? $file : LOCKFILE;
    if(file_exists($lockFile)){
        print "detected lockfile at {$lockFile} ... exiting\n";
        exit;
    } else {
        // Write a lockfile so we prevent other runs while we are running
        file_put_contents($lockFile, 1);
    }
}


// Handle removing a lockfile
function removeLockFile($file=null){
    $lockFile = ($file!='') ? $file : LOCKFILE;
    if(file_exists($lockFile))
        unlink($lockFile);
}


// Simple function to print message and exit
function bye($msg=null){
    print $msg . "\n";
    exit;
}

// Log/Print an error and exit
function byeLog($error=null, $log=null){
    $logFile   = (strlen($log)) ? $log : ERRORLOG;
    $errorLine = '[' . gmdate("Y-m-d H:i:s") . ' UTC] - '. $error . "\n";
    if(strlen($logFile))
        file_put_contents($logFile, $errorLine, FILE_APPEND);
    print $errorLine;
    // Try to remove the lockfile, so we can continue running next time
    removeLockFile();
    exit;
}


// Setup database connection
function initDB($hostname=null, $username=null, $password=null, $database=null, $log=false){
    global $mysqli;
    // Try to establish database connection and exit if we are not able to
    $mysqli = new mysqli($hostname, $username, $password, $database);
    if($mysqli->connect_errno){
        $msg = 'Database Connection Failure: ' . $mysqli->connect_error;
        if($log){
            byeLog($msg);
        } else {
            print $msg;
            exit;
        }
    }
}


// Setup Counterparty API connection
function initCP($hostname=null, $username=null, $password=null, $log=false){
    global $counterparty;
    $counterparty = new Client($hostname);
    $counterparty->authentication($username, $password);
    $status = $counterparty->execute('get_running_info');
    // If we have a successfull response, store it in 'status'
    if(isset($status)){
        $counterparty->status = $status;
    } else {
        // If we failed to establish a connection, bail out
        $msg = 'Counterparty Connection Failure';
        if($log){
            byeLog($msg);
        } else {
            print $msg;
            exit;
        }
    }
}


// Handle getting database id for a given asset
function getAssetDatabaseId($asset=null){
    global $mysqli;
    $id = false;
    $results = $mysqli->query("SELECT id FROM assets WHERE asset='{$asset}' OR asset_longname='{$asset}' LIMIT 1");
    if($results){
        $row = $results->fetch_assoc();
        $id  = $row['id'];
    }
    return $id;
}


// Handle taking string and returning valid url
function getValidUrl($url){
    if(strpos($url, '://') == false)
        $url = 'http://' . $url;
    // If this is indiesquare url.... use http (their ssl cert expired a while ago)
    if(strpos($url,'res.indiesquare.me'))
        $url = str_replace('https://','http://',$url);
    return $url;
}


// Handle making a request for a remote URL via curl (so we can use timeouts nicely)
function getRemoteUrl( $url=null, $timeout=30 ){
    // Make sure url starts with http:// or https://
    if(strtolower(substr($url,0,7))!='http://' && strtolower(substr($url,0,8))!='https://')
        $url = 'http://' . $url;
    // Add unique querystring to force no caching
    $url .= (strpos('?',$url)==false) ? '?' : '&';
    $url .= 'ts=' . time();
    // Make Curl request to get URL
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $data=curl_exec($ch);
    curl_close($ch);
    return $data;
}



?>