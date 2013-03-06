<?php
/**
 * SweeterTweetReader is a little php utility that grabs tweets from Twitter using OAuth authentication.
 *
 * It is designed for a single sign-on scenario where you have a single twitter account (probably your own) that you want to 
 * pull from and display tweets in a custom way.
 * 
 * It recognizes that twitter is flaky, and eschews a javascript only approach with can result in hung pages.
 *
 * Models based upon this default model will always return the cached results, and then do a background request to update 
 * if a fixed amount of time has passed. This should stop most php timeouts when twitter is flaky.
 *
 * SweetTweetReader lets you plug in various models and view - or use the default
 *
 * The following models and views are distributed with SweeterTweeter:
 *
 * Models
 *
 * - public_timeline (default)
 * - home_timeline
 *
 * Views
 *
 * - sweetness (default)
 * - minimal
 */

/**
 * Include dependencies 
 */
require_once('config.php');
require_once( TWITTEROAUTH_PATH.'twitteroauth/twitteroauth.php');
include_once( 'lib/object_cache/object_cache.php');

/** 
 * The bare bones use case - default model and view
 * 
 * <code>
 * $sweet_tweet = new SweeterTweetReader($oauth_connection);
 * $tweet_html = $sweet_tweet->get();
 * </code>
 *
 * Use case with a custom model and view
 *
 * <code>
 * $sweet_tweet = new SweetTweetReader($oauth_connection);
 * $sweet_tweet->set('home_timeline'); // references the home_timeline.php model in the model directory
 $ $tweet_html = $sweet_tweet->get('minimal'); // references the minimal.php view in the views directory (...create me)
 * </code>
 *
 * @uses http://github.com/abraham/twitteroauth
 * @since May 27, 2010
 * @author Nathan White
 * @license http://creativecommons.org/licenses/MIT/ MIT License
 */
final class SweeterTweetReader
{
	/**
	 * TwitterOAuth connection object
	 * @see http://github.com/abraham/twitteroauth
	 * @var object 
	 */
	private $connection = NULL;

	/**
	 * @var string
	 */
	private $default_model = 'public_timeline';
	
	/**
	 * @var string
	 */
	private $default_view = 'sweetness';

	/**
	 * @var string
	 */
	private $default_config = array('lifespan' => '30'); // 30 seconds is 120 times per hour max
	
	/**
	 * We imply we support a connection being set externally but we don't really ... at least not with the current nonce system and no revisions to www/agent.php
	 */
	public function SweeterTweetReader($connection = NULL)
	{
		if ($connection) $this->set_connection($connection);
		elseif (defined("TWITTEROAUTH_CONSUMER_KEY") && defined("TWITTEROAUTH_CONSUMER_SECRET") && defined("TWITTEROAUTH_TOKEN") && defined("TWITTEROAUTH_SECRET")) // instantiate from our config.php
		{
			$connection = new TwitterOAuth(TWITTEROAUTH_CONSUMER_KEY, TWITTEROAUTH_CONSUMER_SECRET, TWITTEROAUTH_TOKEN, TWITTEROAUTH_SECRET);
			$this->set_connection($connection);
		}
	}
	
	/**
	 * @param object TwitterOAuth Connection Object
	 * @return boolean
	 */
	public function set_connection($connection)
	{
		if (!isset($this->connection))
		{
			$this->connection = $connection;
			return true;
		}
		trigger_error('The connection has already been set');
		return false;
	}
	
	/**
	 * Set the model - provide a filename relative to ./models or the absolute path of model (the .php is optional)
	 * @param string $model
	 */
	public function set($model)
	{
		$this->_model = $model;
	}
	
	/**
	 * Return the model - instantiate it if necessary
	 * @access private
	 */
	private function &get_model()
	{
		if (!isset($this->_model)) $this->_model = $this->default_model;
		$model_name = $this->_get_class_name($this->_model, 'models');
		if ($model_name)
		{
			$config =& $this->get_config();
			$model = new $model_name($this->connection, $this->_model, $config);
			return $model;
		}
		trigger_error('the model ' . $this->_model . ' could not be found', E_USER_ERROR);
		return false;
	}

	/**
	 * Instantiate and return the view
	 * @access private
	 */
	private function get_view($view)
	{
		$view_name = $this->_get_class_name($view, 'views');
		if ($view_name)
		{
			$view = new $view_name;
			return $view;
		}
		trigger_error('the view ' . $view . ' could not be found.', E_USER_ERROR);
		return false;
	}

	public function &get_config()
	{
		if (!isset($this->_config)) $this->_config = $this->default_config;
		return $this->_config;
	}
	
	public function get($view = NULL)
	{
		if (!$this->connection) trigger_error('You must set a connection in the constructor or using the set_connection method before called the get method.');
		else
		{
			$view = ($view == NULL) ? $this->default_view : $view;
			$model = $this->get_model();
			if ($model)
			{
				$data = $model->get_data();
				$view = $this->get_view($view);
				return $view->get($data);
			}
		}
	}

	public function update()
	{
		if (!$this->connection) trigger_error('You must set a connection in the constructor or using the set_connection method before called the get method.');
		else
		{
			$model = $this->get_model();
			$model->update_data();
		}
	}
	
	/**
	 * Setup properties - models have access to the config
	 */
	public function &config($config_array_or_setting, $setting_value = NULL)
	{
		if (is_array($config_array_or_setting))
		{
			foreach ($config_array_or_setting as $k=>$v)
			{
				$this->config($k, $v);
			}
		}
		else
		{
			$this->config[$config_array_or_setting] = $setting_value;
		}
		return $this->get_config();
	}
	
	/**
	 * Convert the filename into camel case
	 */
	protected function _camel_case($str)
	{
		$str = str_replace(' ', '', ucwords(str_replace(array("-", "_"), ' ', $str)));
		return $str;
	}
	
	protected function _get_class_name($name, $dir)
	{
		$class_path = (substr($name, -4) != ".php") ? $name . '.php' : $name;
		$class_path = (realpath(dirname(__FILE__) . '/' . $dir . '/' . $class_path)) ? realpath(dirname(__FILE__) . '/' . $dir . '/' . $class_path) : $class_path;
		if (file_exists($class_path))
		{
			include_once($class_path);
			$class_basename = basename($class_path, ".php");
			$class_camel_case = $this->_camel_case($class_basename);
			$class_name = (class_exists($class_camel_case)) ? $class_camel_case : $class_basename;
			return $class_name;
		}
	}	
}
?>
