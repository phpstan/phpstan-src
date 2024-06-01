<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class StringReplaceSubjectParameterRule implements Rule
{

	private const SUBJECT_PARAMETER_POSITION = [
		'strtr' => 0,
		'str_replace' => 2,
		'preg_replace' => 2,
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) < 3) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		if (!array_key_exists($functionReflection->getName(), self::SUBJECT_PARAMETER_POSITION)) {
			return [];
		}

		$constantLiteralCount = 0;
		for ($i = 0; $i < 3; $i++) {
			if (
				$i === self::SUBJECT_PARAMETER_POSITION[$functionReflection->getName()]
				&& !$args[$i]->value instanceof Node\Scalar\String_
			) {
				return [];
			}

			if (!$args[$i]->value instanceof Node\Scalar\String_) {
				continue;
			}

			$constantLiteralCount++;
		}

		if ($constantLiteralCount !== 2) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Argument #%d in unexpected position in call to function %s.',
				self::SUBJECT_PARAMETER_POSITION[$functionReflection->getName()],
				$functionReflection->getName(),
			))
			->identifier('argument.unexpectedPosition')
			->build(),
		];
	}

}
