<?php declare(strict_types = 1);

namespace ConditionalTypeForParameterTemplateBound;

use function PHPStan\Testing\assertType;

/**
 * @template TData
 */
interface FormTypeInterface
{

}

/**
 * @template TData
 */
interface FormInterface
{

	/**
	 * @return TData
	 */
	public function getData();

}

class DataClass
{

}

/**
 * @implements FormTypeInterface<DataClass>
 */
class DataClassType implements FormTypeInterface
{

}

class Controller
{

	/**
	 * @template TFormType of FormTypeInterface<TData>
	 * @template TData
	 *
	 * @param class-string<TFormType> $type
	 * @param TData                   $data
	 * @param array<string, mixed>    $options
	 *
	 * @phpstan-return ($data is null ? FormInterface<null|TData> : FormInterface<TData>)
	 */
	protected function createForm(string $type, $data = null, array $options = []): FormInterface
	{
		throw new \Exception();
	}

	public function doSomethingNullable(): void
	{
		$form = $this->createForm(DataClassType::class);
		assertType('ConditionalTypeForParameterTemplateBound\DataClass|null', $form->getData());
	}

	public function doSomething(): void
	{
		$form = $this->createForm(DataClassType::class, new DataClass());
		assertType('ConditionalTypeForParameterTemplateBound\DataClass', $form->getData());
	}

	public function doSomethingNull(): void
	{
		$form = $this->createForm(DataClassType::class, null);
		assertType('ConditionalTypeForParameterTemplateBound\DataClass|null', $form->getData());
	}

}
