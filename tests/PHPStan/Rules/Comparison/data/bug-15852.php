<?php declare(strict_types = 1);

namespace Bug15852;

interface ITemplate { public function instantiateIn(object $cell): void; }

class TemplateColumn
{
	/** @var ITemplate */
	private $_itemTemplate;
	private $_editItemTemplate;

	/** @return string */
	public function getItemRenderer() { return ''; }

	/** @return string */
	public function getEditItemRenderer() { return ''; }

	/** @param ITemplate|null $value */
	public function setItemTemplate($value): void { $this->_itemTemplate = $value; }

	/** @param ITemplate|null $value */
	public function setEditItemTemplate($value): void { $this->_editItemTemplate = $value; }

	/** @param string $itemType */
	public function initializeCell(object $cell, $itemType): void
	{
		if ($itemType === 'Item' || $itemType === 'EditItem') {
			$template = null;
			$classPath = '';
			if ($itemType === 'EditItem') {
				if (($classPath = $this->getEditItemRenderer()) === '' && ($template = $this->_editItemTemplate) === null) {
					$classPath = $this->getItemRenderer();
					$template = $this->_itemTemplate;
				}
			} else {
				$template = $this->_itemTemplate;
				$classPath = $this->getItemRenderer();
			}
			if ($classPath !== '') {
				echo $classPath;
			} elseif ($template !== null) {
				$template->instantiateIn($cell);
			} elseif ($itemType !== 'EditItem') {
				echo "no template: &nbsp;\n";
			}
		}
	}
}

/** @return bool */
function validate_bool($v) { return (bool) $v; }
function f(array $atts): void {
	$html_atts = array(
		'class'    => $atts['class'],
		'loop'     => validate_bool( $atts['loop'] ),
	);
	if ( empty( $html_atts['loop'] ) ) {
		unset( $html_atts['loop'] );
	}
	foreach ( $html_atts as $attribute_name => $attribute_value ) {
		if ( 'loop' === $attribute_name ) {
			if (true === $attribute_value) { echo 1; }
		}
	}
}

class StoredCondition {
	/** @var int */
	public $foo;
	function f(?int $x): void {
		$ok = $x === $this->foo;
		if ($ok) {
			if ($x === null) { echo 1; }
		}
	}
}
// (new StoredCondition)->f(null) with foo unset: $ok is true and $x is null
