<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\ParentStmtTypesVisitor;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_reverse;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Stmt\Continue_>
 */
#[RegisteredRule(level: 0)]
final class ContinueTargetingSwitchRule implements Rule
{

	private const LOOP_TYPES = [
		Stmt\For_::class,
		Stmt\Foreach_::class,
		Stmt\Do_::class,
		Stmt\While_::class,
	];

	private const SCOPE_BOUNDARY_TYPES = [
		Node\Expr\Closure::class,
		Stmt\ClassMethod::class,
		Stmt\Function_::class,
	];

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function getNodeType(): string
	{
		return Stmt\Continue_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->phpVersion->warnsOnContinueTargetingSwitch()) {
			return [];
		}

		$depth = $node->num instanceof Node\Scalar\Int_ ? $node->num->value : 1;

		$remaining = $depth;
		$targetsSwitch = false;
		$nearestLoopDepth = null;
		foreach (array_reverse($node->getAttribute(ParentStmtTypesVisitor::ATTRIBUTE_NAME, [])) as $parentStmtType) {
			if (in_array($parentStmtType, self::SCOPE_BOUNDARY_TYPES, true)) {
				break;
			}

			$isLoop = in_array($parentStmtType, self::LOOP_TYPES, true);
			if (!$isLoop && $parentStmtType !== Stmt\Switch_::class) {
				continue;
			}

			$remaining--;
			if ($remaining === 0) {
				if (!$isLoop) {
					$targetsSwitch = true;
					continue;
				}

				return [];
			}

			if ($remaining >= 0 || !$isLoop) {
				continue;
			}

			$nearestLoopDepth = $depth - $remaining;
			break;
		}

		if (!$targetsSwitch) {
			return [];
		}

		$continueKeyword = $depth > 1 ? sprintf('continue %d', $depth) : 'continue';
		$breakKeyword = $depth > 1 ? sprintf('break %d', $depth) : 'break';
		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Keyword %s targeting switch is equivalent to %s.',
			$continueKeyword,
			$breakKeyword,
		))->identifier('continue.switch');

		if ($nearestLoopDepth !== null) {
			// the intention was most likely to continue the enclosing loop, so the fix is not obvious
			return [
				$errorBuilder
					->tip(sprintf(
						'Did you mean to use continue %d to continue the enclosing loop? Use %s to keep the current behaviour.',
						$nearestLoopDepth,
						$breakKeyword,
					))
					->build(),
			];
		}

		return [
			$errorBuilder
				->fixNode($node, static fn (Stmt\Continue_ $node) => new Stmt\Break_($node->num, ['comments' => $node->getComments()]))
				->build(),
		];
	}

}
