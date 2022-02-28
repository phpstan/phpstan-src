<?php

namespace Bug5369;

abstract class BrandBase
{
}

class BrandLib
{
	public static function getBrandObjectBySkinname(string $skinname): ?BrandBase
	{
		$ObjName = 'Brand' . $skinname;

		if (\is_subclass_of($ObjName, BrandBase::class)) {
			$BrandObj = new $ObjName();
		} else {
			$BrandObj = null;
		}

		return $BrandObj;
	}
}
