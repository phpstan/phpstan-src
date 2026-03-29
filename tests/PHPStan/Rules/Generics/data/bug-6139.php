<?php declare(strict_types = 1);

namespace Bug6139;

/**
 * @template-covariant T
 */
interface Element {
	/**
	 * @return T
	 */
	public function render(): mixed;
}

/**
 * @template-covariant T
 */
interface FormatLoader
{
	/**
	 * @param Element<T> $element
	 */
	public function addElement(Element $element): void;
}
