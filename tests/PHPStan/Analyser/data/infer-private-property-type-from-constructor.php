<?php

namespace InferPrivatePropertyTypeFromConstructor;

class Foo
{

	/** @var int */
	private $intProp;

	private $stringProp;

	private $unionProp;

	private $stdClassProp;

	/** @ORM\Column */
	private $unrelatedDocComment;

	/** @var mixed */
	private $explicitMixed;

	private $bool;

	private $array;

	/**
	 * @param self|Bar $unionProp
	 */
	public function __construct(
		string $intProp,
		string $stringProp,
		$unionProp,
		\stdClass $unrelatedDocComment,
		\stdClass $explicitMixed
	)
	{
		$this->intProp = $intProp;
		$this->stringProp = $stringProp;
		$this->unionProp = $unionProp;
		$this->stdClassProp = new \stdClass();
		$this->unrelatedDocComment = $unrelatedDocComment;
		$this->explicitMixed = $explicitMixed;
		$this->bool = false;
		$this->array = [];
	}

	public function doFoo()
	{
		die;
	}

}
