/*
 * Inline slot readers of the parameter value classes (DummyParameter.cpp,
 * ExtendedDummyParameter.cpp) for native callers walking a parameter list.
 *
 * A getter of those classes returns its private slot, so a caller holding an
 * instance of exactly DummyParameter or ExtendedDummyParameter reads the slot
 * in place — borrowed, no call. Anything else (a PHP subclass that may
 * override the getter, another ParameterReflection, the PHP twin in the
 * differential tests) and a slot never initialized get NULL: the caller then
 * calls the getter, which raises the twin's Error for the latter.
 */

#ifndef PHPSTANTURBO_PARAMETER_VALUES_H
#define PHPSTANTURBO_PARAMETER_VALUES_H

#include "support.h"
#include "zv.h"
#include "generated/DummyParameter.h"
#include "generated/ExtendedDummyParameter.h"
#include "generated/NativeParameterReflection.h"
#include "generated/ExtendedNativeParameterReflection.h"

/* a DummyParameter slot (ptdecl::DummyParameter::slot::*) of an instance of
 * exactly DummyParameter or ExtendedDummyParameter */
inline zval *pt_dummy_parameter_slot(zval *parameter, uint32_t index)
{
	if (EXPECTED(Z_TYPE_P(parameter) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(parameter);
		if (EXPECTED(ce == pt_ce_extended_dummy_parameter || ce == pt_ce_dummy_parameter) && ce != NULL) {
			zval *value = OBJ_PROP_NUM(Z_OBJ_P(parameter), index);
			if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
		}
	}
	return NULL;
}

/* an ExtendedDummyParameter slot (ptdecl::ExtendedDummyParameter::slot::*)
 * of an instance of that class */
inline zval *pt_extended_dummy_parameter_slot(zval *parameter, uint32_t index)
{
	if (EXPECTED(Z_TYPE_P(parameter) == IS_OBJECT && Z_OBJCE_P(parameter) == pt_ce_extended_dummy_parameter && pt_ce_extended_dummy_parameter != NULL)) {
		zval *value = OBJ_PROP_NUM(Z_OBJ_P(parameter), index);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
	}
	return NULL;
}

/* {{{ $parameter-><member>() of any ParameterReflection
 *
 * An instance of exactly one of the native value classes whose getter
 * returns its slot (DummyParameter, ExtendedDummyParameter,
 * NativeParameterReflection, ExtendedNativeParameterReflection) is read in
 * place; PhpParameterReflection runs its native body; anything else (a PHP
 * implementation of the interface, a subclass of DummyParameter, a slot
 * never initialized, a member the class computes) goes through
 * pt_parameter_reflection_call_slow() — the native body or the method
 * through one cached site per member, with the engine's Error for a call on
 * a non-object. UNDEF = pending exception. */

namespace ptpv {

#define PT_PV_NONE (-1)

/* the slot answering each PT_PR_* member, PT_PV_NONE where the class
 * computes the answer or has no such method */
inline constexpr int8_t dummySlots[PT_PR_MEMBER_COUNT] = {
	/* PT_PR_GET_NAME */ (int8_t) ptdecl::DummyParameter::slot::name,
	/* PT_PR_IS_OPTIONAL */ (int8_t) ptdecl::DummyParameter::slot::optional,
	/* PT_PR_GET_TYPE */ (int8_t) ptdecl::DummyParameter::slot::type,
	/* PT_PR_PASSED_BY_REFERENCE */ (int8_t) ptdecl::DummyParameter::slot::passedByReference,
	/* PT_PR_IS_VARIADIC */ (int8_t) ptdecl::DummyParameter::slot::variadic,
	/* PT_PR_GET_DEFAULT_VALUE */ (int8_t) ptdecl::DummyParameter::slot::defaultValue,
	PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE,
};

inline constexpr int8_t extendedDummySlots[PT_PR_MEMBER_COUNT] = {
	(int8_t) ptdecl::DummyParameter::slot::name,
	(int8_t) ptdecl::DummyParameter::slot::optional,
	(int8_t) ptdecl::DummyParameter::slot::type,
	(int8_t) ptdecl::DummyParameter::slot::passedByReference,
	(int8_t) ptdecl::DummyParameter::slot::variadic,
	(int8_t) ptdecl::DummyParameter::slot::defaultValue,
	/* PT_PR_GET_PHPDOC_TYPE */ (int8_t) ptdecl::ExtendedDummyParameter::slot::phpDocType,
	/* PT_PR_HAS_NATIVE_TYPE */ PT_PV_NONE,
	/* PT_PR_GET_NATIVE_TYPE */ (int8_t) ptdecl::ExtendedDummyParameter::slot::nativeType,
	/* PT_PR_GET_OUT_TYPE */ (int8_t) ptdecl::ExtendedDummyParameter::slot::outType,
	/* PT_PR_IS_IMMEDIATELY_INVOKED_CALLABLE */ (int8_t) ptdecl::ExtendedDummyParameter::slot::immediatelyInvokedCallable,
	/* PT_PR_GET_CLOSURE_THIS_TYPE */ (int8_t) ptdecl::ExtendedDummyParameter::slot::closureThisType,
	/* PT_PR_GET_ATTRIBUTES */ (int8_t) ptdecl::ExtendedDummyParameter::slot::attributes,
	/* PT_PR_GET_ALLOWED_CONSTANTS */ (int8_t) ptdecl::ExtendedDummyParameter::slot::allowedConstants,
	/* PT_PR_IS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETER */ (int8_t) ptdecl::ExtendedDummyParameter::slot::pureUnlessCallableIsImpureParameter,
};

inline constexpr int8_t nativeSlots[PT_PR_MEMBER_COUNT] = {
	(int8_t) ptdecl::NativeParameterReflection::slot::name,
	(int8_t) ptdecl::NativeParameterReflection::slot::optional,
	(int8_t) ptdecl::NativeParameterReflection::slot::type,
	(int8_t) ptdecl::NativeParameterReflection::slot::passedByReference,
	(int8_t) ptdecl::NativeParameterReflection::slot::variadic,
	(int8_t) ptdecl::NativeParameterReflection::slot::defaultValue,
	PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE, PT_PV_NONE,
};

inline constexpr int8_t extendedNativeSlots[PT_PR_MEMBER_COUNT] = {
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::name,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::optional,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::type,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::passedByReference,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::variadic,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::defaultValue,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::phpDocType,
	PT_PV_NONE,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::nativeType,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::outType,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::immediatelyInvokedCallable,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::closureThisType,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::attributes,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::allowedConstants,
	(int8_t) ptdecl::ExtendedNativeParameterReflection::slot::pureUnlessCallableIsImpureParameter,
};

#undef PT_PV_NONE

/* the initialized slot answering member for exactly one of the slot
 * classes, NULL otherwise */
inline zval *slotFor(zend_object *object, pt_parameter_reflection_member member)
{
	zend_class_entry *ce = object->ce;
	int slot;
	if (ce == pt_ce_extended_dummy_parameter) {
		slot = extendedDummySlots[member];
	} else if (ce == pt_ce_extended_native_parameter_reflection) {
		slot = extendedNativeSlots[member];
	} else if (ce == pt_ce_dummy_parameter) {
		slot = dummySlots[member];
	} else if (ce == pt_ce_native_parameter_reflection) {
		slot = nativeSlots[member];
	} else {
		return NULL;
	}
	if (UNEXPECTED(slot < 0)) return NULL;
	zval *value = OBJ_PROP_NUM(object, (uint32_t) slot);
	return EXPECTED(Z_TYPE_P(value) != IS_UNDEF) ? value : NULL;
}

} // namespace ptpv

inline zv::Val pt_parameter_reflection_call(zval *parameter, pt_parameter_reflection_member member)
{
	if (EXPECTED(Z_TYPE_P(parameter) == IS_OBJECT)) {
		zval *value = ptpv::slotFor(Z_OBJ_P(parameter), member);
		if (EXPECTED(value != NULL)) return zv::Val::copyOf(zv::Ref(value));
	}
	return pt_parameter_reflection_call_slow(parameter, member);
}

/* a bool member's answer (truthiness); false = pending exception */
[[nodiscard]] inline bool pt_parameter_reflection_bool(zval *parameter, pt_parameter_reflection_member member, bool &out)
{
	if (EXPECTED(Z_TYPE_P(parameter) == IS_OBJECT)) {
		zval *value = ptpv::slotFor(Z_OBJ_P(parameter), member);
		if (EXPECTED(value != NULL)) {
			out = Z_TYPE_P(value) == IS_TRUE;
			return true;
		}
	}
	zv::Val result = pt_parameter_reflection_call_slow(parameter, member);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

#endif
