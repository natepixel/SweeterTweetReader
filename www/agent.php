<?php
/**
 * The sweeter tweet reader agent handles the background requests when given a proper nonce.
 */

require_once( '../sweeter_tweet_reader.php');

if (isset($_REQUEST['sweeter_tweet_reader_nonce']))
{
	// retrieve the model and config from the nonce
	$sweet_tweet = new SweeterTweetReader();
	$update = new ObjectCache($_REQUEST['sweeter_tweet_reader_nonce'], 999999999);
	$data = $update->fetch();
	if (!empty($data))
	{
		$sweet_tweet->set($data['model']);
		$sweet_tweet->config($data['config']);
		$sweet_tweet->update();
	}
	$update->clear();
}
?>
