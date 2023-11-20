<?php

namespace RuleErrorSignature;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

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

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		// also ok
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

	/**
	 * @return (string|RuleError)[] errors
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		// old return type - not ok
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

	/**
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		// just strings - not ok
	}

}

/**
 * @implements Rule<Node>
 */
class Ipsum implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	/**
	 * @return RuleError[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		// no identifiers - not ok
	}

}

/**
 * @implements Rule<Node>
 */
class Dolor implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	/**
	 * @return IdentifierRuleError[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		// not a list - not ok
	}

}
