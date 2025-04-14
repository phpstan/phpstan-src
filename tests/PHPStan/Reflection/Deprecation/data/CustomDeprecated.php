<?php // lint >= 8.1

namespace CustomDeprecations;

#[\Attribute(\Attribute::TARGET_ALL)]
class CustomDeprecated {

	public ?string $description;

	public function __construct(
		?string $description = null
	) {
		$this->description = $description;
	}
}
