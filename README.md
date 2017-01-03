# Facebook like tracking
Track likes over a period of time for specific URLs

To use this, edit the index.php file and add the URLs you want to track likes of. For example if you want to see how many likes http://www.bbc.co.uk have you would store it as such:

```$apis[] = array('url'=>'http://bbc.co.uk','bbc');```

This woiuld need to be automatically run every 10 minutes by Cron (or if you leave the page open, it will auto-refresh)
