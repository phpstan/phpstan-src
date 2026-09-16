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
#include "generated/DummyParameter.h"
#include "generated/ExtendedDummyParameter.h"

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

#endif
