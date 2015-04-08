<?php
/* Copyright (C) MinSoo Kim */
/**
 * @class soo_kma_rss
 * @author MinSoo Kim (misol.kr@gmail.com)
 * @brief widget to display weather
 */
class soo_kma_rss extends WidgetHandler
{
	/**
	 * @brief Widget handler
	 *
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */

	function proc($args)
	{
		Context::loadLang($this->widget_path.'lang');
		$datas = $this->getRssItems($args);

		$output = $this->_compile($args,$datas);
		return $output;
	}

	function getRssItems($args)
	{
		$datas = array();
		$datas = $this->_getRssItems($args);

		return $datas;
	}

	/**
	 * @brief function to receive contents from rss url
	 * For Tistory blog in Korea, the original RSS url has location header without contents. Fixed to work as same as rss_reader widget.
	 */
	function requestFeedContents($rss_url)
	{
		$rss_url = str_replace('&amp;','&',Context::convertEncodingStr($rss_url));
		return FileHandler::getRemoteResource($rss_url, null, 3, 'GET', 'application/xml');
	}

	function _getRssItems($args)
	{
		$buff = $this->requestFeedContents($args->rss_url);

		$encoding = preg_match("/<\?xml.*encoding=\"(.+)\".*\?>/i", $buff, $matches);
		if($encoding && stripos($matches[1], "UTF-8") === FALSE) $buff = Context::convertEncodingStr($buff);

		$buff = str_replace('body>','wbody>',preg_replace("/<\?xml.*\?>/i", "", $buff));

		$oXmlParser = new XmlParser();
		$xml_doc = $oXmlParser->parse($buff);

		$item = $xml_doc->rss->channel->item;

		$pub_time = strtotime($item->description->header->tm->body);
		$link = htmlspecialchars(trim($item->link->body));
		$datas = $item->description->wbody->data;

		$today = array();
		$tomorrow = array();
		$after_tomorrow = array();
		foreach($datas as $data)
		{
			if($data->day->body == 0)
			{
				$today[$data->hour->body] = $data;
			}
			elseif($data->day->body == 1)
			{
				$tomorrow[$data->hour->body] = $data;
			}
			else
			{
				$after_tomorrow[] = $data;
			}
		}

		$output = new stdClass();
		$output->pubtime = $pub_time;
		$output->link = $link;
		$output->data = $datas;
		$output->today = $today;
		$output->tomorrow = $tomorrow;
		$output->after_tomorrow = $after_tomorrow;
		return $output;
	}

	function _compile($args,$datas)
	{
		$oTemplate = &TemplateHandler::getInstance();

		Context::set('location', $args->location);
		Context::set('datas', $datas);

		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		return $oTemplate->compile($tpl_path, 'weather');
	}
}
/* End of file soo_kma_rss.class.php */
/* Location: ./widgets/soo_kma_rss/soo_kma_rss.class.php */
