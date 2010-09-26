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
			$str = '';
			foreach ($tweets as $tweet)
			{
				$str .= '<div class="twitter_status" id="'.$tweet->id.'">';
				$str .= '<img src="'.$tweet->user->profile_image_url.'" />';
				$str .= '<p><a href="http://www.twitter.com/'.$tweet->user->screen_name.'">'.$tweet->user->screen_name.'</a>';
				$str .= ' <span class="tweet_content">'.$tweet->html.'</span>';
				$str .= ' <span class="twitter_posted_at">'.$tweet->created_at.'</span>';
				if (!empty($tweet->source))
				{
					$str .= ' <span class="tweet_source">via '.$tweet->source.'</span>';
				}
				$str .= '</p>';
			}
			return $str;
		}
	}
}
?>