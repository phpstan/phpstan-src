/*
 * PHPStanTurbo\FuncCallHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\FuncCallHandler.
 *
 * A final DI service (#[AutowiredService] with an #[AutowiredExtensions]
 * collection and two #[AutowiredParameter] bools): the constructor keeps the
 * twin's arginfo so Nette autowires it; processExpr() is the class's handler
 * entry (Engine.h). The twin's closures are native closures capturing what
 * the PHP closures capture: the typeCallback ($this, $nodeScopeResolver,
 * $beforeScope, $expr, $nameResult, $resolvedParametersAcceptor, $argsResult,
 * $storageRef — a real WeakReference, as the twin holds), the early
 * terminating `static fn (bool) => new NeverType(true)`, the
 * specifyTypesCallback and createTypesCallback, array_walk()'s node gatherer
 * (its `&$arrayWalkValueTypes` a by-reference capture) and the mapTypes()
 * callbacks of the assertion narrowing; resolveReturnType()'s `$getType`
 * closure and the array_map() / array_filter() callbacks never escape and
 * are spelled inline.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ArgsResult,
 * ExpressionContext, InternalThrowPoint, ImpurePoint, SimpleImpurePoint,
 * VariableFlow, VariableFlowBuilder, TemplateArgumentFrame, TypeSpecifier,
 * DefaultNarrowingHelper, EarlyTerminatingCallHelper, AssignHandler,
 * DynamicReturnTypeStoragePrimer, FuncCallScopeEffectsHelper, the CallLike
 * argument reader and the Type kernel are called through their direct
 * entries; the function reflection getters through
 * FunctionReflectionAccess.cpp. ArgumentsHandler / ArgumentsNormalizer /
 * ParametersAcceptorSelector and the acceptors' getters go through the call
 * handlers' shared sites (CallHandlerSupport.h); the analyser classes still
 * PHP only here — the reflection provider, ClosureTypeResolver,
 * ClosureProcessor, CloneHandler, SimpleThrowPoint,
 * DynamicReturnTypeExtensionRegistry, ImpossibleCheckTypeHelper — through
 * the cached sites in the block below, one helper each; the dynamic
 * throw-type / return-type / type-specifying extensions (PHP forever)
 * through per-class polymorphic sites.
 */

#include "support.h"
#include "generated/FuncCallHandler.h"

namespace slots = ptdecl::FuncCallHandler::slot;
namespace sigs = ptdecl::FuncCallHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "CallHandlerSupport.h"

#include "zend_weakrefs.h"

zend_class_entry *pt_ce_func_call_handler = nullptr;

namespace {

/* the most accessories the clone-with property list narrows the cloned type
 * with (the twin's `count($accessories) <= 16`) */
constexpr uint32_t PT_FCH_CLONE_ACCESSORIES_LIMIT = 16;
/* the slots of a polymorphic extension site's direct-mapped class-entry
 * table, as a bit count (a colliding class overwrites the slot) */
constexpr uint32_t PT_FCH_POLY_SITE_SLOT_BITS_LIMIT = 8;
constexpr uint32_t PT_FCH_POLY_SITE_SLOTS_LIMIT = 1u << PT_FCH_POLY_SITE_SLOT_BITS_LIMIT;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_fch_has_function_site;
pt_method_site pt_fch_get_function_site;
pt_method_site pt_fch_get_declared_closure_type_site;
pt_method_site pt_fch_process_immediately_called_callable_site;
pt_method_site pt_fch_resolve_clone_type_site;
pt_method_site pt_fch_throw_point_explicit_site;
pt_method_site pt_fch_throw_point_type_site;
pt_method_site pt_fch_throw_point_any_throwable_site;
pt_method_site pt_fch_throw_point_from_throw_expr_site;
pt_method_site pt_fch_dynamic_function_return_type_extensions_site;
pt_method_site pt_fch_find_specified_type_site;
pt_method_site pt_fch_closure_expr_site;
pt_method_site pt_fch_statement_result_site;

/* $reflectionProvider->hasFunction($name, $scope) (coerced to bool); false
 * = pending exception */
[[nodiscard]] bool hasFunction(zval *reflectionProvider, zval *name, zval *scope, bool &out)
{
	zv::Args argv{name, scope};
	zv::Val value = pt_call_method_cached(pt_fch_has_function_site, Z_OBJ_P(reflectionProvider), PT_LC("hasfunction"), 2, argv);
	if (UNEXPECTED(value.isUndef())) return false;
	out = zend_is_true(value.raw());
	return true;
}

/* $reflectionProvider->getFunction($name, $scope) */
zv::Val getFunction(zval *reflectionProvider, zval *name, zval *scope)
{
	zv::Args argv{name, scope};
	return pt_call_method_cached(pt_fch_get_function_site, Z_OBJ_P(reflectionProvider), PT_LC("getfunction"), 2, argv);
}

/* $closureTypeResolver->getDeclaredClosureType($scope, $closure) */
zv::Val getDeclaredClosureType(zval *closureTypeResolver, zval *scope, zval *closure)
{
	zv::Args argv{scope, closure};
	return pt_call_method_cached(pt_fch_get_declared_closure_type_site, Z_OBJ_P(closureTypeResolver), PT_LC("getdeclaredclosuretype"), 2, argv);
}

/* $argumentsHandler->processArgs($nodeScopeResolver, $stmt, $calleeReflection,
 * null, $parametersAcceptors, $namedArgumentsVariants, $callLike, $scope,
 * $storage, $nodeCallback, $context) */
zv::Val processArgs(zval *argumentsHandler, zval *nodeScopeResolver, zval *stmt, zval *calleeReflection, zval *parametersAcceptors, zval *namedArgumentsVariants, zval *callLike, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	zval null = {};
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, calleeReflection, &null, parametersAcceptors, namedArgumentsVariants, callLike, scope, storage, nodeCallback, context};
	return ptcall::processArgs(argumentsHandler, 11, argv);
}

/* $argumentsHandler->processDroppedArgs($nodeScopeResolver, $stmt, $originalCall,
 * $normalizedCall, $scope, $storage, $context); false = pending exception */
[[nodiscard]] bool processDroppedArgs(zval *argumentsHandler, zval *nodeScopeResolver, zval *stmt, zval *originalCall, zval *normalizedCall, zval *scope, zval *storage, zval *context)
{
	zv::Args argv{nodeScopeResolver, stmt, originalCall, normalizedCall, scope, storage, context};
	return ptcall::processDroppedArgs(argumentsHandler, argv);
}

/* $closureProcessor->processImmediatelyCalledCallable($scope, $invalidatedExpressions, $uses) */
zv::Val processImmediatelyCalledCallable(zval *closureProcessor, zval *scope, zval *invalidatedExpressions, zval *uses)
{
	zv::Args argv{scope, invalidatedExpressions, uses};
	return pt_call_method_cached(pt_fch_process_immediately_called_callable_site, Z_OBJ_P(closureProcessor), PT_LC("processimmediatelycalledcallable"), 3, argv);
}

/* CloneHandler::resolveCloneType($exprType) */
zv::Val resolveCloneType(zval *exprType)
{
	return pt_call_static_cached(pt_fch_resolve_clone_type_site, PT_CLASS_CLONE_HANDLER, PT_LC("resolveclonetype"), 1, exprType);
}

/* $assignHandler->processVirtualAssign($nodeScopeResolver, $scope, $storage,
 * $stmt, $var, $assignedExpr, $nodeCallback) — its result discarded; false
 * = pending exception */
[[nodiscard]] bool processVirtualAssign(zval *assignHandler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback)
{
	return !pt_assign_handler_process_virtual_assign(assignHandler, nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, NULL).isUndef();
}

/* a no-argument method of a PHP value object through its site, the Error
 * PHP raises for a call on a non-object */
zv::Val callNoArgs(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* $registry->getDynamicFunctionReturnTypeExtensions($functionReflection) */
zv::Val getDynamicFunctionReturnTypeExtensions(zval *registry, zval *functionReflection)
{
	return pt_call_method_cached(pt_fch_dynamic_function_return_type_extensions_site, Z_OBJ_P(registry), PT_LC("getdynamicfunctionreturntypeextensions"), 1, functionReflection);
}

/* $impossibleCheckTypeHelper->findSpecifiedType($scope, $node, $nodeResult, $argsResult) */
zv::Val findSpecifiedType(zval *impossibleCheckTypeHelper, zval *scope, zval *node, zval *nodeResult, zval *argsResult)
{
	zv::Args argv{scope, node, nodeResult, argsResult};
	return pt_call_method_cached(pt_fch_find_specified_type_site, Z_OBJ_P(impossibleCheckTypeHelper), PT_LC("findspecifiedtype"), 4, argv);
}

/* a method of the PHP extensions (isFunctionSupported(),
 * getThrowTypeFromFunctionCall(), getTypeFromFunctionCall(),
 * specifyTypes()): a per-site direct-mapped table of resolved zend_functions
 * keyed by the extension's class entry — a run's dozens of extension
 * classes each resolve once */
struct PolySite
{
	uint32_t generation;
	zend_class_entry *ce[PT_FCH_POLY_SITE_SLOTS_LIMIT];
	zend_function *fn[PT_FCH_POLY_SITE_SLOTS_LIMIT];
};

PolySite pt_fch_throw_supported_site;
PolySite pt_fch_throw_type_site;
PolySite pt_fch_return_type_site;
PolySite pt_fch_specifying_supported_site;
PolySite pt_fch_specifying_specify_site;

zv::Val callPoly(PolySite &site, zval *object, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	zend_object *receiver = Z_OBJ_P(object);
	if (UNEXPECTED(site.generation != pt_engine_generation)) {
		site.generation = pt_engine_generation;
		memset(site.ce, 0, sizeof(site.ce));
	}
	/* Fibonacci hashing of the class-entry pointer (its low bits are alignment) */
	size_t slot = (size_t) ((((uintptr_t) receiver->ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull) >> (sizeof(uintptr_t) * 8 - PT_FCH_POLY_SITE_SLOT_BITS_LIMIT)) & (PT_FCH_POLY_SITE_SLOTS_LIMIT - 1);
	zend_function *fn;
	if (EXPECTED(site.ce[slot] == receiver->ce)) {
		fn = site.fn[slot];
	} else {
		fn = pt_find_method(receiver->ce, lcname, len);
		if (UNEXPECTED(fn == NULL)) return zv::Val();
		site.ce[slot] = receiver->ce;
		site.fn[slot] = fn;
	}
	zval ret;
	zend_call_known_function(fn, receiver, receiver->ce, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

/* WeakReference::create($referent) / $weakReference->get() */
zend_function *pt_fch_weakref_create_fn = NULL;
zend_function *pt_fch_weakref_get_fn = NULL;

zv::Val weakReferenceCreate(zval *referent)
{
	if (UNEXPECTED(pt_fch_weakref_create_fn == NULL)) {
		pt_fch_weakref_create_fn = pt_find_method(zend_ce_weakref, PT_LC("create"));
		if (UNEXPECTED(pt_fch_weakref_create_fn == NULL)) return zv::Val();
	}
	zval ret;
	zend_call_known_function(pt_fch_weakref_create_fn, NULL, zend_ce_weakref, &ret, 1, referent, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

zv::Val weakReferenceGet(zval *weakReference)
{
	if (UNEXPECTED(pt_fch_weakref_get_fn == NULL)) {
		pt_fch_weakref_get_fn = pt_find_method(zend_ce_weakref, PT_LC("get"));
		if (UNEXPECTED(pt_fch_weakref_get_fn == NULL)) return zv::Val();
	}
	zval ret;
	zend_call_known_function(pt_fch_weakref_get_fn, Z_OBJ_P(weakReference), Z_OBJCE_P(weakReference), &ret, 0, NULL, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}
	return zv::Val::adopt(ret);
}

/* }}} */

/* {{{ node reads */

pt_property_site pt_fch_name_site;
pt_property_site pt_fch_name_string_site;
pt_property_site pt_fch_arg_value_site;
pt_property_site pt_fch_arg_unpack_site;
pt_property_site pt_fch_params_site;
pt_property_site pt_fch_param_by_ref_site;
pt_property_site pt_fch_param_var_site;
pt_property_site pt_fch_variable_name_site;
pt_property_site pt_fch_string_value_site;
pt_property_site pt_fch_inner_name_site;
pt_property_site pt_fch_attributes_site;

/* $value instanceof <class-map class> — a class not declared yet has no
 * instances (no autoload, as instanceof); false with an exception pending
 * only when the class map cannot resolve the key */
inline bool isA(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	zend_class_entry *ce = pt_class_loaded(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

/* a declared property of a node (dereferenced, borrowed) through a per-site
 * offset; the warning PHP raises for a read on a non-object (null then);
 * NULL = pending exception */
zval *nodeProp(pt_property_site &site, zval *node, const char *name, size_t len)
{
	ZVAL_DEREF(node);
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", name, zend_zval_value_name(node));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	zval *slot = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(slot == NULL)) {
		zend_error(E_WARNING, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(slot);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), name);
		return NULL;
	}
	return slot;
}

/* $call->getArgs() (the CallLike reader, support.h) as an owned value;
 * UNDEF = pending exception */
zv::Val callArgs(zval *call)
{
	zv::Val hold;
	zval *args = pt_call_like_args(Z_OBJ_P(call), hold);
	if (UNEXPECTED(args == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(args)) : std::move(hold);
}

/* $call->isFirstClassCallable(); false = pending exception */
[[nodiscard]] inline bool isFirstClassCallable(zval *call, bool &out)
{
	return pt_call_like_is_first_class_callable(Z_OBJ_P(call), out);
}

inline uint32_t countOf(zval *array)
{
	return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(array)) : 0;
}

/* $array[$index] with the warning of a missing key (null then); NULL =
 * pending exception */
zval *arrayIndex(zval *array, zend_ulong index)
{
	zval *found = Z_TYPE_P(array) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(array), index) : NULL;
	if (UNEXPECTED(found == NULL)) {
		zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(found);
	return found;
}

/* $array[$key] of a foreach key (int or string) */
zval *arrayDim(zval *array, zval *key)
{
	if (Z_TYPE_P(key) == IS_LONG) return arrayIndex(array, (zend_ulong) Z_LVAL_P(key));
	zval *found = Z_TYPE_P(array) == IS_ARRAY ? zend_symtable_find(Z_ARRVAL_P(array), Z_STR_P(key)) : NULL;
	if (UNEXPECTED(found == NULL)) {
		zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(Z_STR_P(key)));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(found);
	return found;
}

/* $args[$index]->value (borrowed); NULL = pending exception */
zval *argValueAt(zval *args, zend_ulong index)
{
	zval *arg = arrayIndex(args, index);
	if (UNEXPECTED(arg == NULL)) return NULL;
	return nodeProp(pt_fch_arg_value_site, arg, PT_LC("value"));
}

/* the `name` string of a Name (toString()) — the slot for php-parser's Name
 * and Name\FullyQualified, the method otherwise; NULL = pending exception */
zend_string *nameToString(zval *name, zv::Val &hold)
{
	zend_class_entry *ce = Z_OBJCE_P(name);
	if (EXPECTED(ce == pt_class_loaded(PT_CLASS_NAME) || ce == pt_class_loaded(PT_CLASS_FULLY_QUALIFIED))) {
		zval *value = pt_property_cached(pt_fch_name_string_site, Z_OBJ_P(name), PT_LC("name"));
		if (EXPECTED(value != NULL)) {
			ZVAL_DEREF(value);
			if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return Z_STR_P(value);
		}
	}
	hold = pt_type_call(Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
	if (UNEXPECTED(hold.isUndef())) return NULL;
	if (UNEXPECTED(Z_TYPE_P(hold.raw()) != IS_STRING)) {
		zend_type_error("%s::toString(): Return value must be of type string, %s returned", ZSTR_VAL(ce->name), zend_zval_value_name(hold.raw()));
		return NULL;
	}
	return Z_STR_P(hold.raw());
}

/* }}} */

/* {{{ values */

/* the function reflection's getName(); NULL = pending exception */
zend_string *functionName(zval *functionReflection, zv::Val &hold)
{
	zval *name = pt_function_reflection_name(functionReflection, hold);
	if (UNEXPECTED(name == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
		zend_type_error("%s::getName(): Return value must be of type string, %s returned", ZSTR_VAL(Z_OBJCE_P(functionReflection)->name), zend_zval_value_name(name));
		return NULL;
	}
	return Z_STR_P(name);
}

/* $functionReflection->getName() === $literal; false = pending exception */
template <size_t N>
[[nodiscard]] bool functionNameIs(zval *functionReflection, const char (&literal)[N], bool &out)
{
	zv::Val hold;
	zend_string *name = functionName(functionReflection, hold);
	if (UNEXPECTED(name == NULL)) return false;
	out = zend_string_equals_cstr(name, literal, N - 1);
	return true;
}

/* $type->method() of a Type by name, the Error of a call on a non-object */
zv::Val typeCall(zval *type, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
}

/* a Type op on a value that must be an object */
zv::Val typeOp(zval *type, pt_type_op_id op, const char *name, uint32_t argc = 0, zval *argv = NULL)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_op(Z_OBJ_P(type), op, argc, argv);
}

/* a TrinaryLogic op; -1 = pending exception */
zend_long typeOpTrinary(zval *type, pt_type_op_id op, const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(type));
		return -1;
	}
	return pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
}

/* a TrinaryLogic method by name; -1 = pending exception */
zend_long typeCallTrinary(zval *type, const char *lcname, size_t len, const char *name, uint32_t argc = 0, zval *argv = NULL)
{
	zv::Val result = typeCall(type, lcname, len, name, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

/* the persistent "Closure" / "Throwable" class-name literals */
zend_string *pt_fch_closure_name = NULL;
zend_string *pt_fch_throwable_name = NULL;
zend_string *pt_fch_function_call_identifier = NULL;
zend_string *pt_fch_unknown_function_description = NULL;
zend_string *pt_fch_in_clone_with = NULL;
zend_string *pt_fch_invoke = NULL;

/* (new ObjectType($className))->isSuperTypeOf($type) as PT_TRI_*; -1 =
 * pending exception */
zend_long objectTypeIsSuperTypeOf(zend_string *className, zval *type)
{
	zval objectType;
	if (UNEXPECTED(!pt_object_type_new(&objectType, className))) return -1;
	zv::Val objectTypeHold = zv::Val::adopt(objectType);
	zv::Val result = pt_type_op(Z_OBJ_P(objectTypeHold.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $nameType->isObject()->yes() && $nameType->isCallable()->yes() && (new
 * ObjectType(Closure::class))->isSuperTypeOf($nameType)->no(); false =
 * pending exception */
[[nodiscard]] bool isInvokableObject(zval *nameType, bool &out)
{
	out = false;
	zend_long isObject = typeCallTrinary(nameType, PT_LC("isobject"), "isObject");
	if (UNEXPECTED(isObject < 0)) return false;
	if (isObject != PT_TRI_YES) return true;
	zend_long isCallable = typeOpTrinary(nameType, PT_OP_IS_CALLABLE, "isCallable");
	if (UNEXPECTED(isCallable < 0)) return false;
	if (isCallable != PT_TRI_YES) return true;
	zend_long isClosure = objectTypeIsSuperTypeOf(pt_fch_closure_name, nameType);
	if (UNEXPECTED(isClosure < 0)) return false;
	out = isClosure == PT_TRI_NO;
	return true;
}

/* $into = array_merge($into, $more) (ptcall::arrayMerge()); false = pending
 * exception */
[[nodiscard]] bool arrayMerge(zv::Val &into, zval *more)
{
	if (UNEXPECTED(Z_TYPE_P(more) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(more));
		return false;
	}
	into = ptcall::arrayMerge(into.raw(), more);
	return true;
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\FuncCallHandler; UNDEF = pending
 * exception. */
class FuncCallHandler
{
public:
	explicit FuncCallHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval **argv) const
	{
		static const uint32_t order[] = {
			slots::reflectionProvider, slots::dynamicFunctionThrowTypeExtensions, slots::dynamicReturnTypeExtensionRegistry,
			slots::implicitThrows, slots::rememberPossiblyImpureFunctionValues, slots::scopeEffectsHelper,
			slots::expressionResultFactory, slots::typeSpecifier, slots::defaultNarrowingHelper,
			slots::earlyTerminatingHelper, slots::storagePrimer, slots::impossibleCheckTypeHelper,
			slots::closureTypeResolver, slots::argumentsHandler, slots::closureProcessor, slots::assignHandler,
		};
		zv::ObjRef object(self);
		for (uint32_t i = 0; i < sizeof(order) / sizeof(order[0]); i++) {
			object.propAtWrite(order[i], zv::Val::copyOf(zv::Ref(argv[i])));
		}
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		out = false;
		if (!isA(expr, PT_CLASS_FUNC_CALL)) return EG(exception) == NULL;
		bool firstClassCallable;
		if (UNEXPECTED(!isFirstClassCallable(expr, firstClassCallable))) return false;
		out = !firstClassCallable;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeIn, zval *storage, zval *nodeCallback, zval *contextIn) const
	{
		zval *beforeScope = scopeIn;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeIn));
		zv::Val context = zv::Val::copyOf(zv::Ref(contextIn));
		zv::Val parametersAcceptor = zv::Val::null();
		zv::Val variants = zv::Val(zv::Arr::empty());
		zv::Val namedArgumentsVariants = zv::Val::null();
		zv::Val functionReflection = zv::Val::null();
		zv::Val nameResult = zv::Val::null();
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());

		zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		name = nameHold.raw();
		bool nameIsName = isA(name, PT_CLASS_NAME);
		bool nameIsExpr = !nameIsName && isA(name, PT_CLASS_EXPR);
		if (UNEXPECTED(EG(exception))) return zv::Val();

		// A call configured as early-terminating never returns: give it an explicit
		// never so the statement's exit point follows from the result type, instead of
		// NodeScopeResolver re-deriving it via Scope::getType().
		bool isEarlyTerminating = false;
		if (nameIsName) {
			zv::Val stringHold;
			zend_string *nameString = nameToString(name, stringHold);
			if (UNEXPECTED(nameString == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_early_terminating_call_helper_is_early_terminating_function_call(slot(slots::earlyTerminatingHelper), nameString, isEarlyTerminating))) return zv::Val();
		}
		bool isAlwaysTerminating = isEarlyTerminating;
		zv::Val argumentsWalkedAhead = zv::Val::null();
		if (isA(name, PT_CLASS_CLOSURE_EXPR) || isA(name, PT_CLASS_ARROW_FUNCTION)) {
			zv::Val invokedArgs = pt_engine_node_get_attribute(Z_OBJ_P(name), PT_LC("immediatelyInvokedClosureArgs"));
			if (UNEXPECTED(invokedArgs.isUndef())) return zv::Val();
			if (!invokedArgs.isNull()) {
				// An immediately invoked closure's untyped parameters take the types
				// of its invocation arguments, so the arguments are walked BEFORE the
				// callee - on the closure's declared signature - and the closure body
				// consumes their stored results instead of pricing them ahead of
				// their turn. The closure is then walked on the post-argument scope.
				zv::Val declaredType = getDeclaredClosureType(slot(slots::closureTypeResolver), scope.raw(), name);
				if (UNEXPECTED(declaredType.isUndef())) return zv::Val();
				zv::Val shallowVariants = typeCall(declaredType.raw(), PT_LC("getcallableparametersacceptors"), "getCallableParametersAcceptors", 1, scope.raw());
				if (UNEXPECTED(shallowVariants.isUndef())) return zv::Val();
				zv::Val args = callArgs(expr);
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				zval null;
				ZVAL_NULL(&null);
				zv::Val shallowAcceptor = ptcall::combineVariantsForNormalization(args.raw(), shallowVariants.raw(), &null);
				if (UNEXPECTED(shallowAcceptor.isUndef())) return zv::Val();
				zv::Val reordered = ptcall::reorderFuncArguments(shallowAcceptor.raw(), expr);
				if (UNEXPECTED(reordered.isUndef())) return zv::Val();
				if (reordered.isNull()) reordered = zv::Val::copyOf(zv::Ref(expr));
				argumentsWalkedAhead = processArgs(slot(slots::argumentsHandler), nodeScopeResolver, stmt, &null, shallowVariants.raw(), &null, reordered.raw(), scope.raw(), storage, nodeCallback, context.raw());
				if (UNEXPECTED(argumentsWalkedAhead.isUndef())) return zv::Val();
				zv::Val argsScopeHold;
				zval *argsScope = argsResultScope(argumentsWalkedAhead.raw(), argsScopeHold);
				if (UNEXPECTED(argsScope == NULL)) return zv::Val();
				scope = zv::Val::copyOf(zv::Ref(argsScope));
			}
		}
		if (nameIsExpr) {
			// process the dynamic callee name first, then consume its type (single-pass
			// inside-out) rather than reading it before processExprNode() stores it
			zv::Val deepContext = pt_expression_context_enter_deep(context.raw());
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, scope.raw(), storage, nodeCallback, deepContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			zv::Val nameType = pt_expression_result_get_type(nameResult.raw());
			if (UNEXPECTED(nameType.isUndef())) return zv::Val();
			zend_long isCallable = typeOpTrinary(nameType.raw(), PT_OP_IS_CALLABLE, "isCallable");
			if (UNEXPECTED(isCallable < 0)) return zv::Val();
			if (isCallable != PT_TRI_NO) {
				variants = typeCall(nameType.raw(), PT_LC("getcallableparametersacceptors"), "getCallableParametersAcceptors", 1, scope.raw());
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				// A structural acceptor (names/positions/variadic) drives the per-arg
				// metadata and the throw/impure points - generics are resolved
				// type-driven by processArgs() into $resolvedParametersAcceptor.
				zv::Val args = callArgs(expr);
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				zval null;
				ZVAL_NULL(&null);
				parametersAcceptor = ptcall::combineVariantsForNormalization(args.raw(), variants.raw(), &null);
				if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
			}

			zv::Val hold;
			zval *nameScope = pt_expression_result_scope(nameResult.raw(), hold);
			if (UNEXPECTED(nameScope == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(nameScope));
			if (UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), hasYield))) return zv::Val();
			zval *nameThrowPoints = pt_expression_result_throw_points(nameResult.raw(), hold);
			if (UNEXPECTED(nameThrowPoints == NULL)) return zv::Val();
			throwPoints = zv::Val::copyOf(zv::Ref(nameThrowPoints));
			zval *nameImpurePoints = pt_expression_result_impure_points(nameResult.raw(), hold);
			if (UNEXPECTED(nameImpurePoints == NULL)) return zv::Val();
			impurePoints = zv::Val::copyOf(zv::Ref(nameImpurePoints));
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();

			bool invokable;
			if (UNEXPECTED(!isInvokableObject(nameType.raw(), invokable))) return zv::Val();
			if (invokable) {
				// processed later
			} else if (isA(parametersAcceptor.raw(), PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR)) {
				if (UNEXPECTED(!applyCallableAcceptor(expr, parametersAcceptor.raw(), scope, throwPoints, impurePoints))) return zv::Val();
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			bool has;
			if (UNEXPECTED(!hasFunction(slot(slots::reflectionProvider), name, scope.raw(), has))) return zv::Val();
			if (has) {
				functionReflection = getFunction(slot(slots::reflectionProvider), name, scope.raw());
				if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();
				zv::Val hold;
				zval *functionVariants = pt_function_reflection_variants(functionReflection.raw(), hold);
				if (UNEXPECTED(functionVariants == NULL)) return zv::Val();
				variants = zv::Val::copyOf(zv::Ref(functionVariants));
				zval *named = pt_function_reflection_named_arguments_variants(functionReflection.raw(), hold);
				if (UNEXPECTED(named == NULL)) return zv::Val();
				namedArgumentsVariants = zv::Val::copyOf(zv::Ref(named));
				// A structural acceptor (names/positions/variadic) drives argument
				// normalization, the impure point and the throw points - generics are
				// resolved type-driven by processArgs() into $resolvedParametersAcceptor.
				zv::Val args = callArgs(expr);
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				parametersAcceptor = ptcall::combineVariantsForNormalization(args.raw(), variants.raw(), namedArgumentsVariants.raw());
				if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
			} else {
				zv::Val impurePoint = pt_impure_point_new(scope.raw(), expr, pt_fch_function_call_identifier, pt_fch_unknown_function_description, false);
				if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
				ptcall::appendTo(impurePoints, std::move(impurePoint));
			}
		}

		zv::Val normalizedExpr = zv::Val::copyOf(zv::Ref(expr));
		if (!parametersAcceptor.isNull()) {
			zv::Val reordered = ptcall::reorderFuncArguments(parametersAcceptor.raw(), expr);
			if (UNEXPECTED(reordered.isUndef())) return zv::Val();
			if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			zv::Val hold;
			zval *returnType = pt_parameters_acceptor_return_type(parametersAcceptor.raw(), hold);
			if (UNEXPECTED(returnType == NULL)) return zv::Val();
			if (!isAlwaysTerminating) {
				bool explicitNever;
				if (UNEXPECTED(!ptcall::isExplicitNever(returnType, explicitNever))) return zv::Val();
				isAlwaysTerminating = explicitNever;
			}
		}

		zval *normalizedName = nodeProp(pt_fch_name_site, normalizedExpr.raw(), PT_LC("name"));
		if (UNEXPECTED(normalizedName == NULL)) return zv::Val();
		zv::Val normalizedNameHold = zv::Val::copyOf(zv::Ref(normalizedName));
		normalizedName = normalizedNameHold.raw();
		if (isA(normalizedName, PT_CLASS_NAME) && !functionReflection.isNull()) {
			bool isClone;
			if (UNEXPECTED(!functionNameIs(functionReflection.raw(), "clone", isClone))) return zv::Val();
			if (isClone) {
				zv::Val args = callArgs(normalizedExpr.raw());
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				if (countOf(args.raw()) == 2 && UNEXPECTED(!processCloneWith(nodeScopeResolver, stmt, normalizedExpr.raw(), scope.raw(), storage, nodeCallback, context.raw()))) return zv::Val();
			}
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		/* the by-reference `$arrayWalkValueTypes` the gatherer writes, created
		 * with the gatherer like the twin's `use (&...)` creates it */
		zv::Val arrayWalkValueTypesRef;
		zv::Val arrayWalkArrayArg = zv::Val::null();
		zv::Val argsGatherer = zv::Val::null();
		if (!functionReflection.isNull()) {
			bool isArrayWalk;
			if (UNEXPECTED(!functionNameIs(functionReflection.raw(), "array_walk", isArrayWalk))) return zv::Val();
			if (isArrayWalk) {
				zv::Val args = callArgs(normalizedExpr.raw());
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				if (countOf(args.raw()) >= 2) {
					zv::Val gatherer = arrayWalkGatherer(normalizedExpr.raw(), arrayWalkValueTypesRef, arrayWalkArrayArg);
					if (UNEXPECTED(gatherer.isUndef())) return zv::Val();
					argsGatherer = std::move(gatherer);
				}
			}
		}

		zval *scopeBeforeArgs = scope.raw();
		zv::Val scopeBeforeArgsHold = zv::Val::copyOf(zv::Ref(scopeBeforeArgs));
		scopeBeforeArgs = scopeBeforeArgsHold.raw();
		if (!parametersAcceptor.isNull()) {
			zv::Val inAssignRightSideExpr = pt_expression_context_get_in_assign_right_side_expr(context.raw());
			if (UNEXPECTED(inAssignRightSideExpr.isUndef())) return zv::Val();
			if (Z_TYPE_P(inAssignRightSideExpr.raw()) == IS_OBJECT && Z_OBJ_P(inAssignRightSideExpr.raw()) == Z_OBJ_P(expr)) {
				zv::Val entered = pt_expression_context_enter_assign_right_side_call_args(context.raw(), parametersAcceptor.raw());
				if (UNEXPECTED(entered.isUndef())) return zv::Val();
				context = std::move(entered);
			}
		}
		bool hasGatherer = !argsGatherer.isNull();
		if (hasGatherer && UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, argsGatherer.raw()))) return zv::Val();
		zv::Val argsResult;
		if (!argumentsWalkedAhead.isNull()) {
			// the arguments are processed; the call resolves from the walked
			// closure's acceptor, which is the sole variant of its type
			argsResult = pt_args_result_with_resolved_parameters_acceptor(argumentsWalkedAhead.raw(), parametersAcceptor.raw());
		} else {
			argsResult = processArgs(slot(slots::argumentsHandler), nodeScopeResolver, stmt, functionReflection.raw(), variants.raw(), namedArgumentsVariants.raw(), normalizedExpr.raw(), scope.raw(), storage, nodeCallback, context.raw());
		}
		if (hasGatherer) {
			pt_finally([&]() {
				(void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver);
			});
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zv::Val hold;
		zval *resolvedParametersAcceptorSlot = pt_args_result_resolved_parameters_acceptor(argsResult.raw(), hold);
		if (UNEXPECTED(resolvedParametersAcceptorSlot == NULL)) return zv::Val();
		zv::Val resolvedParametersAcceptor = zv::Val::copyOf(zv::Ref(resolvedParametersAcceptorSlot));
		// arguments walked ahead of the callee: the scope already carries them
		// and the callee walk's own effects (a closure's by-ref uses) on top
		if (argumentsWalkedAhead.isNull()) {
			zval *argsScope = argsResultScope(argsResult.raw(), hold);
			if (UNEXPECTED(argsScope == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(argsScope));
		}
		if (UNEXPECTED(!processDroppedArgs(slot(slots::argumentsHandler), nodeScopeResolver, stmt, expr, normalizedExpr.raw(), scope.raw(), storage, context.raw()))) return zv::Val();
		if (!hasYield && UNEXPECTED(!argsResultBool(argsResult.raw(), true, hasYield))) return zv::Val();
		zval *argsThrowPoints = argsResultPoints(argsResult.raw(), true, hold);
		if (UNEXPECTED(argsThrowPoints == NULL || !arrayMerge(throwPoints, argsThrowPoints))) return zv::Val();
		zval *argsImpurePoints = argsResultPoints(argsResult.raw(), false, hold);
		if (UNEXPECTED(argsImpurePoints == NULL || !arrayMerge(impurePoints, argsImpurePoints))) return zv::Val();
		if (!isAlwaysTerminating && UNEXPECTED(!argsResultBool(argsResult.raw(), false, isAlwaysTerminating))) return zv::Val();

		if (!functionReflection.isNull()) {
			// created after the args were processed - the side-effect flip
			// parameters (print_r's $return, ...) read an argument's type, which
			// is only available once its result is stored
			zv::Val args = callArgs(expr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			pt_simple_impure_point_data impurePoint;
			if (UNEXPECTED(!pt_simple_impure_point_resolve(functionReflection.raw(), parametersAcceptor.raw(), scope.raw(), args.raw(), impurePoint))) return zv::Val();
			if (impurePoint.exists) {
				zv::Val converted = pt_impure_point_new(scopeBeforeArgs, expr, impurePoint.identifier, impurePoint.description, impurePoint.certain);
				zend_string_release(impurePoint.description);
				if (UNEXPECTED(converted.isUndef())) return zv::Val();
				ptcall::appendTo(impurePoints, std::move(converted));
			}
		}

		zval *arrayWalkValueTypesValue = arrayWalkValueTypesRef.isUndef() ? &EG(uninitialized_zval) : Z_REFVAL_P(arrayWalkValueTypesRef.raw());
		if (Z_TYPE_P(arrayWalkValueTypesValue) != IS_NULL && !arrayWalkArrayArg.isNull()) {
			if (UNEXPECTED(Z_TYPE_P(arrayWalkValueTypesValue) != IS_ARRAY)) {
				zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\FuncCallScopeEffectsHelper::applyArrayWalkResult(): Argument #4 ($arrayWalkValueTypes) must be of type array, %s given", zend_zval_value_name(arrayWalkValueTypesValue));
				return zv::Val();
			}
			zv::Val walkTypes = zv::Val::copyOf(zv::Ref(arrayWalkValueTypesValue));
			zv::Val walked = pt_func_call_scope_effects_helper_apply_array_walk_result(slot(slots::scopeEffectsHelper), nodeScopeResolver, stmt, arrayWalkArrayArg.raw(), walkTypes.raw(), argsResult.raw(), scope.raw(), storage, nodeCallback);
			if (UNEXPECTED(walked.isUndef())) return zv::Val();
			scope = std::move(walked);
		}

		// The return type is derived from $resolvedParametersAcceptor - the acceptor
		// processArgs() selected from the arg types gathered on the arg-to-arg
		// evolving scope (type-driven, generics resolved). When null
		// (native-types-promoted, on-demand / synthetic pricing, or special cases
		// inside resolveReturnType), the acceptor is re-derived from the
		// already-processed argument results on the asking scope.
		zv::Val storageRef = weakReferenceCreate(storage);
		if (UNEXPECTED(storageRef.isUndef())) return zv::Val();
		zv::Val typeCallback = isEarlyTerminating
			? pt_native_closure(&neverTypeCallbackBody)
			: pt_native_closure(&typeCallbackBody, self, nodeScopeResolver, beforeScope, expr, nameResult.raw(), resolvedParametersAcceptor.raw(), argsResult.raw(), storageRef.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, beforeScope, expr, normalizedExpr.raw(), nameResult.raw(), resolvedParametersAcceptor.raw(), argsResult.raw());

		// A type constraint on a (narrowable, i.e. non-side-effecting, non-first-class)
		// function call narrows the call itself - the inside-out equivalent of
		// createForExpr's FuncCall purity gate + tail entry. An impure call narrows to
		// nothing.
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, normalizedExpr.raw(), nameResult.raw(), beforeScope, argsResult.raw());

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw-point return type is computed: getFunctionThrowPoint() resolves the
		// return type through the typeCallback, whose type-check verdict reads this
		// very result's narrowing, and dynamic return type extensions may ask about
		// the call too. Without a stored result those asks would re-process this
		// FuncCall on demand and recurse back into getFunctionThrowPoint(). The
		// callbacks are scope-independent, so the preliminary result answers those
		// asks correctly; finalize() below completes it with the resolved scope and
		// throw/impure points.
		pt_expression_result_args resultArgs(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		resultArgs.withCreateTypesCallback(createTypesCallback.raw()).withArgsResult(argsResult.raw());
		zv::Val preliminaryResult = pt_expression_result_create(slot(slots::expressionResultFactory), resultArgs);
		if (UNEXPECTED(preliminaryResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, preliminaryResult.raw()))) return zv::Val();

		if (isA(normalizedName, PT_CLASS_EXPR)) {
			zv::Val stored = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, normalizedName, storage);
			if (UNEXPECTED(stored.isUndef())) return zv::Val();
			if (UNEXPECTED(!stored.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(stored.raw()));
				return zv::Val();
			}
			zv::Val nameType = pt_expression_result_get_type_on_scope(stored.raw(), scope.raw(), false);
			if (UNEXPECTED(nameType.isUndef())) return zv::Val();
			bool invokable;
			if (UNEXPECTED(!isInvokableObject(nameType.raw(), invokable))) return zv::Val();
			if (invokable && UNEXPECTED(!processInvoke(nodeScopeResolver, stmt, normalizedExpr.raw(), normalizedName, scope.raw(), storage, context.raw(), throwPoints, impurePoints, isAlwaysTerminating))) return zv::Val();
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		if (!functionReflection.isNull()) {
			// The call's return type, computed from the already-processed argument
			// results (resolveReturnType reads them from the stored results,
			// never re-running processArgs) - asking Scope::getType() for the
			// FuncCall here would re-enter this handler on demand, as its result is
			// not stored yet.
			// Resolve it through the stored preliminary result so the memoized
			// value seeds the final result below - the first later type read
			// would otherwise run resolveReturnType() again.
			zv::Val returnType = pt_expression_result_get_keep_void_type(preliminaryResult.raw(), false);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			// The early structural check above (line ~180) only sees the unresolved
			// acceptor return type; a conditional-return never (e.g.
			// `($x is Foo ? never : string)`) only resolves to never once the actual
			// argument types are folded in by the type-driven resolved acceptor. Read
			// it from that acceptor's return type, not resolveReturnType(), which
			// folds in call_user_func()/dynamic-extension special cases that must not
			// make the call itself always-terminating (e.g.
			// `call_user_func(fn() => exit())`).
			if (!resolvedParametersAcceptor.isNull()) {
				zv::Val returnTypeHold;
				zval *resolvedReturnType = pt_parameters_acceptor_return_type(resolvedParametersAcceptor.raw(), returnTypeHold);
				if (UNEXPECTED(resolvedReturnType == NULL)) return zv::Val();
				if (!isAlwaysTerminating) {
					bool explicitNever;
					if (UNEXPECTED(!ptcall::isExplicitNever(resolvedReturnType, explicitNever))) return zv::Val();
					isAlwaysTerminating = explicitNever;
				}
			}
			zv::Val functionThrowPoint = getFunctionThrowPoint(functionReflection.raw(), parametersAcceptor.isNull() ? NULL : parametersAcceptor.raw(), returnType.raw(), normalizedExpr.raw(), scope.raw(), context.raw());
			if (UNEXPECTED(functionThrowPoint.isUndef())) return zv::Val();
			if (!functionThrowPoint.isNull()) {
				ptcall::appendTo(throwPoints, std::move(functionThrowPoint));
			}
		} else {
			zv::Val throwPoint = pt_internal_throw_point_create_implicit(scope.raw(), expr);
			if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
			ptcall::appendTo(throwPoints, std::move(throwPoint));
		}

		zv::Val effects = pt_func_call_scope_effects_helper_apply_call_scope_effects(slot(slots::scopeEffectsHelper), nodeScopeResolver, stmt, normalizedExpr.raw(), functionReflection.raw(), parametersAcceptor.raw(), argsResult.raw(), scope.raw(), scopeBeforeArgs, storage, nodeCallback);
		if (UNEXPECTED(effects.isUndef())) return zv::Val();
		scope = std::move(effects);

		zval flows[5];
		zv::Val nameFlow = zv::Val::null();
		if (!nameResult.isNull()) {
			nameFlow = pt_expression_result_variable_flow(nameResult.raw());
			if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
		}
		zv::Val argumentsFlow = pt_variable_flow_builder_arguments(expr, argsResult.raw(), storage);
		if (UNEXPECTED(argumentsFlow.isUndef())) return zv::Val();
		zv::Val callFlow;
		if (!functionReflection.isNull()) {
			zv::Val nameHold2;
			zend_string *functionNameString = functionName(functionReflection.raw(), nameHold2);
			if (UNEXPECTED(functionNameString == NULL)) return zv::Val();
			callFlow = getCallVariableFlow(functionNameString, normalizedExpr.raw(), argsResult.raw(), scope.raw());
		} else {
			callFlow = getCallVariableFlow(NULL, normalizedExpr.raw(), argsResult.raw(), scope.raw());
		}
		if (UNEXPECTED(callFlow.isUndef())) return zv::Val();
		zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
		if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
		zv::Val exitFlow = zv::Val::null();
		if (isAlwaysTerminating) {
			exitFlow = pt_variable_flow_exit_stop();
			if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
		}
		ZVAL_COPY_VALUE(&flows[0], nameFlow.raw());
		ZVAL_COPY_VALUE(&flows[1], argumentsFlow.raw());
		ZVAL_COPY_VALUE(&flows[2], callFlow.raw());
		ZVAL_COPY_VALUE(&flows[3], throwsFlow.raw());
		ZVAL_COPY_VALUE(&flows[4], exitFlow.raw());
		zv::Val variableFlow = pt_variable_flow_sequence(5, flows);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();

		return pt_expression_result_finalize(preliminaryResult.raw(), scope.raw(), hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), variableFlow.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return FuncCallHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	bool flag(uint32_t index) const { return Z_TYPE_P(slot(index)) == IS_TRUE; }

	/* {{{ ArgsResult reads */

	static zval *argsResultScope(zval *argsResult, zv::Val &hold)
	{
		return pt_args_result_scope(argsResult, hold);
	}

	/* hasYield() (yield = true) / isAlwaysTerminating(); false = pending
	 * exception */
	[[nodiscard]] static bool argsResultBool(zval *argsResult, bool yield, bool &out)
	{
		return yield ? pt_args_result_has_yield(argsResult, out) : pt_args_result_is_always_terminating(argsResult, out);
	}

	/* getThrowPoints() (throws = true) / getImpurePoints() */
	static zval *argsResultPoints(zval *argsResult, bool throws, zv::Val &hold)
	{
		return throws ? pt_args_result_throw_points(argsResult, hold) : pt_args_result_impure_points(argsResult, hold);
	}

	/* }}} */

	/* new ImpurePoint($scope, $expr, $impurePoint->getIdentifier(),
	 * $impurePoint->getDescription(), $impurePoint->isCertain()) of a
	 * SimpleImpurePoint (its slots, AnalyserValues.h) */
	static zv::Val impurePointOf(zval *scope, zval *expr, zval *simpleImpurePoint)
	{
		if (UNEXPECTED(Z_TYPE_P(simpleImpurePoint) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getIdentifier() on %s", zend_zval_value_name(simpleImpurePoint));
			return zv::Val();
		}
		zv::Val identifierHold;
		zval *identifier = pt_simple_impure_point_identifier(simpleImpurePoint, identifierHold);
		if (UNEXPECTED(identifier == NULL)) return zv::Val();
		zv::Val descriptionHold;
		zval *description = pt_simple_impure_point_description(simpleImpurePoint, descriptionHold);
		if (UNEXPECTED(description == NULL)) return zv::Val();
		bool certain;
		if (UNEXPECTED(!pt_simple_impure_point_is_certain(simpleImpurePoint, certain))) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(identifier) != IS_STRING || Z_TYPE_P(description) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\ImpurePoint::__construct(): Argument #3 ($identifier) must be of type string, %s given", zend_zval_value_name(identifier));
			return zv::Val();
		}
		return pt_impure_point_new(scope, expr, Z_STR_P(identifier), Z_STR_P(description), certain);
	}

	/* the CallableParametersAcceptor branch of processExpr(): the callable's
	 * throw points and impure points on $scope, then
	 * processImmediatelyCalledCallable(); false = pending exception */
	[[nodiscard]] bool applyCallableAcceptor(zval *expr, zval *parametersAcceptor, zv::Val &scope, zv::Val &throwPoints, zv::Val &impurePoints) const
	{
		zv::Val simpleThrowPoints = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getthrowpoints"), 0, NULL);
		if (UNEXPECTED(simpleThrowPoints.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(simpleThrowPoints.raw()) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(simpleThrowPoints.raw()));
			return false;
		}
		/* array_map() keeps the keys of its one array */
		zv::Arr callableThrowPoints = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(simpleThrowPoints.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(simpleThrowPoints.raw())) {
			zval *simpleThrowPoint = entry.value().deref().raw();
			zv::Val explicitValue = callNoArgs(pt_fch_throw_point_explicit_site, simpleThrowPoint, PT_LC("isexplicit"), "isExplicit");
			if (UNEXPECTED(explicitValue.isUndef())) return false;
			zv::Val throwPoint;
			if (zend_is_true(explicitValue.raw())) {
				zv::Val type = callNoArgs(pt_fch_throw_point_type_site, simpleThrowPoint, PT_LC("gettype"), "getType");
				if (UNEXPECTED(type.isUndef())) return false;
				zv::Val anyThrowable = callNoArgs(pt_fch_throw_point_any_throwable_site, simpleThrowPoint, PT_LC("cancontainanythrowable"), "canContainAnyThrowable");
				if (UNEXPECTED(anyThrowable.isUndef())) return false;
				zv::Val fromThrowExpr = callNoArgs(pt_fch_throw_point_from_throw_expr_site, simpleThrowPoint, PT_LC("isfromthrowexpr"), "isFromThrowExpr");
				if (UNEXPECTED(fromThrowExpr.isUndef())) return false;
				throwPoint = pt_internal_throw_point_create_explicit(scope.raw(), type.raw(), expr, zend_is_true(anyThrowable.raw()), zend_is_true(fromThrowExpr.raw()));
			} else {
				throwPoint = pt_internal_throw_point_create_implicit(scope.raw(), expr);
			}
			if (UNEXPECTED(throwPoint.isUndef())) return false;
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				callableThrowPoints.set(key, std::move(throwPoint));
			} else {
				callableThrowPoints.separate();
				zval value = throwPoint.take();
				zend_hash_index_update(callableThrowPoints.table(), entry.indexKey(), &value);
			}
		}
		zv::Val callableThrowPointsHold = zv::Val(std::move(callableThrowPoints));
		if (!flag(slots::implicitThrows)) {
			/* array_values(array_filter($callableThrowPoints, static fn (InternalThrowPoint $throwPoint) => $throwPoint->isExplicit())) */
			zv::Arr explicitOnly = zv::Arr::create(countOf(callableThrowPointsHold.raw()));
			for (zv::ArrayEntry entry : zv::ArrRef(callableThrowPointsHold.raw())) {
				bool isExplicit;
				if (UNEXPECTED(!pt_internal_throw_point_is_explicit(entry.value().raw(), isExplicit))) return false;
				if (isExplicit) explicitOnly.push(entry.value());
			}
			callableThrowPointsHold = zv::Val(std::move(explicitOnly));
		}
		if (UNEXPECTED(!arrayMerge(throwPoints, callableThrowPointsHold.raw()))) return false;

		zv::Val simpleImpurePoints = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getimpurepoints"), 0, NULL);
		if (UNEXPECTED(simpleImpurePoints.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(simpleImpurePoints.raw()) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(simpleImpurePoints.raw()));
			return false;
		}
		zv::Arr callableImpurePoints = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(simpleImpurePoints.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(simpleImpurePoints.raw())) {
			zv::Val impurePoint = impurePointOf(scope.raw(), expr, entry.value().deref().raw());
			if (UNEXPECTED(impurePoint.isUndef())) return false;
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				callableImpurePoints.set(key, std::move(impurePoint));
			} else {
				callableImpurePoints.separate();
				zval value = impurePoint.take();
				zend_hash_index_update(callableImpurePoints.table(), entry.indexKey(), &value);
			}
		}
		zv::Val callableImpurePointsHold = zv::Val(std::move(callableImpurePoints));
		if (UNEXPECTED(!arrayMerge(impurePoints, callableImpurePointsHold.raw()))) return false;

		zv::Val invalidateExpressions = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getinvalidateexpressions"), 0, NULL);
		if (UNEXPECTED(invalidateExpressions.isUndef())) return false;
		zv::Val usedVariables = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getusedvariables"), 0, NULL);
		if (UNEXPECTED(usedVariables.isUndef())) return false;
		zv::Val next = processImmediatelyCalledCallable(slot(slots::closureProcessor), scope.raw(), invalidateExpressions.raw(), usedVariables.raw());
		if (UNEXPECTED(next.isUndef())) return false;
		scope = std::move(next);
		return true;
	}

	/* the clone-with branch of processExpr(); false = pending exception */
	[[nodiscard]] bool processCloneWith(zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		// process the clone arguments as reads so the cloned object and the
		// properties array resolve from stored results instead of unprocessed
		// nodes; processArgs() below processes them again as clone()'s arguments,
		// so the NoopNodeCallback here avoids duplicate node-callbacks.
		zv::Val objectResult = processArgRead(nodeScopeResolver, stmt, normalizedExpr, 0, scope, storage, context);
		if (UNEXPECTED(objectResult.isUndef())) return false;
		zv::Val propertiesResult = processArgRead(nodeScopeResolver, stmt, normalizedExpr, 1, scope, storage, context);
		if (UNEXPECTED(propertiesResult.isUndef())) return false;
		zv::Val propertiesType = pt_expression_result_get_type(propertiesResult.raw());
		if (UNEXPECTED(propertiesType.isUndef())) return false;
		// the cloned type is composed from the object argument's result -
		// no synthetic Clone_ walk
		zv::Val objectType = pt_expression_result_get_type(objectResult.raw());
		if (UNEXPECTED(objectType.isUndef())) return false;
		zv::Val clonedType = resolveCloneType(objectType.raw());
		if (UNEXPECTED(clonedType.isUndef())) return false;
		zv::Val cloneExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, clonedType.raw());
		if (UNEXPECTED(cloneExpr.isUndef())) return false;
		zv::Val constantArrays = typeOp(propertiesType.raw(), PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
		if (UNEXPECTED(constantArrays.isUndef())) return false;
		if (Z_TYPE_P(constantArrays.raw()) != IS_ARRAY) return true;
		for (zv::ArrayEntry arrayEntry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = arrayEntry.value().deref().raw();
			zv::Val keyTypes = typeOp(constantArray, PT_OP_GET_KEY_TYPES, "getKeyTypes");
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			if (Z_TYPE_P(keyTypes.raw()) != IS_ARRAY) continue;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = keyEntry.value().deref().raw();
				zval i;
				if (keyEntry.hasStringKey()) {
					ZVAL_STR(&i, keyEntry.stringKey());
				} else {
					ZVAL_LONG(&i, (zend_long) keyEntry.indexKey());
				}
				zv::Val scalars = typeOp(keyType, PT_OP_GET_CONSTANT_SCALAR_VALUES, "getConstantScalarValues");
				if (UNEXPECTED(scalars.isUndef())) return false;
				zval *attributes = nodeProp(pt_fch_attributes_site, normalizedExpr, PT_LC("attributes"));
				if (UNEXPECTED(attributes == NULL)) return false;
				zv::Val propertyAttributes = zv::Val::copyOf(zv::Ref(attributes));
				if (Z_TYPE_P(propertyAttributes.raw()) == IS_ARRAY) {
					zval *raw = propertyAttributes.raw();
					SEPARATE_ARRAY(raw);
					zval flagValue;
					ZVAL_TRUE(&flagValue);
					zend_hash_update(Z_ARRVAL_P(raw), pt_fch_in_clone_with, &flagValue);
				}

				zv::Val propertyName;
				if (countOf(scalars.raw()) == 1) {
					zval *scalar = arrayIndex(scalars.raw(), 0);
					if (UNEXPECTED(scalar == NULL)) return false;
					zend_string *scalarString = zval_try_get_string(scalar);
					if (UNEXPECTED(scalarString == NULL)) return false;
					propertyName = zv::Val::adoptString(scalarString);
				} else {
					propertyName = pt_type_new(PT_CLASS_TYPE_EXPR, 1, keyType);
					if (UNEXPECTED(propertyName.isUndef())) return false;
				}
				zv::Args fetchArgs{cloneExpr.raw(), propertyName.raw(), propertyAttributes.raw()};
				zv::Val propertyFetch = pt_type_new(PT_CLASS_PROPERTY_FETCH, 3, fetchArgs);
				if (UNEXPECTED(propertyFetch.isUndef())) return false;
				zv::Val valueTypes = typeOp(constantArray, PT_OP_GET_VALUE_TYPES, "getValueTypes");
				if (UNEXPECTED(valueTypes.isUndef())) return false;
				zval *valueType = arrayDim(valueTypes.raw(), &i);
				if (UNEXPECTED(valueType == NULL)) return false;
				zv::Val assignedExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, valueType);
				if (UNEXPECTED(assignedExpr.isUndef())) return false;
				if (UNEXPECTED(!processVirtualAssign(slot(slots::assignHandler), nodeScopeResolver, scope, storage, stmt, propertyFetch.raw(), assignedExpr.raw(), nodeCallback))) return false;
			}
		}
		return true;
	}

	/* $nodeScopeResolver->processExprNode($stmt, $normalizedExpr->getArgs()[$index]->value,
	 * $scope, $storage, new NoopNodeCallback(),
	 * $context->enterDeep()->withoutTemplateArgumentResolution()) */
	static zv::Val processArgRead(zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zend_ulong index, zval *scope, zval *storage, zval *context)
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zval *value = argValueAt(args.raw(), index);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
		zv::Val noop = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
		if (UNEXPECTED(noop.isUndef())) return zv::Val();
		zv::Val deep = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(deep.isUndef())) return zv::Val();
		zv::Val readContext = pt_expression_context_without_template_argument_resolution(deep.raw());
		if (UNEXPECTED(readContext.isUndef())) return zv::Val();
		if (UNEXPECTED(!valueHold.ref().isObject())) {
			zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::processExprNode(): Argument #2 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(valueHold.raw()));
			return zv::Val();
		}
		return pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, valueHold.raw(), scope, storage, noop.raw(), readContext.raw());
	}

	/* the array_walk() branch: the by-reference closure's first parameter,
	 * the array argument and the gatherer (PHP null when the callback does not
	 * qualify); UNDEF = pending exception */
	static zv::Val arrayWalkGatherer(zval *normalizedExpr, zv::Val &arrayWalkValueTypesRef, zv::Val &arrayWalkArrayArg)
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zval *callbackArg = argValueAt(args.raw(), 1);
		if (UNEXPECTED(callbackArg == NULL)) return zv::Val();
		zv::Val callbackArgHold = zv::Val::copyOf(zv::Ref(callbackArg));
		callbackArg = callbackArgHold.raw();

		zval *firstParamName = NULL;
		if (isA(callbackArg, PT_CLASS_CLOSURE_EXPR)) {
			zval *params = nodeProp(pt_fch_params_site, callbackArg, PT_LC("params"));
			if (UNEXPECTED(params == NULL)) return zv::Val();
			zval *firstParam = Z_TYPE_P(params) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(params), 0) : NULL;
			if (firstParam != NULL) {
				ZVAL_DEREF(firstParam);
			}
			if (firstParam != NULL && Z_TYPE_P(firstParam) != IS_NULL) {
				zval *byRef = nodeProp(pt_fch_param_by_ref_site, firstParam, PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				if (zend_is_true(byRef)) {
					zval *var = nodeProp(pt_fch_param_var_site, firstParam, PT_LC("var"));
					if (UNEXPECTED(var == NULL)) return zv::Val();
					if (isA(var, PT_CLASS_VARIABLE)) {
						zval *variableName = nodeProp(pt_fch_variable_name_site, var, PT_LC("name"));
						if (UNEXPECTED(variableName == NULL)) return zv::Val();
						if (Z_TYPE_P(variableName) == IS_STRING) {
							firstParamName = variableName;
						}
					}
				}
			}
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (firstParamName == NULL) return zv::Val::null();

		zv::Val firstParamNameHold = zv::Val::copyOf(zv::Ref(firstParamName));
		zv::Val arrayArgs = callArgs(normalizedExpr);
		if (UNEXPECTED(arrayArgs.isUndef())) return zv::Val();
		zval *arrayArg = argValueAt(arrayArgs.raw(), 0);
		if (UNEXPECTED(arrayArg == NULL)) return zv::Val();
		arrayWalkArrayArg = zv::Val::copyOf(zv::Ref(arrayArg));

		zval reference;
		ZVAL_NEW_REF(&reference, &EG(uninitialized_zval));
		arrayWalkValueTypesRef = zv::Val::adopt(reference);
		zval captures[3];
		ZVAL_COPY_VALUE(&captures[0], callbackArg);
		ZVAL_COPY_VALUE(&captures[1], firstParamNameHold.raw());
		ZVAL_COPY_VALUE(&captures[2], arrayWalkValueTypesRef.raw());
		return pt_native_closure_new(&arrayWalkGathererBody, 3, captures, 1u << 2);
	}

	/* static function (Node $node, Scope $scope) use ($callbackArg,
	 * $firstParamName, &$arrayWalkValueTypes): void — captures: $callbackArg,
	 * $firstParamName, the reference */
	static void arrayWalkGathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *node = &argv[0];
		ZVAL_DEREF(node);
		if (!isA(node, PT_CLASS_CLOSURE_RETURN_STATEMENTS_NODE)) return;
		zv::Val closureExpr = callNoArgs(pt_fch_closure_expr_site, node, PT_LC("getclosureexpr"), "getClosureExpr");
		if (UNEXPECTED(closureExpr.isUndef())) return;
		if (!closureExpr.ref().isObject() || Z_OBJ_P(closureExpr.raw()) != Z_OBJ(captures[0])) return;

		zend_string *firstParamName = Z_STR(captures[1]);
		zv::Val types = zv::Val(zv::Arr::empty());
		zv::Val nativeTypes = zv::Val(zv::Arr::empty());
		zv::Val stmtResult = callNoArgs(pt_fch_statement_result_site, node, PT_LC("getstatementresult"), "getStatementResult");
		if (UNEXPECTED(stmtResult.isUndef())) return;
		zv::Val hold;
		zval *exitPoints = pt_statement_result_exit_points(stmtResult.raw(), hold);
		if (UNEXPECTED(exitPoints == NULL)) return;
		zv::Val exitPointsHold = zv::Val::copyOf(zv::Ref(exitPoints));
		if (Z_TYPE_P(exitPointsHold.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(exitPointsHold.raw())) {
				zval *exitPoint = entry.value().deref().raw();
				zv::Val exitScopeHold;
				zval *exitScope = pt_statement_exit_point_scope(exitPoint, exitScopeHold);
				if (UNEXPECTED(exitScope == NULL)) return;
				zv::Val exitScopeValue = zv::Val::copyOf(zv::Ref(exitScope));
				zend_long has = variableTrinary(exitScopeValue.raw(), firstParamName);
				if (UNEXPECTED(has < 0)) return;
				if (has != PT_TRI_YES) continue;
				if (UNEXPECTED(!collectVariableTypes(exitScopeValue.raw(), firstParamName, types, nativeTypes))) return;
			}
		}
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_statement_result_is_always_terminating(stmtResult.raw(), isAlwaysTerminating))) return;
		if (!isAlwaysTerminating) {
			zval *stmtScope = pt_statement_result_scope(stmtResult.raw(), hold);
			if (UNEXPECTED(stmtScope == NULL)) return;
			zv::Val stmtScopeValue = zv::Val::copyOf(zv::Ref(stmtScope));
			zend_long has = variableTrinary(stmtScopeValue.raw(), firstParamName);
			if (UNEXPECTED(has < 0)) return;
			if (has == PT_TRI_YES && UNEXPECTED(!collectVariableTypes(stmtScopeValue.raw(), firstParamName, types, nativeTypes))) return;
		}
		if (countOf(types.raw()) <= 0) return;

		zv::Val unioned = unionOfList(types.raw());
		if (UNEXPECTED(unioned.isUndef())) return;
		zv::Val nativeUnioned = unionOfList(nativeTypes.raw());
		if (UNEXPECTED(nativeUnioned.isUndef())) return;
		zv::Arr pair = zv::Arr::create(2);
		pair.push(std::move(unioned));
		pair.push(std::move(nativeUnioned));
		zv::Ref(Z_REFVAL(captures[2])).assign(zv::Val(std::move(pair)));
	}

	/* $scope->hasVariableType($name) as PT_TRI_*; -1 = pending exception */
	static zend_long variableTrinary(zval *scope, zend_string *name)
	{
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasVariableType() on %s", zend_zval_value_name(scope));
			return -1;
		}
		zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope), name);
		if (UNEXPECTED(has.isUndef())) return -1;
		return pt_type_trinary_value(has.raw());
	}

	/* $types[] = $scope->getVariableType($name); $nativeTypes[] =
	 * $scope->toWalkScope()->doNotTreatPhpDocTypesAsCertain()->getVariableType($name) */
	[[nodiscard]] static bool collectVariableTypes(zval *scope, zend_string *name, zv::Val &types, zv::Val &nativeTypes)
	{
		zv::Val type = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), name);
		if (UNEXPECTED(type.isUndef())) return false;
		ptcall::appendTo(types, std::move(type));
		zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(walkScope.isUndef())) return false;
		if (UNEXPECTED(!walkScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function doNotTreatPhpDocTypesAsCertain() on %s", zend_zval_value_name(walkScope.raw()));
			return false;
		}
		zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(walkScope.raw()));
		if (UNEXPECTED(nativeScope.isUndef())) return false;
		if (UNEXPECTED(!nativeScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getVariableType() on %s", zend_zval_value_name(nativeScope.raw()));
			return false;
		}
		zv::Val nativeType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), name);
		if (UNEXPECTED(nativeType.isUndef())) return false;
		ptcall::appendTo(nativeTypes, std::move(nativeType));
		return true;
	}

	/* TypeCombinator::union(...$list) of a list built by push */
	static zv::Val unionOfList(zval *list)
	{
		HashTable *table = Z_ARRVAL_P(list);
		if (UNEXPECTED(!HT_IS_PACKED(table) || table->nNumUsed != zend_hash_num_elements(table))) {
			zend_throw_error(NULL, "phpstan_turbo: FuncCallHandler expected a list");
			return zv::Val();
		}
		return pt_type_combinator_union(zend_hash_num_elements(table), table->arPacked);
	}

	/* the __invoke() branch: an invokable object callee walks the synthetic
	 * MethodCall; false = pending exception */
	[[nodiscard]] bool processInvoke(zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *normalizedName, zval *scope, zval *storage, zval *context, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating) const
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return false;
		zval *attributes = nodeProp(pt_fch_attributes_site, normalizedExpr, PT_LC("attributes"));
		if (UNEXPECTED(attributes == NULL)) return false;
		zv::Val attributesHold = zv::Val::copyOf(zv::Ref(attributes));
		zval invoke;
		ZVAL_STR(&invoke, pt_fch_invoke);
		zv::Args callArgv{normalizedName, &invoke, args.raw(), attributesHold.raw()};
		zv::Val methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 4, callArgv);
		if (UNEXPECTED(methodCall.isUndef())) return false;
		zv::Val noop = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
		if (UNEXPECTED(noop.isUndef())) return false;
		zv::Val deep = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(deep.isUndef())) return false;
		zv::Val invokeResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, methodCall.raw(), scope, storage, noop.raw(), deep.raw());
		if (UNEXPECTED(invokeResult.isUndef())) return false;
		zv::Val hold;
		zval *invokeThrowPoints = pt_expression_result_throw_points(invokeResult.raw(), hold);
		if (UNEXPECTED(invokeThrowPoints == NULL || !arrayMerge(throwPoints, invokeThrowPoints))) return false;
		zval *invokeImpurePoints = pt_expression_result_impure_points(invokeResult.raw(), hold);
		if (UNEXPECTED(invokeImpurePoints == NULL || !arrayMerge(impurePoints, invokeImpurePoints))) return false;
		if (isAlwaysTerminating) return true;
		return pt_expression_result_is_always_terminating(invokeResult.raw(), isAlwaysTerminating);
	}

	/* Mirrors getFunctionThrowPoint(); $parametersAcceptor NULL for null; the
	 * throw point or PHP null */
	zv::Val getFunctionThrowPoint(zval *functionReflection, zval *parametersAcceptor, zval *returnType, zval *normalizedFuncCall, zval *scope, zval *context) const
	{
		zval *collection = slot(slots::dynamicFunctionThrowTypeExtensions);
		if (UNEXPECTED(Z_TYPE_P(collection) != IS_OBJECT)) return uninitialized("dynamicFunctionThrowTypeExtensions");
		zv::Val extensions = pt_extensions_collection_get_all(Z_OBJ_P(collection));
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (EXPECTED(Z_TYPE_P(extensions.raw()) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
				zval *extension = entry.value().deref().raw();
				zv::Val supported = callPoly(pt_fch_throw_supported_site, extension, PT_LC("isfunctionsupported"), "isFunctionSupported", 1, functionReflection);
				if (UNEXPECTED(supported.isUndef())) return zv::Val();
				if (!zend_is_true(supported.raw())) continue;

				zv::Args argv{functionReflection, normalizedFuncCall, scope};
				zv::Val throwType = callPoly(pt_fch_throw_type_site, extension, PT_LC("getthrowtypefromfunctioncall"), "getThrowTypeFromFunctionCall", 3, argv);
				if (UNEXPECTED(throwType.isUndef())) return zv::Val();
				if (throwType.isNull()) return zv::Val::null();
				if (UNEXPECTED(!throwType.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
					return zv::Val();
				}
				zend_long throwTypeIsVoid = pt_type_op_trinary(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
				if (UNEXPECTED(throwTypeIsVoid < 0)) return zv::Val();
				if (throwTypeIsVoid == PT_TRI_YES) return zv::Val::null();

				return pt_internal_throw_point_create_explicit(scope, throwType.raw(), normalizedFuncCall, false, false);
			}
		}

		zv::Val throwTypeHold;
		zval *throwTypeSlot = pt_function_reflection_throw_type(functionReflection, throwTypeHold);
		if (UNEXPECTED(throwTypeSlot == NULL)) return zv::Val();
		zv::Val throwType = zv::Val::copyOf(zv::Ref(throwTypeSlot));
		if (!throwType.isNull() && parametersAcceptor != NULL) {
			zv::Val argsHold;
			zval *callArgs = pt_call_like_args(Z_OBJ_P(normalizedFuncCall), argsHold);
			if (UNEXPECTED(callArgs == NULL)) return zv::Val();
			throwType = pt_conditional_type_resolver_resolve_for_call(throwType.raw(), parametersAcceptor, callArgs, scope);
			if (UNEXPECTED(throwType.isUndef())) return zv::Val();
		}
		if (throwType.isNull()) {
			bool explicitNever;
			if (UNEXPECTED(!ptcall::isExplicitNever(returnType, explicitNever))) return zv::Val();
			if (explicitNever) {
				zval throwable;
				if (UNEXPECTED(!pt_object_type_new(&throwable, pt_fch_throwable_name))) return zv::Val();
				throwType = zv::Val::adopt(throwable);
			}
		}

		if (!throwType.isNull()) {
			zend_long isVoid = typeOpTrinary(throwType.raw(), PT_OP_IS_VOID, "isVoid");
			if (UNEXPECTED(isVoid < 0)) return zv::Val();
			if (isVoid != PT_TRI_YES) return pt_internal_throw_point_create_explicit(scope, throwType.raw(), normalizedFuncCall, true, false);
		} else if (flag(slots::implicitThrows)) {
			bool hasRequiredParameters = false;
			zend_long requiredParameters = 0;
			if (parametersAcceptor != NULL) {
				hasRequiredParameters = true;
				zv::Val parametersHold;
				zval *parameters = pt_parameters_acceptor_parameters(parametersAcceptor, parametersHold);
				if (UNEXPECTED(parameters == NULL)) return zv::Val();
				zv::Val parametersValue = zv::Val::copyOf(zv::Ref(parameters));
				if (Z_TYPE_P(parametersValue.raw()) == IS_ARRAY) {
					for (zv::ArrayEntry entry : zv::ArrRef(parametersValue.raw())) {
						bool optional;
						if (UNEXPECTED(!pt_parameter_reflection_is_optional(entry.value().deref().raw(), optional))) return zv::Val();
						if (optional) continue;
						requiredParameters++;
					}
				}
			}
			bool isBuiltin;
			if (UNEXPECTED(!pt_function_reflection_is_builtin(functionReflection, isBuiltin))) return zv::Val();
			bool mayThrow = !isBuiltin || !hasRequiredParameters || requiredParameters > 0;
			if (!mayThrow) {
				zv::Val args = callArgs(normalizedFuncCall);
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				mayThrow = countOf(args.raw()) > 0;
			}
			if (mayThrow) {
				bool inThrow;
				if (UNEXPECTED(!pt_expression_context_is_in_throw(context, inThrow))) return zv::Val();
				if (!inThrow) return pt_internal_throw_point_create_implicit(scope, normalizedFuncCall);
				zend_long returnsThrowable = objectTypeIsSuperTypeOf(pt_fch_throwable_name, returnType);
				if (UNEXPECTED(returnsThrowable < 0)) return zv::Val();
				if (returnsThrowable != PT_TRI_YES) return pt_internal_throw_point_create_implicit(scope, normalizedFuncCall);
			}
		}

		return zv::Val::null();
	}

	/* the engine's Error for reading a never-written typed property */
	zv::Val uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return zv::Val();
	}

	/* static fn (bool $nativeTypesPromoted): Type => new NeverType(true) */
	static void neverTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argv;
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		if (UNEXPECTED(!pt_never_type_new(return_value, true))) {
			ZVAL_NULL(return_value);
		}
	}

	/* function (bool $nativeTypesPromoted) use ($nodeScopeResolver,
	 * $beforeScope, $expr, $nameResult, $resolvedParametersAcceptor,
	 * $argsResult, $storageRef): Type — captures: $this, then those */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zv::Val type = FuncCallHandler(Z_OBJ(captures[0])).resolveCallbackType(captures, zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	zv::Val resolveCallbackType(zval *captures, bool nativeTypesPromoted) const
	{
		zval *nodeScopeResolver = &captures[1];
		zval *beforeScope = &captures[2];
		zval *expr = &captures[3];
		zval *nameResult = &captures[4];
		zval *resolvedParametersAcceptor = &captures[5];
		zval *argsResult = &captures[6];
		zval *storageRef = &captures[7];

		// for always-true/always-false type checks the call's own narrowing
		// (already produced as this result's specifyTypesCallback) decides
		// the return type - the verdict is a read of that narrowing, not a
		// second derivation. The result is looked up through a weak storage
		// reference: this callback is owned by that very result, and a
		// strong backedge would be a cycle (PHPStan runs with gc_disable()).
		if (!nativeTypesPromoted) {
			zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (isA(name, PT_CLASS_NAME)) {
				zv::Val nameHold;
				zend_string *nameString = nameToString(name, nameHold);
				if (UNEXPECTED(nameString == NULL)) return zv::Val();
				if (zend_string_equals_literal_ci(nameString, "array_key_exists")
					|| zend_string_equals_literal_ci(nameString, "key_exists")
					|| zend_string_equals_literal_ci(nameString, "in_array")
					|| zend_string_equals_literal_ci(nameString, "is_subclass_of")) {
					zv::Val callStorage = weakReferenceGet(storageRef);
					if (UNEXPECTED(callStorage.isUndef())) return zv::Val();
					zv::Val callResult = zv::Val::null();
					if (!callStorage.isNull()) {
						callResult = pt_expression_result_storage_find(callStorage.raw(), expr);
						if (UNEXPECTED(callResult.isUndef())) return zv::Val();
					}
					if (!callResult.isNull()) {
						zv::Val isAlways = findSpecifiedType(slot(slots::impossibleCheckTypeHelper), beforeScope, expr, callResult.raw(), argsResult);
						if (UNEXPECTED(isAlways.isUndef())) return zv::Val();
						if (!isAlways.isNull()) {
							zval constant;
							if (UNEXPECTED(!pt_constant_boolean_type_new(&constant, zend_is_true(isAlways.raw())))) return zv::Val();
							return zv::Val::adopt(constant);
						}
					}
				}
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}

		return resolveReturnType(nodeScopeResolver, beforeScope, nativeTypesPromoted, expr, nameResult, nativeTypesPromoted || Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL ? NULL : resolvedParametersAcceptor, argsResult);
	}

	/* the `$getType` closure of resolveReturnType() */
	static zv::Val getTypeOf(zval *e, zval *expr, zval *nameResult, zval *reflectionScope, zval *nodeScopeResolver, zval *argsResult, bool nativeTypesPromoted)
	{
		if (Z_TYPE_P(nameResult) != IS_NULL) {
			zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) == IS_OBJECT && Z_OBJ_P(name) == Z_OBJ_P(e)) {
				return nativeTypesPromoted ? pt_expression_result_get_native_type(nameResult) : pt_expression_result_get_type(nameResult);
			}
		}

		zv::Val findHold;
		zval *argResult = pt_args_result_find_arg_result(argsResult, e, findHold);
		if (UNEXPECTED(argResult == NULL)) return zv::Val();
		if (Z_TYPE_P(argResult) != IS_NULL) {
			zv::Val argResultHold = zv::Val::copyOf(zv::Ref(argResult));
			return nativeTypesPromoted ? pt_expression_result_get_native_type(argResultHold.raw()) : pt_expression_result_get_type(argResultHold.raw());
		}

		// Synthetic nodes (call_user_func's inner FuncCall, clone-with's Clone_)
		// have no captured arg result; they are priced on demand.
		zv::Val s = zv::Val::copyOf(zv::Ref(reflectionScope));
		if (nativeTypesPromoted) {
			s = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(reflectionScope));
			if (UNEXPECTED(s.isUndef())) return zv::Val();
		}
		zv::Val result = pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, e, s.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!result.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getTypeOnScope() on %s", zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(s.raw()), promoted))) return zv::Val();
		return pt_expression_result_get_type_on_scope(result.raw(), s.raw(), promoted);
	}

	/* count($variants) === 1 ? $variants[0] : ParametersAcceptorSelector::combineAcceptors($variants) */
	static zv::Val singleOrCombined(zval *variants)
	{
		if (UNEXPECTED(Z_TYPE_P(variants) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(variants));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(variants)) == 1) {
			zval *first = arrayIndex(variants, 0);
			if (UNEXPECTED(first == NULL)) return zv::Val();
			return zv::Val::copyOf(zv::Ref(first));
		}
		return ptcall::combineAcceptors(variants);
	}

	/* Mirrors resolveReturnType(); $preResolvedAcceptor NULL for null. */
	zv::Val resolveReturnType(zval *nodeScopeResolver, zval *reflectionScope, bool nativeTypesPromoted, zval *expr, zval *nameResult, zval *preResolvedAcceptor, zval *argsResult) const
	{
		zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		name = nameHold.raw();
		if (isA(name, PT_CLASS_EXPR)) {
			zv::Val calledOnType = getTypeOf(name, expr, nameResult, reflectionScope, nodeScopeResolver, argsResult, nativeTypesPromoted);
			if (UNEXPECTED(calledOnType.isUndef())) return zv::Val();
			zend_long isCallable = typeOpTrinary(calledOnType.raw(), PT_OP_IS_CALLABLE, "isCallable");
			if (UNEXPECTED(isCallable < 0)) return zv::Val();
			if (isCallable == PT_TRI_NO) return pt_type_new_error_type();

			zv::Val parametersAcceptor;
			if (preResolvedAcceptor != NULL) {
				parametersAcceptor = zv::Val::copyOf(zv::Ref(preResolvedAcceptor));
			} else {
				zv::Val variants = typeCall(calledOnType.raw(), PT_LC("getcallableparametersacceptors"), "getCallableParametersAcceptors", 1, reflectionScope);
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				parametersAcceptor = singleOrCombined(variants.raw());
				if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
			}

			// the same shortcut the named-function branch below takes: the native
			// signature answers on its own, before any phpdoc-based dynamic
			// return type extension gets a say
			if (nativeTypesPromoted && isA(parametersAcceptor.raw(), PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR)) {
				return ptcall::acceptorNativeReturnType(parametersAcceptor.raw());
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();

			zv::Val functionName = zv::Val::null();
			if (isA(name, PT_CLASS_SCALAR_STRING)) {
				zval *value = nodeProp(pt_fch_string_value_site, name, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				functionName = pt_type_new(PT_CLASS_NAME, 1, value);
				if (UNEXPECTED(functionName.isUndef())) return zv::Val();
			} else if (isA(name, PT_CLASS_FUNC_CALL)) {
				zval *innerName = nodeProp(pt_fch_inner_name_site, name, PT_LC("name"));
				if (UNEXPECTED(innerName == NULL)) return zv::Val();
				if (isA(innerName, PT_CLASS_NAME)) {
					bool firstClassCallable;
					if (UNEXPECTED(!isFirstClassCallable(name, firstClassCallable))) return zv::Val();
					if (firstClassCallable) functionName = zv::Val::copyOf(zv::Ref(innerName));
				}
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();

			zv::Val normalizedNode = ptcall::reorderFuncArguments(parametersAcceptor.raw(), expr);
			if (UNEXPECTED(normalizedNode.isUndef())) return zv::Val();
			if (!normalizedNode.isNull() && !functionName.isNull()) {
				bool has;
				if (UNEXPECTED(!hasFunction(slot(slots::reflectionProvider), functionName.raw(), reflectionScope, has))) return zv::Val();
				if (has) {
					zv::Val functionReflection = getFunction(slot(slots::reflectionProvider), functionName.raw(), reflectionScope);
					if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();
					zv::Val resolvedType = getDynamicFunctionReturnType(reflectionScope, normalizedNode.raw(), functionReflection.raw(), argsResult);
					if (UNEXPECTED(resolvedType.isUndef())) return zv::Val();
					if (!resolvedType.isNull()) return resolvedType;
				}
			}

			return pt_template_argument_frame_return_type_of_call(parametersAcceptor.raw(), reflectionScope, expr);
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		bool has;
		if (UNEXPECTED(!hasFunction(slot(slots::reflectionProvider), name, reflectionScope, has))) return zv::Val();
		if (!has) return pt_type_new_error_type();

		zv::Val functionReflection = getFunction(slot(slots::reflectionProvider), name, reflectionScope);
		if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();
		zv::Val hold;
		if (nativeTypesPromoted) {
			zval *variants = pt_function_reflection_variants(functionReflection.raw(), hold);
			if (UNEXPECTED(variants == NULL)) return zv::Val();
			zv::Val combined = ptcall::combineAcceptors(variants);
			if (UNEXPECTED(combined.isUndef())) return zv::Val();
			return ptcall::acceptorNativeReturnType(combined.raw());
		}

		for (bool arrayVariant : { false, true }) {
			bool isCallUserFunc;
			if (UNEXPECTED(!(arrayVariant ? functionNameIs(functionReflection.raw(), "call_user_func_array", isCallUserFunc) : functionNameIs(functionReflection.raw(), "call_user_func", isCallUserFunc)))) return zv::Val();
			if (!isCallUserFunc) continue;
			zv::Val result = (arrayVariant ? ptcall::reorderCallUserFuncArrayArguments(expr, reflectionScope) : ptcall::reorderCallUserFuncArguments(expr, reflectionScope));
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (!result.isNull()) {
				zval *innerFuncCall = arrayIndex(result.raw(), 1);
				if (UNEXPECTED(innerFuncCall == NULL)) return zv::Val();
				zv::Val innerHold = zv::Val::copyOf(zv::Ref(innerFuncCall));
				if (UNEXPECTED(!innerHold.ref().isObject())) {
					zend_type_error("PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(): Argument #1 ($e) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(innerHold.raw()));
					return zv::Val();
				}
				return getTypeOf(innerHold.raw(), expr, nameResult, reflectionScope, nodeScopeResolver, argsResult, nativeTypesPromoted);
			}
		}

		zv::Val parametersAcceptor;
		if (preResolvedAcceptor != NULL) {
			parametersAcceptor = zv::Val::copyOf(zv::Ref(preResolvedAcceptor));
		} else {
			zval *variants = pt_function_reflection_variants(functionReflection.raw(), hold);
			if (UNEXPECTED(variants == NULL)) return zv::Val();
			zv::Val variantsHold = zv::Val::copyOf(zv::Ref(variants));
			parametersAcceptor = singleOrCombined(variantsHold.raw());
			if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
		}
		zv::Val normalizedNode = ptcall::reorderFuncArguments(parametersAcceptor.raw(), expr);
		if (UNEXPECTED(normalizedNode.isUndef())) return zv::Val();
		if (!normalizedNode.isNull()) {
			bool isClone;
			if (UNEXPECTED(!functionNameIs(functionReflection.raw(), "clone", isClone))) return zv::Val();
			if (isClone) {
				zv::Val args = callArgs(normalizedNode.raw());
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				if (countOf(args.raw()) > 0) {
					return cloneReturnType(normalizedNode.raw(), expr, nameResult, reflectionScope, nodeScopeResolver, argsResult, nativeTypesPromoted);
				}
			}
			zv::Val resolvedType = getDynamicFunctionReturnType(reflectionScope, normalizedNode.raw(), functionReflection.raw(), argsResult);
			if (UNEXPECTED(resolvedType.isUndef())) return zv::Val();
			if (!resolvedType.isNull()) return resolvedType;
		}

		// the typeCallback keeps void; ExpressionResult projects void->null for
		// value reads, getKeepVoidType() keeps it
		return pt_template_argument_frame_return_type_of_call(parametersAcceptor.raw(), reflectionScope, expr);
	}

	/* the clone() branch of resolveReturnType() */
	static zv::Val cloneReturnType(zval *normalizedNode, zval *expr, zval *nameResult, zval *reflectionScope, zval *nodeScopeResolver, zval *argsResult, bool nativeTypesPromoted)
	{
		zv::Val args = callArgs(normalizedNode);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zval *cloned = argValueAt(args.raw(), 0);
		if (UNEXPECTED(cloned == NULL)) return zv::Val();
		zv::Val cloneNode = pt_type_new(PT_CLASS_CLONE_EXPR, 1, cloned);
		if (UNEXPECTED(cloneNode.isUndef())) return zv::Val();
		zv::Val cloneType = getTypeOf(cloneNode.raw(), expr, nameResult, reflectionScope, nodeScopeResolver, argsResult, nativeTypesPromoted);
		if (UNEXPECTED(cloneType.isUndef())) return zv::Val();

		zv::Val countArgs = callArgs(normalizedNode);
		if (UNEXPECTED(countArgs.isUndef())) return zv::Val();
		if (countOf(countArgs.raw()) != 2) return cloneType;

		zv::Val propertiesArgs = callArgs(normalizedNode);
		if (UNEXPECTED(propertiesArgs.isUndef())) return zv::Val();
		zval *properties = argValueAt(propertiesArgs.raw(), 1);
		if (UNEXPECTED(properties == NULL)) return zv::Val();
		zv::Val propertiesHold = zv::Val::copyOf(zv::Ref(properties));
		if (UNEXPECTED(!propertiesHold.ref().isObject())) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(): Argument #1 ($e) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(propertiesHold.raw()));
			return zv::Val();
		}
		zv::Val propertiesType = getTypeOf(propertiesHold.raw(), expr, nameResult, reflectionScope, nodeScopeResolver, argsResult, nativeTypesPromoted);
		if (UNEXPECTED(propertiesType.isUndef())) return zv::Val();
		zend_long isConstantArray = typeOpTrinary(propertiesType.raw(), PT_OP_IS_CONSTANT_ARRAY, "isConstantArray");
		if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
		if (isConstantArray != PT_TRI_YES) return cloneType;
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(propertiesType.raw()), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		if (countOf(constantArrays.raw()) != 1) return cloneType;

		zval *first = arrayIndex(constantArrays.raw(), 0);
		if (UNEXPECTED(first == NULL)) return zv::Val();
		zv::Val keyTypes = typeOp(first, PT_OP_GET_KEY_TYPES, "getKeyTypes");
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		/* the intersect() arguments: $cloneType, then the accessories */
		zv::Arr accessories = zv::Arr::create(countOf(keyTypes.raw()) + 1);
		accessories.push(cloneType.ref());
		if (Z_TYPE_P(keyTypes.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = entry.value().deref().raw();
				zv::Val constantKeyTypes = typeOp(keyType, PT_OP_GET_CONSTANT_SCALAR_VALUES, "getConstantScalarValues");
				if (UNEXPECTED(constantKeyTypes.isUndef())) return zv::Val();
				if (countOf(constantKeyTypes.raw()) != 1) return cloneType;
				zval *key = arrayIndex(constantKeyTypes.raw(), 0);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				zend_string *propertyName = zval_try_get_string(key);
				if (UNEXPECTED(propertyName == NULL)) return zv::Val();
				zval accessory;
				bool created = pt_has_property_type_new(&accessory, propertyName);
				zend_string_release(propertyName);
				if (UNEXPECTED(!created)) return zv::Val();
				accessories.push(zv::Val::adopt(accessory));
			}
		}
		uint32_t accessoryCount = zend_hash_num_elements(accessories.table()) - 1;
		if (accessoryCount > 0 && accessoryCount <= PT_FCH_CLONE_ACCESSORIES_LIMIT) {
			return pt_type_combinator_intersect(accessoryCount + 1, accessories.table()->arPacked);
		}

		return cloneType;
	}

	/* Mirrors specifyTypes(); $argsResult NULL for null. */
	zv::Val specifyTypes(zval *scope, zval *expr, zval *normalizedExpr, zval *nameResult, zval *resolvedParametersAcceptor, zval *context, zval *argsResult) const
	{
		zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		name = nameHold.raw();
		if (isA(name, PT_CLASS_NAME)) {
			bool has;
			if (UNEXPECTED(!hasFunction(slot(slots::reflectionProvider), name, scope, has))) return zv::Val();
			if (has) {
				zv::Val functionReflection = getFunction(slot(slots::reflectionProvider), name, scope);
				if (UNEXPECTED(functionReflection.isUndef())) return zv::Val();
				zv::Val args = callArgs(expr);
				if (UNEXPECTED(args.isUndef())) return zv::Val();

				// runs lazily at narrowing-apply time - prime the storage with the
				// argument results, see MethodCallHandler::specifyTypes()
				zval nullArgsResult;
				ZVAL_NULL(&nullArgsResult);
				pt_primed_storage primed;
				if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(slot(slots::storagePrimer), scope, argsResult != NULL ? argsResult : &nullArgsResult, primed))) return zv::Val();
				zv::Val specified;
				zv::Val extensions = pt_type_specifier_get_function_type_specifying_extensions(slot(slots::typeSpecifier));
				if (EXPECTED(!extensions.isUndef()) && Z_TYPE_P(extensions.raw()) == IS_ARRAY) {
					for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
						zval *extension = entry.value().deref().raw();
						zv::Args supportedArgv{functionReflection.raw(), normalizedExpr, context};
						zv::Val supported = callPoly(pt_fch_specifying_supported_site, extension, PT_LC("isfunctionsupported"), "isFunctionSupported", 3, supportedArgv);
						if (UNEXPECTED(supported.isUndef())) break;
						if (!zend_is_true(supported.raw())) continue;

						zv::Args specifyArgv{functionReflection.raw(), normalizedExpr, scope, context};
						specified = callPoly(pt_fch_specifying_specify_site, extension, PT_LC("specifytypes"), "specifyTypes", 4, specifyArgv);
						break;
					}
				}
				pt_finally([&]() {
					(void) pt_dynamic_return_type_storage_primer_pop(primed);
				});
				if (UNEXPECTED(EG(exception))) return zv::Val();
				if (!specified.isUndef()) return specified;

				if (countOf(args.raw()) > 0 && resolvedParametersAcceptor != NULL) {
					zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_conditional_return_type(slot(slots::defaultNarrowingHelper), context, expr, resolvedParametersAcceptor, scope);
					if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
					if (!specifiedTypes.isNull()) return specifiedTypes;
				}

				zv::Val assertsHold;
				zval *assertions = pt_function_reflection_asserts(functionReflection.raw(), assertsHold);
				if (UNEXPECTED(assertions == NULL)) return zv::Val();
				zv::Val assertionsValue = zv::Val::copyOf(zv::Ref(assertions));
				zv::Val allHold;
				zval *all = pt_assertions_all(assertionsValue.raw(), allHold);
				if (UNEXPECTED(all == NULL)) return zv::Val();
				bool hasAssertions = !(Z_TYPE_P(all) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(all)) == 0);
				if (hasAssertions && resolvedParametersAcceptor != NULL) {
					zv::Val asserts = resolvedAsserts(assertionsValue.raw(), resolvedParametersAcceptor);
					if (UNEXPECTED(asserts.isUndef())) return zv::Val();
					zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_asserts(slot(slots::defaultNarrowingHelper), context, expr, asserts.raw(), resolvedParametersAcceptor, scope);
					if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
					if (!specifiedTypes.isNull()) {
						// the asserts narrow the arguments regardless; the call's OWN
						// key is a remembered value and gets the same purity gate as
						// the default narrowing below - an impure call (realpath())
						// evaluated a second time must not read the first call's
						// truthiness (mirrors the create() gate the old
						// specifyTypesInCondition() reached for the self key)
						bool narrowable;
						if (UNEXPECTED(!isFuncCallNarrowable(scope, expr, nameResult, narrowable))) return zv::Val();
						zv::Val combined;
						if (narrowable) {
							zv::Val defaultTypes = pt_default_narrowing_helper_specify_default_types(slot(slots::defaultNarrowingHelper), expr, context);
							if (UNEXPECTED(defaultTypes.isUndef())) return zv::Val();
							combined = unionWith(specifiedTypes.raw(), defaultTypes.raw());
						} else {
							combined = zv::Val::copyOf(zv::Ref(specifiedTypes.raw()));
						}
						if (UNEXPECTED(combined.isUndef())) return zv::Val();
						zv::Val rootExpr = pt_specified_types_get_root_expr(Z_OBJ_P(specifiedTypes.raw()));
						if (UNEXPECTED(rootExpr.isUndef())) return zv::Val();
						if (UNEXPECTED(!combined.ref().isObject())) {
							zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(combined.raw()));
							return zv::Val();
						}
						return pt_specified_types_set_root_expr(Z_OBJ_P(combined.raw()), rootExpr.raw());
					}
				}
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();

			return defaultFuncCallNarrowing(scope, expr, nameResult, context);
		}

		zv::Val specifiedTypes = specifyTypesFromCallableCall(context, expr, nameResult, resolvedParametersAcceptor, scope);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
		if (!specifiedTypes.isNull()) return specifiedTypes;

		return defaultFuncCallNarrowing(scope, expr, nameResult, context);
	}

	/* $a->unionWith($b) of SpecifiedTypes */
	static zv::Val unionWith(zval *a, zval *b)
	{
		if (EXPECTED(Z_TYPE_P(a) == IS_OBJECT)) return pt_specified_types_union_with(Z_OBJ_P(a), b);
		zend_throw_error(NULL, "Call to a member function unionWith() on %s", zend_zval_value_name(a));
		return zv::Val();
	}

	/* $assertions->mapTypes(static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(...)) */
	static zv::Val resolvedAsserts(zval *assertions, zval *parametersAcceptor)
	{
		if (UNEXPECTED(Z_TYPE_P(assertions) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function mapTypes() on %s", zend_zval_value_name(assertions));
			return zv::Val();
		}
		zv::Val callback = pt_native_closure(&resolveAssertTypeBody, parametersAcceptor);
		return ptcall::assertionsMapTypes(assertions, callback.raw());
	}

	/* static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type,
	 * $acceptor->getResolvedTemplateTypeMap(), ...,
	 * TemplateTypeVariance::createInvariant()) — captures: the acceptor */
	static void resolveAssertTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		ptcall::resolveAssertType(captures, argc, argv, return_value, "PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}");
	}

	/* Mirrors specifyTypesFromCallableCall(); the SpecifiedTypes or PHP null */
	zv::Val specifyTypesFromCallableCall(zval *context, zval *call, zval *nameResult, zval *resolvedParametersAcceptor, zval *scope) const
	{
		zval *name = nodeProp(pt_fch_name_site, call, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (!isA(name, PT_CLASS_EXPR)) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}

		if (Z_TYPE_P(nameResult) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return zv::Val();
		zv::Val calleeType = pt_expression_result_get_type_on_scope(nameResult, scope, promoted);
		if (UNEXPECTED(calleeType.isUndef())) return zv::Val();

		zv::Val assertions = zv::Val::null();
		zv::Val parametersAcceptor = zv::Val::null();
		zend_long isCallable = typeOpTrinary(calleeType.raw(), PT_OP_IS_CALLABLE, "isCallable");
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable == PT_TRI_YES) {
			if (resolvedParametersAcceptor != NULL) {
				parametersAcceptor = zv::Val::copyOf(zv::Ref(resolvedParametersAcceptor));
			} else {
				zv::Val variants = typeCall(calleeType.raw(), PT_LC("getcallableparametersacceptors"), "getCallableParametersAcceptors", 1, scope);
				if (UNEXPECTED(variants.isUndef())) return zv::Val();
				parametersAcceptor = singleOrCombined(variants.raw());
				if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
			}
			if (isA(parametersAcceptor.raw(), PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR)) {
				assertions = typeCall(parametersAcceptor.raw(), PT_LC("getasserts"), "getAsserts", 0, NULL);
				if (UNEXPECTED(assertions.isUndef())) return zv::Val();
			}
			if (UNEXPECTED(EG(exception))) return zv::Val();
		}

		if (assertions.isNull()) return zv::Val::null();
		zv::Val allHold;
		zval *all = pt_assertions_all(assertions.raw(), allHold);
		if (UNEXPECTED(all == NULL)) return zv::Val();
		if (Z_TYPE_P(all) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(all)) == 0) return zv::Val::null();

		zv::Val asserts = resolvedAsserts(assertions.raw(), parametersAcceptor.raw());
		if (UNEXPECTED(asserts.isUndef())) return zv::Val();

		return pt_default_narrowing_helper_specify_types_from_asserts(slot(slots::defaultNarrowingHelper), context, call, asserts.raw(), parametersAcceptor.raw(), scope);
	}

	/* Mirrors defaultFuncCallNarrowing(). */
	zv::Val defaultFuncCallNarrowing(zval *scope, zval *expr, zval *nameResult, zval *context) const
	{
		bool narrowable;
		if (UNEXPECTED(!isFuncCallNarrowable(scope, expr, nameResult, narrowable))) return zv::Val();
		if (!narrowable) {
			zv::Val empty = pt_specified_types_new();
			if (UNEXPECTED(empty.isUndef())) return zv::Val();
			return pt_specified_types_set_root_expr(Z_OBJ_P(empty.raw()), expr);
		}

		return pt_default_narrowing_helper_specify_default_types(slot(slots::defaultNarrowingHelper), expr, context);
	}

	/* Mirrors isFuncCallNarrowable(); false = pending exception */
	[[nodiscard]] bool isFuncCallNarrowable(zval *scope, zval *expr, zval *nameResult, bool &out) const
	{
		zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		name = nameHold.raw();
		bool remember = flag(slots::rememberPossiblyImpureFunctionValues);
		if (isA(name, PT_CLASS_NAME)) {
			bool has;
			if (UNEXPECTED(!hasFunction(slot(slots::reflectionProvider), name, scope, has))) return false;
			if (!has) {
				// backwards compatibility with previous behaviour
				out = false;
				return true;
			}

			zv::Val functionReflection = getFunction(slot(slots::reflectionProvider), name, scope);
			if (UNEXPECTED(functionReflection.isUndef())) return false;
			zv::Val sideEffectsHold;
			zval *hasSideEffects = pt_function_reflection_has_side_effects(functionReflection.raw(), sideEffectsHold);
			if (UNEXPECTED(hasSideEffects == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(hasSideEffects) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(hasSideEffects));
				return false;
			}
			zend_long sideEffects = pt_type_trinary_value(hasSideEffects);
			if (UNEXPECTED(sideEffects < 0)) return false;
			if (sideEffects == PT_TRI_YES) {
				out = false;
				return true;
			}

			out = remember || sideEffects == PT_TRI_NO;
			return true;
		}
		if (UNEXPECTED(EG(exception))) return false;

		if (Z_TYPE_P(nameResult) == IS_NULL) {
			pt_throw_should_not_happen();
			return false;
		}

		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) return false;
		zv::Val nameType = pt_expression_result_get_type_on_scope(nameResult, scope, promoted);
		if (UNEXPECTED(nameType.isUndef())) return false;
		zend_long isCallable = typeOpTrinary(nameType.raw(), PT_OP_IS_CALLABLE, "isCallable");
		if (UNEXPECTED(isCallable < 0)) return false;
		if (isCallable != PT_TRI_YES) {
			out = true;
			return true;
		}

		zend_long isPure = -2;
		zv::Val variants = typeCall(nameType.raw(), PT_LC("getcallableparametersacceptors"), "getCallableParametersAcceptors", 1, scope);
		if (UNEXPECTED(variants.isUndef())) return false;
		if (Z_TYPE_P(variants.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(variants.raw())) {
				zend_long variantIsPure = typeCallTrinary(entry.value().deref().raw(), PT_LC("ispure"), "isPure");
				if (UNEXPECTED(variantIsPure < 0)) return false;
				/* TrinaryLogic::and(): the smallest */
				isPure = isPure == -2 ? variantIsPure : (variantIsPure < isPure ? variantIsPure : isPure);
			}
		}

		if (isPure == -2) {
			out = true;
			return true;
		}

		if (isPure == PT_TRI_NO) {
			out = false;
			return true;
		}

		out = remember || isPure == PT_TRI_YES;
		return true;
	}

	/* Mirrors getDynamicFunctionReturnType(); the type or PHP null */
	zv::Val getDynamicFunctionReturnType(zval *scope, zval *normalizedNode, zval *functionReflection, zval *argsResult) const
	{
		zv::Val extensions = getDynamicFunctionReturnTypeExtensions(slot(slots::dynamicReturnTypeExtensionRegistry), functionReflection);
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();

		// re-expose the already-processed arguments so an extension's
		// Scope::getType($arg->value) reads the stored result instead of re-walking
		// the argument on demand (the call's argument storage frame is no longer
		// current when the return type is asked lazily)
		pt_primed_storage primed;
		if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(slot(slots::storagePrimer), scope, argsResult, primed))) return zv::Val();
		zv::Val resolved = zv::Val::null();
		if (Z_TYPE_P(extensions.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
				zv::Args argv{functionReflection, normalizedNode, scope};
				zv::Val resolvedType = callPoly(pt_fch_return_type_site, entry.value().deref().raw(), PT_LC("gettypefromfunctioncall"), "getTypeFromFunctionCall", 3, argv);
				if (UNEXPECTED(resolvedType.isUndef())) break;
				if (!resolvedType.isNull()) {
					resolved = std::move(resolvedType);
					break;
				}
			}
		}
		pt_finally([&]() {
			(void) pt_dynamic_return_type_storage_primer_pop(primed);
		});
		if (UNEXPECTED(EG(exception))) return zv::Val();

		return resolved;
	}

	/* Mirrors getCallVariableFlow(); $functionName NULL for null */
	static zv::Val getCallVariableFlow(zend_string *functionName, zval *call, zval *args, zval *scope)
	{
		if (functionName != NULL && (zend_string_equals_literal(functionName, "get_defined_vars") || zend_string_equals_literal(functionName, "extract"))) {
			return pt_variable_flow_all_read_all();
		}
		if (functionName != NULL && (zend_string_equals_literal(functionName, "func_get_arg") || zend_string_equals_literal(functionName, "func_get_args"))) {
			return pt_variable_flow_all_mention_all();
		}
		if (functionName == NULL || !zend_string_equals_literal(functionName, "compact")) {
			return zv::Val::null();
		}
		zv::Val callArguments = callArgs(call);
		if (UNEXPECTED(callArguments.isUndef())) return zv::Val();
		zv::Arr reads = zv::Arr::empty();
		if (Z_TYPE_P(callArguments.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry entry : zv::ArrRef(callArguments.raw())) {
				zval *arg = entry.value().deref().raw();
				zval *unpack = nodeProp(pt_fch_arg_unpack_site, arg, PT_LC("unpack"));
				if (UNEXPECTED(unpack == NULL)) return zv::Val();
				if (zend_is_true(unpack)) {
					return pt_variable_flow_all_read_all();
				}
				zval *value = nodeProp(pt_fch_arg_value_site, arg, PT_LC("value"));
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
				zv::Val argResult = Z_TYPE_P(valueHold.raw()) == IS_OBJECT
					? pt_args_result_require_arg_result(args, valueHold.raw())
					: pt_type_call(Z_OBJ_P(args), PT_LC("requireargresult"), 1, valueHold.raw());
				if (UNEXPECTED(argResult.isUndef())) return zv::Val();
				zv::Val type = pt_expression_result_get_type_on_scope(argResult.raw(), scope, false);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val names = compactNames(type.raw());
				if (UNEXPECTED(names.isUndef())) return zv::Val();
				if (names.isNull()) {
					return pt_variable_flow_all_read_all();
				}
				for (zv::ArrayEntry nameEntry : zv::ArrRef(names.raw())) {
					zval *nameValue = nameEntry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(nameValue) != IS_STRING)) {
						zend_type_error("PHPStan\\Analyser\\VariableFlow::read(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(nameValue));
						return zv::Val();
					}
					zv::Val read = pt_variable_flow_read(Z_STR_P(nameValue), NULL, false, NULL);
					if (UNEXPECTED(read.isUndef())) return zv::Val();
					reads.push(std::move(read));
				}
			}
		}
		return pt_variable_flow_sequence_list(reads.table());
	}

	/* Mirrors compactNames(): the list of names, or PHP null */
	static zv::Val compactNames(zval *type)
	{
		zv::Val strings = typeCall(type, PT_LC("getconstantstrings"), "getConstantStrings", 0, NULL);
		if (UNEXPECTED(strings.isUndef())) return zv::Val();
		if (countOf(strings.raw()) > 0) {
			/* array_map(static fn ($name) => $name->getValue(), $strings): keys kept */
			zv::Arr names = zv::Arr::create(countOf(strings.raw()));
			for (zv::ArrayEntry entry : zv::ArrRef(strings.raw())) {
				zv::Val value = typeOp(entry.value().deref().raw(), PT_OP_GET_VALUE, "getValue");
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL) {
					names.set(key, std::move(value));
				} else {
					names.separate();
					zval v = value.take();
					zend_hash_index_update(names.table(), entry.indexKey(), &v);
				}
			}
			return zv::Val(std::move(names));
		}
		zv::Val arrays = typeOp(type, PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
		if (UNEXPECTED(arrays.isUndef())) return zv::Val();
		if (countOf(arrays.raw()) == 0) return zv::Val::null();
		zv::Arr names = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(arrays.raw())) {
			zval *array = entry.value().deref().raw();
			zend_long unsealed = typeOpTrinary(array, PT_OP_IS_UNSEALED, "isUnsealed");
			if (UNEXPECTED(unsealed < 0)) return zv::Val();
			if (unsealed == PT_TRI_YES) return zv::Val::null();
			zv::Val valueTypes = pt_type_op(Z_OBJ_P(array), PT_OP_GET_VALUE_TYPES, 0, NULL);
			if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
			if (Z_TYPE_P(valueTypes.raw()) != IS_ARRAY) continue;
			for (zv::ArrayEntry valueEntry : zv::ArrRef(valueTypes.raw())) {
				zv::Val values;
				zval *valueType = valueEntry.value().deref().raw();
				pt_engine_with_stack([&]() { values = compactNames(valueType); });
				if (UNEXPECTED(values.isUndef())) return zv::Val();
				if (values.isNull()) return zv::Val::null();
				for (zv::ArrayEntry nameEntry : zv::ArrRef(values.raw())) {
					names.push(nameEntry.value());
				}
			}
		}
		return zv::Val(std::move(names));
	}

	/* fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted):
	 * SpecifiedTypes => $this->specifyTypes(...) — captures: $this,
	 * $beforeScope, $expr, $normalizedExpr, $nameResult,
	 * $resolvedParametersAcceptor, $argsResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *beforeScope = &captures[1];
		zv::Val scope;
		if (zend_is_true(&argv[1])) {
			scope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(scope.isUndef())) return;
		} else {
			scope = zv::Val::copyOf(zv::Ref(beforeScope));
		}
		zval *resolvedParametersAcceptor = &captures[5];
		zval *argsResult = &captures[6];
		zv::Val specifiedTypes = FuncCallHandler(Z_OBJ(captures[0])).specifyTypes(scope.raw(), &captures[2], &captures[3], &captures[4], Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL ? NULL : resolvedParametersAcceptor, &argv[0], Z_TYPE_P(argsResult) == IS_NULL ? NULL : argsResult);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (Type $type, TypeSpecifierContext $createContext, bool
	 * $nativeTypesPromoted) use ($expr, $normalizedExpr, $nameResult,
	 * $beforeScope, $argsResult): SpecifiedTypes — captures: $this, then those */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 3)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\FuncCallHandler::{closure}(), %u passed and exactly 3 expected", argc);
			return;
		}
		zv::Val types = FuncCallHandler(Z_OBJ(captures[0])).createTypes(captures, &argv[0], &argv[1], zend_is_true(&argv[2]));
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}

	zv::Val createTypes(zval *captures, zval *type, zval *createContext, bool nativeTypesPromoted) const
	{
		zval *expr = &captures[1];
		zval *normalizedExpr = &captures[2];
		zval *nameResult = &captures[3];
		zval *beforeScope = &captures[4];
		zval *argsResult = &captures[5];

		zv::Val s;
		if (nativeTypesPromoted) {
			s = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(s.isUndef())) return zv::Val();
		} else {
			s = zv::Val::copyOf(zv::Ref(beforeScope));
		}
		bool narrowable;
		if (UNEXPECTED(!isFuncCallNarrowable(s.raw(), expr, nameResult, narrowable))) return zv::Val();
		if (!narrowable) {
			return pt_specified_types_new();
		}

		zv::Val types = pt_default_narrowing_helper_create_subject_types(slot(slots::defaultNarrowingHelper), s.raw(), expr, NULL, type, createContext);
		if (UNEXPECTED(types.isUndef())) return zv::Val();

		// array_key_first/array_key_last/array_find_key return null iff the
		// array has no matching key - a null constraint on the call narrows
		// the array argument (both directions for first/last, non-empty only
		// for find_key: an empty result does not mean an empty array)
		zval *name = nodeProp(pt_fch_name_site, expr, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		name = nameHold.raw();
		if (!isA(name, PT_CLASS_NAME)) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return types;
		}
		bool firstClassCallable;
		if (UNEXPECTED(!isFirstClassCallable(expr, firstClassCallable))) return zv::Val();
		if (firstClassCallable) return types;
		zv::Val normalizedArgs = callArgs(normalizedExpr);
		if (UNEXPECTED(normalizedArgs.isUndef())) return zv::Val();
		if (!argIsset(normalizedArgs.raw(), 0)) return types;
		zend_long isNull = typeOpTrinary(type, PT_OP_IS_NULL, "isNull");
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		if (isNull != PT_TRI_YES) return types;

		zv::Val nameStringHold;
		zend_string *funcName = nameToString(name, nameStringHold);
		if (UNEXPECTED(funcName == NULL)) return zv::Val();
		bool bothDirections = zend_string_equals_literal_ci(funcName, "array_key_first") || zend_string_equals_literal_ci(funcName, "array_key_last");
		if (!bothDirections && !zend_string_equals_literal_ci(funcName, "array_find_key")) return types;

		// the normalized arguments are the ones processArgs() walked
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zval *argExpr = argValueAt(args.raw(), 0);
		if (UNEXPECTED(argExpr == NULL)) return zv::Val();
		zv::Val argExprHold = zv::Val::copyOf(zv::Ref(argExpr));
		zv::Val argResult = Z_TYPE_P(argExprHold.raw()) == IS_OBJECT
			? pt_args_result_require_arg_result(argsResult, argExprHold.raw())
			: pt_type_call(Z_OBJ_P(argsResult), PT_LC("requireargresult"), 1, argExprHold.raw());
		if (UNEXPECTED(argResult.isUndef())) return zv::Val();
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(s.raw()), promoted))) return zv::Val();
		zv::Val argType = pt_expression_result_get_type_on_scope(argResult.raw(), s.raw(), promoted);
		if (UNEXPECTED(argType.isUndef())) return zv::Val();
		zend_long isArray = typeOpTrinary(argType.raw(), PT_OP_IS_ARRAY, "isArray");
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray != PT_TRI_YES) return types;
		if (!bothDirections) {
			bool falsey;
			if (UNEXPECTED(!pt_type_specifier_context_falsey(Z_OBJ_P(createContext), falsey))) return zv::Val();
			if (!falsey) return types;
		}
		zval nonEmpty;
		if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
		zv::Val nonEmptyHold = zv::Val::adopt(nonEmpty);
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(createContext));
		if (UNEXPECTED(negated.isUndef())) return zv::Val();
		zv::Val subjectTypes = pt_default_narrowing_helper_create_for_subject(slot(slots::defaultNarrowingHelper), argExprHold.raw(), nonEmptyHold.raw(), negated.raw(), s.raw());
		if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
		return unionWith(types.raw(), subjectTypes.raw());
	}

	/* isset($args[$index]) */
	static bool argIsset(zval *args, zend_ulong index)
	{
		zval *arg = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), index) : NULL;
		if (arg == NULL) return false;
		ZVAL_DEREF(arg);
		return Z_TYPE_P(arg) != IS_NULL;
	}
};

} // namespace phpstanturbo

using phpstanturbo::FuncCallHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_func_call_handler()
{
	pt_fch_closure_name = zend_string_init_interned(PT_LC("Closure"), 1);
	pt_fch_throwable_name = zend_string_init_interned(PT_LC("Throwable"), 1);
	pt_fch_function_call_identifier = zend_string_init_interned(PT_LC("functionCall"), 1);
	pt_fch_unknown_function_description = zend_string_init_interned(PT_LC("call to unknown function"), 1);
	pt_fch_in_clone_with = zend_string_init_interned(PT_LC("inCloneWith"), 1);
	pt_fch_invoke = zend_string_init_interned(PT_LC("__invoke"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\FuncCallHandler");
	ptdecl::FuncCallHandler::declareClass(cls);
	ptdecl::FuncCallHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider = NULL, *dynamicFunctionThrowTypeExtensions = NULL, *dynamicReturnTypeExtensionRegistry = NULL, *scopeEffectsHelper = NULL, *expressionResultFactory = NULL, *typeSpecifier = NULL, *defaultNarrowingHelper = NULL, *earlyTerminatingHelper = NULL, *storagePrimer = NULL, *impossibleCheckTypeHelper = NULL, *closureTypeResolver = NULL, *argumentsHandler = NULL, *closureProcessor = NULL, *assignHandler = NULL;
		bool implicitThrows, rememberPossiblyImpureFunctionValues;
		ZEND_PARSE_PARAMETERS_START(16, 16)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_OBJECT(dynamicFunctionThrowTypeExtensions)
			Z_PARAM_OBJECT(dynamicReturnTypeExtensionRegistry)
			Z_PARAM_BOOL(implicitThrows)
			Z_PARAM_BOOL(rememberPossiblyImpureFunctionValues)
			Z_PARAM_OBJECT(scopeEffectsHelper)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(typeSpecifier)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
			Z_PARAM_OBJECT(earlyTerminatingHelper)
			Z_PARAM_OBJECT(storagePrimer)
			Z_PARAM_OBJECT(impossibleCheckTypeHelper)
			Z_PARAM_OBJECT(closureTypeResolver)
			Z_PARAM_OBJECT(argumentsHandler)
			Z_PARAM_OBJECT(closureProcessor)
			Z_PARAM_OBJECT(assignHandler)
		ZEND_PARSE_PARAMETERS_END();
		zval implicitThrowsValue, rememberValue;
		ZVAL_BOOL(&implicitThrowsValue, implicitThrows);
		ZVAL_BOOL(&rememberValue, rememberPossiblyImpureFunctionValues);
		zval *argv[] = { reflectionProvider, dynamicFunctionThrowTypeExtensions, dynamicReturnTypeExtensionRegistry, &implicitThrowsValue, &rememberValue, scopeEffectsHelper, expressionResultFactory, typeSpecifier, defaultNarrowingHelper, earlyTerminatingHelper, storagePrimer, impossibleCheckTypeHelper, closureTypeResolver, argumentsHandler, closureProcessor, assignHandler };
		FuncCallHandler(Z_OBJ_P(ZEND_THIS)).construct(argv);
	});

	cls.method<&FuncCallHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(FuncCallHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_func_call_handler);
	pt_expr_handler_entry_register(&pt_ce_func_call_handler, &FuncCallHandler::processExprEntry);
}

/* }}} */
