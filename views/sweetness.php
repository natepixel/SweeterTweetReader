<?php
include_once(realpath(dirname(__FILE__) . '/../interfaces/view.php'));

// do we need an abstract view class? probably ... skipped for now

/**
 * Sweetness is a view that displays tweets in this format. 
 *
 * <div class="twitter_status" id="TWEET_ID">
 * <p>
 * <img src="TWEETER_IMAGE" class="twitter_image" />
 * <a href="http://twitter.com/TWEETER_SCREEN_NAME/">TWEETER_SCREEN_NAME</a>
 * <span class="tweet_content"> TWEET</span> <span class="twitter_posted_at">TWEET_CREATED_TIME</span> <span class="tweet_source">via SOURCE</span>
 * </p>
 */
class Sweetness implements SweeterTweetReaderView
{
	public function get($tweets)
	{
		if ($tweets)
		{
			$str = '<style>';
			$str .= 'div.twitter_status { clear: both; width: 100%; min-height: 48px; margin-bottom: 20px;}';
			$str .= 'div.twitter_status img { float: left; width: 48px; }'; // we force to 48px some are really large for some reason
			$str .= 'div.twitter_status p { margin-top: 0px; margin-bottom: 0px; padding-top: 0px; padding-bottom: 0px; margin-left: 60px; }';	
			$str .= '</style>';
			foreach ($tweets as $tweet)
			{
				$str .= '<div class="twitter_status" id="'.$tweet->id.'">';
				$str .= '<img src="'.$tweet->user->profile_image_url.'" />';
				$str .= '<p><strong>'.$tweet->user->name.'</strong> (<a href="http://www.twitter.com/'.$tweet->user->screen_name.'">'.$tweet->user->screen_name.'</a>):';
				$str .= ' <span class="tweet_content">'.$tweet->html.'</span>';
				$str .= ' <span class="twitter_posted_at">'.$this->timesince(strtotime($tweet->created_at)).' ago</span>';
				if (!empty($tweet->source))
				{
					$str .= ' <span class="tweet_source">via '.$tweet->source.'</span>';
				}
				$str .= '</p>';
				$str .= '</div>';
			}
			return $str;
		}
	}
	
	/**
	 * This code from http://viralpatel.net/blogs/2009/06/twitter-like-n-min-sec-ago-timestamp-in-php-mysql.html
	 *
	 * This converts the created_at date to a human readable twitter style string, such as "8 hours ago."
	 */
	public function timesince($original)
	{ 
		// array of time period chunks
		$chunks = array(
		array(60 * 60 * 24 * 365 , 'year'),
		array(60 * 60 * 24 * 30 , 'month'),
		array(60 * 60 * 24 * 7, 'week'),
		array(60 * 60 * 24 , 'day'),
		array(60 * 60 , 'hour'),
		array(60 , 'min'),
		array(1 , 'sec'),
		);
		
		$today = time(); /* Current unix time  */
		$since = $today - $original;
		
		// $j saves performing the count function each time around the loop
		for ($i = 0, $j = count($chunks); $i < $j; $i++)
		{
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			// finding the biggest chunk (if the chunk fits, break)
			if (($count = floor($since / $seconds)) != 0)
			{
				break;
			}
		}
		$print = ($count == 1) ? '1 '.$name : "$count {$name}s";
		if ($i + 1 < $j)
		{
			// now getting the second item
			$seconds2 = $chunks[$i + 1][0];
			$name2 = $chunks[$i + 1][1];
			// add second item if its greater than 0
			if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0)
			{
				$print .= ($count2 == 1) ? ', 1 '.$name2 : " $count2 {$name2}s";
			}
		}
		return $print;
	}
}