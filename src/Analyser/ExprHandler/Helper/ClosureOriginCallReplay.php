<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\ClosureOrigin;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_map;
use function array_slice;

/**
 * Calling a closure created by a first-class callable or a partial function
 * application calls its callee: the call is replayed as the callee's call
 * (ClosureOrigin::createCall()), so the closure call gets the callee's
 * dynamic return type, narrowing and by-ref effects. A union of such
 * closures replays each callee: the call returns what any of them returns,
 * narrows what all of them narrow and has the effects of any of them.
 *
 * Not shadowed by a native twin: it runs only for calls of such closures.
 */
#[ReferencedByTurboExtension(key: 'closureOriginCallReplay')]
final class ClosureOriginCallReplay
{

	/**
	 * The result of the call with the replayed callees' answers, or $result itself
	 * when a member of the callee type has no origin or its call does not map onto it.
	 */
	public static function replay(
		NodeScopeResolver $nodeScopeResolver,
		ExpressionResultFactory $expressionResultFactory,
		FuncCall $expr,
		ExpressionResult $nameResult,
		Type $calleeType,
		MutatingScope $scopeBeforeArgs,
		ExpressionResultStorage $storage,
		ExpressionResult $result,
	): ExpressionResult
	{
		$members = $calleeType instanceof UnionType ? $calleeType->getTypes() : [$calleeType];
		/** @var list<ClosureOrigin> $origins */
		$origins = [];
		$calls = [];
		foreach ($members as $member) {
			if (!$member instanceof ClosureType) {
				return $result;
			}
			$origin = $member->getOrigin();
			if ($origin === null) {
				return $result;
			}
			foreach ($origins as $seen) {
				if ($seen->equals($origin)) {
					continue 2;
				}
			}
			$call = $origin->createCall($expr, $member);
			if ($call === null) {
				return $result;
			}
			$origins[] = $origin;
			$calls[] = $call;
		}
		if ($calls === []) {
			return $result;
		}

		// the arguments are already processed: the replayed calls read their
		// stored results, walking from the same position
		$replayed = [];
		foreach ($calls as $call) {
			$replayed[] = $nodeScopeResolver->processExprOnDemand($call, $scopeBeforeArgs, $storage->duplicate());
		}

		$scope = $replayed[0]->getScope();
		$isAlwaysTerminating = $replayed[0]->isAlwaysTerminating();
		// the callee's throw points depend on the arguments (json_decode()'s flags),
		// the closure's are the ones of any call
		$throwPoints = [...$nameResult->getThrowPoints(), ...$replayed[0]->getThrowPoints()];
		foreach (array_slice($replayed, 1) as $replayedResult) {
			$scope = $scope->mergeWith($replayedResult->getScope());
			$isAlwaysTerminating = $isAlwaysTerminating && $replayedResult->isAlwaysTerminating();
			$throwPoints = [...$throwPoints, ...$replayedResult->getThrowPoints()];
		}

		// positional: simple-downgrader resolves named arguments only on $this->property->method() calls
		return $expressionResultFactory->create(
			$scope,
			$result->getBeforeScope(),
			$expr,
			$result->hasYield(),
			$result->isAlwaysTerminating() || $isAlwaysTerminating,
			$throwPoints,
			$result->getImpurePoints(),
			static fn (bool $nativeTypesPromoted): Type => TypeCombinator::union(...array_map(
				static fn (ExpressionResult $replayedResult): Type => $replayedResult->getKeepVoidType($nativeTypesPromoted),
				$replayed,
			)),
			static function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use ($replayed): SpecifiedTypes {
				$specifiedTypes = $replayed[0]->getSpecifiedTypes($context, $nativeTypesPromoted);
				foreach (array_slice($replayed, 1) as $replayedResult) {
					$specifiedTypes = $specifiedTypes->intersectWith($replayedResult->getSpecifiedTypes($context, $nativeTypesPromoted));
				}

				return $specifiedTypes;
			},
			false,
			null,
			null,
			null,
			static fn (Type $type, TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $result->getCreatedTypes($type, $context, $nativeTypesPromoted) ?? new SpecifiedTypes(),
			null,
			null,
			$result->getArgsResult(),
			$result->getVariableFlow(),
			$result->getDependencies(),
		);
	}

}
