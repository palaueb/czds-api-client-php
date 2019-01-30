<?php
    ini_set('memory_limit','-1');

    $environtment = "production"; //testing or production
    
    include('do_authentication.php'); //include('http.php') inside this

##############################################################################################################
# First Step: Get the config data from config.json file
##############################################################################################################

    $cfname = 'config.'.$environtment.'.json';
    if(!file_exists($cfname)){
        echo "Error loading $cfname file.".PHP_EOL;
        exit(1);
    }
    $config_file = file_get_contents('config.'.$environtment.'.json');
    $config = json_decode($config_file, true);


    # The config.$environtment.json file must contain the following data:
    $username           = $config['icann.account.username'];
    $password           = $config['icann.account.password'];
    $authen_base_url    = $config['authentication.base.url'];
    $czds_base_url      = $config['czds.base.url'];


    # This is optional. Default to current directory
    $working_directory = $config['working.directory'];

    if(!isset($username)){
        echo "'icann.account.username' parameter not found in the $cfname file".PHP_EOL;
        exit(1);
    }
    if(!isset($password)){
        echo "'icann.account.password' parameter not found in the $cfname file".PHP_EOL;
        exit(1);
    }    
    if(!isset($authen_base_url)){
        echo "'authentication.base.url' parameter not found in the $cfname file".PHP_EOL;
        exit(1);
    }
    if(!isset($czds_base_url)){
        echo "'czds.base.url' parameter not found in the $cfname file".PHP_EOL;
        exit(1);
    }
    if(!isset($working_directory)){
        # Default to current directory
        $working_directory = '.';
    }


##############################################################################################################
# Second Step: authenticate the user to get an access_token.
# Note that the access_token is global for all the REST API calls afterwards
##############################################################################################################

    echo "Authenticate user $username".PHP_EOL;
    $access_token = authenticate($username, $password, $authen_base_url);


##############################################################################################################
# Third Step: Get the download zone file links
##############################################################################################################

# Function definition for listing the zone links
function get_zone_links($czds_base_url){
    global  $access_token;

    $links_url = $czds_base_url . "/czds/downloads/links";
    $links_response = do_get($links_url, $access_token);

    $status_code = $links_response['info']['http_code'];

    if($status_code == 200){
        $zone_links = json_decode($links_response['result'],true);
        echo now().": The number of zone files to be downloaded is ".count($zone_links).PHP_EOL;
        return $zone_links;
    }elseif($status_code == 401){
        global $username, $password, $authen_base_url;
        echo "The access_token has been expired. Re-authenticate user $username".PHP_EOL;
        $access_token = authenticate($username, $password, $authen_base_url);
        return get_zone_links($czds_base_url);
    }else{
        echo "Failed to get zone links from $links_url with error code $status_code".PHP_EOL;
        return false;
    }
}

# Get the zone links
$zone_links = get_zone_links($czds_base_url);
if($zone_links===false){
    exit(1);
}

##############################################################################################################
# Fourth Step: download zone files
##############################################################################################################

# Function definition to download one zone file
function download_one_zone($url, $output_directory){
    global  $access_token;

    echo now().": Downloading zone file from $url".PHP_EOL;

    $ptmp = explode("/", $url);
    $end = end($ptmp);
    $pcur = explode(".",$end);
    $tmp_file = current($pcur);

    if(file_exists($output_directory ."/". $tmp_file.".txt.gz")){
        echo "Zone file already downloaded from $url".PHP_EOL;
        return true;
    }

    $download_zone_response = do_get($url, $access_token);
    $status_code = $download_zone_response['info']['http_code'];
    

    if($status_code == 200){
        # Try to get the filename from the header
        $content_disposition = $download_zone_response['head']['Content-Disposition'];
        preg_match_all('/="?([^"]*)/', $content_disposition, $maturl, PREG_SET_ORDER, 0);
        $filename = $maturl[0][1];

        # If could get a filename from the header, then makeup one like [tld].txt.gz
        if(!isset($filename)){
            $end = end(explode("/", $url));
            $filename = current(explode(".",$end)).".txt.gz";
        }

        # This is where the zone file will be saved
        $path = $output_directory ."/". $filename;
        file_put_contents($path,$download_zone_response['result']);

        echo now().": Completed downloading zone to file $path".PHP_EOL;

    }else if($status_code == 401){
        echo "The access_token has been expired. Re-authenticate user $username".PHP_EOL;
        global $username, $password, $authen_base_url;
        $access_token = authenticate($username, $password, $authen_base_url);
        download_one_zone($url, $output_directory);
    }else if($status_code == 404){
        echo "No zone file found for $url".PHP_EOL;
    }else{
        echo "Failed to download zone from $url with code $status_code".PHP_EOL;
    }
}




# Function definition for downloading all the zone files
function download_zone_files($urls, $working_directory){

    # The zone files will be saved in a sub-directory
    $output_directory = $working_directory . "/zonefiles";

    if(!is_dir($output_directory)){
        mkdir($output_directory, 0777, true);
    }

    # Download the zone files one by one
    foreach($urls as $link){
        download_one_zone($link, $output_directory);
    }
}

# Finally, download all zone files
$start_time = mktime();
download_zone_files($zone_links, $working_directory);
$end_time = mktime();

echo now().": DONE DONE. Completed downloading all zone files. Time spent: ".gmdate("H:i:s", $end_time - $start_time);



?>