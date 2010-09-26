<?php
include_once(realpath(dirname(__FILE__) . '/../interfaces/model.php'));
include_once(realpath(dirname(__FILE__) . '/abstract.php'));

/**
 * Should we put twitterify and such in the base class?
 */
class HomeTimeline extends AbstractSweeterTweetReaderModel implements SweeterTweetReaderModel
{
	/**
	 * @todo the error stuff should be abstracted out and not implemented per model
	 */
	public function get_remote_data()
	{
		$connection = $this->get_connection();
		$connection->decode_json = false;
		$json = $connection->get("statuses/home_timeline", array('count' => 20));
		$tweets = json_decode($json);
		if (empty($tweets->error))
		{
			$this->filter_protected($tweets);
		}
		else
		{
			trigger_error('Could not get remote data - twitteroauth returned error "'.$tweets->error.'"', E_USER_WARNING);
			$tweets = false;
		}
		return $tweets;
	}
	
	public function get_data_id()
	{
		return 'home_timeline';
	}
	
	private function filter_protected(&$tweets)
	{
		foreach ($tweets as $k=>$tweet)
		{
			if ($tweet->user->protected) unset ($tweets[$k]);
		}
	}
}
?>