<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" dir="ltr">
<head>
<title>Tweets</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>

<?php
require_once('../../sweeter_tweet_reader.php');
$sweet_tweet = new SweeterTweetReader();
$sweet_tweet->set('home_timeline');
$tweet_html = $sweet_tweet->get();
echo $tweet_html;
?>

</body>
</html>
