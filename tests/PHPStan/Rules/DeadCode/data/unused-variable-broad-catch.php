<?php // lint >= 8.0

namespace UnusedVariableBroadCatch;

function readDocumentation(\ReflectionParameter $parameter): void
{
	if ($parameter->getName() === 'required') {
		throw new \RuntimeException('Missing documentation');
	}
}

function defaultValue(\ReflectionParameter $parameter): mixed
{
	try {
		readDocumentation($parameter);
		return $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
	} catch (\Exception) {
		$default = null;
		if ($parameter->isDefaultValueAvailable()) {
			$default = $parameter->getDefaultValue();
		}
		return $default;
	}
}

function readDefinition(bool $fail): void
{
	if ($fail) {
		throw new \Error('Missing definition');
	}
}

/** @throws \TypeError */
function checkType(): void
{
	throw new \TypeError();
}

function definition(bool $fail, bool $check): ?int
{
	try {
		readDefinition($fail);
		if ($check) {
			checkType();
		}
		return 1;
	} catch (\Error) {
		$fallback = null;
		if ($check) {
			$fallback = 0;
		}
		return $fallback;
	}
}

/** @throws \RuntimeException */
function loadFile(): object
{
	return new \stdClass();
}

function validateFile(object $file): void
{
	if (!isset($file->valid)) {
		throw new \RuntimeException('Invalid file');
	}
}

function validatedFile(): ?object
{
	$file = null;
	try {
		$file = loadFile();
		validateFile($file);
	} catch (\Exception) {
		$file = null;
	}
	return $file;
}
