<?php declare(strict_types = 1);

namespace PHPStan\Rules\Cast;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Cast>
 */
class CastObjectToStringRule implements Rule
{

	public function __construct()
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Cast::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\Cast\String_) {
			return [];
		}

		$type = $scope->getType($node->expr);
		if ($type->toString() instanceof ErrorType) {
			return [];
		}

		$objectType = TypeTraverser::map($type, static function (Type $type, callable $traverse) {
			if ($type instanceof UnionType) {
				return $traverse($type);
			}

			if (!(new ObjectWithoutClassType())->isSuperTypeOf($type)->yes()) {
				return new NeverType();
			}

			return $type;
		});

		if ($objectType->hasMethod('__toString')->maybe()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Casting %s to string might result in an error.',
					$type->describe(VerbosityLevel::value()),
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

}
