<?php declare(strict_types = 1);

namespace PHPStan\Rules\Regexp;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;

final class RegularExpressionHelper
{

	public function validatePattern(string $pattern): ?string
	{
		try {
			Strings::match('', $pattern);
		} catch (RegexpException $e) {
			return $e->getMessage();
		}

		return null;
	}

}
