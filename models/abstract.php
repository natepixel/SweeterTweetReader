<?php
/**
 * Our basic abstract class requires any model to define these three methods
 *
 * get_remote_data
 * get_data_id
 *
 * When get_data is called, the following happens:
 *
 * - We get the data from a local source
 * - If the local source is not available, we get the data from a remote source
 * - If the local source data is older than config['lifespan']
 *      - create a randomized nonce that is associated with the config and model name
 *      - request sweeter_tweet_reader www/agent.php using the nonce key - the agent will update local source data with the remote data
 *      - if the remote data is not available, update the timestamp of the data_id with all the needed details, essentially resetting the timer.
 *
 * @todo should the nonce stuff be moved to controller??
 */
abstract class AbstractSweeterTweetReaderModel
{
	private $connection;
	private $config;
	
	final function __construct($conn, $name, &$config)
	{
		$this->connection = $conn;
		$this->model_name = $name;
		$this->config =& $config;
		if (!isset($this->config['lifespan'])) $this->config['lifespan'] = 300; // default to five minute lifespan
	}
	
	/**
	 * Live retrieval of the data - should have a reasonable timeout and return false in the case of failure.
	 */
	abstract public function get_remote_data();
	
	/**
	 * The data id should be derived from non-random elements such that it will always be a same for a cacheable request
	 */
	abstract public function get_data_id();
	
	/** 
	 * @todo update local data if we have to fetch remotely ...
	 */ 
	public function get_data()
	{
		$data = $this->get_local_data();
		if ($data === false) // cache does not exist - lets live update
		{
			$data = $this->update_data();
		}
		if ($this->data_is_expired())
		{
			$nonce = $this->create_nonce();
			$this->curl_post_async(SWEETER_TWEET_READER_WWW_URL . 'agent.php', array('sweeter_tweet_reader_nonce' => $nonce)); 
		}
		return $data;
	}
	
	/**
	 * Fetch remote data and update the cache timestamp
	 *
	 * If not remote data is grabbed and we have local data, we refresh the timestamp for the local data
	 */
	public function update_data()
	{
		$data = $this->get_remote_data();
		if (!empty($data))
		{
			$this->_twitterify($data);
			$id = $this->get_data_id();
			$cache = new ObjectCache($id);
			$cache->set($data);
		}
		else // lets update the timestamp anyway by fetching and resetting the local data (if it is fetchable)
		{
			$data = $this->get_local_data();
			if ($data)
			{
				$id = $this->get_data_id();
				$cache = new ObjectCache($id);
				$cache->set($data);
			}
		}
		return $data;
	}
	
	/**
	 * twitterify found here - thanks!
	 * http://www.snipe.net/2009/09/php-twitter-clickable-links/
	 */
	function _twitterify(&$tweets)
	{
		foreach ($tweets as $k=>$tweet)
		{
			$tweet_html = $tweet->text;
			$tweet_html = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $tweet_html);
			$tweet_html = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $tweet_html);
			$tweet_html = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $tweet_html);
			$tweet_html = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $tweet_html);
			$tweets[$k]->html = $tweet_html;
		}
	}
	
	final function data_is_expired()
	{
		$id = $this->get_data_id();
		$cache = new ObjectCache($id, $this->config['lifespan']); // lets see if the data if within our acceptable lifespan
		$data =& $cache->fetch();
		return (empty($data));
	}
	
	final function create_nonce()
	{
		$nonce_key = md5(uniqid(mt_rand(), true));
		$update_data['config'] = $this->get_config();
		$update_data['model'] = $this->model_name;
		$update = new ObjectCache($nonce_key);
		$update->set($update_data);
		return $nonce_key;
	}
	
	/**
	 * Using the data_id, retrieve the data from our local cache
	 */
	final function get_local_data()
	{
		$id = $this->get_data_id();
		$cache = new ObjectCache($id, 999999999); // we use an enormous expiration ... we want to get whatever exists.
		$data =& $cache->fetch();
		return $data;
	}
	
	public function get_connection()
	{
		return $this->connection;
	}
	
	public function get_config()
	{
		return $this->config;
	}
	
	/**
	 * asynchronous post request - grabbed from stack overflow - we'll try it unless something better comes along
	 *
	 * note that due to the nature of this if it starts failing you will never know it ... maybe we can build in an alert system?
	 *
	 * http://stackoverflow.com/questions/962915/how-do-i-make-an-asynchronous-get-request-in-php
	 *
	 * @todo consider mechanism to alert if the asynchronous update fails
	 */
	final function curl_post_async($abs_url, $params)
	{
		foreach ($params as $key => &$val)
		{
			if (is_array($val)) $val = implode(',', $val);
			$post_params[] = $key.'='.urlencode($val);
		}
		$post_string = implode('&', $post_params);
		$parts=parse_url($abs_url);
		$fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);
		$out = "POST ".$parts['path']." HTTP/1.1\r\n";
		$out.= "Host: ".$parts['host']."\r\n";
		$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out.= "Content-Length: ".strlen($post_string)."\r\n";
		$out.= "Connection: Close\r\n\r\n";
		if (isset($post_string)) $out.= $post_string;
		fwrite($fp, $out);
		fclose($fp);
	}
}
?>
