<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

/**
 * @implements \PHPStan\Rules\Rule<VirtualNode>
 */
class InvalidThrowsPhpDocValueRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return VirtualNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof InFunctionNode && !$node instanceof InClassMethodNode) {
			return [];
		}

		if ($scope->getFunction() === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$throwType = $scope->getFunction()->getThrowType();

		return $this->check($throwType);
	}

	/**
	 * @param Type|null $phpDocThrowType
	 * @return array<int, RuleError> errors
	 */
	private function check(?Type $phpDocThrowType): array
	{
		if ($phpDocThrowType === null) {
			return [];
		}

		if ((new VoidType())->isSuperTypeOf($phpDocThrowType)->yes()) {
			return [];
		}

		$isThrowsSuperType = (new ObjectType(\Throwable::class))->isSuperTypeOf($phpDocThrowType);
		if ($isThrowsSuperType->yes()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @throws with type %s is not subtype of Throwable',
				$phpDocThrowType->describe(VerbosityLevel::typeOnly())
			))->build(),
		];
	}

}
