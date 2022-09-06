<?php declare(strict_types = 1); // lint >= 7.4

namespace Bug6104;

/**
 * @template T
 */
interface FormatLoader
{
    /**
     * @param T $element
     */
    public function addElement(string $name, mixed $element): static;
}

/**
 * Not working as expected.
 *
 * @implements FormatLoader<int>
 */
final class TemporalFormatLoader_Unexpected implements FormatLoader {
    public function addElement(string $name, mixed $element): static {
		return $this;
	}
}

/**
 * Working as expected.
 *
 * @implements FormatLoader<int>
 */
class TemporalFormatLoader_Expected implements FormatLoader {
    public function addElement(string $name, mixed $element): static {
		return $this;
	}
}
