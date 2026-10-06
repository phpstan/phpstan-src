<?php

namespace MissingExceptionFunctionThrowsSealedMultiCatch;

/** @phpstan-sealed SubA|SubB */
abstract class SealedException extends \RuntimeException
{

}

final class SubA extends SealedException
{

}

final class SubB extends SealedException
{

}

final class OtherException extends \RuntimeException
{

}

/** @phpstan-sealed SubC */
class ConcreteSealedException extends \RuntimeException
{

}

final class SubC extends ConcreteSealedException
{

}

/** @throws SealedException */
function throwsSealed(): void
{
	throw new SubA();
}

/** @throws ConcreteSealedException */
function throwsConcreteSealed(): void
{
	throw new SubC();
}

function singleCatch(): void
{
	try {
		throwsSealed();
	} catch (SealedException $e) {
	}
}

function multiCatch(): void
{
	try {
		throwsSealed();
	} catch (SealedException | OtherException $e) {
	}
}

function multiCatchReversed(): void
{
	try {
		throwsSealed();
	} catch (OtherException | SealedException $e) {
	}
}

function multiCatchConcrete(): void
{
	try {
		throwsConcreteSealed();
	} catch (ConcreteSealedException | OtherException $e) {
	}
}

function multiCatchLeaksOtherSubtype(): void
{
	try {
		throwsSealed();
	} catch (SubA | OtherException $e) {
	}
}
