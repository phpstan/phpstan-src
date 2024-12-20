<?php

namespace TraitAttributes;

#[\Attribute]
abstract class AbstractAttribute {}

#[AbstractAttribute]
trait MyTrait {}

#[\Attribute]
class MyAttribute {}

#[MyAttribute]
trait MyTrait2 {}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MyTargettedAttribute {}

#[MyTargettedAttribute]
trait MyTrait3 {}

class Uses
{

	use MyTrait;
	use MyTrait2;
	use MyTrait3;

}
