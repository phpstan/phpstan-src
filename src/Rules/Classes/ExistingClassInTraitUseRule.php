<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\TraitUse>
 */
class ExistingClassInTraitUseRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	public function __construct(ClassCaseSensitivityCheck $classCaseSensitivityCheck)
	{
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Stmt\TraitUse::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->classCaseSensitivityCheck->checkClassNames(
			array_map(static function (Node\Name $traitName): ClassNameNodePair {
				return new ClassNameNodePair((string) $traitName, $traitName);
			}, $node->traits)
		);
	}

}
