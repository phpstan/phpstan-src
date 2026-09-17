/*
 * What the closure cluster ports (ContextualClosureParameterResolver.cpp,
 * ClosureParameterResolver.cpp, ClosureTypeResolver.cpp, ClosureProcessor.cpp,
 * ClosureHandler.cpp, ArrowFunctionHandler.cpp) share: the php-parser node
 * reads of closures, arrow functions and their parameters, the getters of a
 * ParameterReflection of any class (the slots of the native
 * NativeParameterReflection / DummyParameter, one cached method site per
 * getter otherwise), TypeCombinator::union() over a gathered list, and the
 * ClosureParameterTypes value the resolvers' public resolve() methods return
 * (native callers take the two lists without the object).
 */

#ifndef PHPSTANTURBO_CLOSURE_SUPPORT_H
#define PHPSTANTURBO_CLOSURE_SUPPORT_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "CallHandlerSupport.h"
#include "ParameterValues.h"
#include "generated/NativeParameterReflection.h"
#include "StmtHandlerCalls.h"

namespace ptclosure {

/* {{{ node reads */

inline pt_property_site paramsSite;
inline pt_property_site staticSite;
inline pt_property_site returnTypeSite;
inline pt_property_site usesSite;
inline pt_property_site stmtsSite;
inline pt_property_site arrowExprSite;
inline pt_property_site attrGroupsSite;
inline pt_property_site useVarSite;
inline pt_property_site useByRefSite;
inline pt_property_site variableNameSite;
inline pt_property_site paramVarSite;
inline pt_property_site paramByRefSite;
inline pt_property_site paramVariadicSite;
inline pt_property_site paramDefaultSite;
inline pt_property_site paramTypeSite;
inline pt_property_site argValueSite;

/* $node->$name of a declared node property (dereferenced); NULL = pending
 * exception */
inline zval *prop(pt_property_site &site, zval *node, const char *name, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(node));
		return NULL;
	}
	return ptcall::nodeProperty(site, node, name, len);
}

/* $node->getAttribute($key) of a php-parser node: the value (dereferenced,
 * borrowed), NULL when the node has no such attribute */
inline zval *attribute(zval *node, zend_string *key)
{
	zval *value = pt_node_attribute(Z_OBJ_P(node), key);
	if (value != NULL) {
		ZVAL_DEREF(value);
	}
	return value;
}

/* $value instanceof <class-map class>; -1 = pending exception */
inline int instanceOf(zval *value, int classIdx)
{
	return ptcall::isInstanceOf(value, classIdx);
}

/* }}} */

/* {{{ ParameterReflection getters */

/* the getters through pt_parameter_reflection_call() (ParameterValues.h) */
#define PT_CLOSURE_PARAMETER_GETTER(fn, member) \
	inline zv::Val fn(zval *parameter) \
	{ \
		return pt_parameter_reflection_call(parameter, member); \
	}
PT_CLOSURE_PARAMETER_GETTER(parameterGetName, PT_PR_GET_NAME)
PT_CLOSURE_PARAMETER_GETTER(parameterIsOptional, PT_PR_IS_OPTIONAL)
PT_CLOSURE_PARAMETER_GETTER(parameterGetType, PT_PR_GET_TYPE)
PT_CLOSURE_PARAMETER_GETTER(parameterPassedByReference, PT_PR_PASSED_BY_REFERENCE)
PT_CLOSURE_PARAMETER_GETTER(parameterIsVariadic, PT_PR_IS_VARIADIC)
PT_CLOSURE_PARAMETER_GETTER(parameterGetDefaultValue, PT_PR_GET_DEFAULT_VALUE)
PT_CLOSURE_PARAMETER_GETTER(parameterGetNativeType, PT_PR_GET_NATIVE_TYPE)
#undef PT_CLOSURE_PARAMETER_GETTER

/* new NativeParameterReflection($parameter->getName(), $parameter->isOptional(),
 * $type, $parameter->passedByReference(), $parameter->isVariadic(),
 * $parameter->getDefaultValue()) — the getters in that order, $type NULL for
 * $parameter->getType() read in its place; UNDEF = pending exception */
inline zv::Val nativeParameterFrom(zval *parameter, zval *type)
{
	zv::Val name = parameterGetName(parameter);
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	zv::Val optional = parameterIsOptional(parameter);
	if (UNEXPECTED(optional.isUndef())) return zv::Val();
	zv::Val ownType;
	if (type == NULL) {
		ownType = parameterGetType(parameter);
		if (UNEXPECTED(ownType.isUndef())) return zv::Val();
		type = ownType.raw();
	}
	zv::Val passedByReference = parameterPassedByReference(parameter);
	if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
	zv::Val variadic = parameterIsVariadic(parameter);
	if (UNEXPECTED(variadic.isUndef())) return zv::Val();
	zv::Val defaultValue = parameterGetDefaultValue(parameter);
	if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
	zv::Args argv{name.raw(), optional.raw(), type, passedByReference.raw(), variadic.raw(), defaultValue.raw()};
	return pt_native_parameter_reflection_new(6, argv);
}

/* }}} */

/* {{{ small values */

/* TypeCombinator::union(...$types) of a list built with push() */
inline zv::Val unionOf(zv::Arr &types)
{
	HashTable *table = types.table();
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return pt_type_combinator_union(0, NULL);
	if (EXPECTED(HT_IS_PACKED(table) && table->nNumUsed == count)) return pt_type_combinator_union(count, table->arPacked);
	return pt_type_combinator_call_spread(PT_LC("union"), table);
}

/* TrinaryLogic::createFromBoolean($value) (borrowed singleton) */
inline zval *trinaryFromBool(bool value)
{
	return pt_trinary_singleton(value ? PT_TRI_YES : PT_TRI_NO);
}

/* an owned `new ClosureParameterTypes($parameters, $nativeParameters)` */
inline zv::Val newClosureParameterTypes(zval *parameters, zval *nativeParameters)
{
	zv::Args argv{parameters, nativeParameters};
	return pt_type_new(PT_CLASS_CLOSURE_PARAMETER_TYPES, 2, argv);
}

/* $parameterTypes->parameters / ->nativeParameters of a ClosureParameterTypes
 * a PHP resolver returned; false = pending exception */
[[nodiscard]] inline bool readClosureParameterTypes(zval *parameterTypes, zv::Val &parameters, zv::Val &nativeParameters)
{
	if (UNEXPECTED(Z_TYPE_P(parameterTypes) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"parameters\" on %s", zend_zval_value_name(parameterTypes));
		return false;
	}
	for (int i = 0; i < 2; i++) {
		const char *name = i == 0 ? "parameters" : "nativeParameters";
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *value = zend_read_property(Z_OBJCE_P(parameterTypes), Z_OBJ_P(parameterTypes), name, strlen(name), 0, &rv);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&rv);
			return false;
		}
		zv::Val &target = i == 0 ? parameters : nativeParameters;
		if (value == &rv) {
			target = zv::Val::adopt(rv);
		} else {
			target = zv::Val::copyOf(zv::Ref(value).deref());
		}
	}
	return true;
}

/* }}} */

/* {{{ the walk gatherers the closure ports share */

/* the 'propertyAssign' / 'property assignment' literals of the property-assign
 * impure point, permanent interned strings (initStrings() at module startup) */
inline zend_string *propertyAssignIdentifier = nullptr;
inline zend_string *propertyAssignDescription = nullptr;

inline void initStrings()
{
	if (propertyAssignIdentifier != nullptr) return;
	propertyAssignIdentifier = zend_string_init_interned(PT_LC("propertyAssign"), 1);
	propertyAssignDescription = zend_string_init_interned(PT_LC("property assignment"), 1);
}

/* $scope->getAnonymousFunctionReflection() !== $enteredScope->getAnonymousFunctionReflection():
 * 1 different, 0 the same; -1 = pending exception. Two exact MutatingScopes
 * compare their slots. */
inline int differentAnonymousFunction(zval *scope, zval *enteredScope)
{
	if (EXPECTED(Z_TYPE_P(scope) == IS_OBJECT && Z_TYPE_P(enteredScope) == IS_OBJECT)) {
		zval *reflection = pt_mutating_scope_anonymous_function_reflection_slot(Z_OBJ_P(scope));
		zval *enteredReflection = reflection != NULL ? pt_mutating_scope_anonymous_function_reflection_slot(Z_OBJ_P(enteredScope)) : NULL;
		if (EXPECTED(enteredReflection != NULL)) return zend_is_identical(reflection, enteredReflection) ? 0 : 1;
	}
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT || Z_TYPE_P(enteredScope) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getAnonymousFunctionReflection() on %s", zend_zval_value_name(Z_TYPE_P(scope) != IS_OBJECT ? scope : enteredScope));
		return -1;
	}
	zv::Val reflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(scope));
	if (UNEXPECTED(reflection.isUndef())) return -1;
	zv::Val enteredReflection = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(enteredScope));
	if (UNEXPECTED(enteredReflection.isUndef())) return -1;
	return zend_is_identical(reflection.raw(), enteredReflection.raw()) ? 0 : 1;
}

inline pt_property_site propertyFetchSite;

/* $list[] = new ImpurePoint($scope, $node, 'propertyAssign', 'property assignment', true);
 * $invalidateExpressions[] = new InvalidateExprNode($node->getPropertyFetch());
 * — on the by-reference captures of a gatherer; false = pending exception */
[[nodiscard]] inline bool gatherPropertyAssign(zval *node, zval *scope, zval *impurePointsReference, zval *invalidateExpressionsReference)
{
	zv::Val impurePoint = pt_impure_point_new(scope, node, propertyAssignIdentifier, propertyAssignDescription, true);
	if (UNEXPECTED(impurePoint.isUndef())) return false;
	ptsh::appendToReference(impurePointsReference, impurePoint.raw());
	zval *propertyFetch = prop(propertyFetchSite, node, PT_LC("propertyFetch"));
	if (UNEXPECTED(propertyFetch == NULL)) return false;
	zv::Val invalidateExprNode = pt_type_new(PT_CLASS_INVALIDATE_EXPR_NODE, 1, propertyFetch);
	if (UNEXPECTED(invalidateExprNode.isUndef())) return false;
	ptsh::appendToReference(invalidateExpressionsReference, invalidateExprNode.raw());
	return true;
}

/* [$node, $scope] */
inline zv::Val pairOf(zval *node, zval *scope)
{
	zv::Arr pair = zv::Arr::create(2);
	pair.push(zv::Ref(node));
	pair.push(zv::Ref(scope));
	return zv::Val(std::move(pair));
}

/* the arrow-function gatherer the twins spell alike:
 * static function (Node $node, Scope $scope) use ($arrowScope,
 * &$impurePoints, &$invalidateExpressions): void — captures in that order;
 * closureName names the twin's closure in the ArgumentCountError */
inline void arrowFunctionGatherer(zval *captures, uint32_t argc, zval *argv, const char *closureName)
{
	if (UNEXPECTED(!ptcall::requireArguments(argc, 2, closureName))) return;
	zval *node = &argv[0];
	zval *scope = &argv[1];
	if (differentAnonymousFunction(scope, &captures[0]) != 0) return;

	int is = instanceOf(node, PT_CLASS_INVALIDATE_EXPR_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		ptsh::appendToReference(&captures[2], node);
		return;
	}
	is = instanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE);
	if (UNEXPECTED(is < 0) || !is) return;
	(void) gatherPropertyAssign(node, scope, &captures[1], &captures[2]);
}

/* }}} */

} // namespace ptclosure

#endif
