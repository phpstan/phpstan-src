<?php

namespace MissingExceptionMethodThrowsInternalErrors;

class Foo
{

	public function doFoo(string $format, string $haystack): string
	{
		if (strpos($haystack, '::') === false) {
			return $haystack;
		}

		return sprintf($format, $haystack);
	}

	public function doBar(int $size): \SplFixedArray
	{
		$array = new \SplFixedArray($size);
		$array->setSize($size * 2);

		return $array;
	}

}
