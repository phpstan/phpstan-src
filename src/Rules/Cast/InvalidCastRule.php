<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function get_class;
use function sprintf;
use function strtolower;
use function substr;

/**
 * @implements Rule<Node\Expr\Cast>
 */
class InvalidCastRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Cast::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$castTypeCallback = static function (Type $type) use ($node): ?array {
			if ($node instanceof Node\Expr\Cast\Int_) {
				return [$type->toInteger(), 'int'];
			} elseif ($node instanceof Node\Expr\Cast\Bool_) {
				return [$type->toBoolean(), 'bool'];
			} elseif ($node instanceof Node\Expr\Cast\Double) {
				return [$type->toFloat(), 'double'];
			} elseif ($node instanceof Node\Expr\Cast\String_) {
				return [$type->toString(), 'string'];
			}

			return null;
		};

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'',
			static function (Type $type) use ($castTypeCallback): bool {
				$castResult = $castTypeCallback($type);
				if ($castResult === null) {
					return true;
				}

				[$castType] = $castResult;

				return !$castType instanceof ErrorType;
			},
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return [];
		}

		$castResult = $castTypeCallback($type);
		if ($castResult === null) {
			return [];
		}

		[$castType, $castIdentifier] = $castResult;

		if ($castType instanceof ErrorType) {
			$classReflection = $this->reflectionProvider->getClass(get_class($node));
			$shortName = $classReflection->getNativeReflection()->getShortName();
			$shortName = strtolower($shortName);
			if ($shortName === 'double') {
				$shortName = 'float';
			} else {
				$shortName = substr($shortName, 0, -1);
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot cast %s to %s.',
					$scope->getType($node->expr)->describe(VerbosityLevel::value()),
					$shortName,
				))->identifier(sprintf('cast.%s', $castIdentifier))->line($node->getStartLine())->build(),
			];
		}

		return [];
	}

}
