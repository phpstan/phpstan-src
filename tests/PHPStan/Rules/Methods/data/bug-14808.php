<?php declare(strict_types = 1);

namespace Bug14808;

function (\DOMDocument $dom): void {
	$dom->loadHTML('<p>foo</p>', \LIBXML_SCHEMA_CREATE);
	$dom->loadHTML('<p>foo</p>', \LIBXML_NOERROR | \LIBXML_NOWARNING | \LIBXML_SCHEMA_CREATE);
	$dom->loadHTMLFile('foo.html', \LIBXML_SCHEMA_CREATE);
	$dom->load('foo.xml', \LIBXML_SCHEMA_CREATE | \LIBXML_HTML_NODEFDTD);
	$dom->loadXML('<p>foo</p>', \LIBXML_RECOVER);
	$dom->schemaValidate('foo.xsd', \LIBXML_NOENT);
};
