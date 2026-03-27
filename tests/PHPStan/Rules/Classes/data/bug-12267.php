<?php

namespace Bug12267b;

abstract class Model
{
}

class A11yAudit extends Model
{
}

class A11yPhase extends Model
{
}

/**
 * @template TModel of ?Model
 */
class Form
{
	/** @var TModel */
	protected $model;
}

trait ContainsA11yPhaseResultFields
{
	/** @return list<string> */
	protected function getFileExistsHelpBlock(string $field): array
	{
		if (!($this->model instanceof A11yPhase)) {
			return [];
		}

		return [];
	}
}

/**
 * @extends Form<A11yPhase>
 */
class EditA11yPhaseForm extends Form
{
	use ContainsA11yPhaseResultFields;
}

/**
 * @extends Form<null>
 */
class SubmitA11yAuditPhaseForm extends Form
{
	use ContainsA11yPhaseResultFields;
}
