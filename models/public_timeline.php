<?php
include_once(realpath(dirname(__FILE__) . '/../interfaces/model.php'));
include_once(realpath(dirname(__FILE__) . '/abstract.php'));

class PublicTimeline extends AbstractSweeterTweetReaderModel implements SweeterTweetReaderModel
{
	public function get_remote_data()
	{
		$connection = $this->get_connection();
		$connection->decode_json = false;
		$content = $connection->get("statuses/public_timeline", array('count' => 20));
		$decoded_content = json_decode($content);
		return $decoded_content;
	}
	
	public function get_data_id()
	{
		return 'public_timeline';
	}
}
?>