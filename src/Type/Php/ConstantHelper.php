<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use function count;
use function explode;
use function ltrim;

class ConstantHelper
{

	public function createExprFromConstantName(string $constantName): Expr
	{
		$classConstParts = explode('::', $constantName);
		if (count($classConstParts) >= 2) {
			$classConstName = new FullyQualified(ltrim($classConstParts[0], '\\'));
			if ($classConstName->isSpecialClassName()) {
				$classConstName = new Name($classConstName->toString());
			}

			return new ClassConstFetch($classConstName, new Identifier($classConstParts[1]));
		}

		return new ConstFetch(new FullyQualified($constantName));
	}

}
