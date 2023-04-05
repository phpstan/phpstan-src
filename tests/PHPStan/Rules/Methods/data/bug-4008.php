<?php

namespace Bug4008;

/** @template TModlel of BaseModel */
abstract class GenericClass
{
	/** @var OtherGenericClass<TModlel> */
	private $otherGenericClass;

	/** @param OtherGenericClass<TModlel> $otherGenericClass */
	public function __construct(OtherGenericClass $otherGenericClass){
		$this->otherGenericClass = $otherGenericClass;
	}
}

/** @extends GenericClass<Model> */
class ChildGenericClass extends GenericClass
{
	/** @param OtherGenericClass<Model> $otherGenericClass */
	public function __construct(OtherGenericClass $otherGenericClass){
		parent::__construct($otherGenericClass);
	}
}

/** @template TModlel of BaseModel */
class OtherGenericClass{}

abstract class BaseModel{}

class Model extends BaseModel{}

/**
 * @template T of Model
 * @extends GenericClass<T>
 */
class ChildGenericGenericClass extends GenericClass
{

}
