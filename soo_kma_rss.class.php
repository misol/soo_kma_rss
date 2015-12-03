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

		$datas = new stdClass();
		$datas = $this->getRssItems($args);
		$datas->location_array = array();

		// location1 = datas
		$datas->location1 = $datas;
		$args->location1 = $args->location;
		$args->rss_url1 = $args->rss_url;

		if($args->rss_url2 != '' && isset($args->rss_url2))
		{
			$args2 = new stdClass();
			$args2->rss_url = $args->rss_url2;
			$datas->location2 = $this->getRssItems($args2);
		}

		for($location_point = 1; $location_point <= 10; $location_point++)
		{
			if($args->rss_url{$location_point} != '' && isset($args->rss_url{$location_point}))
			{
				$args2 = new stdClass();
				$args2->rss_url = $args->{"rss_url". $location_point};
				$datas->{"location" . $location_point} = $this->getRssItems($args2);

				$datas->location_array[$location_point] = new stdClass();
				$datas->location_array[$location_point]->data = $datas->{"location" . $location_point};
				$datas->location_array[$location_point]->name = trim($args->{"location". $location_point});
				$datas->location_array[$location_point]->rss_url = trim($args->{"rss_url" . $location_point});
			}
		}

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
	 * @brief function to read contents from web as weather rss url
	 */
	function requestFeedContents($rss_url)
	{
		$rss_url = str_replace('&amp;','&',Context::convertEncodingStr($rss_url));
		return FileHandler::getRemoteResource($rss_url, null, 3, 'GET', 'application/xml');
	}

	/**
	 * @brief function to read cache contents with weather rss url
	 */
	private function getCacheContents($rss_url)
	{
		$filename = sprintf('./files/cache/widget/soo_kma_rss/xml_php/%s.php', md5(trim($rss_url)));
		$filename = FileHandler::getRealPath($filename);
		$output = FileHandler::readFile($filename);

		if(is_readable($filename) && (filemtime($filename) > strtotime('-30 minutes')))
		{
			if(isset($output))
			{
				$output = trim(str_replace(array('<?php exit(); /**','SOO_END **/'),array('',''),$output));
				$output = unserialize($output);
				return $output;
			}
		}

		return NULL;
	}

	/**
	 * @brief function to write contents to cache with weather rss url
	 */
	private function writeCacheContents($rss_url, $xml_doc)
	{
		$filename = sprintf('./files/cache/widget/soo_kma_rss/xml_php/%s.php', md5(trim($rss_url)));
		$filename = FileHandler::getRealPath($filename);

		$buff = '<?php exit(); /**' . serialize($xml_doc) . 'SOO_END **/';
		FileHandler::writeFile($filename, $buff);
		return TRUE;
	}

	function _getRssItems($args)
	{
		$args->rss_url = trim($args->rss_url);
		$xml_doc = $this->getCacheContents($args->rss_url);
		if(!isset($xml_doc))
		{
			$buff = $this->requestFeedContents($args->rss_url);

			$encoding = preg_match("/<\?xml.*encoding=\"(.+)\".*\?>/i", $buff, $matches);
			if($encoding && stripos($matches[1], "UTF-8") === FALSE) $buff = Context::convertEncodingStr($buff);

			$buff = str_replace('body>','wbody>',preg_replace("/<\?xml.*\?>/i", "", $buff));

			$oXmlParser = new XmlParser();
			$xml_doc = $oXmlParser->parse($buff);
			$this->writeCacheContents($args->rss_url, $xml_doc);
		}

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

		ksort($today);
		ksort($tomorrow);
		ksort($after_tomorrow);

		reset($today);
		$recent = current($today);
		$recent_time = key($array);

		$output = new stdClass();
		$output->pubtime = $pub_time;
		$output->link = $link;
		$output->data = $datas;
		$output->recent = $recent;
		$output->recent_time = $recent_time;
		$output->today = $today;
		$output->tomorrow = $tomorrow;
		$output->after_tomorrow = $after_tomorrow;
		return $output;
	}

	function _compile($args,$datas)
	{
		$oTemplate = &TemplateHandler::getInstance();

		Context::set('args', $args);
		Context::set('location', $args->location);
		Context::set('location1', $args->location); //Alias of location(for dual location weather)
		Context::set('location2', $args->location2);
		Context::set('datas', $datas);

		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		return $oTemplate->compile($tpl_path, 'weather');
	}
}
/* End of file soo_kma_rss.class.php */
/* Location: ./widgets/soo_kma_rss/soo_kma_rss.class.php */
