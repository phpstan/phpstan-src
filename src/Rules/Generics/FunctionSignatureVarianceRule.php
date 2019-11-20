<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Function_>
 */
class FunctionSignatureVarianceRule implements Rule
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var \PHPStan\Rules\Generics\VarianceCheck */
	private $varianceCheck;

	public function __construct(Broker $broker, VarianceCheck $varianceCheck)
	{
		$this->broker = $broker;
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
		if (!$this->broker->hasCustomFunction($functionNameName, null)) {
			return [];
		}
		$functionReflection = $this->broker->getCustomFunction($functionNameName, null);

		return $this->varianceCheck->checkParametersAcceptor(
			ParametersAcceptorSelector::selectSingle($functionReflection->getVariants()),
			sprintf('in parameter %%s of function %s()', $functionName),
			sprintf('in return type of function %s()', $functionName)
		);
	}

}
