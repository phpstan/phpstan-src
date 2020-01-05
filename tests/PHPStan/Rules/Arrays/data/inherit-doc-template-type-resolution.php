<?php

namespace InheritDocTemplateTypeResolution;

class Foo extends \SimpleXMLElement
{

	public function removeThis(): void
	{
		unset($this[0]);

	}

}
