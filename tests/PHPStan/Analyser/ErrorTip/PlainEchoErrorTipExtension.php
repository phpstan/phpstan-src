<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ErrorTip;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\ErrorTipExtension;
use PHPStan\Analyser\Scope;

final class PlainEchoErrorTipExtension implements ErrorTipExtension
{

	public function getTip(Error $error, Node $node, Scope $scope): ?string
	{
		if ($error->getMessage() !== 'plain') {
			return null;
		}

		return 'Second extension tip.';
	}

}
