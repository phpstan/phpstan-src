<?php declare(strict_types = 1);

namespace Bug14880;

final class KeepSelfClassStaticCall
{

	public function run(): string
	{
		return self::class::sampleClass();
	}

	private function sampleClass(): string
	{
		return 'very creative';
	}

}

final class KeepClassStringStaticCall
{

	public function run(): string
	{
		$class = self::class;
		return $class::sampleClass();
	}

	private function sampleClass(): string
	{
		return 'very creative';
	}

}

final class KeepGenericClassStringStaticCall
{

	public function run(): string
	{
		/** @var class-string<self> $class */
		$class = self::class;
		return $class::sampleClass();
	}

	private function sampleClass(): string
	{
		return 'very creative';
	}

}
