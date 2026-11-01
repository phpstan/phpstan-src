<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex\Parser;

use PHPStan\ShouldNotHappenException;

/**
 * Node of the regex AST built by RegexParser.
 *
 * Rule nodes have an id starting with "#" (like "#capturing" or "#alternation")
 * and children. Token nodes have the id "token", the token name (like "literal")
 * and the matched value. Rule nodes have an empty token name and value.
 */
final class RegexNode
{

	/**
	 * @param list<RegexNode> $children
	 */
	public function __construct(
		private string $id,
		private string $valueToken,
		private string $valueValue,
		private array $children,
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getValueToken(): string
	{
		return $this->valueToken;
	}

	public function getValueValue(): string
	{
		return $this->valueValue;
	}

	/** @return list<RegexNode> */
	public function getChildren(): array
	{
		return $this->children;
	}

	public function getChild(int $i): self
	{
		if (!isset($this->children[$i])) {
			throw new ShouldNotHappenException();
		}

		return $this->children[$i];
	}

	/** @param list<RegexNode> $children */
	public function setChildren(array $children): void
	{
		$this->children = $children;
	}

}
