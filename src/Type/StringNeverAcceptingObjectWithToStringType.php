<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\CompiledIntoTurboExtension;

#[CompiledIntoTurboExtension(new: ['pt_string_never_accepting_object_with_to_string_type_new', 'out'])]
class StringNeverAcceptingObjectWithToStringType extends StringType
{

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$result = parent::isSuperTypeOf($type);
		if (!$type instanceof self) {
			$result = $result->and(IsSuperTypeOfResult::createMaybe());
		}

		return $result;
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return parent::accepts($type, true);
	}

}
