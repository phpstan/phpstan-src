<?php

namespace DOMNodeStubsAccessProperties;

function basic_node(\DOMNode $node): void {
	var_dump($node->attributes);
}

function element_node(\DOMElement $element): void
{
	if ($element->hasAttribute('class')) {
		$attribute = $element->getAttributeNode('class');
		echo $attribute->value;
	}
}

function element_node_attribute_fetch_via_attributes_property(\DOMElement $element): void
{
	$attribute = $element->attributes->getNamedItem('class');
	if ($attribute === null) {
		return;
	}
	echo $attribute->value;
}

function element_node_attribute_fetch_via_getAttributeNode(\DOMElement $element): void
{
	$attribute = $element->getAttributeNode('class');
	if ($attribute === null) {
		return;
	}
	echo $attribute->value;
}
