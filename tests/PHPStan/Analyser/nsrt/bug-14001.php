<?php declare(strict_types = 1);

namespace Bug14001;

use PHPStan\Analyser\StatementResult;
use PHPStan\Node\ExecutionEndNode;
use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param list<ExecutionEndNode> $executionEnds
	 */
	public function testWithEarlyTermination(array $executionEnds): void
	{
		$finalScope = null;
		foreach ($executionEnds as $executionEnd) {
			$statementResult = $executionEnd->getStatementResult();
			if ($statementResult->isAlwaysTerminating()) {
				continue;
			}
			if ($finalScope === null) {
				$finalScope = $statementResult->getScope();
				continue;
			}

			$finalScope = $finalScope->mergeWith($statementResult->getScope());
		}
		assertType('PHPStan\Analyser\MutatingScope|null', $finalScope);
	}

	/**
	 * @param list<ExecutionEndNode> $executionEnds
	 */
	public function testWithoutEarlyTermination(array $executionEnds): void
	{
		$finalScope = null;
		foreach ($executionEnds as $executionEnd) {
			$endScope = $executionEnd->getStatementResult()->getScope();
			if ($finalScope === null) {
				$finalScope = $endScope;
				continue;
			}

			$finalScope = $finalScope->mergeWith($endScope);
		}
		assertType('PHPStan\Analyser\MutatingScope|null', $finalScope);
	}
}
