<?php

namespace Bug9970;

class XML_Parser
{
	public $dummy = "a";

	function parse($data)
	{
		$parser = xml_parser_create();

		xml_set_object($parser, $this);

		xml_set_element_handler($parser, 'startHandler', 'endHandler');

		xml_parse($parser, $data, true);

		xml_parser_free($parser);
	}

	function startHandler($XmlParser, $tag, $attr)
	{
		$this->dummy = "b";
		throw new \Exception("ex");
	}

	function endHandler($XmlParser, $tag)
	{
	}
}

$p1 = new Xml_Parser();
try {
	$p1->parse('<tag1><tag2></tag2></tag1>');
	echo "Exception swallowed\n";
} catch (\Exception $e) {
	echo "OK\n";
}
