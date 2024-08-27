<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use function in_array;
use function is_array;

final class FunctionCallStatementFinder
{

	/**
	 * @param string[] $functionNames
	 * @param mixed $statements
	 */
	public function findFunctionCallInStatements(array $functionNames, $statements): ?Node
	{
		foreach ($statements as $statement) {
			if (is_array($statement)) {
				$result = $this->findFunctionCallInStatements($functionNames, $statement);
				if ($result !== null) {
					return $result;
				}
			}

			if (!($statement instanceof Node)) {
				continue;
			}

			if ($statement instanceof FuncCall && $statement->name instanceof Name) {
				if (in_array((string) $statement->name, $functionNames, true)) {
					return $statement;
				}
			}

			$result = $this->findFunctionCallInStatements($functionNames, $statement);
			if ($result !== null) {
				return $result;
			}
		}

		return null;
	}

}
