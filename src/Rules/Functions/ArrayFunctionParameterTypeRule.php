<?php declare(strict_types=1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function sprintf;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
final class ArrayFunctionParameterTypeRule  implements \PHPStan\Rules\Rule
{

	private const ARRAY_CALLBACK_TYPES = [
		'array_filter' => [
			'callback' => 1,
			'callback_param' => 0,
			'array' => 0,
		],
		'array_map' => [
			'callback' => 0,
			'callback_param' => 0,
			'array' => 1,
		],
		'array_reduce' => [
			'callback' => 1,
			'callback_param' => 1,
			'array' => 0,
		]
	];

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$name = $node->name;
		if (!($name instanceof \PhpParser\Node\Name)) {
			return [];
		}


		if(!array_key_exists($name->toLowerString(), self::ARRAY_CALLBACK_TYPES)) {
			return [];
		}

		$functionName = $name->toLowerString();

		$config = self::ARRAY_CALLBACK_TYPES[$functionName];
		$callbackParam = $config['callback'];

		$callback = $node->args[$callbackParam]->value;
		$type = $scope->getType($callback);


		if(! $type instanceof ParametersAcceptor) {
			return [];
		}

		/** @var ParameterReflection $param */
		$param = $type->getParameters()[$config['callback_param']];
		$arrayType = $scope->getType($node->args[$config['array']]->value);


		if (! $arrayType instanceof ArrayType) {
			return [];
		}

		$item = $arrayType->getItemType();

		if($param->getType()->accepts($item, $scope->isDeclareStrictTypes())->yes()) {
			return [];
		}

		return [RuleErrorBuilder::message(
			sprintf(
				'Parameter %d of callback in function %s does not accept %s',
				self::ARRAY_CALLBACK_TYPES[$functionName]['callback_param'] + 1,
				$functionName,
				$item->describe(VerbosityLevel::typeOnly())
			)
		)->build()];
	}

}
