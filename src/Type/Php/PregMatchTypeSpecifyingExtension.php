<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use function in_array;
use function strtolower;

#[AutowiredService]
final class PregMatchTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(
		private RegexArrayShapeMatcher $regexShapeMatcher,
	)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['preg_match', 'preg_match_all'], true) && !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		$args = $node->getArgs();
		$patternArg = $args[0] ?? null;
		$subjectArg = $args[1] ?? null;
		$matchesArg = $args[2] ?? null;
		$flagsArg = $args[3] ?? null;

		if ($patternArg === null) {
			return new SpecifiedTypes();
		}

		$subjectTypes = new SpecifiedTypes();
		if (
			$subjectArg !== null
			&& $context->true()
			&& $scope->getType($subjectArg->value)->isString()->yes()
			&& !$this->isSubExprOfMatchesArg($subjectArg->value, $matchesArg !== null ? $matchesArg->value : null)
		) {
			$subjectType = $this->regexShapeMatcher->matchSubjectExpr($patternArg->value, $scope);
			if ($subjectType !== null) {
				$subjectTypes = $this->typeSpecifier->create(
					$subjectArg->value,
					$subjectType,
					$context,
					$scope,
				)->setRootExpr($node);
			}
		}

		if ($matchesArg === null) {
			return $subjectTypes;
		}

		$flagsType = null;
		if ($flagsArg !== null) {
			$flagsType = $scope->getType($flagsArg->value);
		}

		if ($context->true() && $context->falsey()) {
			$wasMatched = TrinaryLogic::createMaybe();
		} elseif ($context->true()) {
			$wasMatched = TrinaryLogic::createYes();
		} else {
			$wasMatched = TrinaryLogic::createNo();
		}

		if ($functionReflection->getName() === 'preg_match') {
			$matchedType = $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, $wasMatched, $scope);
		} else {
			$matchedType = $this->regexShapeMatcher->matchAllExpr($patternArg->value, $flagsType, $wasMatched, $scope);
		}
		if ($matchedType === null) {
			return $subjectTypes;
		}

		$overwrite = false;
		if ($context->false()) {
			$overwrite = true;
			$context = $context->negate();
		}

		$types = $this->typeSpecifier->create(
			$matchesArg->value,
			$matchedType,
			$context,
			$scope,
		)->setRootExpr($node);
		if ($overwrite) {
			$types = $types->setAlwaysOverwriteTypes();
		}

		return $types->unionWith($subjectTypes);
	}

	private function isSubExprOfMatchesArg(Expr $subject, ?Expr $matchesVar): bool
	{
		if ($matchesVar === null) {
			return false;
		}
		$rootVar = $subject;
		while ($rootVar instanceof ArrayDimFetch) {
			$rootVar = $rootVar->var;
		}
		return $rootVar instanceof Expr\Variable
			&& $matchesVar instanceof Expr\Variable
			&& $rootVar->name === $matchesVar->name;
	}

}
