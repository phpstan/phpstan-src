<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use function array_key_exists;
use function count;
use function strtolower;

final class StrContainingTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private const STR_CONTAINING_FUNCTIONS = [
		'fnmatch' => [1, 0],
		'str_contains' => [0, 1],
		'str_starts_with' => [0, 1],
		'str_ends_with' => [0, 1],
		'strpos' => [0, 1],
		'strrpos' => [0, 1],
		'stripos' => [0, 1],
		'strripos' => [0, 1],
		'strstr' => [0, 1],
	];

	private TypeSpecifier $typeSpecifier;

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return array_key_exists(strtolower($functionReflection->getName()), self::STR_CONTAINING_FUNCTIONS)
			&& $context->true();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();

		if (count($args) >= 2) {
			[$hackstackArg, $needleArg] = self::STR_CONTAINING_FUNCTIONS[strtolower($functionReflection->getName())];

			$haystackType = $scope->getType($args[$hackstackArg]->value);
			$needleType = $scope->getType($args[$needleArg]->value);

			if ($needleType->isNonEmptyString()->yes() && $haystackType->isString()->yes()) {
				$accessories = [
					new StringType(),
				];

				if ($needleType->isNonFalsyString()->yes()) {
					$accessories[] = new AccessoryNonFalsyStringType();
				} else {
					$accessories[] = new AccessoryNonEmptyStringType();
				}

				if ($haystackType->isLiteralString()->yes()) {
					$accessories[] = new AccessoryLiteralStringType();
				}
				if ($haystackType->isNumericString()->yes()) {
					$accessories[] = new AccessoryNumericStringType();
				}

				return $this->typeSpecifier->create(
					$args[$hackstackArg]->value,
					new IntersectionType($accessories),
					$context,
					false,
					$scope,
					new BooleanAnd(
						new NotIdentical(
							$args[$needleArg]->value,
							new String_(''),
						),
						new FuncCall(new Name('FAUX_FUNCTION'), [
							new Arg($args[$needleArg]->value),
						]),
					),
				);
			}
		}

		return new SpecifiedTypes();
	}

}
