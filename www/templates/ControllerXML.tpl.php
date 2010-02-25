<?php
UNL_MediaYak_OutputController::setOutputTemplate('UNL_MediaYak_FeedAndMedia', 'FeedAndMediaXML');
UNL_MediaYak_OutputController::setOutputTemplate('UNL_MediaYak_MediaList', 'MediaListXML');
if ($this->output instanceof UNL_MediaYak_MediaList
    || (is_array($this->output) && $this->output[0] instanceof UNL_MediaYak_MediaList)) {
 echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:itunesu="http://www.itunesu.com/feed" xmlns:media="http://search.yahoo.com/mrss/">
  <channel>
    <title>UNL MediaHub</title>
    <link><?php echo UNL_MediaYak_Controller::getURL(); ?></link>
    <description>Media from the University of Nebraska-Lincoln</description>
    <language>en-us</language>
    <pubDate><?php echo date('r'); ?></pubDate>
    <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
    <docs>http://www.rssboard.org/rss-specification</docs>
    <generator>UNL_MediaYak 0.1.0</generator>
    <managingEditor>brett.bieber@gmail.com (Brett Bieber)</managingEditor>
    <webMaster>brett.bieber@gmail.com (Brett Bieber)</webMaster>
    <ttl>5</ttl>
    <?php 
    echo UNL_MediaYak_OutputController::display($this->output, true);
    ?>
    </channel>
</rss>
<?php 
} else {
    echo UNL_MediaYak_OutputController::display($this->output, true);
}
