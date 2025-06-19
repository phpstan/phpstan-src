<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_change_key_case;
use function array_key_exists;
use function array_keys;
use function sprintf;
use function str_starts_with;

/**
 * @implements Rule<Name>
 */
#[RegisteredRule(level: 0)]
final class OldPhpParser4ClassRule implements Rule
{

	private const NAME_MAPPING = [
		// from https://github.com/nikic/PHP-Parser/blob/master/UPGRADE-5.0.md#renamed-nodes
		'PhpParser\Node\Scalar\LNumber' => Node\Scalar\Int_::class,
		'PhpParser\Node\Scalar\DNumber' => Node\Scalar\Float_::class,
		'PhpParser\Node\Scalar\Encapsed' => Node\Scalar\InterpolatedString::class,
		'PhpParser\Node\Scalar\EncapsedStringPart' => Node\InterpolatedStringPart::class,
		'PhpParser\Node\Expr\ArrayItem' => Node\ArrayItem::class,
		'PhpParser\Node\Expr\ClosureUse' => Node\ClosureUse::class,
		'PhpParser\Node\Stmt\DeclareDeclare' => Node\DeclareItem::class,
		'PhpParser\Node\Stmt\PropertyProperty' => Node\PropertyItem::class,
		'PhpParser\Node\Stmt\StaticVar' => Node\StaticVar::class,
		'PhpParser\Node\Stmt\UseUse' => Node\UseItem::class,
	];

	public function getNodeType(): string
	{
		return Name::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$nameMapping = array_change_key_case(self::NAME_MAPPING);
		$lowerName = $node->toLowerString();
		if (!array_key_exists($lowerName, $nameMapping)) {
			return [];
		}

		$newName = $nameMapping[$lowerName];

		if (!$scope->isInClass()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		$hasPhpStanInterface = false;
		foreach (array_keys($classReflection->getInterfaces()) as $interfaceName) {
			if (!str_starts_with($interfaceName, 'PHPStan\\')) {
				continue;
			}

			$hasPhpStanInterface = true;
		}

		if (!$hasPhpStanInterface) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Class %s not found. It has been renamed to %s in PHP-Parser v5.',
				$node->toString(),
				$newName,
			))->identifier('phpParser.classRenamed')
				->build(),
		];
	}

}
