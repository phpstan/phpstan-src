<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function strtolower;
use const PREG_OFFSET_CAPTURE;
use const PREG_UNMATCHED_AS_NULL;

final class PregMatchTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private RegexShapeMatcher $regexShapeMatcher)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['preg_match'], true);
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (count($args) < 2) {
			return new SpecifiedTypes();
		}

		$patternArg = $args[0] ?? null;
		$matchesArg = $args[2] ?? null;
		$flagsArg = $args[3] ?? null;

		if ($patternArg === null || $matchesArg === null) {
			return new SpecifiedTypes();
		}

		$patternType = $scope->getType($patternArg->value);
		$constantStrings = $patternType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return new SpecifiedTypes();
		}

		$flags = null;
		if ($flagsArg !== null) {
			$flagsType = $scope->getType($flagsArg->value);

			if (
				!$flagsType instanceof ConstantIntegerType
				|| !in_array($flagsType->getValue(), [PREG_OFFSET_CAPTURE, PREG_UNMATCHED_AS_NULL, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL], true)
			) {
				return new SpecifiedTypes();
			}

			$flags = $flagsType->getValue();
		}

		$matchedTypes = [];
		foreach ($constantStrings as $constantString) {
			$matchedTypes[] = $this->regexShapeMatcher->matchType($constantString->getValue(), $flags, $context);
		}

		return $this->typeSpecifier->create(
			$matchesArg->value,
			TypeCombinator::union(...$matchedTypes),
			$context,
			false,
			$scope,
		);
	}

}
