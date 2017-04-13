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

    if (isset($_GET['action'])){
      $action = $_GET['action'];
    }
    else if(isset($_SESSION['action'])){
      $action = $_SESSION['action'];
    }

    if(isset($videoId) && isset($action) && !isset($_GET['state'])) {

      file_put_contents('php://stderr', print_r("adding video to watch later playlist " . $videoId . "\n", TRUE));
      
      if ($action == "Add to Watch Later playlist") {
        $playlistId = "WL";
      }
      else {
          $listResponse = $youtube->channels->listChannels("contentDetails", array(
              'mine' => true
          ));

          if (!empty($listResponse)) {
            if ($action == "Add to Like playlist"){
              $playlistId = $listResponse['items'][0]['contentDetails']['relatedPlaylists']['likes'];
            }
            else if ($action == "Add to Favorite playlist"){
              $playlistId = $listResponse['items'][0]['contentDetails']['relatedPlaylists']['favorites'];
            }
          }
      }

      if (isset($playlistId)){

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
      }
      else{
        $response = "no playlist selected";
      }

      $_SESSION['video'] = "";
      $_SESSION["action"] = "";
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
} else {

  if(isset($_GET['video'])){

    $_SESSION["video"] = $_GET['video'];
    $_SESSION["action"] = $_GET['action'];
    
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
 <title>Add to playlists</title>
</head>
<body>
    <div>
        <form id="form" action="multi-playlist.php"">
            <input type="hidden"  name="video" value="EH3gqI2NAiE">
            <input name="action" type="submit" value="Add to Watch Later playlist" />
            <input name="action" type="submit" value="Add to Like playlist" />
            <input name="action" type="submit" value="Add to Favorite playlist" />
        </form>
        <div>
          <?php echo $response ?>
        </div>
    </div>
</body>
</html>