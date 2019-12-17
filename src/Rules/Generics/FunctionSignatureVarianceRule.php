<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Function_>
 */
class FunctionSignatureVarianceRule implements Rule
{

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $reflectionProvider;

	/** @var \PHPStan\Rules\Generics\VarianceCheck */
	private $varianceCheck;

	public function __construct(ReflectionProvider $reflectionProvider, VarianceCheck $varianceCheck)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->varianceCheck = $varianceCheck;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Function_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionName = $node->name->name;
		if (isset($node->namespacedName)) {
			$functionName = (string) $node->namespacedName;
		}
		$functionNameName = new Name($functionName);
		if (!$this->reflectionProvider->hasFunction($functionNameName, null)) {
			return [];
		}
		$functionReflection = $this->reflectionProvider->getFunction($functionNameName, null);

		return $this->varianceCheck->checkParametersAcceptor(
			ParametersAcceptorSelector::selectSingle($functionReflection->getVariants()),
			sprintf('in parameter %%s of function %s()', $functionName),
			sprintf('in return type of function %s()', $functionName),
			false
		);
	}

}
