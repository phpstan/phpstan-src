<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Cast>
 */
class InvalidCastRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\Cast::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$castTypeCallback = static function (Type $type) use ($node): ?Type {
			if ($node instanceof \PhpParser\Node\Expr\Cast\Int_) {
				return $type->toInteger();
			} elseif ($node instanceof \PhpParser\Node\Expr\Cast\Bool_) {
				return $type->toBoolean();
			} elseif ($node instanceof \PhpParser\Node\Expr\Cast\Double) {
				return $type->toFloat();
			} elseif ($node instanceof \PhpParser\Node\Expr\Cast\String_) {
				return $type->toString();
			}

			return null;
		};

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->expr,
			'',
			static function (Type $type) use ($castTypeCallback): bool {
				$castType = $castTypeCallback($type);
				if ($castType === null) {
					return true;
				}

				return !$castType instanceof ErrorType;
			},
			false
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return [];
		}

		$castType = $castTypeCallback($type);
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
					$shortName
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

}
