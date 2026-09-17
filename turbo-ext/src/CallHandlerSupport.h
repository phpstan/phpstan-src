/*
 * What the call handler ports (MethodCallHandler.cpp, StaticCallHandler.cpp,
 * NewHandler.cpp, FuncCallHandler.cpp) share: the calls into their collaborators — ArgumentsHandler,
 * ArgumentsNormalizer and ParametersAcceptorSelector through their direct
 * entries, the parameters acceptors and Assertions (still PHP) each over
 * one cached method site (C++17 inline variables: one site per helper for the
 * whole extension, so a later port switches the helper to a direct entry in
 * one place), the php-parser node property reads, and the small value helpers
 * the twins spell alike (array_merge(), `$type instanceof NeverType &&
 * $type->isExplicit()`, TemplateTypeHelper::resolveTemplateTypes() against an
 * acceptor, the `%s::%s()` description of a PossiblyImpureCallExpr).
 */

#ifndef PHPSTANTURBO_CALL_HANDLER_SUPPORT_H
#define PHPSTANTURBO_CALL_HANDLER_SUPPORT_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "AcceptorValues.h"

#include "zend_smart_str.h"

namespace ptcall {

/* {{{ the collaborators (the PHP ones one site each; switch to their direct
 * entries once they are ported) */

inline pt_method_site assertionsGetAllSite;
inline pt_method_site assertionsMapTypesSite;
inline pt_method_site getConstantStringsSite;

/* ParametersAcceptorSelector::combineVariantsForNormalization($args, $variants, $namedArgumentsVariants) */
inline zv::Val combineVariantsForNormalization(zval *args, zval *variants, zval *namedArgumentsVariants)
{
	return pt_parameters_acceptor_selector_combine_variants_for_normalization(args, variants, namedArgumentsVariants);
}

/* ParametersAcceptorSelector::combineAcceptors($acceptors) */
inline zv::Val combineAcceptors(zval *acceptors)
{
	return pt_parameters_acceptor_selector_combine_acceptors(acceptors);
}

/* ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall) */
inline zv::Val reorderMethodArguments(zval *parametersAcceptor, zval *methodCall)
{
	return pt_arguments_normalizer_reorder_method_arguments(parametersAcceptor, methodCall);
}

/* ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $staticCall) */
inline zv::Val reorderStaticCallArguments(zval *parametersAcceptor, zval *staticCall)
{
	return pt_arguments_normalizer_reorder_static_call_arguments(parametersAcceptor, staticCall);
}

/* ArgumentsNormalizer::reorderNewArguments($parametersAcceptor, $new) */
inline zv::Val reorderNewArguments(zval *parametersAcceptor, zval *new_)
{
	return pt_arguments_normalizer_reorder_new_arguments(parametersAcceptor, new_);
}

/* ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $functionCall) */
inline zv::Val reorderFuncArguments(zval *parametersAcceptor, zval *functionCall)
{
	return pt_arguments_normalizer_reorder_func_arguments(parametersAcceptor, functionCall);
}

/* ArgumentsNormalizer::reorderCallUserFuncArguments($callUserFuncCall, $scope) */
inline zv::Val reorderCallUserFuncArguments(zval *callUserFuncCall, zval *scope)
{
	return pt_arguments_normalizer_reorder_call_user_func_arguments(callUserFuncCall, scope);
}

/* ArgumentsNormalizer::reorderCallUserFuncArrayArguments($callUserFuncArrayCall, $scope) */
inline zv::Val reorderCallUserFuncArrayArguments(zval *callUserFuncArrayCall, zval *scope)
{
	return pt_arguments_normalizer_reorder_call_user_func_array_arguments(callUserFuncArrayCall, scope);
}

/* $parametersAcceptor->getReturnType() (the structural acceptor) —
 * AcceptorValues.h */
inline zv::Val acceptorReturnType(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RETURN_TYPE);
}

/* $resolvedParametersAcceptor->getReturnType() */
inline zv::Val resolvedAcceptorReturnType(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RETURN_TYPE);
}

/* $acceptor->getNativeReturnType() */
inline zv::Val acceptorNativeReturnType(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_NATIVE_RETURN_TYPE);
}

/* $acceptor->getResolvedTemplateTypeMap() */
inline zv::Val acceptorResolvedTemplateTypeMap(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_RESOLVED_TEMPLATE_TYPE_MAP);
}

/* $acceptor->getCallSiteVarianceMap() */
inline zv::Val acceptorCallSiteVarianceMap(zval *acceptor)
{
	return pt_parameters_acceptor_call(acceptor, PT_PA_GET_CALL_SITE_VARIANCE_MAP);
}

/* $argumentsHandler->processArgs(...) with the arguments the twin passes
 * (eleven, or twelve with $closureBindScopeFactory) — ArgumentsHandler.cpp */
inline zv::Val processArgs(zval *argumentsHandler, uint32_t argc, zval *argv)
{
	return pt_arguments_handler_process_args(argumentsHandler, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], &argv[6], &argv[7], &argv[8], &argv[9], &argv[10], argc > 11 ? &argv[11] : NULL);
}

/* $argumentsHandler->processDroppedArgs($nodeScopeResolver, $stmt, $originalCall,
 * $normalizedCall, $scope, $storage, $context); false = pending exception */
[[nodiscard]] inline bool processDroppedArgs(zval *argumentsHandler, zval *argv)
{
	return pt_arguments_handler_process_dropped_args(argumentsHandler, &argv[0], &argv[1], &argv[2], &argv[3], &argv[4], &argv[5], &argv[6]);
}

/* $assertions->getAll() */
inline zv::Val assertionsGetAll(zval *assertions)
{
	return pt_call_method_cached(assertionsGetAllSite, Z_OBJ_P(assertions), PT_LC("getall"), 0, NULL);
}

/* $assertions->mapTypes($callable) */
inline zv::Val assertionsMapTypes(zval *assertions, zval *callable)
{
	return pt_call_method_cached(assertionsMapTypesSite, Z_OBJ_P(assertions), PT_LC("maptypes"), 1, callable);
}

/* $type->getConstantStrings() */
inline zv::Val getConstantStrings(zval *type)
{
	return pt_call_method_cached(getConstantStringsSite, Z_OBJ_P(type), PT_LC("getconstantstrings"), 0, NULL);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

/* $node->$name (dereferenced) through the caller's site; NULL with the
 * uninitialized-read / undefined-property Error pending */
inline zval *nodeProperty(pt_property_site &site, zval *node, const char *name, size_t len)
{
	zval *value = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (EXPECTED(value != NULL)) {
		ZVAL_DEREF(value);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	zend_throw_error(NULL, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
	return NULL;
}

/* whether $value instanceof the class-map class; -1 = pending exception */
inline int isInstanceOf(zval *value, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return -1;
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce) ? 1 : 0;
}

/* whether $node instanceof Identifier; -1 = pending exception */
inline int isIdentifier(zval *node)
{
	return isInstanceOf(node, PT_CLASS_IDENTIFIER);
}

/* }}} */

/* {{{ small value helpers */

/* $array[] = $value (the array separated first) */
inline void appendTo(zv::Val &array, zv::Val value)
{
	zval *arr = array.raw();
	SEPARATE_ARRAY(arr);
	zval item = value.take();
	if (UNEXPECTED(zend_hash_next_index_insert(Z_ARRVAL_P(arr), &item) == NULL)) {
		zval_ptr_dtor(&item);
	}
}

/* array_merge($a, $b) of two arrays (string keys kept, integer keys
 * renumbered), with array_merge()'s own shortcut: an empty side yields the
 * other array itself when that one is a hole-free list or a map with string
 * keys only (so [] + [] is the shared empty array, as in the engine) */
inline zv::Val arrayMerge(zval *a, zval *b)
{
	HashTable *first = Z_ARRVAL_P(a);
	HashTable *second = Z_ARRVAL_P(b);
	zval *only = zend_hash_num_elements(first) == 0 ? b : (zend_hash_num_elements(second) == 0 ? a : NULL);
	if (only != NULL) {
		HashTable *onlyTable = Z_ARRVAL_P(only);
		if (HT_IS_PACKED(onlyTable)) {
			if (HT_IS_WITHOUT_HOLES(onlyTable)) return zv::Val::copyOf(zv::Ref(only));
		} else {
			bool stringKeysOnly = true;
			for (zv::ArrayEntry entry : zv::TableRef(onlyTable)) {
				if (!entry.hasStringKey()) {
					stringKeysOnly = false;
					break;
				}
			}
			if (stringKeysOnly) return zv::Val::copyOf(zv::Ref(only));
		}
	}
	zv::Arr merged = zv::Arr::create(zend_hash_num_elements(first) + zend_hash_num_elements(second));
	for (HashTable *source : { first, second }) {
		for (zv::ArrayEntry entry : zv::TableRef(source)) {
			if (entry.hasStringKey()) {
				merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
			} else {
				merged.push(entry.value());
			}
		}
	}
	return zv::Val(std::move(merged));
}

/* $type instanceof NeverType && $type->isExplicit(); false = pending exception */
[[nodiscard]] inline bool isExplicitNever(zval *type, bool &out)
{
	out = false;
	if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return true;
	return pt_never_type_is_explicit(Z_OBJ_P(type), out);
}

/* the sprintf('%s::%s()', $methodReflection->getDeclaringClass()->getDisplayName(),
 * $methodReflection->getName()) of a PossiblyImpureCallExpr; UNDEF = pending
 * exception */
inline zv::Val possiblyImpureCallDescription(zval *methodReflection)
{
	zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
	if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
	zv::Val displayName = pt_class_reflection_get_display_name(Z_OBJ_P(declaringClass.raw()), true);
	if (UNEXPECTED(displayName.isUndef())) return zv::Val();
	zv::Val methodName = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_NAME);
	if (UNEXPECTED(methodName.isUndef())) return zv::Val();
	zend_string *displayNameString = zval_get_string(displayName.raw());
	zend_string *methodNameString = zval_get_string(methodName.raw());
	smart_str description = {NULL, 0};
	smart_str_append(&description, displayNameString);
	smart_str_appends(&description, "::");
	smart_str_append(&description, methodNameString);
	smart_str_appends(&description, "()");
	zend_string_release(displayNameString);
	zend_string_release(methodNameString);
	zval value;
	ZVAL_STR(&value, smart_str_extract(&description));
	return zv::Val::adopt(value);
}

/* TemplateTypeHelper::resolveTemplateTypes($type,
 * $acceptor->getResolvedTemplateTypeMap(), $acceptor instanceof
 * ExtendedParametersAcceptor ? $acceptor->getCallSiteVarianceMap() :
 * TemplateTypeVarianceMap::createEmpty(), TemplateTypeVariance::create<variance>());
 * UNDEF = pending exception */
inline zv::Val resolveTemplateTypesAgainst(zval *type, zval *acceptor, zend_long variance)
{
	zv::Val resolvedTemplateTypeMap = acceptorResolvedTemplateTypeMap(acceptor);
	if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
	zend_class_entry *extendedAcceptorCe = pt_class(PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR);
	if (UNEXPECTED(extendedAcceptorCe == NULL)) return zv::Val();
	zv::Val callSiteVarianceMap = instanceof_function(Z_OBJCE_P(acceptor), extendedAcceptorCe)
		? acceptorCallSiteVarianceMap(acceptor)
		: pt_type_template_type_variance_map_empty();
	if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
	zval *positionVariance = pt_template_type_variance_singleton(variance);
	if (UNEXPECTED(positionVariance == NULL)) return zv::Val();
	return pt_type_template_type_helper_resolve_template_types(type, resolvedTemplateTypeMap.raw(), callSiteVarianceMap.raw(), positionVariance, false);
}

/* the body of the handlers' `static fn (Type $type) =>
 * TemplateTypeHelper::resolveTemplateTypes($type,
 * $resolvedParametersAcceptor->getResolvedTemplateTypeMap(), ...,
 * TemplateTypeVariance::createInvariant())` asserts mapping closure —
 * captures: $resolvedParametersAcceptor; closureName is the twin's
 * `<Handler>::{closure}` for the engine's messages */
inline void resolveAssertType(zval *captures, uint32_t argc, zval *argv, zval *return_value, const char *closureName)
{
	if (UNEXPECTED(argc < 1)) {
		zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and exactly 1 expected", closureName, argc);
		return;
	}
	zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
	if (UNEXPECTED(typeCe == NULL)) return;
	if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT || !instanceof_function(Z_OBJCE(argv[0]), typeCe))) {
		zend_type_error("%s(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", closureName, zend_zval_value_name(&argv[0]));
		return;
	}
	zv::Val type = resolveTemplateTypesAgainst(&argv[0], &captures[0], PT_TEMPLATE_TYPE_VARIANCE_INVARIANT);
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* the ArgumentCountError of a native closure body called with too few
 * arguments; false = raised */
[[nodiscard]] inline bool requireArguments(uint32_t argc, uint32_t required, const char *closureName)
{
	if (EXPECTED(argc >= required)) return true;
	zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function %s(), %u passed and exactly %u expected", closureName, argc, required);
	return false;
}

/* }}} */

} // namespace ptcall

#endif
