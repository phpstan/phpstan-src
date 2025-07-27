<?php declare(strict_types = 1);

namespace Bug3761;

class NamedTag{
	public function getValue() : int{
		return 1;
	}
}

class SubclassOfNamedTag extends NamedTag{}

class OtherSubclassOfNamedTag extends NamedTag{}

class HelloWorld
{
	public function getTag() : ?NamedTag{
		return new SubclassOfNamedTag();
	}

	/**
	 * @phpstan-param class-string<NamedTag> $class
	 */
	public function getTagValue(string $class = NamedTag::class) : int{
		$tag = $this->getTag();
		if($tag instanceof $class){
			return $tag->getValue();
		}

		throw new \RuntimeException(($tag === null ? "Missing" : get_class($tag)));
	}
}

(new HelloWorld())->getTagValue(OtherSubclassOfNamedTag::class); //runtime exception: SubclassOfNamedTag
