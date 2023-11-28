<?php

namespace Bug8724b;

class Input {
	public function getLabel(): string
	{
		return '';
	}
}

/**
 * @param array{label:string}|array{input:Input} $data
 */
function test(array $data): void
{
	$label = $data['label'] ?? $data['input']->getLabel();

	$label2 = $data['label'] ?? (isset($data['input']) ? $data['input']->getLabel() : '');
}
