#!/usr/bin/env php
<?php
/*********************************************************************
 * counterparty-json-cache.php 
 * 
 * Script to handle parsing counterparty data into mysql database
 * 
 * Command line arguments :
 * --testnet    Load data from testnet
 * --asset=X    Update JSON for a given asset X
 * --block=#    Lookup asset issuance updates since a given block
 * --all        Full parse of all JSON files 
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Parse in the command line args and set some flags based on them
$args     = getopt("", array("testnet::", "asset::", "block::", "all::"));
$testnet  = (isset($args['testnet'])) ? 1 : 0;
$asset    = (isset($args['asset'])) ? $args['asset'] : false;
$all      = (isset($args['all'])) ? true : false;
$block    = (is_numeric($args['block'])) ? intval($args['block']) : false;
$runtype  = ($testnet) ? 'testnet' : 'mainnet';
$dbase    = 'XChain'; // Name of the database where counterparty_nfts table exists

// Load config (only after runtype is defined)
require_once(__DIR__ . '/includes/config.php');

// Define some constants used for locking processes and logging errors
define("LOCKFILE", '/var/tmp/counterparty-json-cache-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/counterparty-json-cache-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/counterparty-json-cache-' . $runtype . '.errors');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Create a lock file, and bail if we detect an instance is already running
createLockFile();

// If no block given, load last block from state file, or use first block with CP tx
if(!$block){
    $last  = file_get_contents(LASTFILE);
    $first = ($regtest) ? 1 : (($testnet) ? 310000 : 278270);
    $block = (isset($last) && $last>=$first) ? (intval($last) + 1) : $first;
}

// Get the current block index from status info
$current = $counterparty->status['last_block']['block_index'];

$assets = array();

// Handle looking up a single assets
if($asset){
    array_push($assets, $asset);
} else {
    if($all)
        $block = $first;
    // Lookup any asset issuances since given block
    $results = $mysqli->query("SELECT DISTINCT(a.asset) as asset FROM issuances i, assets a WHERE a.id=i.asset_id AND i.description LIKE '%json%' AND i.block_index>={$block} ORDER BY asset");
    if($results){
        while($row = $results->fetch_assoc())
            array_push($assets, $row['asset']);
    } else {
        bye('Error looking up asset list');
    }
}

print "Processing " . count($assets) . " assets\n";
foreach($assets as $asset){
    $timer = new Profiler();
    print "Processing {$asset}...";
    // Lookup information on this asset
    $results = $mysqli->query("SELECT id, asset, description FROM assets WHERE asset='{$asset}' OR asset_longname='{$asset}'");
    if($results){
        $row = (object) $results->fetch_assoc();
        // Check if description ends in .json
        if(preg_match('/.json$/i', $row->description)){
            $url = getValidUrl($row->description);
            print "requesting json from {$url}...";
            $data = getRemoteUrl($url);
            if($data){
                $json = json_decode($data, true);
                if($json){
                    $json = json_encode($json, JSON_UNESCAPED_SLASHES);
                    $hash = hash('sha256', $json);
                    $json = $mysqli->real_escape_string($json);
                    $url  = $mysqli->real_escape_string($url);
                    $sql  = false;
                    // Handle checking if we already have JSON for this asset
                    $results2 = $mysqli->query("SELECT id, hash FROM {$dbase}.counterparty_json WHERE asset_id='{$row->id}' LIMIT 1");
                    if($results2){
                        if($results2->num_rows==0){
                            $sql = "INSERT INTO {$dbase}.counterparty_json (testnet, asset_id, url, json, hash, created, updated) values ({$testnet}, {$row->id}, '{$url}','{$json}', '{$hash}',now(), now() )";
                        } else {
                            $info = (object) $results2->fetch_assoc();
                            // Only update if the sha256 hashes differ
                            if($info->hash != $hash)
                                $sql = "UPDATE {$dbase}.counterparty_json SET url='{$url}', json='{$json}', hash='{$hash}', updated=now() WHERE id='{$info->id}'";
                        }
                    }
                    // Handle creating / Updating the record in counterparty_json
                    if($sql){
                        $results2 = $mysqli->query($sql);
                        if(!$results2){
                            bye("Error creating / updating json record: {$sql}");
                        }
                    }
                }
            }
        }
    } else {
        bye('Error looking up asset');
    }

    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms]\n";

    // Save block# to state file (so we can resume from this block next run)
    if(!$all && !$asset)
        file_put_contents(LASTFILE, $current);
}


// Remove the lockfile now that we are done running
removeLockFile();

print "Total Execution time: " . $runtime->finish() ." seconds\n";


?>