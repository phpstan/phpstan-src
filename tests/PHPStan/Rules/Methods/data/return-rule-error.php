<?php

namespace ReturnRuleError;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class Foo implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// ok
		return [
			RuleErrorBuilder::message('foo')
				->identifier('abc')
				->build(),
		];
	}

}

/**
 * @implements Rule<Node>
 */
class Bar implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// not ok - returns plain string
		return ['foo'];
	}

}

/**
 * @implements Rule<Node>
 */
class Baz implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// not ok - missing identifier
		return [
			RuleErrorBuilder::message('foo')
				->build(),
		];
	}

}

/**
 * @implements Rule<Node>
 */
class Lorem implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// not ok - not a list
		return [
			1 => RuleErrorBuilder::message('foo')
				->identifier('abc')
				->build(),
		];
	}

}
