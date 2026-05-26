<?php // lint >= 8.2

namespace Bug14703;

enum MyEnum: string
{
	case A = 'a';
	case B = 'b';
}

final readonly class MyObject
{
	public ?string $case;

	public function __construct(
		public ?string $prefix = null,
		private ?MyEnum $enum = null,
	) {
		$this->case = $enum?->value;
	}

	public function withPrefix(string $prefix): self
	{
		return new self(
			prefix: $prefix,
			enum: $this->enum,
		);
	}
}
