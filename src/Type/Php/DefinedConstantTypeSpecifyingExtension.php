<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use function count;
use function explode;
use function ltrim;

class DefinedConstantTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

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
			&& $context->truthy();
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$constantName = $scope->getType($node->getArgs()[0]->value);
		if (
			!$constantName instanceof ConstantStringType
			|| $constantName->getValue() === ''
		) {
			return new SpecifiedTypes([], []);
		}

		$classConstParts = explode('::', $constantName->getValue());
		if (count($classConstParts) >= 2) {
			$classConstName = new Node\Name\FullyQualified(ltrim($classConstParts[0], '\\'));
			if ($classConstName->isSpecialClassName()) {
				$classConstName = new Node\Name($classConstName->toString());
			}
			$constNode = new Node\Expr\ClassConstFetch(
				$classConstName,
				new Node\Identifier($classConstParts[1]),
			);
		} else {
			$constNode = new Node\Expr\ConstFetch(
				new Node\Name\FullyQualified($constantName->getValue()),
			);
		}

		return $this->typeSpecifier->create(
			$constNode,
			new MixedType(),
			$context,
			false,
			$scope,
		);
	}

}
