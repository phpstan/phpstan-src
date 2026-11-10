<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Scalar\InterpolatedString;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use function count;
use function preg_match;
use function strtolower;

#[AutowiredService]
final class DefinedConstantTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(private ConstantHelper $constantHelper)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return $functionReflection->getName() === 'defined'
			&& count($node->getArgs()) >= 1
			&& $context->true();
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$args = $node->getArgs();
		$constantNames = $scope->getType($args[0]->value)->getConstantStrings();

		if (count($constantNames) === 1) {
			if ($constantNames[0]->getValue() === '') {
				return new SpecifiedTypes([], []);
			}

			$expr = $this->constantHelper->createExprFromConstantName($constantNames[0]->getValue());
		} else {
			$expr = $this->createClassConstFetchFromDynamicName($args[0]->value, $scope);
		}

		if ($expr === null) {
			return new SpecifiedTypes([], []);
		}

		return $this->typeSpecifier->create(
			$expr,
			new MixedType(),
			$context,
			$scope,
		);
	}

	/**
	 * Recognizes `$class . '::NAME'`, `"$class::NAME"` and `"{$class}::NAME"`
	 * so that the guarded `$class::NAME` fetch is narrowed.
	 */
	private function createClassConstFetchFromDynamicName(Expr $nameExpr, Scope $scope): ?ClassConstFetch
	{
		if ($nameExpr instanceof Concat) {
			$classExpr = $nameExpr->left;
			$suffixes = $scope->getType($nameExpr->right)->getConstantStrings();
			if (count($suffixes) !== 1) {
				return null;
			}
			$suffix = $suffixes[0]->getValue();
		} elseif (
			$nameExpr instanceof InterpolatedString
			&& count($nameExpr->parts) === 2
			&& $nameExpr->parts[0] instanceof Expr
			&& $nameExpr->parts[1] instanceof InterpolatedStringPart
		) {
			$classExpr = $nameExpr->parts[0];
			$suffix = $nameExpr->parts[1]->value;
		} else {
			return null;
		}

		if (!$scope->getType($classExpr)->isString()->yes()) {
			return null;
		}

		if (preg_match('/^::([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)$/', $suffix, $matches) !== 1) {
			return null;
		}

		if (strtolower($matches[1]) === 'class') {
			return null;
		}

		return new ClassConstFetch($classExpr, new Identifier($matches[1]));
	}

}
