<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ErrorTip;

use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\ErrorTipExtension;
use PHPStan\Analyser\Scope;
use function sprintf;

final class EchoErrorTipExtension implements ErrorTipExtension
{

	public function getTip(Error $error, Node $node, Scope $scope): ?string
	{
		if ($error->getIdentifier() !== 'tests.echo' || !$node instanceof Echo_) {
			return null;
		}

		if ($error->getMessage() === 'none') {
			return null;
		}

		return sprintf('Extension tip for %s in namespace %s.', $error->getMessage(), $scope->getNamespace() ?? '');
	}

}
