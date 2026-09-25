/*
 * PHPStanTurbo\StringAlwaysAcceptingObjectWithToStringType — native
 * implementation of PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType.
 *
 * Declared as PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType
 * itself at activation: not final, extending the native StringType,
 * without state (the inherited empty constructor is all `new` does). The
 * twin overrides isSuperTypeOf() and accepts() to answer for an object type
 * by whether every named class has a native __toString(); the `parent::`
 * calls go to the native bodies (JustNullableTypeTrait::isSuperTypeOf()
 * bound to StringType, StringType::accepts()) run on the object.
 */

#include "TypeTraits.h"
#include "generated/StringAlwaysAcceptingObjectWithToStringType.h"

namespace sigs = ptdecl::StringAlwaysAcceptingObjectWithToStringType::sig;

zend_class_entry *pt_ce_string_always_accepting_object_with_to_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType. */
class StringAlwaysAcceptingObjectWithToStringType
{
public:
	explicit StringAlwaysAcceptingObjectWithToStringType(zend_object *self) : self(self) {}

	/* new StringAlwaysAcceptingObjectWithToStringType(); UNDEF = pending
	 * exception */
	static zv::Val create() { return pt_new_instance(pt_ce_string_always_accepting_object_with_to_string_type); }

	/* the CompoundType callback; parent::isSuperTypeOf($type) for a type
	 * naming no class, else the or over the named classes of whether each
	 * has a native __toString() (no as soon as one is unknown); UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval thisValue;
			ZVAL_OBJ(&thisValue, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &thisValue);
		}

		zv::Val thatClassNames = objectClassNames(type);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (zv::ArrRef(thatClassNames.raw()).size() == 0) return pt_type_just_nullable_is_super_type_of(self, pt_ce_string_type, type);

		return toStringResult(thatClassNames, pt_type_is_super_type_of_result);
	}

	/* parent::accepts($type, $strictTypes) for a type naming no class, else
	 * the or over the named classes of whether each has a native
	 * __toString() (no as soon as one is unknown); UNDEF = pending
	 * exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zv::Val thatClassNames = objectClassNames(type);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (zv::ArrRef(thatClassNames.raw()).size() == 0) return pt_string_type_accepts(self, type, strictTypes);

		return toStringResult(thatClassNames, pt_type_accepts_result);
	}

private:
	/* $type->getObjectClassNames(), checked to be an array; UNDEF =
	 * pending exception */
	static zv::Val objectClassNames(zval *type)
	{
		zv::Val thatClassNames = pt_type_op(Z_OBJ_P(type), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatClassNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return array", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}
		return thatClassNames;
	}

	/* $result = createNo(); foreach ($thatClassNames as $thatClassName) {
	 * unknown class → return createNo(); $result = $result->or(
	 * createFromBoolean($typeClass->hasNativeMethod('__toString'))); }
	 * — over the result class resultOf builds (IsSuperTypeOfResult or
	 * AcceptsResult); UNDEF = pending exception */
	static zv::Val toStringResult(zv::Val &thatClassNames, zv::Val (*resultOf)(zend_long))
	{
		zv::Val result = resultOf(PT_TRI_NO);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zv::Val reflectionProvider = pt_reflection_provider_instance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(reflectionProvider.raw()).isObject())) {
			zend_type_error("phpstan_turbo: ReflectionProviderStaticAccessor::getInstance() must return an object");
			return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(thatClassNames.raw())) {
			zv::Ref thatClassName = entry.value();
			if (UNEXPECTED(!thatClassName.isString())) {
				zend_type_error("phpstan_turbo: getObjectClassNames() must return a list of strings");
				return zv::Val();
			}
			zv::Val hasClass = pt_reflection_provider_has_class_zv(Z_OBJ_P(reflectionProvider.raw()), thatClassName.raw());
			if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
			if (!zend_is_true(hasClass.raw())) return resultOf(PT_TRI_NO);

			zv::Val typeClass = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), thatClassName.raw());
			if (UNEXPECTED(typeClass.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(typeClass.raw()).isObject())) {
				zend_type_error("phpstan_turbo: ReflectionProvider::getClass() must return an object");
				return zv::Val();
			}
			zv::Val toString = zv::Val::string("__toString", sizeof("__toString") - 1);
			zv::Val hasNativeMethod = pt_type_call(Z_OBJ_P(typeClass.raw()), PT_LC("hasnativemethod"), 1, toString.raw());
			if (UNEXPECTED(hasNativeMethod.isUndef())) return zv::Val();
			zv::Val fromBoolean = resultOf(zend_is_true(hasNativeMethod.raw()) ? PT_TRI_YES : PT_TRI_NO);
			if (UNEXPECTED(fromBoolean.isUndef())) return zv::Val();
			result = pt_type_call(Z_OBJ_P(result.raw()), PT_LC("or"), 1, fromBoolean.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
		}

		return result;
	}

	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::StringAlwaysAcceptingObjectWithToStringType;

bool pt_string_always_accepting_object_with_to_string_type_new(zval *out)
{
	return pt_val_into(StringAlwaysAcceptingObjectWithToStringType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS StringAlwaysAcceptingObjectWithToStringType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_string_always_accepting_object_with_to_string_type)
{
	reg::Class cls("PHPStan\\Type\\StringAlwaysAcceptingObjectWithToStringType");
	ptdecl::StringAlwaysAcceptingObjectWithToStringType::declareClass(cls);
	ptdecl::StringAlwaysAcceptingObjectWithToStringType::declareProperties(cls);

	cls.method<&StringAlwaysAcceptingObjectWithToStringType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&StringAlwaysAcceptingObjectWithToStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.shadow(&pt_ce_string_always_accepting_object_with_to_string_type);
}

/* }}} */
