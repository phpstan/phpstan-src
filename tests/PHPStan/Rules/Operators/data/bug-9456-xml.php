<?php

namespace Bug9456Xml;

use SimpleXMLElement;

/**
 * @param SimpleXMLElement $xml
 */
function simplexml($xml): void
{
	if (isset($xml->modules)) {
		foreach ($xml->modules->module as $data) {
			/** @var SimpleXMLElement $data */
			$attributes = $data->attributes();
			$install = ($attributes['install'] == 1) ? true : false;
		}
	}
}
