<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class TypevalFamilyParametersRule implements Rule
{

	private const FUNCTIONS = [
		'intval',
		'floatval',
		'doubleval',
	];

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}
		$name = strtolower($node->name->toString());
		if (!in_array($name, self::FUNCTIONS, true)) {
			return [];
		}
		if (count($node->getArgs()) === 0) {
			return [];
		}

		$paramTypeCallback = static fn (Type $type): Type => $type->toString();

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->getArgs()[0]->value,
			'',
			static function (Type $type) use ($paramTypeCallback): bool {
				$paramType = $paramTypeCallback($type);

				return !$paramType instanceof ErrorType;
			},
		);
		$type = $typeResult->getType();
		$base = new ObjectType('');

		if ($type instanceof ErrorType || !$base->isSuperTypeOf($type)->maybe()) {
			return [];
		}

		$paramType = $paramTypeCallback($type);
		// only check if a stringable object is given, everything else is handled by the parameter typehint
		if (!$paramType instanceof ErrorType) {

			return [
				RuleErrorBuilder::message(sprintf(
					'Parameter #1 $value of function %s does not accept object, %s given.',
					$name,
					$scope->getType($node->getArgs()[0]->value)->describe(VerbosityLevel::value()),
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

}
