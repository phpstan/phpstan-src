/*
 * $acceptor-><member>() of any ParametersAcceptor for native callers
 * (FunctionVariant.cpp, ExtendedFunctionVariant.cpp,
 * ExtendedCallableFunctionVariant.cpp, ResolvedFunctionVariantWithOriginal.cpp,
 * TrivialParametersAcceptor.cpp).
 *
 * An instance of exactly one of the native function variants whose getter
 * returns a slot — FunctionVariant, ExtendedFunctionVariant,
 * ExtendedCallableFunctionVariant, and ResolvedFunctionVariantWithOriginal's
 * plain slots and already filled memo slots (getParameters(),
 * getReturnType(), ...) — is read in place, borrowed, without a call. The
 * non-final variants are matched by exact class entry, so a PHP subclass
 * overriding a getter keeps its override. Everything else — a slot never
 * initialized, a memo not filled yet, a computed answer, TrivialParametersAcceptor,
 * any PHP implementation (ClosureType, a third-party acceptor) — goes through
 * pt_parameters_acceptor_call_slow(): the native body, or the method through
 * one cached site per member, with the engine's Error for a non-object.
 */

#ifndef PHPSTANTURBO_ACCEPTOR_VALUES_H
#define PHPSTANTURBO_ACCEPTOR_VALUES_H

#include "support.h"
#include "zv.h"
#include "generated/FunctionVariant.h"
#include "generated/ExtendedFunctionVariant.h"
#include "generated/ExtendedCallableFunctionVariant.h"
#include "generated/ResolvedFunctionVariantWithOriginal.h"

namespace ptacc {

#define PT_ACC_NONE (-1)
#define PT_ACC_FV(name) (int8_t) ptdecl::FunctionVariant::slot::name
#define PT_ACC_EFV(name) (int8_t) ptdecl::ExtendedFunctionVariant::slot::name
#define PT_ACC_ECFV(name) (int8_t) ptdecl::ExtendedCallableFunctionVariant::slot::name
#define PT_ACC_RFV(name) (int8_t) ptdecl::ResolvedFunctionVariantWithOriginal::slot::name

/* the slot answering each PT_PA_* member (in enum order) when it holds a
 * non-null value; PT_ACC_NONE where the class computes the answer
 * (getResolvedTemplateTypeMap()'s `?? createEmpty()`, getAsserts()'s `??
 * createEmpty()`, ...) or has no such method */
inline constexpr int8_t functionVariantSlots[PT_PA_MEMBER_COUNT] = {
	PT_ACC_FV(templateTypeMap), PT_ACC_NONE, PT_ACC_FV(parameters), PT_ACC_FV(isVariadic), PT_ACC_FV(returnType),
	PT_ACC_NONE, PT_ACC_NONE, PT_ACC_FV(callSiteVarianceMap), PT_ACC_NONE, PT_ACC_NONE,
	PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE,
};

inline constexpr int8_t extendedFunctionVariantSlots[PT_PA_MEMBER_COUNT] = {
	PT_ACC_FV(templateTypeMap), PT_ACC_NONE, PT_ACC_FV(parameters), PT_ACC_FV(isVariadic), PT_ACC_FV(returnType),
	PT_ACC_EFV(phpDocReturnType), PT_ACC_EFV(nativeReturnType), PT_ACC_FV(callSiteVarianceMap), PT_ACC_NONE, PT_ACC_NONE,
	PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE,
};

inline constexpr int8_t extendedCallableFunctionVariantSlots[PT_PA_MEMBER_COUNT] = {
	PT_ACC_FV(templateTypeMap), PT_ACC_NONE, PT_ACC_FV(parameters), PT_ACC_FV(isVariadic), PT_ACC_FV(returnType),
	PT_ACC_EFV(phpDocReturnType), PT_ACC_EFV(nativeReturnType), PT_ACC_FV(callSiteVarianceMap), PT_ACC_NONE, PT_ACC_NONE,
	PT_ACC_ECFV(throwPoints), PT_ACC_ECFV(isPure), PT_ACC_ECFV(impurePoints), PT_ACC_ECFV(invalidateExpressions), PT_ACC_ECFV(usedVariables),
	PT_ACC_ECFV(acceptsNamedArguments), PT_ACC_ECFV(mustUseReturnValue), PT_ACC_NONE, PT_ACC_NONE,
};

inline constexpr int8_t resolvedFunctionVariantSlots[PT_PA_MEMBER_COUNT] = {
	PT_ACC_NONE, PT_ACC_RFV(resolvedTemplateTypeMap), PT_ACC_RFV(parameters), PT_ACC_NONE, PT_ACC_RFV(returnType),
	PT_ACC_RFV(phpDocReturnType), PT_ACC_NONE, PT_ACC_RFV(callSiteVarianceMap), PT_ACC_RFV(parametersAcceptor), PT_ACC_RFV(returnTypeWithUnresolvableTemplateTypes),
	PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE, PT_ACC_NONE,
};

#undef PT_ACC_NONE
#undef PT_ACC_FV
#undef PT_ACC_EFV
#undef PT_ACC_ECFV
#undef PT_ACC_RFV

/* the non-null slot answering member for exactly one of the native
 * variants, NULL otherwise (borrowed) */
inline zval *slotFor(zend_object *object, pt_parameters_acceptor_member member)
{
	zend_class_entry *ce = object->ce;
	const int8_t *slots;
	if (ce == pt_ce_resolved_function_variant_with_original) {
		slots = resolvedFunctionVariantSlots;
	} else if (ce == pt_ce_extended_function_variant) {
		slots = extendedFunctionVariantSlots;
	} else if (ce == pt_ce_function_variant) {
		slots = functionVariantSlots;
	} else if (ce == pt_ce_extended_callable_function_variant) {
		slots = extendedCallableFunctionVariantSlots;
	} else {
		return NULL;
	}
	int slot = slots[member];
	if (slot < 0) return NULL;
	zval *value = OBJ_PROP_NUM(object, (uint32_t) slot);
	/* IS_UNDEF: never initialized; IS_NULL: a memo not filled yet */
	return EXPECTED(Z_TYPE_P(value) > IS_NULL) ? value : NULL;
}

} // namespace ptacc

inline zv::Val pt_parameters_acceptor_call(zval *acceptor, pt_parameters_acceptor_member member)
{
	if (EXPECTED(Z_TYPE_P(acceptor) == IS_OBJECT)) {
		zval *value = ptacc::slotFor(Z_OBJ_P(acceptor), member);
		if (EXPECTED(value != NULL)) return zv::Val::copyOf(zv::Ref(value));
	}
	return pt_parameters_acceptor_call_slow(acceptor, member);
}

/* the answer borrowed where it is a slot, kept alive in hold otherwise;
 * NULL = pending exception */
inline zval *pt_parameters_acceptor_read(zval *acceptor, pt_parameters_acceptor_member member, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(acceptor) == IS_OBJECT)) {
		zval *value = ptacc::slotFor(Z_OBJ_P(acceptor), member);
		if (EXPECTED(value != NULL)) return value;
	}
	hold = pt_parameters_acceptor_call_slow(acceptor, member);
	return hold.isUndef() ? NULL : hold.raw();
}

/* a bool member's answer (truthiness); false = pending exception */
[[nodiscard]] inline bool pt_parameters_acceptor_bool(zval *acceptor, pt_parameters_acceptor_member member, bool &out)
{
	zv::Val hold;
	zval *value = pt_parameters_acceptor_read(acceptor, member, hold);
	if (UNEXPECTED(value == NULL)) return false;
	out = zend_is_true(value);
	return true;
}

#endif
