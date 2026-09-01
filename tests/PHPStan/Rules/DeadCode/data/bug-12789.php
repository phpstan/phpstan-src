<?php declare(strict_types = 1);

namespace Bug12789;

class HelloWorld
{
	public function concat(string $str1, string $str2): string
	{
		$retVal = $str1 . $str2;
		if (strlen($retVal) > 4) {
			// Typo in next line - should be $retVal rather than $RetVal
			$RetVal = $str2 . $str1;
		}
		return $retVal;
	}
}
