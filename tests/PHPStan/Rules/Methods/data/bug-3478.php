<?php

namespace Bug3478;

class ExtendedDocument extends \DOMDocument
{
	public function saveHTML(\DOMNode $node = null)
	{
		return parent::saveHTML($node);
	}
}
