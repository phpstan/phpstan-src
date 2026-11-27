<?php declare(strict_types = 1);

namespace Bug15852Types;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

interface ITemplate {}

class TemplateColumn
{
	/** @var ITemplate */
	private $itemTemplate;

	public function branchMerge(bool $edit): void
	{
		$template = null;
		if ($edit) {
			$template = $this->getMixed();
		} else {
			$template = $this->itemTemplate;
		}
		if (!$edit) {
			assertType('Bug15852Types\ITemplate', $template);
			assertNativeType('mixed', $template);
		}
	}

	/** @return mixed */
	private function getMixed()
	{
		return null;
	}
}

/** @return bool */
function validate_bool($v) { return (bool) $v; }

function foreachKeyGuard(array $atts): void
{
	$html_atts = [
		'class' => $atts['class'],
		'loop' => validate_bool($atts['loop']),
	];
	if (empty($html_atts['loop'])) {
		unset($html_atts['loop']);
	}
	foreach ($html_atts as $attribute_name => $attribute_value) {
		if ('loop' === $attribute_name) {
			assertNativeType('mixed', $attribute_value);
		}
	}
}

class StoredCondition
{
	/** @var int */
	public $foo;

	public function f(?int $x): void
	{
		$ok = $x === $this->foo;
		if ($ok) {
			assertType('int', $x);
			assertNativeType('int|null', $x);
		}
	}
}
