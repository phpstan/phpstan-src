<?php

namespace Bug8938;

class HelloWorld
{
	public function processData(string $data): string
	{
		$returnValue = '';
		while (strlen($data) > 0) {
			$firstChar = substr($data, 0, 1);
			$data = substr($data, 1);
			$returnValue = $returnValue . $firstChar;
		}
		return $returnValue;
	}
}
