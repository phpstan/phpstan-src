<?php

declare(strict_types=1);

use function PHPStan\Testing\assertType;

class MbStrlenPhp7
{

	/**
	 * @param 'utf-8'|'8bit' $utf8And8bit
	 * @param 'utf-8'|'foo' $utf8AndInvalidEncoding
	 */
	public function doFoo($utf8And8bit, $utf8AndInvalidEncoding, string $unknownEncoding)
	{
		assertType('8', mb_strlen('паляниця', 'utf-8'));
		assertType('11', mb_strlen('alias test🤔', 'utf8'));
		assertType('false', mb_strlen('', 'invalid encoding'));
		assertType('int<5, 6>', mb_strlen('école', $utf8And8bit));
		assertType('5|false', mb_strlen('école', $utf8AndInvalidEncoding));
		assertType('1|3|5|6|false', mb_strlen('école', $unknownEncoding));
		assertType('2|4|5|6|8|false', mb_strlen('מזגן', $unknownEncoding));
		assertType('6|8|12|13|15|18|24|false', mb_strlen('いい天気ですね〜', $unknownEncoding));
		assertType('3|false', mb_strlen(123, $utf8AndInvalidEncoding));
	}

}
