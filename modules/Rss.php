<?php
namespace amber\modules;

class Rss
{

    /**
     * $rss内容
     */
    private static $rss;

    /**
     * add channel info
     * $meta array
     * default array('title'=>'site title','link'=>'home location','description'=>'site description','lastBuildDate'=>'')
     */
    public static function addChannel($meta)
    {

        self::$rss = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0">
<channel>
	<title>' . $meta['title'] . '</title>
	<link>' . $meta['link'] . '</link>
	<description>' . $meta['description'] . '</description>
	<lastBuildDate>' . self::rfc822($meta['lastBuildDate']) . '</lastBuildDate>
  ';
    }

    /**
     * add item to rss
     * @param $item array
     *'pubDate'=>'unix timestamp'
     */
    public static function addItem($item = array())
    {
        self::$rss .= '
	<item>
    	<title>' . $item['title'] . '</title>
    	<link>' . $item['link'] . '</link>
    	<pubDate>' . self::rfc822($item['pubDate']) . '</pubDate>
    	<description><![CDATA[' . $item['description'] . ']]></description>
  	</item>';
    }

    /**
     * output rss info
     */
    public static function display($charset = 'utf-8')
    {
        self::_addEnd();
        header("Content-type:text/xml;charset=$charset");
        echo self::$rss;
    }

    /**
     * 时间转化
     */
    private function rfc822($timestamp = '')
    {
        if ($timestamp == '') {
            $timestamp = time();
        }

        $date = gmdate("D, d M Y H:i:s", $timestamp);
        $date .= " " . str_replace(":", "", "GMT");
        return $date;
    }

    /**
     * 添加rss的结尾
     */
    private function _addEnd()
    {
        self::$rss .= '</channel>
</rss>';
    }

}
