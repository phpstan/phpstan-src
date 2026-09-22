/*
 * PHPStanTurbo\IssetabilityResolution — native implementation of
 * PHPStan\Analyser\IssetabilityResolution.
 *
 * The resolved view of an isset / empty / ?? chain: a link
 * (IssetabilityLinkInfo) plus the resolution of the chain inward of it.
 * IssetabilityDescriptor::resolve() and ExpressionResult's leaf resolution
 * create it (pt_issetability_resolution_new()); the isset / empty / ??
 * handlers and DefaultNarrowingHelper fold it through
 * pt_issetability_resolution_is_set() / _not_empty(), the IssetCheck rule
 * reads its links. State lives in the twin's two promoted property slots.
 *
 * The link's facts are read through the AnalyserValues.h readers (the slots
 * of the native link, the getters of anything else); an inner resolution
 * that is not the native class (the PHP twin in the differential tests) is
 * called by name. The typeCallback is any callable — a native closure is
 * entered directly — and notEmpty()'s static closure is a native closure,
 * allocated per call like the twin's.
 */

#include "support.h"
#include "generated/IssetabilityResolution.h"

namespace slots = ptdecl::IssetabilityResolution::slot;
namespace sigs = ptdecl::IssetabilityResolution::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_issetability_resolution = nullptr;

namespace {

constexpr const char *pt_ir_closure_name = "PHPStan\\Analyser\\IssetabilityResolution::{closure}";

inline bool isNullValue(zval *value)
{
	return value == NULL || Z_TYPE_P(value) == IS_NULL;
}

/* the TrinaryLogic value of a borrowed link fact; -1 = pending exception */
inline zend_long trinaryOf(zval *trinary)
{
	if (UNEXPECTED(trinary == NULL)) return -1;
	return pt_type_trinary_value(trinary);
}

/* $typeCallback($type) as a ?bool; UNDEF = pending exception */
zv::Val callTypeCallback(zval *typeCallback, zval *type)
{
	zv::Val result = pt_type_call_callable(typeCallback, 1, type);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	zend_uchar resultType = Z_TYPE_P(result.raw());
	if (EXPECTED(resultType == IS_TRUE || resultType == IS_FALSE || resultType == IS_NULL)) return result;
	zend_type_error("PHPStan\\Analyser\\IssetabilityResolution::isSet(): Return value must be of type ?bool, %s returned", zend_zval_value_name(result.raw()));
	return zv::Val();
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\IssetabilityResolution; UNDEF = pending
 * exception. */
class IssetabilityResolution
{
public:
	explicit IssetabilityResolution(zend_object *self) : self(self) {}

	/* __construct(private IssetabilityLinkInfo $link, private ?IssetabilityResolution $inner) */
	void construct(zval *link, zval *inner) const
	{
		pt_write_slot(self, slots::link, link);
		if (inner != NULL) {
			pt_write_slot(self, slots::inner, inner);
		} else {
			zval null = {};
			ZVAL_NULL(&null);
			pt_write_slot(self, slots::inner, &null);
		}
	}

	/* new self(...); $inner NULL (or IS_NULL) for null */
	static zv::Val create(zval *link, zval *inner)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_issetability_resolution) != SUCCESS)) return zv::Val();
		IssetabilityResolution(Z_OBJ(object)).construct(link, isNullValue(inner) ? NULL : inner);
		return zv::Val::adopt(object);
	}

	zv::Val getLink() const { return read(slots::link, "link"); }
	zv::Val getInner() const { return read(slots::inner, "inner"); }

	/* Mirrors isSet(); $result NULL (or IS_NULL) for null */
	zv::Val isSet(zval *typeCallback, zval *result) const
	{
		zval *link = pt_typed_slot(self, slots::link, self->ce, "link");
		if (UNEXPECTED(link == NULL)) return zv::Val();
		zval *inner = pt_typed_slot(self, slots::inner, self->ce, "inner");
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(link) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isVariable() on %s", zend_zval_value_name(link));
			return zv::Val();
		}

		bool is;
		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_VARIABLE, is))) return zv::Val();
		if (is) {
			zv::Val hold;
			zend_long hasVariable = trinaryOf(pt_issetability_link_info_has_variable(link, hold));
			if (UNEXPECTED(hasVariable < 0)) return zv::Val();
			if (hasVariable == PT_TRI_MAYBE) return zv::Val::null();

			if (isNullValue(result)) {
				if (hasVariable == PT_TRI_YES) {
					zv::Val nameHold;
					zval *variableName = pt_issetability_link_info_variable_name(link, nameHold);
					if (UNEXPECTED(variableName == NULL)) return zv::Val();
					if (Z_TYPE_P(variableName) == IS_STRING && zend_string_equals_literal(Z_STR_P(variableName), "_SESSION")) return zv::Val::null();

					zv::Val typeHold;
					zval *valueType = pt_issetability_link_info_value_type(link, typeHold);
					if (UNEXPECTED(valueType == NULL)) return zv::Val();
					return callTypeCallback(typeCallback, valueType);
				}

				return zv::Val::boolean(false);
			}

			return zv::Val::copyOf(zv::Ref(result));
		}

		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_OFFSET, is))) return zv::Val();
		if (is) {
			zv::Val hold;
			zend_long isOffsetAccessible = trinaryOf(pt_issetability_link_info_is_offset_accessible(link, hold));
			if (UNEXPECTED(isOffsetAccessible < 0)) return zv::Val();
			if (isOffsetAccessible != PT_TRI_YES) {
				if (!isNullValue(result)) return zv::Val::copyOf(zv::Ref(result));
				return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();
			}

			zv::Val offsetHold;
			zend_long hasOffsetValue = trinaryOf(pt_issetability_link_info_has_offset_value(link, offsetHold));
			if (UNEXPECTED(hasOffsetValue < 0)) return zv::Val();
			if (hasOffsetValue == PT_TRI_NO) return zv::Val::boolean(false);

			// an offset that cannot be null stores its verdict and asks the
			// earlier offsets
			if (hasOffsetValue == PT_TRI_YES) {
				zv::Val typeHold;
				zval *valueType = pt_issetability_link_info_value_type(link, typeHold);
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zv::Val callbackResult = callTypeCallback(typeCallback, valueType);
				if (UNEXPECTED(callbackResult.isUndef())) return zv::Val();

				if (!callbackResult.isNull()) {
					return Z_TYPE_P(inner) != IS_NULL ? isSetOf(inner, typeCallback, callbackResult.raw()) : std::move(callbackResult);
				}
			}

			// has offset, it is nullable
			return zv::Val::null();
		}

		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_PROPERTY, is))) return zv::Val();
		if (is) {
			zv::Val hold;
			zval *propertyReflection = pt_issetability_link_info_property_reflection(link, hold);
			if (UNEXPECTED(propertyReflection == NULL)) return zv::Val();
			bool flag = Z_TYPE_P(propertyReflection) == IS_NULL;
			if (!flag) {
				bool reflectionNative;
				if (UNEXPECTED(!pt_issetability_link_info_is_reflection_native(link, reflectionNative))) return zv::Val();
				flag = !reflectionNative;
			}
			if (flag) return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();

			bool undefined;
			if (UNEXPECTED(!propertyMayBeUninitialized(link, undefined))) return zv::Val();
			if (undefined) return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();

			if (!isNullValue(result)) {
				return Z_TYPE_P(inner) != IS_NULL ? isSetOf(inner, typeCallback, result) : zv::Val::copyOf(zv::Ref(result));
			}

			zv::Val typeHold;
			zval *valueType = pt_issetability_link_info_value_type(link, typeHold);
			if (UNEXPECTED(valueType == NULL)) return zv::Val();
			zv::Val callbackResult = callTypeCallback(typeCallback, valueType);
			if (UNEXPECTED(callbackResult.isUndef())) return zv::Val();
			if (!callbackResult.isNull()) {
				// the slot re-read: the callback may run arbitrary code
				inner = pt_typed_slot(self, slots::inner, self->ce, "inner");
				if (UNEXPECTED(inner == NULL)) return zv::Val();
				if (Z_TYPE_P(inner) != IS_NULL) return isSetOf(inner, typeCallback, callbackResult.raw());
			}

			return callbackResult;
		}

		// leaf
		if (!isNullValue(result)) return zv::Val::copyOf(zv::Ref(result));
		zv::Val typeHold;
		zval *valueType = pt_issetability_link_info_value_type(link, typeHold);
		if (UNEXPECTED(valueType == NULL)) return zv::Val();
		return callTypeCallback(typeCallback, valueType);
	}

	/* Mirrors the private isSetUndefined(). */
	zv::Val isSetUndefined() const
	{
		zval *link = pt_typed_slot(self, slots::link, self->ce, "link");
		if (UNEXPECTED(link == NULL)) return zv::Val();
		zval *inner = pt_typed_slot(self, slots::inner, self->ce, "inner");
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(link) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isVariable() on %s", zend_zval_value_name(link));
			return zv::Val();
		}

		bool is;
		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_VARIABLE, is))) return zv::Val();
		if (is) {
			zv::Val hold;
			zend_long hasVariable = trinaryOf(pt_issetability_link_info_has_variable(link, hold));
			if (UNEXPECTED(hasVariable < 0)) return zv::Val();
			if (hasVariable != PT_TRI_NO) return zv::Val::null();

			return zv::Val::boolean(false);
		}

		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_OFFSET, is))) return zv::Val();
		if (is) {
			zv::Val hold;
			zend_long isOffsetAccessible = trinaryOf(pt_issetability_link_info_is_offset_accessible(link, hold));
			if (UNEXPECTED(isOffsetAccessible < 0)) return zv::Val();
			if (isOffsetAccessible != PT_TRI_YES) return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();

			zv::Val offsetHold;
			zend_long hasOffsetValue = trinaryOf(pt_issetability_link_info_has_offset_value(link, offsetHold));
			if (UNEXPECTED(hasOffsetValue < 0)) return zv::Val();
			if (hasOffsetValue != PT_TRI_NO) return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();

			return zv::Val::boolean(false);
		}

		if (UNEXPECTED(!pt_issetability_link_info_is_kind(link, PT_ISSETABILITY_LINK_PROPERTY, is))) return zv::Val();
		if (is) return Z_TYPE_P(inner) != IS_NULL ? isSetUndefinedOf(inner) : zv::Val::null();

		return zv::Val::null();
	}

	/* Mirrors notEmpty(). */
	zv::Val notEmpty() const
	{
		zv::Val typeCallback = pt_native_closure(&notEmptyTypeCallbackBody);
		return isSet(typeCallback.raw(), NULL);
	}

	/* $inner->isSet($typeCallback, $result) of an inner resolution; the
	 * native recursion down the chain continues on a fresh C stack segment
	 * when the current one runs low (the twin recursed on the VM stack) */
	static zv::Val isSetOf(zval *resolution, zval *typeCallback, zval *result)
	{
		if (EXPECTED(Z_TYPE_P(resolution) == IS_OBJECT && Z_OBJCE_P(resolution) == pt_ce_issetability_resolution)) {
			zv::Val held = zv::Val::copyOf(zv::Ref(resolution));
			zv::Val isSetResult;
			pt_engine_with_stack([&]() { isSetResult = IssetabilityResolution(Z_OBJ_P(held.raw())).isSet(typeCallback, result); });
			return isSetResult;
		}
		if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isSet() on %s", zend_zval_value_name(resolution));
			return zv::Val();
		}
		zval null = {};
		ZVAL_NULL(&null);
		zv::Args argv{typeCallback, result != NULL ? result : &null};
		return pt_type_call(Z_OBJ_P(resolution), PT_LC("isset"), 2, argv);
	}

	/* $inner->isSetUndefined() of an inner resolution (on a fresh C stack
	 * segment when the current one runs low, like isSetOf()) */
	static zv::Val isSetUndefinedOf(zval *resolution)
	{
		if (EXPECTED(Z_TYPE_P(resolution) == IS_OBJECT && Z_OBJCE_P(resolution) == pt_ce_issetability_resolution)) {
			zv::Val held = zv::Val::copyOf(zv::Ref(resolution));
			zv::Val isSetResult;
			pt_engine_with_stack([&]() { isSetResult = IssetabilityResolution(Z_OBJ_P(held.raw())).isSetUndefined(); });
			return isSetResult;
		}
		if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isSetUndefined() on %s", zend_zval_value_name(resolution));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(resolution), PT_LC("issetundefined"), 0, NULL);
	}

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}

	/* $link->hasNativeType() && !$link->isVirtual()->yes() &&
	 * !$link->hasExpressionTypeOfFetch() && !$link->nativeHasDefaultValue() &&
	 * (!$link->nativeReflectionExists() || !$link->nativeIsPromoted() ||
	 * (!$link->nativeIsReadOnly() && !$link->nativeIsHooked())); false =
	 * pending exception */
	[[nodiscard]] static bool propertyMayBeUninitialized(zval *link, bool &out)
	{
		out = false;
		bool flag;
		if (UNEXPECTED(!pt_issetability_link_info_has_native_type(link, flag))) return false;
		if (!flag) return true;
		zv::Val hold;
		zend_long isVirtual = trinaryOf(pt_issetability_link_info_is_virtual(link, hold));
		if (UNEXPECTED(isVirtual < 0)) return false;
		if (isVirtual == PT_TRI_YES) return true;
		if (UNEXPECTED(!pt_issetability_link_info_has_expression_type_of_fetch(link, flag))) return false;
		if (flag) return true;
		if (UNEXPECTED(!pt_issetability_link_info_native_has_default_value(link, flag))) return false;
		if (flag) return true;
		if (UNEXPECTED(!pt_issetability_link_info_native_reflection_exists(link, flag))) return false;
		if (!flag) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!pt_issetability_link_info_native_is_promoted(link, flag))) return false;
		if (!flag) {
			out = true;
			return true;
		}
		if (UNEXPECTED(!pt_issetability_link_info_native_is_read_only(link, flag))) return false;
		if (flag) return true;
		if (UNEXPECTED(!pt_issetability_link_info_native_is_hooked(link, flag))) return false;
		out = !flag;
		return true;
	}

	/* static function (Type $type): ?bool { ... } of notEmpty() */
	static void notEmptyTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and exactly 1 expected", pt_ir_closure_name, argc);
			return;
		}
		zval *type = &argv[0];
		ZVAL_DEREF(type);
		zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
		if (UNEXPECTED(typeCe == NULL)) return;
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), typeCe))) {
			zend_type_error("%s(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", pt_ir_closure_name, zend_zval_value_name(type));
			return;
		}
		zend_long isNull = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return;
		zv::Val boolean = pt_type_call(Z_OBJ_P(type), PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return;
		if (UNEXPECTED(!boolean.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isFalse() on %s", zend_zval_value_name(boolean.raw()));
			return;
		}
		zend_long isFalsey = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(isFalsey < 0)) return;
		if (isNull == PT_TRI_MAYBE || isFalsey == PT_TRI_MAYBE) {
			ZVAL_NULL(return_value);
			return;
		}

		if (isNull == PT_TRI_YES) {
			ZVAL_BOOL(return_value, isFalsey == PT_TRI_NO);
			return;
		}

		ZVAL_BOOL(return_value, isFalsey != PT_TRI_YES);
	}
};

} // namespace phpstanturbo

using phpstanturbo::IssetabilityResolution;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_issetability_resolution_new(zval *link, zval *inner)
{
	return IssetabilityResolution::create(link, inner);
}

zv::Val pt_issetability_resolution_is_set(zval *resolution, zval *typeCallback)
{
	return IssetabilityResolution::isSetOf(resolution, typeCallback, NULL);
}

zv::Val pt_issetability_resolution_not_empty(zval *resolution)
{
	if (EXPECTED(Z_TYPE_P(resolution) == IS_OBJECT && Z_OBJCE_P(resolution) == pt_ce_issetability_resolution)) return IssetabilityResolution(Z_OBJ_P(resolution)).notEmpty();
	if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function notEmpty() on %s", zend_zval_value_name(resolution));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(resolution), PT_LC("notempty"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_issetability_resolution()
{
	reg::Class cls("PHPStan\\Analyser\\IssetabilityResolution");
	ptdecl::IssetabilityResolution::declareClass(cls);
	ptdecl::IssetabilityResolution::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *link, *inner;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(link)
			Z_PARAM_OBJECT_OR_NULL(inner)
		ZEND_PARSE_PARAMETERS_END();
		IssetabilityResolution(Z_OBJ_P(ZEND_THIS)).construct(link, inner);
	});

	cls.method<&IssetabilityResolution::getLink>(sigs::getLink);

	cls.method<&IssetabilityResolution::getInner>(sigs::getInner);

	cls.method(sigs::isSet, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *typeCallback;
		zval *result = NULL;
		ZEND_PARSE_PARAMETERS_START(1, 2)
			Z_PARAM_ZVAL(typeCallback)
			Z_PARAM_OPTIONAL
			Z_PARAM_ZVAL(result)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(typeCallback, 0, NULL))) {
			zend_argument_type_error(1, "must be of type callable, %s given", zend_zval_value_name(typeCallback));
			RETURN_THROWS();
		}
		if (result != NULL && UNEXPECTED(Z_TYPE_P(result) != IS_NULL && Z_TYPE_P(result) != IS_TRUE && Z_TYPE_P(result) != IS_FALSE)) {
			zend_argument_type_error(2, "must be of type ?bool, %s given", zend_zval_value_name(result));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(IssetabilityResolution(Z_OBJ_P(ZEND_THIS)).isSet(typeCallback, result));
	});

	cls.method<&IssetabilityResolution::isSetUndefined>(sigs::isSetUndefined);

	cls.method<&IssetabilityResolution::notEmpty>(sigs::notEmpty);

	cls.shadow(&pt_ce_issetability_resolution);
}

/* }}} */
