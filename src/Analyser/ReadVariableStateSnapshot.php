<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * The (certainty, type) state of every variable an expression reads, captured
 * at its evaluation position - the retained half of
 * ExpressionResult::askScopeVariableStateMatches() without keeping the walk
 * scopes alive. Consumed by FiberNodeScopeResolver's flush memo.
 */
final class ReadVariableStateSnapshot
{

	/**
	 * PHP coerces numeric-string variable names to int array keys, hence the
	 * union and the casts below.
	 *
	 * @param array<int|string, array{TrinaryLogic, ?Type, TrinaryLogic, ?Type}> $variableStates
	 */
	public function __construct(private array $variableStates)
	{
	}

	public function matches(MutatingScope $askScope): bool
	{
		if ($this->variableStates === []) {
			return true;
		}

		$nativeAskScope = $askScope->doNotTreatPhpDocTypesAsCertain();
		foreach ($this->variableStates as $name => [$knows, $type, $nativeKnows, $nativeType]) {
			if (
				!$this->flavourMatches($askScope, (string) $name, $knows, $type)
				|| !$this->flavourMatches($nativeAskScope, (string) $name, $nativeKnows, $nativeType)
			) {
				return false;
			}
		}

		return true;
	}

	private function flavourMatches(MutatingScope $scope, string $name, TrinaryLogic $positionKnows, ?Type $positionType): bool
	{
		$askKnows = $scope->hasVariableType($name);
		if ($askKnows->no() && $positionKnows->no()) {
			return true;
		}
		if (!$askKnows->equals($positionKnows)) {
			return false;
		}
		if ($positionType === null) {
			return false;
		}

		return $scope->getVariableType($name)->equals($positionType);
	}

}
