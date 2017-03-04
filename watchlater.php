<?php
/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();

$response = "";

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'YOUR_CLIENT_ID';
$OAUTH2_CLIENT_SECRET = 'YOUR_CLIENT_SECRET';
$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');

$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);

$client->setRedirectUri($redirect);
// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);
// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }
  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}
if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}
// Check to ensure that the access token was successfully acquired.

if ($client->getAccessToken()) {
  try {

  	$videoId = "";

  	if (isset($_GET['video'])){
		$videoId = $_GET['video'];
  	}
  	else if(isset($_SESSION['video'])){
  		$videoId = $_SESSION['video'];
  	}

  	if(isset($videoId) && !isset($_GET['state'])) {

  		file_put_contents('php://stderr', print_r("adding video to watch later playlist " . $videoId . "\n", TRUE));
  	
	    $playlistId = "WL";
	    // 5. Add a video to the playlist. First, define the resource being added
	    // to the playlist by setting its video ID and kind.
	    $resourceId = new Google_Service_YouTube_ResourceId();
	    $resourceId->setVideoId($videoId);
	    $resourceId->setKind('youtube#video');

	    // Then define a snippet for the playlist item. Set the playlist item's
	    // title if you want to display a different value than the title of the
	    // video being added. Add the resource ID and the playlist ID retrieved
	    // in step 4 to the snippet as well.
	    $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
	    $playlistItemSnippet->setTitle('First video in the test playlist');
	    $playlistItemSnippet->setPlaylistId($playlistId);
	    $playlistItemSnippet->setResourceId($resourceId);
	    // Finally, create a playlistItem resource and add the snippet to the
	    // resource, then call the playlistItems.insert method to add the playlist
	    // item.
	    $playlistItem = new Google_Service_YouTube_PlaylistItem();
	    $playlistItem->setSnippet($playlistItemSnippet);

	    $playlistItemResponse = $youtube->playlistItems->insert(
	        'snippet,contentDetails', $playlistItem, array());

	    $response = json_encode($playlistItem);

	    $_SESSION['video'] = "";
	}
	else{
		file_put_contents('php://stderr', print_r("no video was specified", TRUE));
	}

  } catch (Google_Service_Exception $e) {
  	$response = htmlspecialchars($e->getMessage());
  } catch (Google_Exception $e) {
    $response = htmlspecialchars($e->getMessage());
  }
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {

	if(isset($_GET['video'])){

	  $_SESSION["video"] = $_GET['video'];

	  // If the user hasn't authorized the app, initiate the OAuth flow
	  $state = mt_rand();
	  $client->setState($state);
	  $_SESSION['state'] = $state;
	  $authUrl = $client->createAuthUrl();
	  header('Location: ' . $authUrl);
	}
}
?>

<!doctype html>
<html>
<head>
 <title>Add to Watch Later playlist</title>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <style>
    .btn-tech {
        color: #2c3e50;
        border: solid 2px #2c3e50;
        background: transparent;
        transition: all 0.3s ease-in-out;
        margin: 20px;
        border-radius: 20% 20% 20% 20%;
    }
    
    .btn-tech:hover,
    .btn-tech:active,
    .btn-tech.active {
        color: #FFFFFF;
        background: #2c3e50;
        cursor: pointer;
    }
    </style>
    <script type="text/javascript">
	    function get_action(form) {
	        form.action = "insert";
	    }
	</script>
</head>
<body>
   <div id="watch_later">
        <form action="test.php" onsubmit="get_action(this);">

       		<label>Enter Video ID you want to add to Watch Later playlist :
                <input id="video-id" name="video" value='T4ZE2KtoFzs' type="text" />
            </label>

	        <div class="like" onClick="javascript:this.parentNode.submit();">
	            <a id="fb-link">
	                <span class="btn-tech fa-stack fa-3x">
	                  <i class="fa fa-thumbs-up fa-stack-1x"></i>
	                </span>
	            </a>
	        </div>
        </form>
        <div id="playlist-container">
        	<?php echo $response ?>
        </div>
        <p>
            <a href="https://www.youtube.com/playlist?list=WL">check your watch later playlist</a>
        </p>
    </div>
</body>
</html>