/*
 * PHPStanTurbo\ClosureTypeResolver — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver.
 *
 * A DI service (#[AutowiredService], PerFileAnalysisResettable): the
 * constructor keeps the twin's arginfo so Nette autowires it, the per-file
 * cache lives in the twin's $cachedTypes slot with the twin's shape
 * (spl_object_id => ['expr' => ..., 'types' => [cacheKey => [...]]]) and is
 * updated in place. The private static $resolveClosureTypeDepth guard is a
 * C++ counter (the declared static stays for reflection parity).
 *
 * getClosureType(), buildClosureTypeForClosure(),
 * buildClosureTypeForArrowFunction() and getDeclaredClosureType() are exported
 * as pt_closure_type_resolver_*() (Engine.h conventions); MutatingScope,
 * ArgumentsHandler and ClosureParameterResolver call them directly. The body
 * walks of getClosureType() recurse through NodeScopeResolver's direct entries
 * (which keep the fresh-stack guard on the path); their node callbacks are
 * native closures capturing what the PHP closures capture (the entered scope
 * by value, the gathered lists by reference). The array_map() callbacks and
 * the ImpurePoints the twin builds only to map them to SimpleImpurePoints are
 * spelled out inline.
 *
 * ContextualClosureParameterResolver, MutatingScope, NodeScopeResolver, the
 * statement / expression results and throw / impure points, ClosureType,
 * NativeParameterReflection, InitializerExprTypeResolver and the Type kernel
 * are called through their direct entries; InitializerExprContext,
 * SimpleThrowPoint and TemplateArgumentStats stay PHP (cached sites below).
 */

#include "support.h"
#include "generated/ClosureTypeResolver.h"

namespace slots = ptdecl::ClosureTypeResolver::slot;
namespace sigs = ptdecl::ClosureTypeResolver::sig;
#include "ClosureSupport.h"

#include "zend_smart_str.h"

zend_class_entry *pt_ce_closure_type_resolver = nullptr;

namespace {

/* the literals, permanent interned strings (module startup) */
zend_string *pt_ctr_free_variable_roots = nullptr;
zend_string *pt_ctr_this_root = nullptr;
zend_string *pt_ctr_function_call = nullptr;
zend_string *pt_ctr_by_ref_use = nullptr;
zend_string *pt_ctr_by_ref_parameter = nullptr;
zend_string *pt_ctr_generator = nullptr;
zend_string *pt_ctr_expr = nullptr;
zend_string *pt_ctr_types = nullptr;
zend_string *pt_ctr_return_type = nullptr;
zend_string *pt_ctr_throw_points = nullptr;
zend_string *pt_ctr_impure_points = nullptr;
zend_string *pt_ctr_invalidate_expressions = nullptr;
zend_string *pt_ctr_used_variables = nullptr;

/* the private static $resolveClosureTypeDepth */
int pt_ctr_resolve_closure_type_depth = 0;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_ctr_simple_throw_point_explicit_site;
pt_method_site pt_ctr_simple_throw_point_implicit_site;
pt_method_site pt_ctr_stats_increment_site;

/* $this->initializerExprTypeResolver->getType($expr, InitializerExprContext::fromScope($scope)) */
zv::Val initializerExprType(zval *initializerExprTypeResolver, zval *expr, zval *scope)
{
	zv::Val context = pt_initializer_expr_context_from_scope(scope);
	if (UNEXPECTED(context.isUndef())) return zv::Val();
	return pt_initializer_expr_type_resolver_get_type(initializerExprTypeResolver, expr, context.raw());
}

/* SimpleThrowPoint::createExplicit($type, $canContainAnyThrowable) / ::createImplicit() */
zv::Val simpleThrowPointExplicit(zval *type, bool canContainAnyThrowable)
{
	zv::Args argv{type, canContainAnyThrowable};
	return pt_call_static_cached(pt_ctr_simple_throw_point_explicit_site, PT_CLASS_SIMPLE_THROW_POINT, PT_LC("createexplicit"), 2, argv);
}

zv::Val simpleThrowPointImplicit()
{
	return pt_call_static_cached(pt_ctr_simple_throw_point_implicit_site, PT_CLASS_SIMPLE_THROW_POINT, PT_LC("createimplicit"), 0, NULL);
}

/* TemplateArgumentStats::$enabled && TemplateArgumentStats::increment('closureTypeBodyWalks'); false = pending exception */
[[nodiscard]] bool countClosureTypeBodyWalk()
{
	zend_class_entry *ce = pt_class(PT_CLASS_TEMPLATE_ARGUMENT_STATS);
	if (UNEXPECTED(ce == NULL)) return false;
	zval *enabled = zend_read_static_property(ce, PT_LC("enabled"), 0);
	if (UNEXPECTED(enabled == NULL)) return false;
	ZVAL_DEREF(enabled);
	if (EXPECTED(Z_TYPE_P(enabled) != IS_TRUE)) return true;
	zval counter;
	ZVAL_STRINGL(&counter, "closureTypeBodyWalks", sizeof("closureTypeBodyWalks") - 1);
	zv::Val result = pt_call_static_cached(pt_ctr_stats_increment_site, PT_CLASS_TEMPLATE_ARGUMENT_STATS, PT_LC("increment"), 1, &counter);
	zval_ptr_dtor(&counter);
	return !result.isUndef();
}

/* }}} */

/* {{{ node reads */

pt_property_site pt_ctr_return_expr_site;
pt_property_site pt_ctr_yield_key_site;
pt_property_site pt_ctr_yield_value_site;
pt_property_site pt_ctr_yield_from_expr_site;
pt_property_site pt_ctr_execution_end_statement_result_site;
pt_property_site pt_ctr_attrs_site;
pt_property_site pt_ctr_attr_name_site;
pt_property_site pt_ctr_name_name_site;
pt_property_site pt_ctr_func_call_name_site;

/* $name->toLowerString() === $lowercase of a php-parser Name */
[[nodiscard]] bool nameIsLowercase(zval *name, const char *lowercase, size_t len, bool &out)
{
	zval *value = ptclosure::prop(pt_ctr_name_name_site, name, PT_LC("name"));
	if (UNEXPECTED(value == NULL)) return false;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
		zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(value));
		return false;
	}
	out = ZSTR_LEN(Z_STR_P(value)) == len && zend_binary_strcasecmp(ZSTR_VAL(Z_STR_P(value)), len, lowercase, len) == 0;
	return true;
}

/* TrinaryLogic::createYes() when an attribute of $expr->attrGroups is named
 * NoDiscard (case-insensitively), createNo() otherwise (borrowed) */
zval *mustUseReturnValueOf(zval *expr)
{
	zval *attrGroups = ptclosure::prop(ptclosure::attrGroupsSite, expr, PT_LC("attrGroups"));
	if (UNEXPECTED(attrGroups == NULL)) return NULL;
	bool mustUse = false;
	if (EXPECTED(Z_TYPE_P(attrGroups) == IS_ARRAY) && zend_hash_num_elements(Z_ARRVAL_P(attrGroups)) > 0) {
		for (zv::ArrayEntry groupEntry : zv::ArrRef(attrGroups)) {
			zval *attrs = ptclosure::prop(pt_ctr_attrs_site, groupEntry.value().deref().raw(), PT_LC("attrs"));
			if (UNEXPECTED(attrs == NULL)) return NULL;
			if (UNEXPECTED(Z_TYPE_P(attrs) != IS_ARRAY)) continue;
			for (zv::ArrayEntry attrEntry : zv::ArrRef(attrs)) {
				zval *name = ptclosure::prop(pt_ctr_attr_name_site, attrEntry.value().deref().raw(), PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return NULL;
				bool isNoDiscard = false;
				if (UNEXPECTED(!nameIsLowercase(name, "nodiscard", sizeof("nodiscard") - 1, isNoDiscard))) return NULL;
				if (isNoDiscard) {
					mustUse = true;
					break;
				}
			}
		}
	}
	return ptclosure::trinaryFromBool(mustUse);
}

/* }}} */

/* the throw point, impure point and statement result reads of value
 * objects the walk produced */

/* array_map(static fn ($throwPoint) => $throwPoint->toPublic(), $throwPoints) */
zv::Val throwPointsToPublic(zval *throwPoints)
{
	if (UNEXPECTED(Z_TYPE_P(throwPoints) != IS_ARRAY)) {
		zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(throwPoints));
		return zv::Val();
	}
	HashTable *table = Z_ARRVAL_P(throwPoints);
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return zv::Val(zv::Arr::empty());
	zv::Arr mapped = zv::Arr::create(count);
	for (zv::ArrayEntry entry : zv::TableRef(table)) {
		zv::Val publicPoint = pt_internal_throw_point_to_public(entry.value().deref().raw());
		if (UNEXPECTED(publicPoint.isUndef())) return zv::Val();
		if (entry.hasStringKey()) {
			mapped.set(entry.stringKey(), std::move(publicPoint));
		} else {
			zval value = publicPoint.take();
			zend_hash_index_update(mapped.table(), entry.indexKey(), &value);
		}
	}
	return zv::Val(std::move(mapped));
}

/* array_merge($a, $b) of two lists the walk built */
zv::Val mergeLists(zval *a, zval *b)
{
	zv::Arr merged = zv::Arr::empty();
	if (UNEXPECTED(!pt_callable_array_merge_into(merged, a) || !pt_callable_array_merge_into(merged, b))) return zv::Val();
	return zv::Val(std::move(merged));
}

/* static function (Node $node, Scope $scope) use ($arrowScope,
 * &$arrowFunctionImpurePoints, &$invalidateExpressions): void — captures in
 * that order */
void arrowFunctionWalkCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) return_value;
	ptclosure::arrowFunctionGatherer(captures, argc, argv, "PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver::{closure}");
}

/* static function (Node $node, Scope $scope) use ($closureScope,
 * &$closureReturnStatements, &$closureYieldStatements, &$closureExecutionEnds,
 * &$closureImpurePoints, &$invalidateExpressions): void — captures in that
 * order */
void closureWalkCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) return_value;
	if (UNEXPECTED(!ptcall::requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver::{closure}"))) return;
	zval *node = &argv[0];
	zval *scope = &argv[1];
	if (ptclosure::differentAnonymousFunction(scope, &captures[0]) != 0) return;

	int is = ptclosure::instanceOf(node, PT_CLASS_INVALIDATE_EXPR_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		ptsh::appendToReference(&captures[5], node);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		(void) ptclosure::gatherPropertyAssign(node, scope, &captures[4], &captures[5]);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_EXECUTION_END_NODE);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		ptsh::appendToReference(&captures[3], node);
		return;
	}
	is = ptclosure::instanceOf(node, PT_CLASS_RETURN_STMT);
	if (UNEXPECTED(is < 0)) return;
	if (is) {
		zv::Val pair = ptclosure::pairOf(node, scope);
		ptsh::appendToReference(&captures[1], pair.raw());
	}
	is = ptclosure::instanceOf(node, PT_CLASS_YIELD);
	if (UNEXPECTED(is < 0)) return;
	if (!is) {
		is = ptclosure::instanceOf(node, PT_CLASS_YIELD_FROM);
		if (UNEXPECTED(is < 0) || !is) return;
	}
	zv::Val pair = ptclosure::pairOf(node, scope);
	ptsh::appendToReference(&captures[2], pair.raw());
}

/* the collected state of freeVariableRoots()'s NodeFinder walks over an
 * arrow function body */
struct FreeVariableFindCtx : pt_find_ctx
{
	zend_class_entry *variableCe;
	zend_class_entry *funcCallCe;
	zend_class_entry *nameCe;
	HashTable *paramNames;
	zv::Arr *roots;
	bool dynamic;
};

/* the Variable walk: roots in pre-order, stopping at a dynamic name */
bool freeVariableMatcher(zend_object *node, void *vctx)
{
	FreeVariableFindCtx *ctx = static_cast<FreeVariableFindCtx *>(static_cast<pt_find_ctx *>(vctx));
	if (!instanceof_function(node->ce, ctx->variableCe)) return false;
	zval nodeZv;
	ZVAL_OBJ(&nodeZv, node);
	zval *name = pt_property_cached(ptclosure::variableNameSite, node, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) {
		zend_throw_error(NULL, "Undefined property: %s::$name", ZSTR_VAL(node->ce->name));
		ctx->failed = true;
		return true;
	}
	ZVAL_DEREF(name);
	if (Z_TYPE_P(name) != IS_STRING) {
		ctx->dynamic = true;
		return true;
	}
	zend_string *root = zend_string_concat2("$", 1, Z_STRVAL_P(name), Z_STRLEN_P(name));
	if (zend_hash_find(ctx->paramNames, root) == NULL) {
		zval flag;
		ZVAL_TRUE(&flag);
		ctx->roots->separate();
		zend_symtable_update(ctx->roots->table(), root, &flag);
	}
	zend_string_release(root);
	return false;
}

/* the FuncCall walk: compact() / extract() / get_defined_vars() */
bool scopeReadingCallMatcher(zend_object *node, void *vctx)
{
	FreeVariableFindCtx *ctx = static_cast<FreeVariableFindCtx *>(static_cast<pt_find_ctx *>(vctx));
	if (!instanceof_function(node->ce, ctx->funcCallCe)) return false;
	zval *name = pt_property_cached(pt_ctr_func_call_name_site, node, PT_LC("name"));
	if (UNEXPECTED(name == NULL)) return false;
	ZVAL_DEREF(name);
	if (Z_TYPE_P(name) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(name), ctx->nameCe)) return false;
	bool matches = false;
	for (const char *function : { "compact", "extract", "get_defined_vars" }) {
		if (UNEXPECTED(!nameIsLowercase(name, function, strlen(function), matches))) {
			ctx->failed = true;
			return true;
		}
		if (matches) {
			ctx->dynamic = true;
			return true;
		}
	}
	return false;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver; UNDEF /
 * false = pending exception. */
class ClosureTypeResolver
{
public:
	explicit ClosureTypeResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *nodeScopeResolver, zval *initializerExprTypeResolver, zval *contextualClosureParameterResolver)
	{
		writeSlot(slots::nodeScopeResolver, zv::Val::copyOf(zv::Ref(nodeScopeResolver)));
		writeSlot(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
		writeSlot(slots::contextualClosureParameterResolver, zv::Val::copyOf(zv::Ref(contextualClosureParameterResolver)));
	}

	/* Mirrors resetFileAnalysisState() */
	void resetFileAnalysisState()
	{
		writeSlot(slots::cachedTypes, zv::Val(zv::Arr::empty()));
	}

	/* Mirrors getClosureType() ($storage NULL for null) */
	zv::Val getClosureType(zval *scope, zval *expr, bool shallow, zval *storage)
	{
		Parameters p;
		if (UNEXPECTED(!buildParametersAndAcceptors(scope, expr, storage, NULL, NULL, p))) return zv::Val();

		// A shallow reflection is the closure/arrow function's signature without
		// walking its body: parameters plus the DECLARED return type.
		if (shallow) return signatureClosureType(scope, expr, p.parameters.raw(), p.isVariadic);

		zv::Val cachedTypes = findCachedTypes(expr);
		zv::Str cacheKey = closureContextCacheKey(scope, expr, p.callableParameters.isNull() ? NULL : p.callableParameters.raw(), p.parameters.raw());
		if (UNEXPECTED(cacheKey.isNull())) return zv::Val();
		zval *cached = zend_symtable_find(Z_ARRVAL_P(cachedTypes.raw()), cacheKey.get());
		if (cached != NULL) {
			ZVAL_DEREF(cached);
			return createClosureTypeFromCache(expr, p.parameters.raw(), p.isVariadic, cached);
		}
		if (pt_ctr_resolve_closure_type_depth >= 2) return signatureClosureType(scope, expr, p.parameters.raw(), p.isVariadic);

		if (UNEXPECTED(!countClosureTypeBodyWalk())) return zv::Val();
		int isArrowFunction = ptclosure::instanceOf(expr, PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(isArrowFunction < 0)) return zv::Val();
		if (isArrowFunction) return walkArrowFunction(scope, expr, p, cacheKey.get());
		return walkClosure(scope, expr, p, cacheKey.get());
	}

	/* Mirrors buildClosureTypeForClosure() (the nullable ones NULL for null) */
	zv::Val buildClosureTypeForClosure(zval *scope, zval *expr, zval *returnStatements, zval *yieldStatements, zval *executionEnds, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, bool native, zval *storage, zval *passedToType, zval *nativePassedToType)
	{
		Parameters p;
		if (UNEXPECTED(!buildParametersAndAcceptors(scope, expr, storage, passedToType, nativePassedToType, p))) return zv::Val();
		zv::Val publicThrowPoints = throwPointsToPublic(throwPoints);
		if (UNEXPECTED(publicThrowPoints.isUndef())) return zv::Val();
		// the flavour-correct key both keeps the native build from clobbering
		// the phpdoc cache slot and lets a later getClosureType() ask on the
		// promoted scope answer from this build instead of re-walking
		zv::Str cacheKey = flavourCacheKey(scope, expr, p, native);
		if (UNEXPECTED(cacheKey.isNull())) return zv::Val();
		return buildClosureTypeFromClosureWalk(scope, expr, p.parameters.raw(), p.isVariadic, returnStatements, yieldStatements, executionEnds, publicThrowPoints.raw(), impurePoints, invalidateExpressions, cacheKey.get(), native, storage);
	}

	/* Mirrors buildClosureTypeForArrowFunction() (the nullable ones NULL for null) */
	zv::Val buildClosureTypeForArrowFunction(zval *scope, zval *expr, zval *arrowScope, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, bool native, zval *storage, zval *passedToType, zval *nativePassedToType)
	{
		Parameters p;
		if (UNEXPECTED(!buildParametersAndAcceptors(scope, expr, storage, passedToType, nativePassedToType, p))) return zv::Val();
		zv::Val returnType = resolveArrowFunctionReturnType(scope, arrowScope, expr, native, storage);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zv::Str cacheKey = flavourCacheKey(scope, expr, p, native);
		if (UNEXPECTED(cacheKey.isNull())) return zv::Val();
		zv::Arr usedVariables = zv::Arr::empty();
		return assembleClosureType(scope, expr, p.parameters.raw(), p.isVariadic, returnType.raw(), throwPoints, impurePoints, false, invalidateExpressions, usedVariables.raw(), cacheKey.get());
	}

	/* Mirrors getDeclaredClosureType() */
	zv::Val getDeclaredClosureType(zval *scope, zval *expr)
	{
		zv::Val parameters;
		bool isVariadic = false;
		if (UNEXPECTED(!buildDeclaredParameters(scope, expr, parameters, isVariadic))) return zv::Val();
		return signatureClosureType(scope, expr, parameters.raw(), isVariadic);
	}

private:
	zend_object *self;

	/* [$parameters, $isVariadic, $callableParameters, $nativeCallableParameters] */
	struct Parameters
	{
		zv::Val parameters;
		bool isVariadic = false;
		zv::Val callableParameters; /* null for null */
		zv::Val nativeCallableParameters;
	};

	void writeSlot(uint32_t index, zv::Val value)
	{
		zv::ObjRef(self).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, index)) = 0;
	}

	/* new ClosureType($parameters, $scope->getFunctionType($expr->returnType,
	 * false, false), $isVariadic, isStatic: TrinaryLogic::createFromBoolean($expr->static)) */
	static zv::Val signatureClosureType(zval *scope, zval *expr, zval *parameters, bool isVariadic)
	{
		zval *returnTypeNode = ptclosure::prop(ptclosure::returnTypeSite, expr, PT_LC("returnType"));
		if (UNEXPECTED(returnTypeNode == NULL)) return zv::Val();
		zv::Val returnType = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), returnTypeNode, false, false);
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zval *isStatic = ptclosure::prop(ptclosure::staticSite, expr, PT_LC("static"));
		if (UNEXPECTED(isStatic == NULL)) return zv::Val();
		zval out;
		if (UNEXPECTED(!pt_closure_type_new(&out, parameters, returnType.raw(), isVariadic, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ptclosure::trinaryFromBool(zend_is_true(isStatic))))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* Mirrors findCachedTypes(): an owned copy of the types array ([] when
	 * none is cached for this very node) */
	zv::Val findCachedTypes(zval *expr) const
	{
		zval *cachedTypes = OBJ_PROP_NUM(self, slots::cachedTypes);
		if (EXPECTED(Z_TYPE_P(cachedTypes) == IS_ARRAY)) {
			zval *entry = zend_hash_index_find(Z_ARRVAL_P(cachedTypes), Z_OBJ_HANDLE_P(expr));
			if (entry != NULL && Z_TYPE_P(entry) == IS_ARRAY) {
				zval *entryExpr = zend_hash_find(Z_ARRVAL_P(entry), pt_ctr_expr);
				zval *types = zend_hash_find(Z_ARRVAL_P(entry), pt_ctr_types);
				if (entryExpr != NULL && types != NULL && Z_TYPE_P(entryExpr) == IS_OBJECT && Z_OBJ_P(entryExpr) == Z_OBJ_P(expr) && Z_TYPE_P(types) == IS_ARRAY) {
					return zv::Val::copyOf(zv::Ref(types));
				}
			}
		}
		return zv::Val(zv::Arr::empty());
	}

	/* the cache key of a flavour build: closureContextCacheKey($native ?
	 * $scope->doNotTreatPhpDocTypesAsCertain() : $scope, $expr, $native ?
	 * $nativeCallableParameters : $callableParameters, $parameters) */
	zv::Str flavourCacheKey(zval *scope, zval *expr, Parameters &p, bool native)
	{
		zv::Val nativeScope;
		zval *keyScope = scope;
		if (native) {
			nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope));
			if (UNEXPECTED(nativeScope.isUndef())) return zv::Str();
			keyScope = nativeScope.raw();
		}
		zv::Val &callable = native ? p.nativeCallableParameters : p.callableParameters;
		return closureContextCacheKey(keyScope, expr, callable.isNull() ? NULL : callable.raw(), p.parameters.raw());
	}

	/* Mirrors closureContextCacheKey() ($callableParameters NULL for null) */
	zv::Str closureContextCacheKey(zval *scope, zval *expr, zval *callableParameters, zval *parameters)
	{
		zval *cacheLevel = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_CACHE);
		if (UNEXPECTED(cacheLevel == NULL)) return zv::Str();
		smart_str parts = {};
		zval *described = callableParameters != NULL ? callableParameters : parameters;
		if (EXPECTED(Z_TYPE_P(described) == IS_ARRAY)) {
			bool first = true;
			for (zv::ArrayEntry entry : zv::ArrRef(described)) {
				zval *parameter = entry.value().deref().raw();
				zv::Val type = ptclosure::parameterGetType(parameter);
				zv::Val description = type.isUndef() ? zv::Val() : describeType(type.raw(), cacheLevel);
				if (UNEXPECTED(description.isUndef())) {
					smart_str_free(&parts);
					return zv::Str();
				}
				if (!first) {
					smart_str_appendc(&parts, '|');
				}
				first = false;
				smart_str_append(&parts, Z_STR_P(description.raw()));
			}
		}

		// a closure whose body creates an unresolved template argument has the
		// same key in both passes of the enclosing body; the resolutions installed
		// for the second pass change its type
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		zv::Val roots = frame.isUndef() ? zv::Val() : freeVariableRoots(expr);
		zv::Val scopeKey = roots.isUndef() ? zv::Val() : pt_mutating_scope_get_closure_scope_cache_key(Z_OBJ_P(scope), roots.isNull() ? NULL : roots.raw());
		if (UNEXPECTED(scopeKey.isUndef())) {
			smart_str_free(&parts);
			return zv::Str();
		}
		if (UNEXPECTED(!scopeKey.ref().isString())) {
			smart_str_free(&parts);
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver::closureContextCacheKey(): Return value must be of type string, %s returned", zend_zval_value_name(scopeKey.raw()));
			return zv::Str();
		}
		bool promoted = false;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), promoted))) {
			smart_str_free(&parts);
			return zv::Str();
		}
		zv::Val suffix;
		if (!frame.isNull()) {
			suffix = pt_template_argument_frame_resolution_cache_key_suffix(frame.raw());
			if (UNEXPECTED(suffix.isUndef())) {
				smart_str_free(&parts);
				return zv::Str();
			}
		}

		smart_str key = {};
		smart_str_append(&key, Z_STR_P(scopeKey.raw()));
		smart_str_appendc(&key, '/');
		if (parts.s != NULL) {
			smart_str_append(&key, parts.s);
		}
		smart_str_free(&parts);
		if (promoted) {
			smart_str_appendl(&key, "/native", sizeof("/native") - 1);
		} else {
			smart_str_appendl(&key, "/phpdoc", sizeof("/phpdoc") - 1);
		}
		if (!suffix.isUndef()) {
			zend_string *suffixString = zval_get_string(suffix.raw());
			smart_str_append(&key, suffixString);
			zend_string_release(suffixString);
		}
		return zv::Str::adopt(smart_str_extract(&key));
	}

	/* $type->describe($level) as a string; UNDEF = pending exception */
	static zv::Val describeType(zval *type, zval *level)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function describe() on %s", zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val description = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!description.ref().isString())) {
			zend_type_error("implode(): Argument #2 ($array) must contain strings only");
			return zv::Val();
		}
		return description;
	}

	/* Mirrors freeVariableRoots(): the list, or null */
	static zv::Val freeVariableRoots(zval *expr)
	{
		zval *cached = ptclosure::attribute(expr, pt_ctr_free_variable_roots);
		if (cached != NULL) return zv::Val::copyOf(zv::Ref(cached));

		zv::Arr roots = zv::Arr::empty();
		zval *isStatic = ptclosure::prop(ptclosure::staticSite, expr, PT_LC("static"));
		if (UNEXPECTED(isStatic == NULL)) return zv::Val();
		zval flag;
		ZVAL_TRUE(&flag);
		if (!zend_is_true(isStatic)) {
			roots.separate();
			zend_hash_update(roots.table(), pt_ctr_this_root, &flag);
		}

		int isClosure = ptclosure::instanceOf(expr, PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(isClosure < 0)) return zv::Val();
		zval null;
		ZVAL_NULL(&null);
		if (isClosure) {
			zval *uses = ptclosure::prop(ptclosure::usesSite, expr, PT_LC("uses"));
			if (UNEXPECTED(uses == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(uses) == IS_ARRAY)) {
				for (zv::ArrayEntry entry : zv::ArrRef(uses)) {
					zval *var = ptclosure::prop(ptclosure::useVarSite, entry.value().deref().raw(), PT_LC("var"));
					if (UNEXPECTED(var == NULL)) return zv::Val();
					zval *name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
					if (UNEXPECTED(name == NULL)) return zv::Val();
					if (Z_TYPE_P(name) != IS_STRING) {
						if (UNEXPECTED(!pt_node_set_attribute(Z_OBJ_P(expr), pt_ctr_free_variable_roots, &null))) return zv::Val();
						return zv::Val::null();
					}
					zend_string *root = zend_string_concat2("$", 1, Z_STRVAL_P(name), Z_STRLEN_P(name));
					roots.separate();
					zend_symtable_update(roots.table(), root, &flag);
					zend_string_release(root);
				}
			}
		} else {
			zv::ScratchTable paramNames(8);
			zval *params = ptclosure::prop(ptclosure::paramsSite, expr, PT_LC("params"));
			if (UNEXPECTED(params == NULL)) return zv::Val();
			zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
			zend_class_entry *funcCallCe = pt_class(PT_CLASS_FUNC_CALL);
			zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
			if (UNEXPECTED(variableCe == NULL || funcCallCe == NULL || nameCe == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(params) == IS_ARRAY)) {
				for (zv::ArrayEntry entry : zv::ArrRef(params)) {
					zval *var = ptclosure::prop(ptclosure::paramVarSite, entry.value().deref().raw(), PT_LC("var"));
					if (UNEXPECTED(var == NULL)) return zv::Val();
					if (Z_TYPE_P(var) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(var), variableCe)) continue;
					zval *name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
					if (UNEXPECTED(name == NULL)) return zv::Val();
					if (Z_TYPE_P(name) != IS_STRING) continue;
					zend_string *paramName = zend_string_concat2("$", 1, Z_STRVAL_P(name), Z_STRLEN_P(name));
					zend_hash_update(paramNames.table(), paramName, &flag);
					zend_string_release(paramName);
				}
			}

			zval *body = ptclosure::prop(ptclosure::arrowExprSite, expr, PT_LC("expr"));
			if (UNEXPECTED(body == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(body) == IS_OBJECT)) {
				FreeVariableFindCtx ctx{};
				ctx.variableCe = variableCe;
				ctx.funcCallCe = funcCallCe;
				ctx.nameCe = nameCe;
				ctx.paramNames = paramNames.table();
				ctx.roots = &roots;
				(void) pt_find_first_recursive(Z_OBJ_P(body), freeVariableMatcher, static_cast<pt_find_ctx *>(&ctx));
				if (UNEXPECTED(ctx.failed || EG(exception))) return zv::Val();
				if (!ctx.dynamic) {
					(void) pt_find_first_recursive(Z_OBJ_P(body), scopeReadingCallMatcher, static_cast<pt_find_ctx *>(&ctx));
					if (UNEXPECTED(ctx.failed || EG(exception))) return zv::Val();
				}
				if (ctx.dynamic) {
					if (UNEXPECTED(!pt_node_set_attribute(Z_OBJ_P(expr), pt_ctr_free_variable_roots, &null))) return zv::Val();
					return zv::Val::null();
				}
			}
		}

		/* array_keys($roots) */
		HashTable *rootsTable = roots.table();
		zv::Arr rootList = zend_hash_num_elements(rootsTable) > 0 ? zv::Arr::create(zend_hash_num_elements(rootsTable)) : zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::TableRef(rootsTable)) {
			if (entry.hasStringKey()) {
				rootList.push(zv::Val::string(entry.stringKey()));
			} else {
				rootList.push(zv::Val::integer((zend_long) entry.indexKey()));
			}
		}
		if (UNEXPECTED(!pt_node_set_attribute(Z_OBJ_P(expr), pt_ctr_free_variable_roots, rootList.raw()))) return zv::Val();
		return zv::Val(std::move(rootList));
	}

	/* Mirrors readExprType() ($storage NULL for null) */
	static zv::Val readExprType(zval *storage, zval *expr, zval *readScope, bool native)
	{
		if (storage != NULL) {
			zv::Val result = pt_expression_result_storage_find(storage, expr);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (!result.isNull()) {
				return native ? pt_expression_result_get_native_type(result.raw()) : pt_expression_result_get_type(result.raw());
			}
		}
		return pt_mutating_scope_get_type(Z_OBJ_P(readScope), expr);
	}

	/* $gatheredScope->toWalkScope(), promoted for the native flavour */
	static zv::Val readScopeOf(zval *gatheredScope, bool native)
	{
		if (UNEXPECTED(Z_TYPE_P(gatheredScope) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toWalkScope() on %s", zend_zval_value_name(gatheredScope));
			return zv::Val();
		}
		zv::Val readScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(gatheredScope));
		if (UNEXPECTED(readScope.isUndef()) || !native) return readScope;
		return pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(readScope.raw()));
	}

	/* [$node, $scope] = $pair (borrowed); false = pending exception */
	[[nodiscard]] static bool unpackPair(zval *pair, zval *&node, zval *&scope)
	{
		ZVAL_DEREF(pair);
		node = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), 0) : NULL;
		scope = Z_TYPE_P(pair) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(pair), 1) : NULL;
		if (UNEXPECTED(node == NULL || scope == NULL)) {
			zend_throw_error(NULL, "Cannot destructure the gathered statement pair");
			return false;
		}
		ZVAL_DEREF(node);
		ZVAL_DEREF(scope);
		return true;
	}

	/* Mirrors buildClosureTypeFromClosureWalk() ($cacheKey / $storage NULL for null) */
	zv::Val buildClosureTypeFromClosureWalk(zval *scope, zval *expr, zval *parameters, bool isVariadic, zval *returnStatements, zval *yieldStatements, zval *executionEnds, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zend_string *cacheKey, bool native, zval *storage)
	{
		int onlyNeverExecutionEnds = deriveOnlyNeverExecutionEnds(executionEnds);
		if (UNEXPECTED(onlyNeverExecutionEnds == -2)) return zv::Val();

		// like resolveArrowFunctionReturnType(): the single walk stored both
		// flavours on the gathered scopes, so the native flavour just reads the
		// stored native types - no second body walk on the promoted scope
		zv::Arr returnTypes = zv::Arr::empty();
		bool hasNull = false;
		if (EXPECTED(Z_TYPE_P(returnStatements) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(returnStatements)) {
				zval *returnNode, *returnScope;
				if (UNEXPECTED(!unpackPair(entry.value().raw(), returnNode, returnScope))) return zv::Val();
				zval *returnExpr = ptclosure::prop(pt_ctr_return_expr_site, returnNode, PT_LC("expr"));
				if (UNEXPECTED(returnExpr == NULL)) return zv::Val();
				if (Z_TYPE_P(returnExpr) == IS_NULL) {
					hasNull = true;
					continue;
				}
				zv::Val returnExprHold = zv::Val::copyOf(zv::Ref(returnExpr));
				zv::Val readScope = readScopeOf(returnScope, native);
				if (UNEXPECTED(readScope.isUndef())) return zv::Val();
				zv::Val type = readExprType(storage, returnExprHold.raw(), readScope.raw(), native);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				returnTypes.push(std::move(type));
			}
		}

		zv::Val returnType;
		if (zend_hash_num_elements(returnTypes.table()) == 0) {
			zval type;
			bool created = onlyNeverExecutionEnds == 1 && !hasNull ? pt_non_accepting_never_type_new(&type) : pt_void_type_new(&type);
			if (UNEXPECTED(!created)) return zv::Val();
			returnType = zv::Val::adopt(type);
		} else {
			if (onlyNeverExecutionEnds == 1) {
				zval neverType;
				if (UNEXPECTED(!pt_non_accepting_never_type_new(&neverType))) return zv::Val();
				returnTypes.push(zv::Val::adopt(neverType));
			}
			if (hasNull) {
				zval nullType;
				if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
				returnTypes.push(zv::Val::adopt(nullType));
			}
			returnType = ptclosure::unionOf(returnTypes);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		}

		if (Z_TYPE_P(yieldStatements) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(yieldStatements)) > 0) {
			zv::Arr keyTypes = zv::Arr::empty();
			zv::Arr valueTypes = zv::Arr::empty();
			for (zv::ArrayEntry entry : zv::ArrRef(yieldStatements)) {
				zval *yieldNode, *yieldScope;
				if (UNEXPECTED(!unpackPair(entry.value().raw(), yieldNode, yieldScope))) return zv::Val();
				zv::Val readScope = readScopeOf(yieldScope, native);
				if (UNEXPECTED(readScope.isUndef())) return zv::Val();
				zv::Val keyType, valueType;
				if (UNEXPECTED(!yieldTypes(storage, yieldNode, readScope.raw(), native, keyType, valueType))) return zv::Val();
				keyTypes.push(std::move(keyType));
				valueTypes.push(std::move(valueType));
			}
			zv::Val keyUnion = ptclosure::unionOf(keyTypes);
			if (UNEXPECTED(keyUnion.isUndef())) return zv::Val();
			zv::Val valueUnion = ptclosure::unionOf(valueTypes);
			if (UNEXPECTED(valueUnion.isUndef())) return zv::Val();
			returnType = generatorType(keyUnion.raw(), valueUnion.raw(), returnType.raw());
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		} else {
			zval *returnTypeNode = ptclosure::prop(ptclosure::returnTypeSite, expr, PT_LC("returnType"));
			if (UNEXPECTED(returnTypeNode == NULL)) return zv::Val();
			if (Z_TYPE_P(returnTypeNode) != IS_NULL) {
				zv::Val nativeReturnType = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), returnTypeNode, false, false);
				if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
				returnType = pt_mutating_scope_intersect_but_not_never(nativeReturnType.raw(), returnType.raw());
				if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			}
		}

		zval *uses = ptclosure::prop(ptclosure::usesSite, expr, PT_LC("uses"));
		if (UNEXPECTED(uses == NULL)) return zv::Val();
		zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
		zv::Arr usedVariables = zv::Arr::empty();
		bool hasByRefUse = false;
		if (EXPECTED(Z_TYPE_P(usesHold.raw()) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(usesHold.raw())) {
				zval *var = ptclosure::prop(ptclosure::useVarSite, entry.value().deref().raw(), PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return zv::Val();
				zval *name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				if (Z_TYPE_P(name) != IS_STRING) continue;
				usedVariables.push(zv::Ref(name));
			}
			for (zv::ArrayEntry entry : zv::ArrRef(usesHold.raw())) {
				zval *byRef = ptclosure::prop(ptclosure::useByRefSite, entry.value().deref().raw(), PT_LC("byRef"));
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				if (!zend_is_true(byRef)) continue;
				hasByRefUse = true;
				break;
			}
		}

		return assembleClosureType(scope, expr, parameters, isVariadic, returnType.raw(), throwPoints, impurePoints, hasByRefUse, invalidateExpressions, usedVariables.raw(), cacheKey);
	}

	/* the key and value types of a Yield_ / YieldFrom node read on readScope;
	 * false = pending exception */
	[[nodiscard]] static bool yieldTypes(zval *storage, zval *yieldNode, zval *readScope, bool native, zv::Val &keyType, zv::Val &valueType)
	{
		int isYield = ptclosure::instanceOf(yieldNode, PT_CLASS_YIELD);
		if (UNEXPECTED(isYield < 0)) return false;
		if (isYield) {
			zval *key = ptclosure::prop(pt_ctr_yield_key_site, yieldNode, PT_LC("key"));
			if (UNEXPECTED(key == NULL)) return false;
			if (Z_TYPE_P(key) == IS_NULL) {
				zval integerType;
				if (UNEXPECTED(!pt_integer_type_new(&integerType))) return false;
				keyType = zv::Val::adopt(integerType);
			} else {
				zv::Val keyHold = zv::Val::copyOf(zv::Ref(key));
				keyType = readExprType(storage, keyHold.raw(), readScope, native);
				if (UNEXPECTED(keyType.isUndef())) return false;
			}
			zval *value = ptclosure::prop(pt_ctr_yield_value_site, yieldNode, PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return false;
			if (Z_TYPE_P(value) == IS_NULL) {
				zval nullType;
				if (UNEXPECTED(!pt_null_type_new(&nullType))) return false;
				valueType = zv::Val::adopt(nullType);
			} else {
				zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
				valueType = readExprType(storage, valueHold.raw(), readScope, native);
				if (UNEXPECTED(valueType.isUndef())) return false;
			}
			return true;
		}

		zval *fromExpr = ptclosure::prop(pt_ctr_yield_from_expr_site, yieldNode, PT_LC("expr"));
		if (UNEXPECTED(fromExpr == NULL)) return false;
		zv::Val fromHold = zv::Val::copyOf(zv::Ref(fromExpr));
		zv::Val yieldFromType = readExprType(storage, fromHold.raw(), readScope, native);
		if (UNEXPECTED(yieldFromType.isUndef())) return false;
		if (UNEXPECTED(!yieldFromType.ref().isObject())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getIterableKeyType(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(yieldFromType.raw()));
			return false;
		}
		keyType = pt_mutating_scope_get_iterable_key_type(Z_OBJ_P(readScope), yieldFromType.raw());
		if (UNEXPECTED(keyType.isUndef())) return false;
		valueType = pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(readScope), yieldFromType.raw());
		return !valueType.isUndef();
	}

	/* new GenericObjectType(Generator::class, [$keyType, $valueType, new MixedType(), $returnType]) */
	static zv::Val generatorType(zval *keyType, zval *valueType, zval *returnType)
	{
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(4);
		types.push(zv::Ref(keyType));
		types.push(zv::Ref(valueType));
		types.push(std::move(mixed));
		types.push(zv::Ref(returnType));
		zval out;
		if (UNEXPECTED(!pt_generic_object_type_new(&out, pt_ctr_generator, types.raw()))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* Mirrors resolveArrowFunctionReturnType() ($storage NULL for null) */
	static zv::Val resolveArrowFunctionReturnType(zval *scope, zval *arrowScope, zval *expr, bool native, zval *storage)
	{
		// Unlike a closure (whose native type equals its phpdoc type), an arrow
		// function's native return type is the body expression's native type.
		zv::Val nativeArrowScope;
		zval *readScope = arrowScope;
		if (native) {
			nativeArrowScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(arrowScope));
			if (UNEXPECTED(nativeArrowScope.isUndef())) return zv::Val();
			readScope = nativeArrowScope.raw();
		}

		zval *body = ptclosure::prop(ptclosure::arrowExprSite, expr, PT_LC("expr"));
		if (UNEXPECTED(body == NULL)) return zv::Val();
		zv::Val bodyHold = zv::Val::copyOf(zv::Ref(body));
		int isYield = ptclosure::instanceOf(bodyHold.raw(), PT_CLASS_YIELD);
		if (UNEXPECTED(isYield < 0)) return zv::Val();
		if (!isYield) {
			isYield = ptclosure::instanceOf(bodyHold.raw(), PT_CLASS_YIELD_FROM);
			if (UNEXPECTED(isYield < 0)) return zv::Val();
		}
		if (isYield) {
			zv::Val keyType, valueType;
			if (UNEXPECTED(!yieldTypes(storage, bodyHold.raw(), readScope, native, keyType, valueType))) return zv::Val();
			zval voidType;
			if (UNEXPECTED(!pt_void_type_new(&voidType))) return zv::Val();
			zv::Val voidHold = zv::Val::adopt(voidType);
			return generatorType(keyType.raw(), valueType.raw(), voidHold.raw());
		}

		// prefer the stored result of the single body walk - it carries the
		// extension-resolved type (dynamic return type extensions)
		zv::Val storedBodyResult = zv::Val::null();
		if (storage != NULL) {
			storedBodyResult = pt_expression_result_storage_find(storage, bodyHold.raw());
			if (UNEXPECTED(storedBodyResult.isUndef())) return zv::Val();
		}
		zv::Val returnType;
		if (!storedBodyResult.isNull()) {
			bool promoted = false;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(readScope), promoted))) return zv::Val();
			returnType = pt_expression_result_get_keep_void_type(storedBodyResult.raw(), promoted);
		} else {
			returnType = pt_mutating_scope_get_keep_void_type(Z_OBJ_P(readScope), bodyHold.raw());
		}
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		zval *returnTypeNode = ptclosure::prop(ptclosure::returnTypeSite, expr, PT_LC("returnType"));
		if (UNEXPECTED(returnTypeNode == NULL)) return zv::Val();
		if (Z_TYPE_P(returnTypeNode) != IS_NULL) {
			zv::Val nativeReturnType = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), returnTypeNode, false, false);
			if (UNEXPECTED(nativeReturnType.isUndef())) return zv::Val();
			returnType = pt_mutating_scope_intersect_but_not_never(nativeReturnType.raw(), returnType.raw());
		}
		return returnType;
	}

	/* Mirrors deriveOnlyNeverExecutionEnds(): -1 null, 0 false, 1 true; -2 =
	 * pending exception */
	static int deriveOnlyNeverExecutionEnds(zval *executionEnds)
	{
		int onlyNever = -1;
		if (UNEXPECTED(Z_TYPE_P(executionEnds) != IS_ARRAY)) return onlyNever;
		for (zv::ArrayEntry entry : zv::ArrRef(executionEnds)) {
			zval *node = entry.value().deref().raw();
			zval *statementResult = ptclosure::prop(pt_ctr_execution_end_statement_result_site, node, PT_LC("statementResult"));
			if (UNEXPECTED(statementResult == NULL)) return -2;
			bool alwaysTerminating = false;
			if (UNEXPECTED(!pt_statement_result_is_always_terminating(statementResult, alwaysTerminating))) return -2;
			if (!alwaysTerminating) {
				onlyNever = 0;
				continue;
			}
			zv::Val exitPointsHold;
			zval *exitPoints = pt_statement_result_exit_points(statementResult, exitPointsHold);
			if (UNEXPECTED(exitPoints == NULL)) return -2;
			if (EXPECTED(Z_TYPE_P(exitPoints) == IS_ARRAY)) {
				for (zv::ArrayEntry exitEntry : zv::ArrRef(exitPoints)) {
					zv::Val statementHold;
					zval *statement = pt_statement_exit_point_statement(exitEntry.value().deref().raw(), statementHold);
					if (UNEXPECTED(statement == NULL)) return -2;
					int isReturn = ptclosure::instanceOf(statement, PT_CLASS_RETURN_STMT);
					if (UNEXPECTED(isReturn < 0)) return -2;
					if (isReturn) {
						onlyNever = 0;
						continue;
					}
					if (onlyNever == -1) {
						onlyNever = 1;
					}
					break;
				}
			}
			zv::Val countHold;
			zval *countedExitPoints = pt_statement_result_exit_points(statementResult, countHold);
			if (UNEXPECTED(countedExitPoints == NULL)) return -2;
			if (Z_TYPE_P(countedExitPoints) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(countedExitPoints)) == 0 && onlyNever == -1) {
				onlyNever = 1;
			}
		}
		return onlyNever;
	}

	/* Mirrors buildDeclaredParameters(): the list and $isVariadic */
	[[nodiscard]] bool buildDeclaredParameters(zval *scope, zval *expr, zv::Val &parameters, bool &isVariadic)
	{
		isVariadic = false;
		zval *params = ptclosure::prop(ptclosure::paramsSite, expr, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return false;
		zv::Val paramsHold = zv::Val::copyOf(zv::Ref(params));
		if (UNEXPECTED(Z_TYPE_P(paramsHold.raw()) != IS_ARRAY) || zend_hash_num_elements(Z_ARRVAL_P(paramsHold.raw())) == 0) {
			parameters = zv::Val(zv::Arr::empty());
			return true;
		}

		/* the index from which every parameter is an optional candidate
		 * (-1: none); the keys of a parameter list are its positions */
		zend_long firstOptionalParameterIndex = -1;
		for (zv::ArrayEntry entry : zv::ArrRef(paramsHold.raw())) {
			zval *param = entry.value().deref().raw();
			zval *defaultValue = ptclosure::prop(ptclosure::paramDefaultSite, param, PT_LC("default"));
			if (UNEXPECTED(defaultValue == NULL)) return false;
			bool isOptionalCandidate = Z_TYPE_P(defaultValue) != IS_NULL;
			if (!isOptionalCandidate) {
				zval *variadic = ptclosure::prop(ptclosure::paramVariadicSite, param, PT_LC("variadic"));
				if (UNEXPECTED(variadic == NULL)) return false;
				isOptionalCandidate = zend_is_true(variadic);
			}
			if (isOptionalCandidate) {
				if (firstOptionalParameterIndex == -1) {
					firstOptionalParameterIndex = (zend_long) entry.indexKey();
				}
			} else {
				firstOptionalParameterIndex = -1;
			}
		}

		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return false;
		zval *initializerExprTypeResolver = OBJ_PROP_NUM(self, slots::initializerExprTypeResolver);
		zv::Arr list = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(paramsHold.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(paramsHold.raw())) {
			zval *param = entry.value().deref().raw();
			zval *variadic = ptclosure::prop(ptclosure::paramVariadicSite, param, PT_LC("variadic"));
			if (UNEXPECTED(variadic == NULL)) return false;
			bool paramVariadic = zend_is_true(variadic);
			if (paramVariadic) {
				isVariadic = true;
			}
			zval *var = ptclosure::prop(ptclosure::paramVarSite, param, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return false;
			zval *name = NULL;
			if (Z_TYPE_P(var) == IS_OBJECT && instanceof_function(Z_OBJCE_P(var), variableCe)) {
				name = ptclosure::prop(ptclosure::variableNameSite, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return false;
			}
			if (name == NULL || Z_TYPE_P(name) != IS_STRING) {
				pt_throw_should_not_happen();
				return false;
			}
			zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
			zend_long index = (zend_long) entry.indexKey();
			bool optional = firstOptionalParameterIndex != -1 && index >= firstOptionalParameterIndex;

			bool nullable = false;
			if (UNEXPECTED(!pt_mutating_scope_is_parameter_value_nullable(Z_OBJ_P(scope), param, nullable))) return false;
			zval *typeNode = ptclosure::prop(ptclosure::paramTypeSite, param, PT_LC("type"));
			if (UNEXPECTED(typeNode == NULL)) return false;
			zv::Val typeNodeHold = zv::Val::copyOf(zv::Ref(typeNode));
			zv::Val type = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), typeNodeHold.raw(), nullable, false);
			if (UNEXPECTED(type.isUndef())) return false;

			zval *byRef = ptclosure::prop(ptclosure::paramByRefSite, param, PT_LC("byRef"));
			if (UNEXPECTED(byRef == NULL)) return false;
			zend_object *passedByReference = zend_is_true(byRef) ? pt_passed_by_reference_create_creates_new_variable() : pt_passed_by_reference_create_no();
			if (UNEXPECTED(passedByReference == NULL)) return false;

			// a default is a constant expression - price it without a scope
			// walk, the same way parameter defaults are priced elsewhere
			zval *defaultValue = ptclosure::prop(ptclosure::paramDefaultSite, param, PT_LC("default"));
			if (UNEXPECTED(defaultValue == NULL)) return false;
			zv::Val defaultType = zv::Val::null();
			if (Z_TYPE_P(defaultValue) != IS_NULL) {
				zv::Val defaultHold = zv::Val::copyOf(zv::Ref(defaultValue));
				defaultType = initializerExprType(initializerExprTypeResolver, defaultHold.raw(), scope);
				if (UNEXPECTED(defaultType.isUndef())) return false;
			}

			zval passedByReferenceZv;
			ZVAL_OBJ(&passedByReferenceZv, passedByReference);
			zv::Args argv{nameHold.raw(), optional, type.raw(), &passedByReferenceZv, paramVariadic, defaultType.raw()};
			zv::Val parameter = pt_native_parameter_reflection_new(6, argv);
			if (UNEXPECTED(parameter.isUndef())) return false;
			list.push(std::move(parameter));
		}
		parameters = zv::Val(std::move(list));
		return true;
	}

	/* Mirrors buildParametersAndAcceptors() (the nullable ones NULL for null) */
	[[nodiscard]] bool buildParametersAndAcceptors(zval *scope, zval *expr, zval *storage, zval *passedToType, zval *nativePassedToType, Parameters &p)
	{
		if (UNEXPECTED(!buildDeclaredParameters(scope, expr, p.parameters, p.isVariadic))) return false;
		zv::Val passedToTypeHold, nativePassedToTypeHold;
		if (passedToType == NULL) {
			zval *stack = pt_mutating_scope_in_function_calls_stack(Z_OBJ_P(scope));
			if (UNEXPECTED(stack == NULL)) return false;
			uint32_t count = Z_TYPE_P(stack) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(stack)) : 0;
			if (count > 0) {
				zval *top = zend_hash_index_find(Z_ARRVAL_P(stack), count - 1);
				zval *inParameter = NULL;
				if (EXPECTED(top != NULL)) {
					ZVAL_DEREF(top);
					inParameter = Z_TYPE_P(top) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(top), 1) : NULL;
				}
				if (inParameter != NULL) {
					ZVAL_DEREF(inParameter);
				}
				if (inParameter != NULL && Z_TYPE_P(inParameter) != IS_NULL) {
					zv::Val inParameterHold = zv::Val::copyOf(zv::Ref(inParameter));
					passedToTypeHold = ptclosure::parameterGetType(inParameterHold.raw());
					if (UNEXPECTED(passedToTypeHold.isUndef())) return false;
					int isExtended = ptclosure::instanceOf(inParameterHold.raw(), PT_CLASS_EXTENDED_PARAMETER_REFLECTION);
					if (UNEXPECTED(isExtended < 0)) return false;
					nativePassedToTypeHold = isExtended ? ptclosure::parameterGetNativeType(inParameterHold.raw()) : ptclosure::parameterGetType(inParameterHold.raw());
					if (UNEXPECTED(nativePassedToTypeHold.isUndef())) return false;
					passedToType = passedToTypeHold.raw();
					nativePassedToType = nativePassedToTypeHold.raw();
				}
			}
		}
		return pt_contextual_closure_parameter_resolver_resolve(OBJ_PROP_NUM(self, slots::contextualClosureParameterResolver), scope, expr, storage, passedToType, nativePassedToType, p.callableParameters, p.nativeCallableParameters);
	}

	/* Mirrors createClosureTypeFromCache() */
	static zv::Val createClosureTypeFromCache(zval *expr, zval *parameters, bool isVariadic, zval *cachedClosureData)
	{
		zval *mustUseReturnValue = mustUseReturnValueOf(expr);
		if (UNEXPECTED(mustUseReturnValue == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(cachedClosureData) != IS_ARRAY)) {
			zend_throw_error(NULL, "Cannot read the cached closure data");
			return zv::Val();
		}
		HashTable *data = Z_ARRVAL_P(cachedClosureData);
		zval *returnType = zend_hash_find(data, pt_ctr_return_type);
		zval *throwPoints = zend_hash_find(data, pt_ctr_throw_points);
		zval *impurePoints = zend_hash_find(data, pt_ctr_impure_points);
		zval *invalidateExpressions = zend_hash_find(data, pt_ctr_invalidate_expressions);
		zval *usedVariables = zend_hash_find(data, pt_ctr_used_variables);
		if (UNEXPECTED(returnType == NULL || throwPoints == NULL || impurePoints == NULL || invalidateExpressions == NULL || usedVariables == NULL)) {
			zend_throw_error(NULL, "Cannot read the cached closure data");
			return zv::Val();
		}
		/* the cached arrays kept alive over the construction */
		zv::Val entryHold = zv::Val::copyOf(zv::Ref(cachedClosureData));
		return newClosureType(expr, parameters, returnType, isVariadic, throwPoints, impurePoints, invalidateExpressions, usedVariables, mustUseReturnValue);
	}

	/* new ClosureType($parameters, $returnType, $isVariadic,
	 * TemplateTypeMap::createEmpty(), TemplateTypeMap::createEmpty(),
	 * TemplateTypeVarianceMap::createEmpty(), throwPoints: ..., impurePoints:
	 * ..., invalidateExpressions: ..., usedVariables: ...,
	 * acceptsNamedArguments: TrinaryLogic::createYes(), mustUseReturnValue:
	 * ..., isStatic: TrinaryLogic::createFromBoolean($expr->static)) */
	static zv::Val newClosureType(zval *expr, zval *parameters, zval *returnType, bool isVariadic, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *usedVariables, zval *mustUseReturnValue)
	{
		zval templateTypeMap, resolvedTemplateTypeMap, callSiteVarianceMap;
		if (UNEXPECTED(!pt_template_type_map_empty(&templateTypeMap))) return zv::Val();
		zv::Val templateTypeMapHold = zv::Val::adopt(templateTypeMap);
		if (UNEXPECTED(!pt_template_type_map_empty(&resolvedTemplateTypeMap))) return zv::Val();
		zv::Val resolvedTemplateTypeMapHold = zv::Val::adopt(resolvedTemplateTypeMap);
		if (UNEXPECTED(!pt_template_type_variance_map_empty(&callSiteVarianceMap))) return zv::Val();
		zv::Val callSiteVarianceMapHold = zv::Val::adopt(callSiteVarianceMap);
		zval *isStatic = ptclosure::prop(ptclosure::staticSite, expr, PT_LC("static"));
		if (UNEXPECTED(isStatic == NULL)) return zv::Val();
		zval out;
		if (UNEXPECTED(!pt_closure_type_new(&out, parameters, returnType, isVariadic, templateTypeMapHold.raw(), resolvedTemplateTypeMapHold.raw(), callSiteVarianceMapHold.raw(), NULL, throwPoints, impurePoints, invalidateExpressions, usedVariables, pt_trinary_singleton(PT_TRI_YES), mustUseReturnValue, NULL, ptclosure::trinaryFromBool(zend_is_true(isStatic))))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* array_map(static fn (ThrowPoint $throwPoint) => $throwPoint->isExplicit()
	 * ? SimpleThrowPoint::createExplicit($throwPoint->getType(),
	 * $throwPoint->canContainAnyThrowable()) : SimpleThrowPoint::createImplicit(),
	 * $throwPoints) */
	static zv::Val simpleThrowPointsOf(zval *throwPoints)
	{
		if (UNEXPECTED(Z_TYPE_P(throwPoints) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(throwPoints));
			return zv::Val();
		}
		HashTable *table = Z_ARRVAL_P(throwPoints);
		uint32_t count = zend_hash_num_elements(table);
		if (count == 0) return zv::Val(zv::Arr::empty());
		zv::Arr mapped = zv::Arr::create(count);
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zval *throwPoint = entry.value().deref().raw();
			bool isExplicit = false;
			if (UNEXPECTED(!pt_throw_point_is_explicit(throwPoint, isExplicit))) return zv::Val();
			zv::Val simple;
			if (isExplicit) {
				zv::Val typeHold;
				zval *type = pt_throw_point_type(throwPoint, typeHold);
				if (UNEXPECTED(type == NULL)) return zv::Val();
				bool canContainAnyThrowable = false;
				if (UNEXPECTED(!pt_throw_point_can_contain_any_throwable(throwPoint, canContainAnyThrowable))) return zv::Val();
				simple = simpleThrowPointExplicit(type, canContainAnyThrowable);
			} else {
				simple = simpleThrowPointImplicit();
			}
			if (UNEXPECTED(simple.isUndef())) return zv::Val();
			if (entry.hasStringKey()) {
				mapped.set(entry.stringKey(), std::move(simple));
			} else {
				zval value = simple.take();
				zend_hash_index_update(mapped.table(), entry.indexKey(), &value);
			}
		}
		return zv::Val(std::move(mapped));
	}

	/* array_map(static fn (ImpurePoint $impurePoint) => new
	 * SimpleImpurePoint($impurePoint->getIdentifier(),
	 * $impurePoint->getDescription(), $impurePoint->isCertain()), $impurePoints)
	 * with the by-ref-use and by-ref-parameter impure points the twin appends
	 * first mapped after them */
	static zv::Val simpleImpurePointsOf(zval *impurePoints, bool byRefUse, uint32_t byRefParameters)
	{
		if (UNEXPECTED(Z_TYPE_P(impurePoints) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(impurePoints));
			return zv::Val();
		}
		HashTable *table = Z_ARRVAL_P(impurePoints);
		uint32_t count = zend_hash_num_elements(table) + (byRefUse ? 1 : 0) + byRefParameters;
		if (count == 0) return zv::Val(zv::Arr::empty());
		zv::Arr mapped = zv::Arr::create(count);
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zval *impurePoint = entry.value().deref().raw();
			zv::Val identifierHold, descriptionHold;
			zval *identifier = pt_impure_point_identifier(impurePoint, identifierHold);
			if (UNEXPECTED(identifier == NULL)) return zv::Val();
			zval *description = pt_impure_point_description(impurePoint, descriptionHold);
			if (UNEXPECTED(description == NULL)) return zv::Val();
			bool certain = false;
			if (UNEXPECTED(!pt_impure_point_is_certain(impurePoint, certain))) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(identifier) != IS_STRING || Z_TYPE_P(description) != IS_STRING)) {
				zend_type_error("PHPStan\\Reflection\\Callables\\SimpleImpurePoint::__construct(): Argument #%d must be of type string, %s given", Z_TYPE_P(identifier) != IS_STRING ? 1 : 2, zend_zval_value_name(Z_TYPE_P(identifier) != IS_STRING ? identifier : description));
				return zv::Val();
			}
			zv::Val simple = pt_simple_impure_point_new(Z_STR_P(identifier), Z_STR_P(description), certain);
			if (UNEXPECTED(simple.isUndef())) return zv::Val();
			if (entry.hasStringKey()) {
				mapped.set(entry.stringKey(), std::move(simple));
			} else {
				zval value = simple.take();
				zend_hash_index_update(mapped.table(), entry.indexKey(), &value);
			}
		}
		for (uint32_t i = 0; i < (byRefUse ? 1u : 0u) + byRefParameters; i++) {
			zv::Val simple = pt_simple_impure_point_new(pt_ctr_function_call, byRefUse && i == 0 ? pt_ctr_by_ref_use : pt_ctr_by_ref_parameter, true);
			if (UNEXPECTED(simple.isUndef())) return zv::Val();
			mapped.push(std::move(simple));
		}
		return zv::Val(std::move(mapped));
	}

	/* Mirrors assembleClosureType() — byRefUse: the by-ref-use impure point
	 * buildClosureTypeFromClosureWalk() appends ($cacheKey NULL for null) */
	zv::Val assembleClosureType(zval *scope, zval *expr, zval *parameters, bool isVariadic, zval *returnType, zval *throwPoints, zval *impurePoints, bool byRefUse, zval *invalidateExpressions, zval *usedVariables, zend_string *cacheKey)
	{
		uint32_t byRefParameters = 0;
		if (EXPECTED(Z_TYPE_P(parameters) == IS_ARRAY)) {
			for (zv::ArrayEntry entry : zv::ArrRef(parameters)) {
				zval *parameter = entry.value().deref().raw();
				zv::Val passedByReference = ptclosure::parameterPassedByReference(parameter);
				if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
				zend_long mode = pt_passed_by_reference_mode(passedByReference.raw());
				if (UNEXPECTED(mode < 0)) return zv::Val();
				if (mode == PT_PASSED_BY_REFERENCE_NO) continue;
				byRefParameters++;
			}
		}

		zv::Val throwPointsForClosureType = simpleThrowPointsOf(throwPoints);
		if (UNEXPECTED(throwPointsForClosureType.isUndef())) return zv::Val();
		zv::Val impurePointsForClosureType = simpleImpurePointsOf(impurePoints, byRefUse, byRefParameters);
		if (UNEXPECTED(impurePointsForClosureType.isUndef())) return zv::Val();

		zv::Str ownKey;
		if (cacheKey == NULL) {
			ownKey = closureContextCacheKey(scope, expr, NULL, parameters);
			if (UNEXPECTED(ownKey.isNull())) return zv::Val();
			cacheKey = ownKey.get();
		}
		storeCachedTypes(expr, cacheKey, returnType, throwPointsForClosureType.raw(), impurePointsForClosureType.raw(), invalidateExpressions, usedVariables);

		zval *mustUseReturnValue = mustUseReturnValueOf(expr);
		if (UNEXPECTED(mustUseReturnValue == NULL)) return zv::Val();
		return newClosureType(expr, parameters, returnType, isVariadic, throwPointsForClosureType.raw(), impurePointsForClosureType.raw(), invalidateExpressions, usedVariables, mustUseReturnValue);
	}

	/* $cachedTypes = $this->findCachedTypes($expr); $cachedTypes[$cacheKey] =
	 * [...]; $this->cachedTypes[spl_object_id($expr)] = ['expr' => $expr,
	 * 'types' => $cachedTypes] — in place */
	void storeCachedTypes(zval *expr, zend_string *cacheKey, zval *returnType, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, zval *usedVariables)
	{
		zv::Arr data = zv::Arr::create(5);
		data.set(pt_ctr_return_type, zv::Val::copyOf(zv::Ref(returnType)));
		data.set(pt_ctr_throw_points, zv::Val::copyOf(zv::Ref(throwPoints)));
		data.set(pt_ctr_impure_points, zv::Val::copyOf(zv::Ref(impurePoints)));
		data.set(pt_ctr_invalidate_expressions, zv::Val::copyOf(zv::Ref(invalidateExpressions)));
		data.set(pt_ctr_used_variables, zv::Val::copyOf(zv::Ref(usedVariables)));

		zval *cachedTypes = OBJ_PROP_NUM(self, slots::cachedTypes);
		if (UNEXPECTED(Z_TYPE_P(cachedTypes) != IS_ARRAY)) {
			zv::ObjRef(self).propAtWrite(slots::cachedTypes, zv::Val(zv::Arr::empty()));
			cachedTypes = OBJ_PROP_NUM(self, slots::cachedTypes);
		}
		SEPARATE_ARRAY(cachedTypes);
		zend_ulong id = Z_OBJ_HANDLE_P(expr);
		zval *entry = zend_hash_index_find(Z_ARRVAL_P(cachedTypes), id);
		if (entry != NULL && Z_TYPE_P(entry) == IS_ARRAY) {
			zval *entryExpr = zend_hash_find(Z_ARRVAL_P(entry), pt_ctr_expr);
			zval *types = zend_hash_find(Z_ARRVAL_P(entry), pt_ctr_types);
			if (entryExpr != NULL && types != NULL && Z_TYPE_P(entryExpr) == IS_OBJECT && Z_OBJ_P(entryExpr) == Z_OBJ_P(expr) && Z_TYPE_P(types) == IS_ARRAY) {
				SEPARATE_ARRAY(entry);
				types = zend_hash_find(Z_ARRVAL_P(entry), pt_ctr_types);
				SEPARATE_ARRAY(types);
				zval value = data.take();
				zend_symtable_update(Z_ARRVAL_P(types), cacheKey, &value);
				return;
			}
		}
		zv::Arr types = zv::Arr::create(1);
		types.set(cacheKey, std::move(data));
		zv::Arr newEntry = zv::Arr::create(2);
		newEntry.set(pt_ctr_expr, zv::Val::copyOf(zv::Ref(expr)));
		newEntry.set(pt_ctr_types, std::move(types));
		zval value = newEntry.take();
		zend_hash_index_update(Z_ARRVAL_P(cachedTypes), id, &value);
	}

	/* the arrow-function body walk of getClosureType() */
	zv::Val walkArrowFunction(zval *scope, zval *expr, Parameters &p, zend_string *cacheKey)
	{
		zval *body = ptclosure::prop(ptclosure::arrowExprSite, expr, PT_LC("expr"));
		if (UNEXPECTED(body == NULL)) return zv::Val();
		zv::Val bodyHold = zv::Val::copyOf(zv::Ref(body));
		zval null;
		ZVAL_NULL(&null);
		zv::Val arrowScope = pt_mutating_scope_enter_arrow_function_without_reflection(Z_OBJ_P(scope), expr, p.callableParameters.isNull() ? &null : p.callableParameters.raw(), p.nativeCallableParameters.isNull() ? &null : p.nativeCallableParameters.raw());
		if (UNEXPECTED(arrowScope.isUndef())) return zv::Val();
		if (UNEXPECTED(!arrowScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function pushExpressionResultStorage() on %s", zend_zval_value_name(arrowScope.raw()));
			return zv::Val();
		}

		zv::Val arrowFunctionImpurePoints = ptsh::newArrayReference();
		zv::Val invalidateExpressions = ptsh::newArrayReference();
		pt_ctr_resolve_closure_type_depth++;
		zv::Val walkStorage = pt_expression_result_storage_new();
		if (UNEXPECTED(walkStorage.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(arrowScope.raw()), walkStorage.raw()))) return zv::Val();
		zv::Val exprResult;
		{
			zv::Val stmt = pt_type_new(PT_CLASS_EXPRESSION_STMT, 1, bodyHold.raw());
			if (EXPECTED(!stmt.isUndef())) {
				zval captures[3];
				ZVAL_COPY_VALUE(&captures[0], arrowScope.raw());
				ZVAL_COPY_VALUE(&captures[1], arrowFunctionImpurePoints.raw());
				ZVAL_COPY_VALUE(&captures[2], invalidateExpressions.raw());
				zv::Val callback = pt_native_closure_new(&arrowFunctionWalkCallbackBody, 3, captures, 0b110);
				zv::Val context = pt_expression_context_create_deep();
				if (EXPECTED(!context.isUndef())) {
					exprResult = pt_node_scope_resolver_process_expr_node(OBJ_PROP_NUM(self, slots::nodeScopeResolver), stmt.raw(), bodyHold.raw(), arrowScope.raw(), walkStorage.raw(), callback.raw(), context.raw());
				}
			}
		}
		pt_finally([&]() {
			(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(arrowScope.raw()));
			pt_ctr_resolve_closure_type_depth--;
		});
		if (UNEXPECTED(exprResult.isUndef() || EG(exception) != NULL)) return zv::Val();

		zv::Val throwPointsHold, impurePointsHold;
		zval *resultThrowPoints = pt_expression_result_throw_points(exprResult.raw(), throwPointsHold);
		if (UNEXPECTED(resultThrowPoints == NULL)) return zv::Val();
		zv::Val throwPoints = throwPointsToPublic(resultThrowPoints);
		if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
		zval *resultImpurePoints = pt_expression_result_impure_points(exprResult.raw(), impurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return zv::Val();
		zv::Val impurePoints = mergeLists(Z_REFVAL_P(arrowFunctionImpurePoints.raw()), resultImpurePoints);
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		// the body was processed just above; resolve the return type from its stored
		// result rather than reading the still-unprocessed body expression
		zv::Val returnType = resolveArrowFunctionReturnType(scope, arrowScope.raw(), expr, false, walkStorage.raw());
		if (UNEXPECTED(returnType.isUndef())) return zv::Val();

		zv::Arr usedVariables = zv::Arr::empty();
		return assembleClosureType(scope, expr, p.parameters.raw(), p.isVariadic, returnType.raw(), throwPoints.raw(), impurePoints.raw(), false, Z_REFVAL_P(invalidateExpressions.raw()), usedVariables.raw(), cacheKey);
	}

	/* the closure body walk of getClosureType() */
	zv::Val walkClosure(zval *scope, zval *expr, Parameters &p, zend_string *cacheKey)
	{
		pt_ctr_resolve_closure_type_depth++;

		zval null;
		ZVAL_NULL(&null);
		zv::Val closureScope = pt_mutating_scope_enter_anonymous_function_without_reflection(Z_OBJ_P(scope), expr, p.callableParameters.isNull() ? &null : p.callableParameters.raw(), p.nativeCallableParameters.isNull() ? &null : p.nativeCallableParameters.raw());
		if (UNEXPECTED(closureScope.isUndef())) return zv::Val();
		if (UNEXPECTED(!closureScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function pushExpressionResultStorage() on %s", zend_zval_value_name(closureScope.raw()));
			return zv::Val();
		}
		zv::Val closureReturnStatements = ptsh::newArrayReference();
		zv::Val closureYieldStatements = ptsh::newArrayReference();
		zv::Val closureExecutionEnds = ptsh::newArrayReference();
		zv::Val closureImpurePoints = ptsh::newArrayReference();
		zv::Val invalidateExpressions = ptsh::newArrayReference();

		zv::Val walkStorage;
		zv::Val closureStatementResult;
		{
			walkStorage = pt_expression_result_storage_new();
			if (EXPECTED(!walkStorage.isUndef()) && EXPECTED(pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(closureScope.raw()), walkStorage.raw()))) {
				zval *stmts = ptclosure::prop(ptclosure::stmtsSite, expr, PT_LC("stmts"));
				if (EXPECTED(stmts != NULL)) {
					zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
					zval captures[6];
					ZVAL_COPY_VALUE(&captures[0], closureScope.raw());
					ZVAL_COPY_VALUE(&captures[1], closureReturnStatements.raw());
					ZVAL_COPY_VALUE(&captures[2], closureYieldStatements.raw());
					ZVAL_COPY_VALUE(&captures[3], closureExecutionEnds.raw());
					ZVAL_COPY_VALUE(&captures[4], closureImpurePoints.raw());
					ZVAL_COPY_VALUE(&captures[5], invalidateExpressions.raw());
					zv::Val callback = pt_native_closure_new(&closureWalkCallbackBody, 6, captures, 0b111110);
					zv::Val context = pt_statement_context_create_top_level();
					if (EXPECTED(!context.isUndef())) {
						zv::Val internalResult = pt_node_scope_resolver_process_stmt_nodes_internal(OBJ_PROP_NUM(self, slots::nodeScopeResolver), expr, stmtsHold.raw(), closureScope.raw(), walkStorage.raw(), callback.raw(), context.raw());
						if (EXPECTED(!internalResult.isUndef())) {
							if (UNEXPECTED(!internalResult.ref().isObject())) {
								zend_throw_error(NULL, "Call to a member function toPublic() on %s", zend_zval_value_name(internalResult.raw()));
							} else {
								closureStatementResult = pt_internal_statement_result_to_public(internalResult.raw());
							}
						}
					}
				}
			}
		}
		pt_finally([&]() {
			(void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(closureScope.raw()));
			pt_ctr_resolve_closure_type_depth--;
		});
		if (UNEXPECTED(closureStatementResult.isUndef() || EG(exception) != NULL)) return zv::Val();

		zv::Val throwPointsHold, impurePointsHold;
		zval *throwPoints = pt_statement_result_throw_points(closureStatementResult.raw(), throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		zv::Val throwPointsOwned = zv::Val::copyOf(zv::Ref(throwPoints));
		zval *resultImpurePoints = pt_statement_result_impure_points(closureStatementResult.raw(), impurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return zv::Val();
		zv::Val impurePoints = mergeLists(Z_REFVAL_P(closureImpurePoints.raw()), resultImpurePoints);
		if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();

		return buildClosureTypeFromClosureWalk(scope, expr, p.parameters.raw(), p.isVariadic, Z_REFVAL_P(closureReturnStatements.raw()), Z_REFVAL_P(closureYieldStatements.raw()), Z_REFVAL_P(closureExecutionEnds.raw()), throwPointsOwned.raw(), impurePoints.raw(), Z_REFVAL_P(invalidateExpressions.raw()), cacheKey, false, walkStorage.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureTypeResolver;

/* {{{ direct entries (support.h) */

zv::Val pt_closure_type_resolver_get_closure_type(zval *resolver, zval *scope, zval *expr, bool shallow, zval *storage)
{
	if (storage != NULL && Z_TYPE_P(storage) == IS_NULL) storage = NULL;
	if (EXPECTED(Z_TYPE_P(resolver) == IS_OBJECT && Z_OBJCE_P(resolver) == pt_ce_closure_type_resolver)) return ClosureTypeResolver(Z_OBJ_P(resolver)).getClosureType(scope, expr, shallow, storage);
	if (UNEXPECTED(Z_TYPE_P(resolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getClosureType() on %s", zend_zval_value_name(resolver));
		return zv::Val();
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, shallow, storage != NULL ? storage : &null};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("getclosuretype"), 4, argv);
}

zv::Val pt_closure_type_resolver_build_closure_type_for_closure(zval *resolver, zval *scope, zval *expr, zval *returnStatements, zval *yieldStatements, zval *executionEnds, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, bool native, zval *storage, zval *passedToType, zval *nativePassedToType)
{
	if (storage != NULL && Z_TYPE_P(storage) == IS_NULL) storage = NULL;
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_closure_type_resolver && Z_TYPE_P(returnStatements) == IS_ARRAY && Z_TYPE_P(yieldStatements) == IS_ARRAY && Z_TYPE_P(executionEnds) == IS_ARRAY && Z_TYPE_P(throwPoints) == IS_ARRAY && Z_TYPE_P(impurePoints) == IS_ARRAY && Z_TYPE_P(invalidateExpressions) == IS_ARRAY)) {
		return ClosureTypeResolver(Z_OBJ_P(resolver)).buildClosureTypeForClosure(scope, expr, returnStatements, yieldStatements, executionEnds, throwPoints, impurePoints, invalidateExpressions, native, storage, passedToType, nativePassedToType);
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, returnStatements, yieldStatements, executionEnds, throwPoints, impurePoints, invalidateExpressions, native, storage != NULL ? storage : &null, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("buildclosuretypeforclosure"), 12, argv);
}

zv::Val pt_closure_type_resolver_build_closure_type_for_arrow_function(zval *resolver, zval *scope, zval *expr, zval *arrowScope, zval *throwPoints, zval *impurePoints, zval *invalidateExpressions, bool native, zval *storage, zval *passedToType, zval *nativePassedToType)
{
	if (storage != NULL && Z_TYPE_P(storage) == IS_NULL) storage = NULL;
	if (passedToType != NULL && Z_TYPE_P(passedToType) == IS_NULL) passedToType = NULL;
	if (nativePassedToType != NULL && Z_TYPE_P(nativePassedToType) == IS_NULL) nativePassedToType = NULL;
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_closure_type_resolver && Z_TYPE_P(throwPoints) == IS_ARRAY && Z_TYPE_P(impurePoints) == IS_ARRAY && Z_TYPE_P(invalidateExpressions) == IS_ARRAY)) {
		return ClosureTypeResolver(Z_OBJ_P(resolver)).buildClosureTypeForArrowFunction(scope, expr, arrowScope, throwPoints, impurePoints, invalidateExpressions, native, storage, passedToType, nativePassedToType);
	}
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, expr, arrowScope, throwPoints, impurePoints, invalidateExpressions, native, storage != NULL ? storage : &null, passedToType != NULL ? passedToType : &null, nativePassedToType != NULL ? nativePassedToType : &null};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("buildclosuretypeforarrowfunction"), 10, argv);
}

zv::Val pt_closure_type_resolver_get_declared_closure_type(zval *resolver, zval *scope, zval *expr)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_closure_type_resolver)) return ClosureTypeResolver(Z_OBJ_P(resolver)).getDeclaredClosureType(scope, expr);
	zv::Args argv{scope, expr};
	return pt_type_call(Z_OBJ_P(resolver), PT_LC("getdeclaredclosuretype"), 2, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

namespace {

/* the Closure|ArrowFunction parameter checks of the public methods */
[[nodiscard]] bool isClosureLike(zval *expr, bool arrowFunctionOnly, bool closureOnly)
{
	zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
	zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
	if (UNEXPECTED(closureCe == NULL || arrowFunctionCe == NULL)) return false;
	bool ok = (!arrowFunctionOnly && instanceof_function(Z_OBJCE_P(expr), closureCe)) || (!closureOnly && instanceof_function(Z_OBJCE_P(expr), arrowFunctionCe));
	if (UNEXPECTED(!ok)) {
		const char *expected = arrowFunctionOnly ? "PhpParser\\Node\\Expr\\ArrowFunction" : (closureOnly ? "PhpParser\\Node\\Expr\\Closure" : "PhpParser\\Node\\Expr\\Closure|PhpParser\\Node\\Expr\\ArrowFunction");
		zend_type_error("Argument #2 ($expr) must be of type %s, %s given", expected, zend_zval_value_name(expr));
	}
	return ok;
}

} // namespace

void pt_register_closure_type_resolver()
{
	pt_ctr_free_variable_roots = zend_string_init_interned(PT_LC("phpstanFreeVariableRoots"), 1);
	pt_ctr_this_root = zend_string_init_interned(PT_LC("$this"), 1);
	ptclosure::initStrings();
	pt_ctr_function_call = zend_string_init_interned(PT_LC("functionCall"), 1);
	pt_ctr_by_ref_use = zend_string_init_interned(PT_LC("call to a Closure with by-ref use"), 1);
	pt_ctr_by_ref_parameter = zend_string_init_interned(PT_LC("call to a Closure with by-ref parameter"), 1);
	pt_ctr_generator = zend_string_init_interned(PT_LC("Generator"), 1);
	pt_ctr_expr = zend_string_init_interned(PT_LC("expr"), 1);
	pt_ctr_types = zend_string_init_interned(PT_LC("types"), 1);
	pt_ctr_return_type = zend_string_init_interned(PT_LC("returnType"), 1);
	pt_ctr_throw_points = zend_string_init_interned(PT_LC("throwPoints"), 1);
	pt_ctr_impure_points = zend_string_init_interned(PT_LC("impurePoints"), 1);
	pt_ctr_invalidate_expressions = zend_string_init_interned(PT_LC("invalidateExpressions"), 1);
	pt_ctr_used_variables = zend_string_init_interned(PT_LC("usedVariables"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver");
	ptdecl::ClosureTypeResolver::declareClass(cls);
	ptdecl::ClosureTypeResolver::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *initializerExprTypeResolver, *contextualClosureParameterResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, nodeScopeResolver, initializerExprTypeResolver, contextualClosureParameterResolver)) RETURN_THROWS();
		ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).construct(nodeScopeResolver, initializerExprTypeResolver, contextualClosureParameterResolver);
	});

	cls.method(sigs::resetFileAnalysisState, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).resetFileAnalysisState();
	});

	cls.method(sigs::getClosureType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *storage = NULL;
		bool shallow = false;
		ZEND_PARSE_PARAMETERS_START(2, 4)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(shallow)
			Z_PARAM_OBJECT_OR_NULL(storage)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!isClosureLike(expr, false, false))) RETURN_THROWS();
		PT_RETURN_VAL(ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).getClosureType(scope, expr, shallow, storage));
	});

	cls.method(sigs::buildClosureTypeForClosure, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *returnStatements, *yieldStatements, *executionEnds, *throwPoints, *impurePoints, *invalidateExpressions, *storage = NULL, *passedToType = NULL, *nativePassedToType = NULL;
		bool native = false;
		ZEND_PARSE_PARAMETERS_START(8, 12)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_ARRAY(returnStatements)
			Z_PARAM_ARRAY(yieldStatements)
			Z_PARAM_ARRAY(executionEnds)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_ARRAY(invalidateExpressions)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(native)
			Z_PARAM_OBJECT_OR_NULL(storage)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!isClosureLike(expr, false, true))) RETURN_THROWS();
		PT_RETURN_VAL(ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).buildClosureTypeForClosure(scope, expr, returnStatements, yieldStatements, executionEnds, throwPoints, impurePoints, invalidateExpressions, native, storage, passedToType, nativePassedToType));
	});

	cls.method(sigs::buildClosureTypeForArrowFunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *arrowScope, *throwPoints, *impurePoints, *invalidateExpressions, *storage = NULL, *passedToType = NULL, *nativePassedToType = NULL;
		bool native = false;
		ZEND_PARSE_PARAMETERS_START(6, 10)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OF_CLASS(arrowScope, pt_ce_mutating_scope)
			Z_PARAM_ARRAY(throwPoints)
			Z_PARAM_ARRAY(impurePoints)
			Z_PARAM_ARRAY(invalidateExpressions)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(native)
			Z_PARAM_OBJECT_OR_NULL(storage)
			Z_PARAM_OBJECT_OR_NULL(passedToType)
			Z_PARAM_OBJECT_OR_NULL(nativePassedToType)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!isClosureLike(expr, true, false))) RETURN_THROWS();
		PT_RETURN_VAL(ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).buildClosureTypeForArrowFunction(scope, expr, arrowScope, throwPoints, impurePoints, invalidateExpressions, native, storage, passedToType, nativePassedToType));
	});

	cls.method(sigs::getDeclaredClosureType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(scope, pt_ce_mutating_scope)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!isClosureLike(expr, false, false))) RETURN_THROWS();
		PT_RETURN_VAL(ClosureTypeResolver(Z_OBJ_P(ZEND_THIS)).getDeclaredClosureType(scope, expr));
	});

	cls.shadow(&pt_ce_closure_type_resolver);
}

/* }}} */
