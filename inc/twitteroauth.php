<?php

require_once('twitteroauth/twitteroauth.php');
session_start();

/*redirect*/
function twitter_oauth_redirect()
{
	global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require_once("../wp-load.php");
require_once('twitteroauth/twitteroauth.php');

define('CONSUMER_KEY', get_option('twitter_oauth_consumer_key'));
define('CONSUMER_SECRET', get_option('twitter_oauth_consumer_secret'));
define('OAUTH_CALLBACK', get_site_url() . '/wp-admin/admin-ajax.php?action=twitter_oauth_callback');

	/* Build TwitterOAuth object with client credentials. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
 
/* Get temporary credentials. */
$request_token = $connection->getRequestToken(OAUTH_CALLBACK);

/* Save temporary credentials to session. */
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
 
/* If last connection failed don't display authorization link. */
switch ($connection->http_code) {
  case 200:
    /* Build authorize URL and redirect user to Twitter. */
    $url = $connection->getAuthorizeURL($token);
    header('Location: ' . $url); 
    break;
  default:
    /* Show notification if something went wrong. */
    header('Location: ' . get_site_url());
}
die();
}

add_action("wp_ajax_twitter_oauth_redirect", "twitter_oauth_redirect");
add_action("wp_ajax_nopriv_twitter_oauth_redirect", "twitter_oauth_redirect");

/*callback*/
function twitter_oauth_callback()
{

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require_once("../wp-load.php");
require_once('twitteroauth/twitteroauth.php');

define('CONSUMER_KEY', get_option('twitter_oauth_consumer_key'));
define('CONSUMER_SECRET', get_option('twitter_oauth_consumer_secret'));
define('OAUTH_CALLBACK', get_site_url() . '/wp-admin/admin-ajax.php?action=twitter_oauth_callback');
/* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

/* Save the access tokens. Normally these would be saved in a database for future use. */
$_SESSION['twitter_access_token'] = $access_token;

/* Remove no longer needed request tokens */
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);
/* If HTTP response is 200 continue otherwise send to connect page to retry */
if (200 == $connection->http_code) {
  /* The user has been verified and the access tokens can be saved for future use */
  $_SESSION['status'] = 'verified';
  header('Location: ' . get_site_url() . '/wp-admin/admin-ajax.php?action=twitter_oauth_login');
} else {
  /* Save HTTP status for error dialog on connnect page.*/
  header('Location: ' . get_site_url());
}
die();
}

add_action("wp_ajax_twitter_oauth_callback", "twitter_oauth_callback");
add_action("wp_ajax_nopriv_twitter_oauth_callback", "twitter_oauth_callback");

/*login into wordpress using wp_set_auth_cookie*/
function twitter_oauth_login()
{

global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require_once("../wp-load.php");
require_once('twitteroauth/twitteroauth.php');


define('CONSUMER_KEY', get_option('twitter_oauth_consumer_key'));
define('CONSUMER_SECRET', get_option('twitter_oauth_consumer_secret'));
define('OAUTH_CALLBACK', get_site_url() . '/wp-admin/admin-ajax.php?action=twitter_oauth_callback');
	$access_token = $_SESSION['twitter_access_token'];
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
	$content = $connection->get('account/verify_credentials');
	
	if(username_exists($content->screen_name))
	{
		$user_id = username_exists($content->screen_name);
		wp_set_auth_cookie($user_id);
		update_user_meta($user_id, "twitter_access_token", $access_token['oauth_token']);
		update_user_meta($user_id, "twitter_secret_access_token", $access_token['oauth_token_secret']);
		header('Location: ' . get_site_url());
	}
	else
	{
		//create a new account and then login
		wp_create_user($content->screen_name, $content->id);
		$user_id = username_exists($content->screen_name);
		wp_set_auth_cookie($user_id);
		update_user_meta($user_id, "twitter_access_token", $access_token['oauth_token']);
		update_user_meta($user_id, "twitter_secret_access_token", $access_token['oauth_token_secret']);
		header('Location: ' . get_site_url());
	}
	
	die();
}

add_action("wp_ajax_twitter_oauth_login", "twitter_oauth_login");
add_action("wp_ajax_nopriv_twitter_oauth_login", "twitter_oauth_login");
