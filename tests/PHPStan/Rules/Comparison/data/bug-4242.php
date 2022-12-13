<?php // lint >= 8.1

namespace Bug4242;

class Enum
{
	public const TYPE_A = 1;
	public const TYPE_B = 2;
	public const TYPE_C = 3;
	public const TYPE_D = 4;
}

class Data
{
	private int $type;
	private int $someLoad;
	public function __construct(int $type)
	{
		$this->type=$type;
	}
	public function getType(): int
	{
		return $this->type;
	}
	public function someLoad(int $type): self
	{
		$this->someLoad=$type;
		return $this;
	}
	public function getSomeLoad(): int
	{
		return $this->someLoad;
	}
}

class HelloWorld
{
	public function case1(): void
	{
		$data=(new Data(Enum::TYPE_A));
		if($data->getType()===Enum::TYPE_A){
			$data->someLoad(4);
		}elseif(\in_array($data->getType(), [7,8,9,100], true)){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_B){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_C){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_D){
			$data->someLoad(6);
		}else{
			return;
		}


		if($data->getType()===Enum::TYPE_A){
			$data->someLoad(4);
		}elseif(\in_array($data->getType(), [7,8,9,100], true)){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_B){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_C){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_D){ // expected to work without an error
			$data->someLoad(6);
		}

	}

	public function case2(): void
	{
		$data=(new Data(Enum::TYPE_A));
		if($data->getType()===Enum::TYPE_A){
			$data->someLoad(4);
		}elseif(\in_array($data->getType(), [7,8,9,100], true)){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_B){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_C){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_D){
			$data->someLoad(6);
		}else{
			return;
		}

		// code above is the same as in case1. code bellow with sorted elseif's
		if($data->getType()===Enum::TYPE_A){
			$data->someLoad(4);
		}elseif($data->getType()===Enum::TYPE_B){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_C){
			$data->someLoad(6);
		}elseif($data->getType()===Enum::TYPE_D){
			$data->someLoad(6);
		}elseif(\in_array($data->getType(), [7,8,9,100], true)){
			$data->someLoad(6);
		}

	}
}
