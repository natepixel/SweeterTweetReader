<?php
/**
 * Sweeter Tweet Reader configuration file.
 *
 * Copy this to a file called config.php.
 *
 * Edit config.php with your settings.
 */

/**
 * The full path to the sweeter_tweet_reader_directory
 */
define("SWEETER_TWEET_READER_PATH", dirname(__FILE__)."/");

/**
 * aboslute url of the www directory alias
 */
define("SWEETER_TWEET_READER_WWW_URL", 'http://127.0.0.1:8888/sweeter_tweet_reader/');

/**
 * The path to an instance of twitteroauth - defaults to the one distributed with Sweeter Tweet Reader
 */
define("TWITTEROAUTH_PATH", dirname(__FILE__)."/lib/twitteroauth/");
define("TWITTEROAUTH_CONSUMER_KEY", "");
define("TWITTEROAUTH_CONSUMER_SECRET","");
define("TWITTEROAUTH_TOKEN","");
define("TWITTEROAUTH_SECRET","");
?>
