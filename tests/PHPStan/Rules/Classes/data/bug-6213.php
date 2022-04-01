<?php

namespace Bug6213;

use DOMDocument;
use DOMText;

class HelloWorld
{
	public function sayHello(): void {
		$document = new DOMDocument('1.0', 'utf-8');
		$element = $document->createElement('div', 'content');

		// Incorrect! This is DOMNode|null not DOMElement|null
		// It's also possible to contain a DOMText node, which is not an instance
		// of DOMElement, but an instance of DOMNode!
		if ($element->firstChild instanceof DOMText) {
			// do something
		}
	}
}
