/*
 * PHPStanTurbo\ExpressionResult — native implementation of
 * PHPStan\Analyser\ExpressionResult.
 *
 * The state lives in the twin's property slots, declared here in the twin's
 * declaration order (the explicit properties, then the promoted constructor
 * parameters); the constructor keeps the twin's exact arginfo — the DI
 * container reflects it to generate ExpressionResultFactory (parameter
 * names, types and defaults are what Nette pairs the factory's create()
 * parameters with, and #[AutowiredExtensions] requires the native
 * ExtensionsCollection type on the first parameter).
 *
 * The collaborators that stay PHP (MutatingScope, the extensions collection
 * and its extensions, the type/specify/create callbacks, the issetability
 * descriptor) are called through the engine; the Type queries go through
 * the Type ops, late-resolvable resolution and the void->null traverse
 * through the native TypeUtils and TypeTraverser.
 */

#include "support.h"
#include "generated/ExpressionResult.h"

namespace slots = ptdecl::ExpressionResult::slot;
namespace sigs = ptdecl::ExpressionResult::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

#include "zend_closures.h" /* zend_ce_closure */

#include <cstring>

zend_class_entry *pt_ce_expression_result;

/* the property slots, in declaration order */
#define PT_ER_PROP_COUNT 30

namespace {

/* the twin's READ_VARIABLE_NAMES_ATTRIBUTE, a permanent interned string */
zend_string *pt_er_read_variable_names_attribute = nullptr;

/* the constructor's values: NULL stands for a null argument, the bools are
 * plain, the arrays and objects borrowed */
struct ConstructorArgs
{
	zval *expressionTypeResolverExtensions;
	zval *defaultNarrowingHelper;
	zval *scope;
	zval *beforeScope;
	zval *expr;
	bool hasYield;
	bool isAlwaysTerminating;
	zval *throwPoints;
	zval *impurePoints;
	zval *typeCallback;
	zval *specifyTypesCallback;
	bool containsNullsafe = false;
	zval *issetabilityDescriptor = NULL;
	zval *truthyScopeOverrideResult = NULL;
	zval *falseyScopeOverrideResult = NULL;
	zval *createTypesCallback = NULL;
	zval *type = NULL;
	zval *nativeType = NULL;
	zval *argsResult = NULL;
	zval *variableFlow = NULL;
	zval *specifiedTypes = NULL; /* NULL = [] */
	zval *cachedType = NULL;
	bool extensionsDeclined = false;
	zval *cachedNativeType = NULL;
	zval *resolvedType = NULL;
	zval *resolvedNativeType = NULL;
	zval *projectedType = NULL;
	zval *projectedNativeType = NULL;
	zval *readVariableNames = NULL;
};

/* a nullable slot value as a constructor argument: NULL for PHP null */
zval *argOf(zval *slot)
{
	return Z_TYPE_P(slot) == IS_NULL ? NULL : slot;
}

void writeSlot(zend_object *object, uint32_t slot, zval *value)
{
	zval *p = OBJ_PROP_NUM(object, slot);
	if (value != NULL) {
		ZVAL_COPY(p, value);
	} else {
		ZVAL_NULL(p);
	}
}

void writeBoolSlot(zend_object *object, uint32_t slot, bool value)
{
	ZVAL_BOOL(OBJ_PROP_NUM(object, slot), value);
}

/* new ShouldNotHappenException($message) */
void throwShouldNotHappen(const char *message)
{
	zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
	if (ce == NULL) return;
	zend_throw_exception(ce, message, 0);
}

/* $scope->nativeTypesPromoted (a public property of MutatingScope): the slot
 * of a MutatingScope, the property of any other scope object
 * (pt_mutating_scope_native_types_promoted()); false = pending exception */
bool scopeNativeTypesPromoted(zval *scope, bool &out)
{
	return pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), out);
}

/* $scope->doNotTreatPhpDocTypesAsCertain(); UNDEF = pending exception */
zv::Val scopeNativeView(zval *scope)
{
	return pt_type_call(Z_OBJ_P(scope), PT_LC("donottreatphpdoctypesascertain"), 0, NULL);
}

/* $scope->hasExpressionType($expr)->yes() / $scope->hasVariableType($name)->no():
 * the PT_TRI_* value, -1 = pending exception */
[[nodiscard]] zend_long scopeTrinary(zval *scope, const char *lcname, size_t len, zval *argument)
{
	return pt_type_call_trinary(Z_OBJ_P(scope), lcname, len, 1, argument);
}

/* TypeUtils::resolveLateResolvableTypes($type); UNDEF = pending exception */
zv::Val resolveLateResolvableTypes(zval *type)
{
	return pt_type_call_static_ce(pt_ce_type_utils, PT_LC("resolvelateresolvabletypes"), 1, type);
}

} // namespace

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Analyser\ExpressionResult. The handle wraps one object;
 * methods returning zv::Val use UNDEF for a pending exception (a legitimate
 * PHP null is zv::Val::null()), methods returning bool report a pending
 * exception with false where noted.
 */
class ExpressionResult
{
public:
	explicit ExpressionResult(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the twin's invariants, then the slots. false =
	 * pending exception */
	static bool construct(zend_object *object, const ConstructorArgs &a)
	{
		// A precomputed type and a lazy typeCallback are mutually exclusive, but
		// one must be set unless both callback results have already been memoized.
		// PHPDoc and native types are precomputed together or not at all.
		if (a.typeCallback != NULL && a.type != NULL) {
			throwShouldNotHappen("ExpressionResult cannot have both a typeCallback and a precomputed type.");
			return false;
		}
		if (a.typeCallback == NULL && a.type == NULL && (a.resolvedType == NULL || a.resolvedNativeType == NULL)) {
			throwShouldNotHappen("ExpressionResult must have precomputed types, a typeCallback, or both resolved types.");
			return false;
		}
		if ((a.type == NULL) != (a.nativeType == NULL)) {
			throwShouldNotHappen("ExpressionResult type and nativeType must both be set or both be null.");
			return false;
		}

		writeSlot(object, slots::expressionTypeResolverExtensions, a.expressionTypeResolverExtensions);
		writeSlot(object, slots::defaultNarrowingHelper, a.defaultNarrowingHelper);
		writeSlot(object, slots::scope, a.scope);
		writeSlot(object, slots::beforeScope, a.beforeScope);
		writeSlot(object, slots::expr, a.expr);
		writeBoolSlot(object, slots::hasYield, a.hasYield);
		writeBoolSlot(object, slots::isAlwaysTerminating, a.isAlwaysTerminating);
		writeSlot(object, slots::throwPoints, a.throwPoints);
		writeSlot(object, slots::impurePoints, a.impurePoints);
		writeBoolSlot(object, slots::containsNullsafe, a.containsNullsafe);
		writeSlot(object, slots::issetabilityDescriptor, a.issetabilityDescriptor);
		writeSlot(object, slots::truthyScopeOverrideResult, a.truthyScopeOverrideResult);
		writeSlot(object, slots::falseyScopeOverrideResult, a.falseyScopeOverrideResult);
		writeSlot(object, slots::type, a.type);
		writeSlot(object, slots::nativeType, a.nativeType);
		writeSlot(object, slots::argsResult, a.argsResult);
		writeSlot(object, slots::variableFlow, a.variableFlow);
		if (a.specifiedTypes != NULL) {
			writeSlot(object, slots::specifiedTypes, a.specifiedTypes);
		} else {
			ZVAL_EMPTY_ARRAY(OBJ_PROP_NUM(object, slots::specifiedTypes));
		}
		writeSlot(object, slots::cachedType, a.cachedType);
		writeSlot(object, slots::cachedNativeType, a.cachedNativeType);
		writeSlot(object, slots::resolvedType, a.resolvedType);
		writeSlot(object, slots::resolvedNativeType, a.resolvedNativeType);
		writeSlot(object, slots::projectedType, a.projectedType);
		writeSlot(object, slots::projectedNativeType, a.projectedNativeType);
		writeSlot(object, slots::readVariableNames, a.readVariableNames);

		writeSlot(object, slots::typeCallback, a.typeCallback);
		writeSlot(object, slots::specifyTypesCallback, a.specifyTypesCallback);
		writeSlot(object, slots::createTypesCallback, a.createTypesCallback);
		writeBoolSlot(object, slots::extensionsDeclined, a.extensionsDeclined);
		return true;
	}

	/* Mirrors finalize(); $variableFlow NULL for null. */
	zv::Val finalize(zval *scope, bool hasYield, bool isAlwaysTerminating, zval *throwPoints, zval *impurePoints, zval *variableFlow) const
	{
		ConstructorArgs a;
		a.expressionTypeResolverExtensions = slot(slots::expressionTypeResolverExtensions);
		a.defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
		a.scope = scope;
		a.beforeScope = slot(slots::beforeScope);
		a.expr = slot(slots::expr);
		a.hasYield = hasYield;
		a.isAlwaysTerminating = isAlwaysTerminating;
		a.throwPoints = throwPoints;
		a.impurePoints = impurePoints;
		a.typeCallback = argOf(slot(slots::typeCallback));
		a.specifyTypesCallback = slot(slots::specifyTypesCallback);
		a.containsNullsafe = boolSlot(slots::containsNullsafe);
		a.issetabilityDescriptor = argOf(slot(slots::issetabilityDescriptor));
		a.truthyScopeOverrideResult = argOf(slot(slots::truthyScopeOverrideResult));
		a.falseyScopeOverrideResult = argOf(slot(slots::falseyScopeOverrideResult));
		a.createTypesCallback = argOf(slot(slots::createTypesCallback));
		a.type = argOf(slot(slots::type));
		a.nativeType = argOf(slot(slots::nativeType));
		a.argsResult = argOf(slot(slots::argsResult));
		a.variableFlow = variableFlow;
		a.specifiedTypes = slot(slots::specifiedTypes);
		a.cachedType = argOf(slot(slots::cachedType));
		a.extensionsDeclined = boolSlot(slots::extensionsDeclined);
		a.cachedNativeType = argOf(slot(slots::cachedNativeType));
		a.resolvedType = argOf(slot(slots::resolvedType));
		a.resolvedNativeType = argOf(slot(slots::resolvedNativeType));
		a.projectedType = argOf(slot(slots::projectedType));
		a.projectedNativeType = argOf(slot(slots::projectedNativeType));
		a.readVariableNames = argOf(slot(slots::readVariableNames));
		return newSelf(a);
	}

	zv::Val getScope() const { return copySlot(slots::scope); }
	zv::Val getVariableFlow() const { return copySlot(slots::variableFlow); }

	/* Mirrors withScope(). */
	zv::Val withScope(zval *scope) const
	{
		if (Z_OBJ_P(scope) == Z_OBJ_P(slot(slots::scope))) {
			zval selfValue;
			ZVAL_OBJ(&selfValue, self);
			return zv::Val::copyOf(zv::Ref(&selfValue));
		}

		return finalize(scope, boolSlot(slots::hasYield), boolSlot(slots::isAlwaysTerminating), slot(slots::throwPoints), slot(slots::impurePoints), argOf(slot(slots::variableFlow)));
	}

	zv::Val getBeforeScope() const { return copySlot(slots::beforeScope); }
	zv::Val getExpr() const { return copySlot(slots::expr); }
	zv::Val getArgsResult() const { return copySlot(slots::argsResult); }
	bool hasYield() const { return boolSlot(slots::hasYield); }
	bool containsNullsafe() const { return boolSlot(slots::containsNullsafe); }

	/* Mirrors getIssetabilityResolution(). */
	zv::Val getIssetabilityResolution(zval *scope, bool useNativeTypes, bool reprocessUntrackedLinks)
	{
		zval *descriptor = slot(slots::issetabilityDescriptor);
		zval *expr = slot(slots::expr);
		if (Z_TYPE_P(descriptor) == IS_OBJECT) return pt_issetability_descriptor_resolve(descriptor, scope, useNativeTypes, expr, reprocessUntrackedLinks);

		zv::Val type;
		bool tracked = true;
		if (reprocessUntrackedLinks) {
			zend_long has = scopeTrinary(scope, PT_LC("hasexpressiontype"), expr);
			if (UNEXPECTED(has < 0)) return zv::Val();
			tracked = has == PT_TRI_YES;
		}
		if (reprocessUntrackedLinks && !tracked) {
			if (useNativeTypes) {
				zv::Val nativeScope = scopeNativeView(scope);
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				type = pt_type_call(Z_OBJ_P(nativeScope.raw()), PT_LC("getnativetype"), 1, expr);
			} else {
				type = pt_type_call(Z_OBJ_P(scope), PT_LC("gettype"), 1, expr);
			}
		} else {
			type = getTypeOnScope(scope, useNativeTypes);
		}
		if (UNEXPECTED(type.isUndef())) return zv::Val();

		zend_class_entry *nullsafePropertyFetchCe = pt_class(PT_CLASS_NULLSAFE_PROPERTY_FETCH);
		if (UNEXPECTED(nullsafePropertyFetchCe == NULL)) return zv::Val();
		zv::Args leafArgv{type.raw(), expr, bool(instanceof_function(Z_OBJCE_P(expr), nullsafePropertyFetchCe))};
		zv::Val link = pt_type_call_static(PT_CLASS_ISSETABILITY_LINK_INFO, PT_LC("leaf"), 3, leafArgv);
		if (UNEXPECTED(link.isUndef())) return zv::Val();
		zv::Args resolutionArgv{link.raw(), zv::null};
		return pt_type_new(PT_CLASS_ISSETABILITY_RESOLUTION, 2, resolutionArgv);
	}

	zv::Val getThrowPoints() const { return copySlot(slots::throwPoints); }
	zv::Val getImpurePoints() const { return copySlot(slots::impurePoints); }

	/* Mirrors getTruthyScope(). */
	zv::Val getTruthyScope()
	{
		return branchScope(slots::truthyScope, slots::truthyScopeOverrideResult, PT_LC("gettruthyscope"), pt_type_specifier_context_create_truthy);
	}

	/* Mirrors getFalseyScope(). */
	zv::Val getFalseyScope()
	{
		return branchScope(slots::falseyScope, slots::falseyScopeOverrideResult, PT_LC("getfalseyscope"), pt_type_specifier_context_create_falsey);
	}

	bool isAlwaysTerminating() const { return boolSlot(slots::isAlwaysTerminating); }

	/* Mirrors getType(). */
	zv::Val getType()
	{
		zval *cached = slot(slots::cachedType);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));

		zv::Val extensionType = consultExpressionTypeResolverExtensions(slot(slots::beforeScope));
		if (UNEXPECTED(extensionType.isUndef())) return zv::Val();
		if (Z_TYPE_P(extensionType.raw()) == IS_OBJECT) return memoize(slots::cachedType, std::move(extensionType));

		zval *type = slot(slots::type);
		if (Z_TYPE_P(type) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(type));

		if (hasOwnLazyResolution()) {
			zend_long tracked = hasTrackedExpressionType(slot(slots::beforeScope));
			if (UNEXPECTED(tracked < 0)) return zv::Val();
			if (tracked == 0) return memoize(slots::cachedType, resolveOwnType(false));
		}

		// The guard above leaves only one way here: the expression is tracked on
		// beforeScope (typeCallback is set but a holder wins). Read the holder
		// directly instead of re-entering MutatingScope::getType() - resolving
		// its late-resolvable types the way that method would have.
		zv::Val trackedType = pt_type_call(Z_OBJ_P(slot(slots::beforeScope)), PT_LC("gettrackedexpressiontype"), 1, slot(slots::expr));
		if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
		return memoize(slots::cachedType, resolveLateResolvableTypes(trackedType.raw()));
	}

	/* Mirrors getNativeType(). */
	zv::Val getNativeType()
	{
		zval *cached = slot(slots::cachedNativeType);
		if (Z_TYPE_P(cached) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(cached));

		// old-world getNativeType() promoted the scope and re-entered
		// resolveType(), extension hook included
		zv::Val nativeScope;
		if (!boolSlot(slots::extensionsDeclined)) {
			nativeScope = scopeNativeView(slot(slots::beforeScope));
			if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
			zv::Val extensionType = consultExpressionTypeResolverExtensions(nativeScope.raw());
			if (UNEXPECTED(extensionType.isUndef())) return zv::Val();
			if (Z_TYPE_P(extensionType.raw()) == IS_OBJECT) return memoize(slots::cachedNativeType, std::move(extensionType));
		}

		zval *nativeType = slot(slots::nativeType);
		if (Z_TYPE_P(nativeType) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(nativeType));

		if (nativeScope.isUndef()) {
			nativeScope = scopeNativeView(slot(slots::beforeScope));
			if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
		}
		if (hasOwnLazyResolution()) {
			zend_long tracked = hasTrackedExpressionType(nativeScope.raw());
			if (UNEXPECTED(tracked < 0)) return zv::Val();
			if (tracked == 0) return memoize(slots::cachedNativeType, resolveOwnType(true));
		}

		// Tracked native holder (getNativeType() promotes the scope, so its
		// expressionTypes are the native ones) - read it directly, resolving its
		// late-resolvable types the way MutatingScope::getType() would have.
		zv::Val trackedType = pt_type_call(Z_OBJ_P(nativeScope.raw()), PT_LC("gettrackedexpressiontype"), 1, slot(slots::expr));
		if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
		return memoize(slots::cachedNativeType, resolveLateResolvableTypes(trackedType.raw()));
	}

	/* Mirrors getKeepVoidType(). */
	zv::Val getKeepVoidType(bool nativeTypesPromoted)
	{
		zv::Val rawType = resolveOwnRawType(nativeTypesPromoted);
		if (UNEXPECTED(rawType.isUndef())) return zv::Val();
		zend_long isVoid = trinaryOp(rawType.raw(), PT_OP_IS_VOID);
		if (UNEXPECTED(isVoid < 0)) return zv::Val();
		if (isVoid != PT_TRI_NO) {
			// there is void to keep - the raw type is the answer, and no read that
			// projects it away may run
			return rawType;
		}

		// nothing to keep, so this is an ordinary value read: it must honour a
		// holder tracked for the expression (a match arm body narrowed by its own
		// condition) and the extensions, exactly like getType() does
		return nativeTypesPromoted ? getNativeType() : getType();
	}

	/* Mirrors canResolveOwnType(). */
	bool canResolveOwnType() const
	{
		return Z_TYPE_P(slot(slots::type)) == IS_OBJECT || hasOwnLazyResolution();
	}

	/* Mirrors getSpecifiedTypesForScope(). */
	zv::Val getSpecifiedTypesForScope(zval *scope, zval *context)
	{
		bool nativeTypesPromoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(scope, nativeTypesPromoted))) return zv::Val();
		return getSpecifiedTypes(context, nativeTypesPromoted);
	}

	/* Mirrors getSpecifiedTypes(). */
	zv::Val getSpecifiedTypes(zval *context, bool nativeTypesPromoted)
	{
		zend_ulong key = ((zend_ulong) Z_OBJ_HANDLE_P(context) << 1) | (nativeTypesPromoted ? 1 : 0);
		zval *memo = slot(slots::specifiedTypes);
		zval *found = Z_TYPE_P(memo) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(memo), key) : NULL;
		if (found != NULL && Z_TYPE_P(found) != IS_NULL) return zv::Val::copyOf(zv::Ref(found));

		zv::Args argv{context, nativeTypesPromoted};
		zv::Val specified = pt_type_call_callable(slot(slots::specifyTypesCallback), 2, argv);
		if (UNEXPECTED(specified.isUndef())) return zv::Val();
		/* $this->specifiedTypes[$key] ??= ... */
		memo = slot(slots::specifiedTypes);
		if (Z_TYPE_P(memo) != IS_ARRAY) {
			ZVAL_EMPTY_ARRAY(memo);
		}
		SEPARATE_ARRAY(memo);
		Z_TRY_ADDREF_P(specified.raw());
		zend_hash_index_update(Z_ARRVAL_P(memo), key, specified.raw());
		return specified;
	}

	/* Mirrors getCreatedTypesForScope(). */
	zv::Val getCreatedTypesForScope(zval *scope, zval *type, zval *context)
	{
		bool nativeTypesPromoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(scope, nativeTypesPromoted))) return zv::Val();
		return getCreatedTypes(type, context, nativeTypesPromoted);
	}

	/* Mirrors getCreatedTypes(). */
	zv::Val getCreatedTypes(zval *type, zval *context, bool nativeTypesPromoted)
	{
		zval *callback = slot(slots::createTypesCallback);
		if (Z_TYPE_P(callback) == IS_NULL) return zv::Val::null();

		zv::Args argv{type, context, nativeTypesPromoted};
		return pt_type_call_callable(callback, 3, argv);
	}

	/* Mirrors getTypeOnScope(). */
	zv::Val getTypeOnScope(zval *scope, bool useNativeTypes)
	{
		// An already-promoted asking scope selects native types on its own - a
		// caller that promotes the scope instead of passing the flag (the
		// isset/empty/?? folds) must not fall through to the phpdoc flavour of
		// the result's own type.
		bool promoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(scope, promoted))) return zv::Val();
		useNativeTypes = useNativeTypes || promoted;
		zv::Val readScopeHolder;
		zval *readScope = scope;
		if (useNativeTypes) {
			readScopeHolder = scopeNativeView(scope);
			if (UNEXPECTED(readScopeHolder.isUndef())) return zv::Val();
			readScope = readScopeHolder.raw();
		}
		// old-world resolveType() consulted these on every ask, both flavours -
		// a consumer reading a call's type here (an assign filling the target's
		// holder) must see the override or it never enters the scope state
		zv::Val extensionType = consultExpressionTypeResolverExtensions(readScope);
		if (UNEXPECTED(extensionType.isUndef())) return zv::Val();
		if (Z_TYPE_P(extensionType.raw()) == IS_OBJECT) return extensionType;

		if (Z_TYPE_P(slot(slots::type)) == IS_NULL) {
			zend_long authoritative = isScopeAuthoritative(readScope);
			if (UNEXPECTED(authoritative < 0)) return zv::Val();
			if (authoritative == 1) {
				// the state read is a value read: resolve late-resolvable types and
				// project void to null exactly like resolveOwnType() does
				zv::Val stateType = pt_type_call(Z_OBJ_P(readScope), PT_LC("getstatetype"), 1, slot(slots::expr));
				if (UNEXPECTED(stateType.isUndef())) return zv::Val();
				zv::Val resolved = resolveLateResolvableTypes(stateType.raw());
				if (UNEXPECTED(resolved.isUndef())) return zv::Val();
				return projectVoidToNull(std::move(resolved), useNativeTypes);
			}
		}

		return resolveOwnType(useNativeTypes);
	}

	/* Mirrors answersOnScope(); -1 = pending exception, else 0/1. */
	[[nodiscard]] zend_long answersOnScope(zval *scope, bool useNativeTypes)
	{
		if (Z_TYPE_P(slot(slots::type)) == IS_OBJECT) return 1;

		bool promoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(scope, promoted))) return -1;
		useNativeTypes = useNativeTypes || promoted;
		zv::Val readScopeHolder;
		zval *readScope = scope;
		if (useNativeTypes) {
			readScopeHolder = scopeNativeView(scope);
			if (UNEXPECTED(readScopeHolder.isUndef())) return -1;
			readScope = readScopeHolder.raw();
		}

		zend_long authoritative = isScopeAuthoritative(readScope);
		if (authoritative != 0) return authoritative;
		return askScopeVariableStateMatches(scope, useNativeTypes, false);
	}

	/* Mirrors askScopeVariableStateMatches(); -1 = pending exception, else 0/1. */
	[[nodiscard]] zend_long askScopeVariableStateMatches(zval *scope, bool useNativeTypes, bool ruleFacingAsk)
	{
		zval *beforeScope = slot(slots::beforeScope);
		// same unpromoted position implies same promoted position - skip the
		// flavour derivation for the common same-position ask
		if (Z_OBJ_P(scope) == Z_OBJ_P(beforeScope)) return 1;
		// a closure's stored result IS its (by-ref converged) walk; re-walking
		// it at a foreign position would re-run the whole convergence loop. Its
		// body variables are not reads of the asking position, and the
		// position-sensitive TYPE is computed by getClosureType at ask sites.
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(closureCe == NULL || arrowFunctionCe == NULL)) return -1;
		zend_object *expr = Z_OBJ_P(slot(slots::expr));
		if (instanceof_function(expr->ce, closureCe) || instanceof_function(expr->ce, arrowFunctionCe)) return 1;
		zv::Val names = getReadVariableNames();
		if (UNEXPECTED(names.isUndef())) return -1;
		if (zend_hash_num_elements(Z_ARRVAL_P(names.raw())) == 0) return 1;

		zv::Val readScopeHolder, positionScopeHolder;
		zval *readScope = scope;
		zval *positionScope = beforeScope;
		if (useNativeTypes) {
			readScopeHolder = scopeNativeView(scope);
			if (UNEXPECTED(readScopeHolder.isUndef())) return -1;
			readScope = readScopeHolder.raw();
			positionScopeHolder = scopeNativeView(beforeScope);
			if (UNEXPECTED(positionScopeHolder.isUndef())) return -1;
			positionScope = positionScopeHolder.raw();
		}
		if (Z_OBJ_P(readScope) == Z_OBJ_P(positionScope)) return 1;

		for (auto entry : zv::TableRef(Z_ARRVAL_P(names.raw()))) {
			zval *name = entry.value().raw();
			zend_long askKnows = scopeTrinary(readScope, PT_LC("hasvariabletype"), name);
			if (UNEXPECTED(askKnows < 0)) return -1;
			zend_long positionKnows = scopeTrinary(positionScope, PT_LC("hasvariabletype"), name);
			if (UNEXPECTED(positionKnows < 0)) return -1;
			if (ruleFacingAsk) {
				if (askKnows == PT_TRI_NO) continue;
				if (positionKnows == PT_TRI_NO) return 0;
				zv::Val askType = pt_type_call(Z_OBJ_P(readScope), PT_LC("getvariabletype"), 1, name);
				if (UNEXPECTED(askType.isUndef())) return -1;
				zv::Val positionType = pt_type_call(Z_OBJ_P(positionScope), PT_LC("getvariabletype"), 1, name);
				if (UNEXPECTED(positionType.isUndef())) return -1;
				// identity and equality short-circuit the O(keys^2) constant-array
				// isSuperTypeOf() - unchanged variables are the common ask case
				if (pt_types_identical_or_equal(askType.raw(), positionType.raw())) continue;
				if (UNEXPECTED(EG(exception))) return -1;
				zend_long superType = isSuperTypeOf(askType.raw(), positionType.raw());
				if (UNEXPECTED(superType < 0)) return -1;
				if (superType == PT_TRI_YES) continue;

				return 0;
			}
			if (askKnows == PT_TRI_NO && positionKnows == PT_TRI_NO) continue;
			if (askKnows != positionKnows) return 0;
			zv::Val askType = pt_type_call(Z_OBJ_P(readScope), PT_LC("getvariabletype"), 1, name);
			if (UNEXPECTED(askType.isUndef())) return -1;
			zv::Val positionType = pt_type_call(Z_OBJ_P(positionScope), PT_LC("getvariabletype"), 1, name);
			if (UNEXPECTED(positionType.isUndef())) return -1;
			if (!pt_types_identical_or_equal(askType.raw(), positionType.raw())) {
				if (UNEXPECTED(EG(exception))) return -1;
				return 0;
			}
		}

		return 1;
	}

	/* Mirrors atAskPosition(). */
	zv::Val atAskPosition(zval *scope)
	{
		// Scope-authoritative types must come from the asking position: the
		// original callback captures the original scope.
		bool fromScope = false;
		if (Z_TYPE_P(slot(slots::type)) == IS_NULL) {
			zend_long authoritative = isScopeAuthoritative(scope);
			if (UNEXPECTED(authoritative < 0)) return zv::Val();
			fromScope = authoritative == 1;
		}

		zv::Val stateType, nativeStateType;
		if (fromScope) {
			stateType = pt_type_call(Z_OBJ_P(scope), PT_LC("getstatetype"), 1, slot(slots::expr));
			if (UNEXPECTED(stateType.isUndef())) return zv::Val();
			zv::Val nativeScope = scopeNativeView(scope);
			if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
			nativeStateType = pt_type_call(Z_OBJ_P(nativeScope.raw()), PT_LC("getstatetype"), 1, slot(slots::expr));
			if (UNEXPECTED(nativeStateType.isUndef())) return zv::Val();
		}

		ConstructorArgs a;
		a.expressionTypeResolverExtensions = slot(slots::expressionTypeResolverExtensions);
		a.defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
		a.scope = scope;
		a.beforeScope = scope;
		a.expr = slot(slots::expr);
		a.hasYield = boolSlot(slots::hasYield);
		a.isAlwaysTerminating = boolSlot(slots::isAlwaysTerminating);
		a.throwPoints = slot(slots::throwPoints);
		a.impurePoints = slot(slots::impurePoints);
		a.typeCallback = fromScope ? NULL : argOf(slot(slots::typeCallback));
		a.specifyTypesCallback = slot(slots::specifyTypesCallback);
		a.containsNullsafe = boolSlot(slots::containsNullsafe);
		a.issetabilityDescriptor = argOf(slot(slots::issetabilityDescriptor));
		a.createTypesCallback = argOf(slot(slots::createTypesCallback));
		a.type = fromScope ? stateType.raw() : argOf(slot(slots::type));
		a.nativeType = fromScope ? nativeStateType.raw() : argOf(slot(slots::nativeType));
		a.argsResult = argOf(slot(slots::argsResult));
		a.variableFlow = argOf(slot(slots::variableFlow));
		a.specifiedTypes = slot(slots::specifiedTypes);
		a.extensionsDeclined = boolSlot(slots::extensionsDeclined);
		a.resolvedType = fromScope ? NULL : argOf(slot(slots::resolvedType));
		a.resolvedNativeType = fromScope ? NULL : argOf(slot(slots::resolvedNativeType));
		a.projectedType = fromScope ? NULL : argOf(slot(slots::projectedType));
		a.projectedNativeType = fromScope ? NULL : argOf(slot(slots::projectedNativeType));
		a.readVariableNames = argOf(slot(slots::readVariableNames));
		return newSelf(a);
	}

	/* Mirrors onNonNullabilityDevicedScopes(). */
	zv::Val onNonNullabilityDevicedScopes(zval *beforeScope, zval *scope) const
	{
		ConstructorArgs a;
		a.expressionTypeResolverExtensions = slot(slots::expressionTypeResolverExtensions);
		a.defaultNarrowingHelper = slot(slots::defaultNarrowingHelper);
		a.scope = scope;
		a.beforeScope = beforeScope;
		a.expr = slot(slots::expr);
		a.hasYield = boolSlot(slots::hasYield);
		a.isAlwaysTerminating = boolSlot(slots::isAlwaysTerminating);
		a.throwPoints = slot(slots::throwPoints);
		a.impurePoints = slot(slots::impurePoints);
		a.typeCallback = argOf(slot(slots::typeCallback));
		a.specifyTypesCallback = slot(slots::specifyTypesCallback);
		a.containsNullsafe = boolSlot(slots::containsNullsafe);
		a.issetabilityDescriptor = argOf(slot(slots::issetabilityDescriptor));
		a.truthyScopeOverrideResult = argOf(slot(slots::truthyScopeOverrideResult));
		a.falseyScopeOverrideResult = argOf(slot(slots::falseyScopeOverrideResult));
		a.createTypesCallback = argOf(slot(slots::createTypesCallback));
		a.type = argOf(slot(slots::type));
		a.nativeType = argOf(slot(slots::nativeType));
		a.argsResult = argOf(slot(slots::argsResult));
		a.variableFlow = argOf(slot(slots::variableFlow));
		a.specifiedTypes = slot(slots::specifiedTypes);
		a.extensionsDeclined = boolSlot(slots::extensionsDeclined);
		a.resolvedType = argOf(slot(slots::resolvedType));
		a.resolvedNativeType = argOf(slot(slots::resolvedNativeType));
		a.projectedType = argOf(slot(slots::projectedType));
		a.projectedNativeType = argOf(slot(slots::projectedNativeType));
		a.readVariableNames = argOf(slot(slots::readVariableNames));
		return newSelf(a);
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }
	bool boolSlot(uint32_t index) const { return Z_TYPE_P(slot(index)) == IS_TRUE; }
	zv::Val copySlot(uint32_t index) const { return zv::Val::copyOf(zv::Ref(slot(index))); }

	/* $this->x = $value; returns a copy of it (the `return $this->x = ...` idiom) */
	zv::Val memoize(uint32_t index, zv::Val value) const
	{
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zv::Ref(slot(index)).assign(zv::Val::copyOf(value.ref()));
		return value;
	}

	/* new self(...) on the object's own (final) class */
	zv::Val newSelf(const ConstructorArgs &a) const
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, self->ce) != SUCCESS)) return zv::Val();
		if (UNEXPECTED(!construct(Z_OBJ(object), a))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the shared body of getTruthyScope() / getFalseyScope() */
	zv::Val branchScope(uint32_t memoSlot, uint32_t overrideSlot, const char *getter, size_t getterLen, zend_object *(*contextFactory)())
	{
		zval *memo = slot(memoSlot);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));

		// see the twin: the override is held as the RESULT, derived on first use
		zval *override = slot(overrideSlot);
		if (Z_TYPE_P(override) == IS_OBJECT) {
			zv::Val derived;
			if (Z_OBJCE_P(override) == self->ce) {
				derived = memoSlot == slots::truthyScope ? ExpressionResult(Z_OBJ_P(override)).getTruthyScope() : ExpressionResult(Z_OBJ_P(override)).getFalseyScope();
			} else {
				derived = pt_type_call(Z_OBJ_P(override), getter, getterLen, 0, NULL);
			}
			return memoize(memoSlot, std::move(derived));
		}

		zval *scope = slot(slots::scope);
		bool nativeTypesPromoted;
		if (UNEXPECTED(!scopeNativeTypesPromoted(scope, nativeTypesPromoted))) return zv::Val();
		/* the singleton, borrowed: the context registry holds it */
		zend_object *contextObject = contextFactory();
		if (UNEXPECTED(contextObject == NULL)) return zv::Val();
		zval context;
		ZVAL_OBJ(&context, contextObject);
		zv::Val specified = getSpecifiedTypes(&context, nativeTypesPromoted);
		if (UNEXPECTED(specified.isUndef())) return zv::Val();
		specified = withEqualityCheckResult(std::move(specified), memoSlot == slots::truthyScope);
		if (UNEXPECTED(specified.isUndef())) return zv::Val();
		return memoize(memoSlot, pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), specified.raw()));
	}

	/* Mirrors withEqualityCheckResult() (private): an equality check narrows
	 * its operands without its outcome being determined by them, so the
	 * branch scope stores what the check itself returned - which is what
	 * makes a duplicate check in the branch report as always-true. */
	zv::Val withEqualityCheckResult(zv::Val specifiedTypes, bool value)
	{
		bool isEquality;
		if (UNEXPECTED(!pt_specified_types_is_equality(specifiedTypes.raw(), isEquality))) return zv::Val();
		if (!isEquality) return specifiedTypes;
		zv::Val type = getType();
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zend_long isBoolean = pt_type_op_trinary(Z_OBJ_P(type.raw()), PT_OP_IS_BOOLEAN, 0, NULL);
		if (UNEXPECTED(isBoolean < 0)) return zv::Val();
		if (isBoolean != PT_TRI_YES) return specifiedTypes;

		zval constantBoolean;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&constantBoolean, value))) return zv::Val();
		zv::Val booleanType = zv::Val::adopt(constantBoolean);
		zend_object *trueContext = pt_type_specifier_context_create_true();
		if (UNEXPECTED(trueContext == NULL)) return zv::Val();
		zval context, thisValue;
		ZVAL_OBJ(&context, trueContext);
		ZVAL_OBJ(&thisValue, self);
		zval *helper = slot(slots::defaultNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(helper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\ExpressionResult::$defaultNarrowingHelper must not be accessed before initialization");
			return zv::Val();
		}
		zval createArgs[5];
		ZVAL_COPY_VALUE(&createArgs[0], slot(slots::scope));
		ZVAL_COPY_VALUE(&createArgs[1], slot(slots::expr));
		ZVAL_COPY_VALUE(&createArgs[2], &thisValue);
		ZVAL_COPY_VALUE(&createArgs[3], booleanType.raw());
		ZVAL_COPY_VALUE(&createArgs[4], &context);
		zv::Val subjectTypes = pt_type_call(Z_OBJ_P(helper), PT_LC("createsubjecttypes"), 5, createArgs);
		if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
		return pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), subjectTypes.raw());
	}

	/* Mirrors consultExpressionTypeResolverExtensions(): the extension type,
	 * PHP null when every extension declined; UNDEF = pending exception. */
	zv::Val consultExpressionTypeResolverExtensions(zval *readScope)
	{
		if (boolSlot(slots::extensionsDeclined)) return zv::Val::null();

		zv::Val extensions = pt_extensions_collection_get_all(Z_OBJ_P(slot(slots::expressionTypeResolverExtensions)));
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (Z_TYPE_P(extensions.raw()) == IS_ARRAY) {
			zv::Args argv{slot(slots::expr), readScope};
			for (auto entry : zv::TableRef(Z_ARRVAL_P(extensions.raw()))) {
				zv::Ref extension = entry.value().deref();
				if (UNEXPECTED(!extension.isObject())) continue;
				zv::Val type = pt_type_call(extension.asObject(), PT_LC("gettype"), 2, argv);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (Z_TYPE_P(type.raw()) != IS_NULL) return type;
			}
		}

		writeBoolSlot(self, slots::extensionsDeclined, true);

		return zv::Val::null();
	}

	/* Mirrors resolveOwnRawType(). */
	zv::Val resolveOwnRawType(bool nativeTypesPromoted)
	{
		uint32_t eagerSlot = nativeTypesPromoted ? slots::nativeType : slots::type;
		uint32_t resolvedSlot = nativeTypesPromoted ? slots::resolvedNativeType : slots::resolvedType;
		if (Z_TYPE_P(slot(eagerSlot)) == IS_OBJECT) return copySlot(eagerSlot);
		if (Z_TYPE_P(slot(resolvedSlot)) == IS_OBJECT) return copySlot(resolvedSlot);
		zval *callback = slot(slots::typeCallback);
		if (Z_TYPE_P(callback) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Args argv{nativeTypesPromoted};
		zv::Val callbackType = pt_type_call_callable(callback, 1, argv);
		if (UNEXPECTED(callbackType.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(callbackType.raw()) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: the ExpressionResult type callback did not return a Type");
			return zv::Val();
		}
		zv::Val resolved = resolveLateResolvableTypes(callbackType.raw());
		if (UNEXPECTED(resolved.isUndef())) return zv::Val();
		zv::Ref(slot(resolvedSlot)).assign(zv::Val::copyOf(resolved.ref()));
		releaseTypeCallbackIfResolved();

		return resolved;
	}

	/* Mirrors releaseTypeCallbackIfResolved(). */
	void releaseTypeCallbackIfResolved()
	{
		if (Z_TYPE_P(slot(slots::resolvedType)) != IS_OBJECT || Z_TYPE_P(slot(slots::resolvedNativeType)) != IS_OBJECT) return;

		zv::Ref(slot(slots::typeCallback)).assign(zv::Val::null());
	}

	/* Mirrors resolveOwnType(). */
	zv::Val resolveOwnType(bool nativeTypesPromoted)
	{
		uint32_t projectedSlot = nativeTypesPromoted ? slots::projectedNativeType : slots::projectedType;
		if (Z_TYPE_P(slot(projectedSlot)) == IS_OBJECT) return copySlot(projectedSlot);
		zv::Val raw = resolveOwnRawType(nativeTypesPromoted);
		if (UNEXPECTED(raw.isUndef())) return zv::Val();
		return memoize(projectedSlot, projectVoidToNull(std::move(raw), nativeTypesPromoted));
	}

	/* Mirrors projectVoidToNull(); $type consumed. */
	zv::Val projectVoidToNull(zv::Val type, bool nativeTypesPromoted)
	{
		// the overwhelmingly common non-void, non-union result skips the
		// traverser entirely
		if (!instanceof_function(Z_OBJCE_P(type.raw()), pt_ce_union_type)) {
			zend_long isVoid = trinaryOp(type.raw(), PT_OP_IS_VOID);
			if (UNEXPECTED(isVoid < 0)) return zv::Val();
			if (isVoid == PT_TRI_NO) return type;
		}

		zend_long projects = projectsVoidToNull(nativeTypesPromoted);
		if (UNEXPECTED(projects < 0)) return zv::Val();
		if (projects == 0) return type;

		zv::Val traverser = pt_type_new(PT_CLASS_VOID_TO_NULL_TRAVERSER, 0, NULL);
		if (UNEXPECTED(traverser.isUndef())) return zv::Val();
		zval out;
		if (UNEXPECTED(!pt_type_traverser_map(&out, type.raw(), traverser.raw()))) return zv::Val();
		return zv::Val::adopt(out);
	}

	/* Mirrors projectsVoidToNull(); -1 = pending exception, else 0/1. */
	[[nodiscard]] zend_long projectsVoidToNull(bool nativeTypesPromoted) const
	{
		if (nativeTypesPromoted) return 0;

		zend_class_entry *funcCallCe = pt_class(PT_CLASS_FUNC_CALL);
		zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		zend_class_entry *nullsafeMethodCallCe = pt_class(PT_CLASS_NULLSAFE_METHOD_CALL);
		zend_class_entry *staticCallCe = pt_class(PT_CLASS_STATIC_CALL);
		if (UNEXPECTED(funcCallCe == NULL || nameCe == NULL || methodCallCe == NULL || nullsafeMethodCallCe == NULL || staticCallCe == NULL)) return -1;
		zend_object *expr = Z_OBJ_P(slot(slots::expr));
		if (instanceof_function(expr->ce, funcCallCe)) {
			zv::Ref name = zv::ObjRef(expr).prop(PT_LC("name"));
			if (name.raw() == NULL || !name.deref().instanceOf(nameCe)) return 0;
			bool firstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(expr, firstClassCallable))) return -1;
			return firstClassCallable ? 0 : 1;
		}

		if (!instanceof_function(expr->ce, methodCallCe) && !instanceof_function(expr->ce, nullsafeMethodCallCe) && !instanceof_function(expr->ce, staticCallCe)) {
			return 0;
		}
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(expr, firstClassCallable))) return -1;
		return firstClassCallable ? 0 : 1;
	}

	/* Mirrors hasTrackedExpressionType(); -1 = pending exception, else 0/1. */
	[[nodiscard]] zend_long hasTrackedExpressionType(zval *scope) const
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(variableCe == NULL || closureCe == NULL || arrowFunctionCe == NULL)) return -1;
		zend_object *expr = Z_OBJ_P(slot(slots::expr));
		if (instanceof_function(expr->ce, variableCe) || instanceof_function(expr->ce, closureCe) || instanceof_function(expr->ce, arrowFunctionCe)) return 0;
		zend_long has = scopeTrinary(scope, PT_LC("hasexpressiontype"), slot(slots::expr));
		if (UNEXPECTED(has < 0)) return -1;
		return has == PT_TRI_YES ? 1 : 0;
	}

	/* Mirrors hasOwnLazyResolution(). */
	bool hasOwnLazyResolution() const
	{
		return Z_TYPE_P(slot(slots::typeCallback)) != IS_NULL || Z_TYPE_P(slot(slots::resolvedType)) == IS_OBJECT;
	}

	/* Mirrors isScopeAuthoritative(); -1 = pending exception, else 0/1. */
	[[nodiscard]] zend_long isScopeAuthoritative(zval *scope) const
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(variableCe == NULL || closureCe == NULL || arrowFunctionCe == NULL)) return -1;
		zend_object *expr = Z_OBJ_P(slot(slots::expr));
		if (instanceof_function(expr->ce, variableCe)) {
			zv::Ref name = zv::ObjRef(expr).prop(PT_LC("name"));
			if (name.raw() == NULL || !name.deref().isString()) return 0;
			zend_long has = scopeTrinary(scope, PT_LC("hasvariabletype"), name.deref().raw());
			if (UNEXPECTED(has < 0)) return -1;
			return has != PT_TRI_NO ? 1 : 0;
		}

		if (instanceof_function(expr->ce, closureCe) || instanceof_function(expr->ce, arrowFunctionCe)) return 0;
		zend_long has = scopeTrinary(scope, PT_LC("hasexpressiontype"), slot(slots::expr));
		if (UNEXPECTED(has < 0)) return -1;
		return has == PT_TRI_YES ? 1 : 0;
	}

	/* Mirrors getReadVariableNames(): the list (an owned array). */
	zv::Val getReadVariableNames()
	{
		zval *memo = slot(slots::readVariableNames);
		if (Z_TYPE_P(memo) == IS_ARRAY) return zv::Val::copyOf(zv::Ref(memo));
		zv::Val names = collectReadVariableNames(Z_OBJ_P(slot(slots::expr)));
		return memoize(slots::readVariableNames, std::move(names));
	}

	/* Mirrors collectReadVariableNames(): a list of the names, cached as a
	 * node attribute on Expr nodes; UNDEF = pending exception. */
	static zv::Val collectReadVariableNames(zend_object *node)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		zend_class_entry *nodeCe = pt_class(PT_CLASS_NODE);
		if (UNEXPECTED(exprCe == NULL || variableCe == NULL || closureCe == NULL || nodeCe == NULL)) return zv::Val();
		bool isExpr = instanceof_function(node->ce, exprCe);
		if (isExpr) {
			zval *cached = pt_node_attribute(node, pt_er_read_variable_names_attribute);
			if (cached != NULL && Z_TYPE_P(cached) != IS_NULL) return zv::Val::copyOf(zv::Ref(cached));
		}

		/* $names as a set (the keys), the list is array_keys() of it */
		zv::Arr names = zv::Arr::empty();
		if (instanceof_function(node->ce, variableCe)) {
			zv::Ref name = zv::ObjRef(node).prop(PT_LC("name"));
			if (name.raw() != NULL && name.deref().isString() && !zend_string_equals_literal(name.deref().asString(), "this")) {
				zval trueValue;
				ZVAL_TRUE(&trueValue);
				names.separate();
				zend_symtable_update(names.table(), name.deref().asString(), &trueValue);
			}
		}
		if (instanceof_function(node->ce, closureCe)) {
			// a closure body's variables live in its own scope - only the
			// use() clause reads the enclosing position. Arrow functions
			// capture implicitly and are traversed.
			zv::Ref uses = zv::ObjRef(node).prop(PT_LC("uses"));
			if (uses.raw() != NULL && uses.deref().isArray()) {
				for (auto entry : zv::TableRef(uses.deref().asArrayTable())) {
					zv::Ref use = entry.value().deref();
					if (!use.isObject()) continue;
					zv::Ref var = zv::ObjRef(use.asObject()).prop(PT_LC("var"));
					if (var.raw() == NULL || !var.deref().isObject()) continue;
					zv::Ref useName = zv::ObjRef(var.deref().asObject()).prop(PT_LC("name"));
					if (useName.raw() == NULL || !useName.deref().isString()) continue;
					zval trueValue;
					ZVAL_TRUE(&trueValue);
					names.separate();
					zend_symtable_update(names.table(), useName.deref().asString(), &trueValue);
				}
			}
		} else {
			pt_node_class_info *info = pt_node_class_info_for_object(node);
			if (info != NULL && PT_HAS_SUBNODES(info)) {
				for (uint32_t i = 0; i < info->subnode_count; i++) {
					zval *subNode = OBJ_PROP(node, info->subnode_offsets[i]);
					ZVAL_DEINDIRECT(subNode);
					ZVAL_DEREF(subNode);
					if (Z_TYPE_P(subNode) == IS_OBJECT && instanceof_function(Z_OBJCE_P(subNode), nodeCe)) {
						if (UNEXPECTED(!mergeNames(names, Z_OBJ_P(subNode)))) return zv::Val();
					} else if (Z_TYPE_P(subNode) == IS_ARRAY) {
						for (auto entry : zv::TableRef(Z_ARRVAL_P(subNode))) {
							zv::Ref item = entry.value().deref();
							if (!item.isObject() || !instanceof_function(Z_OBJCE_P(item.raw()), nodeCe)) continue;
							if (UNEXPECTED(!mergeNames(names, item.asObject()))) return zv::Val();
						}
					}
				}
			}
		}

		/* array_keys($names) */
		zv::Arr result = zv::Arr::create(zend_hash_num_elements(names.table()));
		for (auto entry : zv::TableRef(names.table())) {
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				result.push(zv::Val::string(key));
			} else {
				result.push(zv::Val::integer((zend_long) entry.indexKey()));
			}
		}
		if (isExpr) {
			if (UNEXPECTED(!pt_node_set_attribute(node, pt_er_read_variable_names_attribute, result.raw()))) return zv::Val();
		}

		return zv::Val(std::move(result));
	}

	/* foreach (self::collectReadVariableNames($subNode) as $name) $names[$name] = true */
	static bool mergeNames(zv::Arr &names, zend_object *subNode)
	{
		zv::Val subNames = collectReadVariableNames(subNode);
		if (UNEXPECTED(subNames.isUndef())) return false;
		for (auto entry : zv::TableRef(Z_ARRVAL_P(subNames.raw()))) {
			zval trueValue;
			ZVAL_TRUE(&trueValue);
			names.separate();
			zv::Ref name = entry.value().deref();
			if (name.isString()) {
				zend_symtable_update(names.table(), name.asString(), &trueValue);
			} else if (name.isLong()) {
				zend_hash_index_update(names.table(), (zend_ulong) name.asLong(), &trueValue);
			}
		}
		return true;
	}

	/* $type->isVoid() as a PT_TRI_* value; -1 = pending exception */
	[[nodiscard]] static zend_long trinaryOp(zval *type, pt_type_op_id op)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected a Type, got %s", zend_zval_value_name(type));
			return -1;
		}
		zv::Val result = pt_type_op(Z_OBJ_P(type), op, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_trinary_value(result.raw());
	}

	/* $a->isSuperTypeOf($b)->result as a PT_TRI_* value; -1 = pending exception */
	[[nodiscard]] static zend_long isSuperTypeOf(zval *a, zval *b)
	{
		zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
		if (UNEXPECTED(result.isUndef())) return -1;
		if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() did not return a result object");
			return -1;
		}
		return pt_result_value(Z_OBJ_P(result.raw()));
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionResult;

zv::Val pt_expression_result_variable_flow(zval *result)
{
	/* the twin is final: an instance of the native class entry takes the
	 * slot, anything else (the PHP twin declared next to the native class in
	 * the differential tests) the method */
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_expression_result)) return ExpressionResult(Z_OBJ_P(result)).getVariableFlow();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getvariableflow"), 0, NULL);
}

/* {{{ direct entries for native callers (Engine.h, the analyser value
 * classes); the slot getters are inline in AnalyserValues.h */

namespace {

/* the twin is final: an instance of the native class entry is read
 * natively, anything else (the PHP twin under the prefixed differential
 * activation) through its method */
inline bool isNativeResult(zval *result)
{
	return EXPECTED(Z_OBJCE_P(result) == pt_ce_expression_result);
}

} // namespace

zv::Val pt_expression_result_get_type(zval *result)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getType();
	return pt_type_call(Z_OBJ_P(result), PT_LC("gettype"), 0, NULL);
}

zv::Val pt_expression_result_get_native_type(zval *result)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getNativeType();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getnativetype"), 0, NULL);
}

zv::Val pt_expression_result_get_type_on_scope(zval *result, zval *scope, bool useNativeTypes)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_expression_result)) return ExpressionResult(Z_OBJ_P(result)).getTypeOnScope(scope, useNativeTypes);
	zv::Args argv{scope, useNativeTypes};
	return pt_type_call(Z_OBJ_P(result), PT_LC("gettypeonscope"), 2, argv);
}

zv::Val pt_expression_result_get_issetability_resolution(zval *result, zval *scope, bool useNativeTypes, bool reprocessUntrackedLinks)
{
	if (EXPECTED(Z_OBJCE_P(result) == pt_ce_expression_result)) return ExpressionResult(Z_OBJ_P(result)).getIssetabilityResolution(scope, useNativeTypes, reprocessUntrackedLinks);
	zv::Args argv{scope, useNativeTypes, reprocessUntrackedLinks};
	return pt_type_call(Z_OBJ_P(result), PT_LC("getissetabilityresolution"), 3, argv);
}

/* the NodeScopeResolver / NonNullabilityHelper ports' reads and derivations */
zv::Val pt_expression_result_with_scope(zval *result, zval *scope)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).withScope(scope);
	return pt_type_call(Z_OBJ_P(result), PT_LC("withscope"), 1, scope);
}

/* the method call handler's (MethodCallHandler.cpp) */
zv::Val pt_expression_result_finalize(zval *result, zval *scope, bool hasYield, bool isAlwaysTerminating, zval *throwPoints, zval *impurePoints, zval *variableFlow)
{
	if (variableFlow != NULL && Z_TYPE_P(variableFlow) == IS_NULL) variableFlow = NULL;
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).finalize(scope, hasYield, isAlwaysTerminating, throwPoints, impurePoints, variableFlow);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{scope, hasYield, isAlwaysTerminating, throwPoints, impurePoints, variableFlow != NULL ? variableFlow : &null};
	return pt_type_call(Z_OBJ_P(result), PT_LC("finalize"), 6, argv);
}

zv::Val pt_expression_result_get_keep_void_type(zval *result, bool nativeTypesPromoted)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getKeepVoidType(nativeTypesPromoted);
	zval argv;
	ZVAL_BOOL(&argv, nativeTypesPromoted);
	return pt_type_call(Z_OBJ_P(result), PT_LC("getkeepvoidtype"), 1, &argv);
}

zv::Val pt_expression_result_get_args_result(zval *result)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getArgsResult();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getargsresult"), 0, NULL);
}

bool pt_expression_result_ask_scope_variable_state_matches(zval *result, zval *scope, bool useNativeTypes, bool &out)
{
	if (isNativeResult(result)) {
		zend_long matches = ExpressionResult(Z_OBJ_P(result)).askScopeVariableStateMatches(scope, useNativeTypes, false);
		if (UNEXPECTED(matches < 0)) return false;
		out = matches == 1;
		return true;
	}
	zv::Args argv{scope, useNativeTypes};
	zv::Val value = pt_type_call(Z_OBJ_P(result), PT_LC("askscopevariablestatematches"), 2, argv);
	if (UNEXPECTED(value.isUndef())) return false;
	out = Z_TYPE_P(value.raw()) == IS_TRUE;
	return true;
}

zv::Val pt_expression_result_at_ask_position(zval *result, zval *scope)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).atAskPosition(scope);
	return pt_type_call(Z_OBJ_P(result), PT_LC("ataskposition"), 1, scope);
}

zv::Val pt_expression_result_on_non_nullability_deviced_scopes(zval *result, zval *beforeScope, zval *scope)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).onNonNullabilityDevicedScopes(beforeScope, scope);
	zv::Args argv{beforeScope, scope};
	return pt_type_call(Z_OBJ_P(result), PT_LC("onnonnullabilitydevicedscopes"), 2, argv);
}

/* }}} */

/* {{{ ExpressionResult creation for native callers (Engine.h) */

namespace {

/* the extensions collection and the DefaultNarrowingHelper each recently
 * used generated factory passes, learned from its first result; the entries
 * hold the factory, so its object can never be reused by another factory
 * while it is cached */
struct FactoryCollection
{
	zend_object *factory;
	zval collection;
	zval defaultNarrowingHelper;
};

#define PT_ER_FACTORY_CACHE_LIMIT 4
FactoryCollection pt_er_factories[PT_ER_FACTORY_CACHE_LIMIT];
uint32_t pt_er_factory_next = 0;

FactoryCollection *cachedCollection(zend_object *factory)
{
	for (FactoryCollection &entry : pt_er_factories) {
		if (entry.factory == factory) return &entry;
	}
	return NULL;
}

void rememberCollection(zend_object *factory, zval *collection, zval *defaultNarrowingHelper)
{
	FactoryCollection &entry = pt_er_factories[pt_er_factory_next];
	pt_er_factory_next = (pt_er_factory_next + 1) % PT_ER_FACTORY_CACHE_LIMIT;
	zend_object *previousFactory = entry.factory;
	zval previousCollection, previousHelper;
	ZVAL_COPY_VALUE(&previousCollection, &entry.collection);
	ZVAL_COPY_VALUE(&previousHelper, &entry.defaultNarrowingHelper);
	GC_ADDREF(factory);
	entry.factory = factory;
	ZVAL_COPY(&entry.collection, collection);
	ZVAL_COPY(&entry.defaultNarrowingHelper, defaultNarrowingHelper);
	if (previousFactory != NULL) {
		OBJ_RELEASE(previousFactory);
		zval_ptr_dtor(&previousCollection);
		zval_ptr_dtor(&previousHelper);
	}
}

/* Whether the factory is the implementation Nette generates for
 * #[GenerateFactory]: `new class ($this) implements <Factory> { private
 * $container; ... create(...) { return new <Class>($this->container->
 * getService(...), ...); } }`, declared inside the container class's file —
 * an anonymous user class whose only property holds an object of a class
 * declared in the same file. Its create() forwards every parameter to the
 * constructor with the extensions collection as the first argument. */
bool isGeneratedFactory(zend_object *factory)
{
	zend_class_entry *ce = factory->ce;
	if ((ce->ce_flags & ZEND_ACC_ANON_CLASS) == 0 || ce->type != ZEND_USER_CLASS || ce->default_properties_count != 1 || ce->info.user.filename == NULL) return false;
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("container"));
	if (info == NULL || info->offset != OBJ_PROP_TO_OFFSET(0)) return false;
	zval *container = OBJ_PROP_NUM(factory, 0);
	if (Z_TYPE_P(container) != IS_OBJECT) return false;
	zend_class_entry *containerCe = Z_OBJCE_P(container);
	return containerCe->type == ZEND_USER_CLASS && containerCe->info.user.filename != NULL && zend_string_equals(containerCe->info.user.filename, ce->info.user.filename);
}

inline zval *nullable(zval *value)
{
	return value != NULL && Z_TYPE_P(value) != IS_NULL ? value : NULL;
}

/* a callable argument as the constructor's zpp checks it (a closure is
 * callable without asking the engine); false with the TypeError pending */
bool checkCallable(zval *value, uint32_t argNum, bool nullable)
{
	if (value == NULL) {
		if (nullable) return true;
	} else if (Z_TYPE_P(value) == IS_OBJECT && (Z_OBJCE_P(value) == pt_ce_native_closure || Z_OBJCE_P(value) == zend_ce_closure)) {
		return true;
	} else if (zend_is_callable(value, 0, NULL)) {
		return true;
	}
	zend_type_error("PHPStan\\Analyser\\ExpressionResult::__construct(): Argument #%u must be of type %scallable, %s given", argNum, nullable ? "?" : "", value == NULL ? "null" : zend_zval_value_name(value));
	return false;
}

/* new ExpressionResult($collection, $defaultNarrowingHelper, ...$args) */
zv::Val constructDirect(zval *collection, zval *defaultNarrowingHelper, const pt_expression_result_args &args)
{
	zval emptyArray;
	ZVAL_EMPTY_ARRAY(&emptyArray);
	ConstructorArgs a;
	a.expressionTypeResolverExtensions = collection;
	a.defaultNarrowingHelper = defaultNarrowingHelper;
	a.scope = args.scope;
	a.beforeScope = args.beforeScope;
	a.expr = args.expr;
	a.hasYield = args.hasYield;
	a.isAlwaysTerminating = args.isAlwaysTerminating;
	a.throwPoints = args.throwPoints != NULL ? args.throwPoints : &emptyArray;
	a.impurePoints = args.impurePoints != NULL ? args.impurePoints : &emptyArray;
	a.typeCallback = nullable(args.typeCallback);
	a.specifyTypesCallback = nullable(args.specifyTypesCallback);
	a.containsNullsafe = args.containsNullsafe;
	a.issetabilityDescriptor = nullable(args.issetabilityDescriptor);
	a.truthyScopeOverrideResult = nullable(args.truthyScopeOverrideResult);
	a.falseyScopeOverrideResult = nullable(args.falseyScopeOverrideResult);
	a.createTypesCallback = nullable(args.createTypesCallback);
	a.type = nullable(args.type);
	a.nativeType = nullable(args.nativeType);
	a.argsResult = nullable(args.argsResult);
	a.variableFlow = nullable(args.variableFlow);
	if (UNEXPECTED(Z_TYPE_P(a.throwPoints) != IS_ARRAY || Z_TYPE_P(a.impurePoints) != IS_ARRAY)) {
		zend_type_error("PHPStan\\Analyser\\ExpressionResult::__construct(): Argument #%u must be of type array, %s given", Z_TYPE_P(a.throwPoints) != IS_ARRAY ? 8 : 9, zend_zval_value_name(Z_TYPE_P(a.throwPoints) != IS_ARRAY ? a.throwPoints : a.impurePoints));
		return zv::Val();
	}
	if (UNEXPECTED(!checkCallable(a.typeCallback, 10, true) || !checkCallable(a.specifyTypesCallback, 11, false) || !checkCallable(a.createTypesCallback, 16, true))) return zv::Val();

	zval object;
	if (UNEXPECTED(object_init_ex(&object, pt_ce_expression_result) != SUCCESS)) return zv::Val();
	if (UNEXPECTED(!ExpressionResult::construct(Z_OBJ(object), a))) {
		zval_ptr_dtor(&object);
		return zv::Val();
	}
	return zv::Val::adopt(object);
}

/* $factory->create(...) through the engine: the required parameters
 * positionally, the optional ones the call names as named arguments */
zv::Val createThroughFactory(zend_object *factory, const pt_expression_result_args &args)
{
	zend_function *create = pt_find_method(factory->ce, PT_LC("create"));
	if (UNEXPECTED(create == NULL)) return zv::Val();

	zval null, emptyArray;
	ZVAL_NULL(&null);
	ZVAL_EMPTY_ARRAY(&emptyArray);
	auto orNull = [&null](zval *value) { return value != NULL ? value : &null; };
	zv::Args argv{
		orNull(args.scope),
		orNull(args.beforeScope),
		orNull(args.expr),
		args.hasYield,
		args.isAlwaysTerminating,
		args.throwPoints != NULL ? args.throwPoints : &emptyArray,
		args.impurePoints != NULL ? args.impurePoints : &emptyArray,
		orNull(args.typeCallback),
		orNull(args.specifyTypesCallback),
	};

	HashTable named;
	zend_hash_init(&named, 8, NULL, NULL, 0);
	zval containsNullsafe;
	ZVAL_BOOL(&containsNullsafe, args.containsNullsafe);
	const struct
	{
		uint32_t bit;
		const char *name;
		size_t len;
		zval *value;
	} optionals[] = {
		{ PT_ER_NAMED_CONTAINS_NULLSAFE, PT_LC("containsNullsafe"), &containsNullsafe },
		{ PT_ER_NAMED_ISSETABILITY_DESCRIPTOR, PT_LC("issetabilityDescriptor"), orNull(args.issetabilityDescriptor) },
		{ PT_ER_NAMED_TRUTHY_SCOPE_OVERRIDE_RESULT, PT_LC("truthyScopeOverrideResult"), orNull(args.truthyScopeOverrideResult) },
		{ PT_ER_NAMED_FALSEY_SCOPE_OVERRIDE_RESULT, PT_LC("falseyScopeOverrideResult"), orNull(args.falseyScopeOverrideResult) },
		{ PT_ER_NAMED_CREATE_TYPES_CALLBACK, PT_LC("createTypesCallback"), orNull(args.createTypesCallback) },
		{ PT_ER_NAMED_TYPE, PT_LC("type"), orNull(args.type) },
		{ PT_ER_NAMED_NATIVE_TYPE, PT_LC("nativeType"), orNull(args.nativeType) },
		{ PT_ER_NAMED_ARGS_RESULT, PT_LC("argsResult"), orNull(args.argsResult) },
		{ PT_ER_NAMED_VARIABLE_FLOW, PT_LC("variableFlow"), orNull(args.variableFlow) },
	};
	for (const auto &optional : optionals) {
		if ((args.named & optional.bit) != 0) {
			zend_hash_str_add(&named, optional.name, optional.len, optional.value);
		}
	}

	zval ret;
	zend_call_known_function(create, factory, factory->ce, &ret, 9, argv, zend_hash_num_elements(&named) > 0 ? &named : NULL);
	zend_hash_destroy(&named);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&ret);
		return zv::Val();
	}

	if (Z_TYPE(ret) == IS_OBJECT && Z_OBJCE(ret) == pt_ce_expression_result && isGeneratedFactory(factory)) {
		rememberCollection(factory, OBJ_PROP_NUM(Z_OBJ(ret), slots::expressionTypeResolverExtensions), OBJ_PROP_NUM(Z_OBJ(ret), slots::defaultNarrowingHelper));
	}
	return zv::Val::adopt(ret);
}

} // namespace

zv::Val pt_expression_result_create(zval *factory, const pt_expression_result_args &args)
{
	FactoryCollection *cached = cachedCollection(Z_OBJ_P(factory));
	if (EXPECTED(cached != NULL)) return constructDirect(&cached->collection, &cached->defaultNarrowingHelper, args);
	return createThroughFactory(Z_OBJ_P(factory), args);
}

void pt_expression_result_rshutdown()
{
	for (FactoryCollection &entry : pt_er_factories) {
		if (entry.factory == NULL) continue;
		zend_object *factory = entry.factory;
		entry.factory = NULL;
		OBJ_RELEASE(factory);
		zval_ptr_dtor(&entry.collection);
		ZVAL_UNDEF(&entry.collection);
		zval_ptr_dtor(&entry.defaultNarrowingHelper);
		ZVAL_UNDEF(&entry.defaultNarrowingHelper);
	}
	pt_er_factory_next = 0;
}

/* }}} */
/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_ER_RETURN(expr) \
	do { \
		zv::Val pt_er_result = (expr); \
		if (UNEXPECTED(pt_er_result.isUndef())) { \
			RETURN_THROWS(); \
		} \
		pt_er_result.intoReturnValue(return_value); \
	} while (0)

#define PT_ER_RETURN_TRINARY_BOOL(expr) \
	do { \
		zend_long pt_er_result = (expr); \
		if (UNEXPECTED(pt_er_result < 0)) { \
			RETURN_THROWS(); \
		} \
		RETURN_BOOL(pt_er_result == 1); \
	} while (0)

namespace {

inline constexpr const char *pt_er_self = "PHPStan\\Analyser\\ExpressionResult";
inline constexpr const char *pt_er_scope = "PHPStan\\Analyser\\MutatingScope";
inline constexpr const char *pt_er_type = "PHPStan\\Type\\Type";

/* a `?callable` argument: NULL for null, the (verified) callable otherwise;
 * false with the engine's TypeError pending */
bool pt_er_callable_arg(zval *arg, uint32_t argNum, bool nullable, zval *&out)
{
	if (arg == NULL || Z_TYPE_P(arg) == IS_NULL) {
		if (nullable) {
			out = NULL;
			return true;
		}
	} else if (zend_is_callable(arg, 0, NULL)) {
		out = arg;
		return true;
	}
	zend_argument_type_error(argNum, "must be of type %scallable, %s given", nullable ? "?" : "", arg == NULL ? "null" : zend_zval_value_name(arg));
	return false;
}

} // namespace

void pt_register_expression_result()
{
	pt_er_read_variable_names_attribute = zend_string_init_interned(PT_LC("readVariableNames"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExpressionResult");
	ptdecl::ExpressionResult::declareClass(cls);
	/* the twin's properties in declaration order (the OBJ_PROP_NUM slots) */
	cls.privateNullProperty("typeCallback");
	cls.privateNullProperty("specifyTypesCallback");
	cls.privateNullProperty("createTypesCallback");
	cls.privateTypedClassPropertyDefaultNull("truthyScope", pt_er_scope);
	cls.privateTypedClassPropertyDefaultNull("falseyScope", pt_er_scope);
	cls.privateTypedBoolProperty("extensionsDeclined", false);
	cls.privateTypedClassProperty("expressionTypeResolverExtensions", "PHPStan\\DependencyInjection\\ExtensionsCollection", false);
	cls.privateTypedClassProperty("defaultNarrowingHelper", "PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper", false);
	cls.privateTypedClassProperty("scope", pt_er_scope, false);
	cls.privateTypedClassProperty("beforeScope", pt_er_scope, false);
	cls.privateTypedClassProperty("expr", "PhpParser\\Node\\Expr", false);
	cls.privateTypedProperty("hasYield", MAY_BE_BOOL);
	cls.privateTypedProperty("isAlwaysTerminating", MAY_BE_BOOL);
	cls.privateTypedProperty("throwPoints", MAY_BE_ARRAY);
	cls.privateTypedProperty("impurePoints", MAY_BE_ARRAY);
	cls.privateTypedBoolProperty("containsNullsafe", false);
	cls.privateTypedClassPropertyDefaultNull("issetabilityDescriptor", "PHPStan\\Analyser\\IssetabilityDescriptor");
	cls.privateTypedClassPropertyDefaultNull("truthyScopeOverrideResult", pt_er_self);
	cls.privateTypedClassPropertyDefaultNull("falseyScopeOverrideResult", pt_er_self);
	cls.privateTypedClassPropertyDefaultNull("type", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("nativeType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("argsResult", "PHPStan\\Analyser\\ArgsResult");
	cls.privateTypedClassPropertyDefaultNull("variableFlow", "PHPStan\\Analyser\\VariableFlow");
	cls.privateTypedArrayPropertyDefaultEmpty("specifiedTypes");
	cls.privateTypedClassPropertyDefaultNull("cachedType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("cachedNativeType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("resolvedType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("resolvedNativeType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("projectedType", pt_er_type);
	cls.privateTypedClassPropertyDefaultNull("projectedNativeType", pt_er_type);
	cls.privateTypedPropertyDefaultNull("readVariableNames", MAY_BE_ARRAY);

	cls.method("__construct", reg::Public, 11, {
		reg::obj("expressionTypeResolverExtensions", "PHPStan\\DependencyInjection\\ExtensionsCollection"),
		reg::obj("defaultNarrowingHelper", "PHPStan\\Analyser\\ExprHandler\\Helper\\DefaultNarrowingHelper"),
		reg::obj("scope", pt_er_scope),
		reg::obj("beforeScope", pt_er_scope),
		reg::obj("expr", "PhpParser\\Node\\Expr"),
		reg::boolArg("hasYield"),
		reg::boolArg("isAlwaysTerminating"),
		reg::arrayArg("throwPoints"),
		reg::arrayArg("impurePoints"),
		reg::callableArg("typeCallback", true),
		reg::callableArg("specifyTypesCallback"),
		reg::withDefault(reg::boolArg("containsNullsafe"), "false"),
		reg::withDefault(reg::obj("issetabilityDescriptor", "PHPStan\\Analyser\\IssetabilityDescriptor", true), "null"),
		reg::withDefault(reg::obj("truthyScopeOverrideResult", pt_er_self, true), "null"),
		reg::withDefault(reg::obj("falseyScopeOverrideResult", pt_er_self, true), "null"),
		reg::withDefault(reg::callableArg("createTypesCallback", true), "null"),
		reg::withDefault(reg::obj("type", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("nativeType", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("argsResult", "PHPStan\\Analyser\\ArgsResult", true), "null"),
		reg::withDefault(reg::obj("variableFlow", "PHPStan\\Analyser\\VariableFlow", true), "null"),
		reg::withDefault(reg::arrayArg("specifiedTypes"), "[]"),
		reg::withDefault(reg::obj("cachedType", pt_er_type, true), "null"),
		reg::withDefault(reg::boolArg("extensionsDeclined"), "false"),
		reg::withDefault(reg::obj("cachedNativeType", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("resolvedType", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("resolvedNativeType", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("projectedType", pt_er_type, true), "null"),
		reg::withDefault(reg::obj("projectedNativeType", pt_er_type, true), "null"),
		reg::withDefault(reg::arrayArg("readVariableNames", true), "null"),
	}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ConstructorArgs a;
		zval *typeCallback, *specifyTypesCallback, *createTypesCallback = NULL;
		bool hasYield, isAlwaysTerminating;
		bool containsNullsafe = false;
		bool extensionsDeclined = false;
		zval *issetabilityDescriptor = NULL, *truthyScopeOverrideResult = NULL, *falseyScopeOverrideResult = NULL;
		zval *type = NULL, *nativeType = NULL, *argsResult = NULL, *variableFlow = NULL;
		zval *specifiedTypes = NULL, *cachedType = NULL, *cachedNativeType = NULL, *resolvedType = NULL, *resolvedNativeType = NULL, *projectedType = NULL, *projectedNativeType = NULL, *readVariableNames = NULL;
		ZEND_PARSE_PARAMETERS_START(11, 29)
			Z_PARAM_OBJECT(a.expressionTypeResolverExtensions)
			Z_PARAM_OBJECT(a.defaultNarrowingHelper)
			Z_PARAM_OBJECT(a.scope)
			Z_PARAM_OBJECT(a.beforeScope)
			Z_PARAM_OBJECT(a.expr)
			Z_PARAM_BOOL(hasYield)
			Z_PARAM_BOOL(isAlwaysTerminating)
			Z_PARAM_ARRAY(a.throwPoints)
			Z_PARAM_ARRAY(a.impurePoints)
			Z_PARAM_ZVAL(typeCallback)
			Z_PARAM_ZVAL(specifyTypesCallback)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(containsNullsafe)
			Z_PARAM_OBJECT_OR_NULL(issetabilityDescriptor)
			Z_PARAM_OBJECT_OR_NULL(truthyScopeOverrideResult)
			Z_PARAM_OBJECT_OR_NULL(falseyScopeOverrideResult)
			Z_PARAM_ZVAL(createTypesCallback)
			Z_PARAM_OBJECT_OR_NULL(type)
			Z_PARAM_OBJECT_OR_NULL(nativeType)
			Z_PARAM_OBJECT_OR_NULL(argsResult)
			Z_PARAM_OBJECT_OR_NULL(variableFlow)
			Z_PARAM_ARRAY(specifiedTypes)
			Z_PARAM_OBJECT_OR_NULL(cachedType)
			Z_PARAM_BOOL(extensionsDeclined)
			Z_PARAM_OBJECT_OR_NULL(cachedNativeType)
			Z_PARAM_OBJECT_OR_NULL(resolvedType)
			Z_PARAM_OBJECT_OR_NULL(resolvedNativeType)
			Z_PARAM_OBJECT_OR_NULL(projectedType)
			Z_PARAM_OBJECT_OR_NULL(projectedNativeType)
			Z_PARAM_ARRAY_OR_NULL(readVariableNames)
		ZEND_PARSE_PARAMETERS_END();
		if (!pt_er_callable_arg(typeCallback, 10, true, a.typeCallback)
			|| !pt_er_callable_arg(specifyTypesCallback, 11, false, a.specifyTypesCallback)
			|| !pt_er_callable_arg(createTypesCallback, 16, true, a.createTypesCallback)) {
			RETURN_THROWS();
		}
		a.hasYield = hasYield;
		a.isAlwaysTerminating = isAlwaysTerminating;
		a.containsNullsafe = containsNullsafe;
		a.issetabilityDescriptor = issetabilityDescriptor;
		a.truthyScopeOverrideResult = truthyScopeOverrideResult;
		a.falseyScopeOverrideResult = falseyScopeOverrideResult;
		a.type = type;
		a.nativeType = nativeType;
		a.argsResult = argsResult;
		a.variableFlow = variableFlow;
		a.specifiedTypes = specifiedTypes;
		a.cachedType = cachedType;
		a.extensionsDeclined = extensionsDeclined;
		a.cachedNativeType = cachedNativeType;
		a.resolvedType = resolvedType;
		a.resolvedNativeType = resolvedNativeType;
		a.projectedType = projectedType;
		a.projectedNativeType = projectedNativeType;
		a.readVariableNames = readVariableNames;
		if (UNEXPECTED(!ExpressionResult::construct(Z_OBJ_P(ZEND_THIS), a))) RETURN_THROWS();
	});

	cls.method(sigs::finalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *throwPoints, *impurePoints, *variableFlow;
		bool hasYield, isAlwaysTerminating;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool, zp::Arr, zp::Arr, zp::ObjOrNull>(execute_data, scope, hasYield, isAlwaysTerminating, throwPoints, impurePoints, variableFlow)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).finalize(scope, hasYield, isAlwaysTerminating, throwPoints, impurePoints, variableFlow));
	});

	cls.method(sigs::getScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getScope());
	});

	cls.method(sigs::getVariableFlow, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getVariableFlow());
	});

	cls.method(sigs::withScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).withScope(scope));
	});

	cls.method(sigs::getBeforeScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getBeforeScope());
	});

	cls.method(sigs::getExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getExpr());
	});

	cls.method(sigs::getArgsResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getArgsResult());
	});

	cls.method(sigs::hasYield, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).hasYield());
	});

	cls.method(sigs::containsNullsafe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).containsNullsafe());
	});

	cls.method(sigs::getIssetabilityResolution, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		bool useNativeTypes, reprocessUntrackedLinks = false;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Opt<zp::Bool>>(execute_data, scope, useNativeTypes, reprocessUntrackedLinks)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getIssetabilityResolution(scope, useNativeTypes, reprocessUntrackedLinks));
	});

	cls.method(sigs::getThrowPoints, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getThrowPoints());
	});

	cls.method(sigs::getImpurePoints, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getImpurePoints());
	});

	cls.method(sigs::getTruthyScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getTruthyScope());
	});

	cls.method(sigs::getFalseyScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getFalseyScope());
	});

	cls.method(sigs::isAlwaysTerminating, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).isAlwaysTerminating());
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getType());
	});

	cls.method(sigs::getNativeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getNativeType());
	});

	cls.method(sigs::getKeepVoidType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool nativeTypesPromoted;
		if (!zp::parse<zp::Bool>(execute_data, nativeTypesPromoted)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getKeepVoidType(nativeTypesPromoted));
	});

	cls.method(sigs::canResolveOwnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).canResolveOwnType());
	});

	cls.method(sigs::getSpecifiedTypesForScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *context;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, context)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getSpecifiedTypesForScope(scope, context));
	});

	cls.method(sigs::getSpecifiedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *context;
		bool nativeTypesPromoted = false;
		if (!zp::parse<zp::Obj, zp::Opt<zp::Bool>>(execute_data, context, nativeTypesPromoted)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getSpecifiedTypes(context, nativeTypesPromoted));
	});

	cls.method(sigs::getCreatedTypesForScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *type, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, scope, type, context)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getCreatedTypesForScope(scope, type, context));
	});

	cls.method(sigs::getCreatedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *context;
		bool nativeTypesPromoted = false;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Opt<zp::Bool>>(execute_data, type, context, nativeTypesPromoted)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getCreatedTypes(type, context, nativeTypesPromoted));
	});

	cls.method(sigs::getTypeOnScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		bool useNativeTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, scope, useNativeTypes)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).getTypeOnScope(scope, useNativeTypes));
	});

	cls.method(sigs::answersOnScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		bool useNativeTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, scope, useNativeTypes)) RETURN_THROWS();
		PT_ER_RETURN_TRINARY_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).answersOnScope(scope, useNativeTypes));
	});

	cls.method(sigs::askScopeVariableStateMatches, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		bool useNativeTypes, ruleFacingAsk = false;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Opt<zp::Bool>>(execute_data, scope, useNativeTypes, ruleFacingAsk)) RETURN_THROWS();
		PT_ER_RETURN_TRINARY_BOOL(ExpressionResult(Z_OBJ_P(ZEND_THIS)).askScopeVariableStateMatches(scope, useNativeTypes, ruleFacingAsk));
	});

	cls.method(sigs::atAskPosition, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		if (!zp::parse<zp::Obj>(execute_data, scope)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).atAskPosition(scope));
	});

	cls.method(sigs::onNonNullabilityDevicedScopes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *beforeScope, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, beforeScope, scope)) RETURN_THROWS();
		PT_ER_RETURN(ExpressionResult(Z_OBJ_P(ZEND_THIS)).onNonNullabilityDevicedScopes(beforeScope, scope));
	});

	cls.shadow(&pt_ce_expression_result);
}

/* }}} */

/* {{{ direct entries for the narrowing helpers (DefaultNarrowingHelper.cpp,
 * IdenticalNarrowingHelper.cpp): $result->getCreatedTypesForScope() /
 * ->getSpecifiedTypesForScope() — the native body for a native result, the
 * method otherwise (the result and the arguments borrowed);
 * ->containsNullsafe() is the inline slot reader of AnalyserValues.h */

zv::Val pt_expression_result_get_created_types_for_scope(zval *result, zval *scope, zval *type, zval *context)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getCreatedTypesForScope(scope, type, context);
	zv::Args argv{scope, type, context};
	return pt_type_call(Z_OBJ_P(result), PT_LC("getcreatedtypesforscope"), 3, argv);
}

zv::Val pt_expression_result_get_specified_types_for_scope(zval *result, zval *scope, zval *context)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getSpecifiedTypesForScope(scope, context);
	zv::Args argv{scope, context};
	return pt_type_call(Z_OBJ_P(result), PT_LC("getspecifiedtypesforscope"), 2, argv);
}

/* the operator handlers' (TernaryHandler.cpp, CoalesceCompositionHelper.cpp,
 * ...): $result->getSpecifiedTypes($context, $nativeTypesPromoted) /
 * ->answersOnScope($scope, $useNativeTypes) */
zv::Val pt_expression_result_get_specified_types(zval *result, zval *context, bool nativeTypesPromoted)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getSpecifiedTypes(context, nativeTypesPromoted);
	zv::Args argv{context, nativeTypesPromoted};
	return pt_type_call(Z_OBJ_P(result), PT_LC("getspecifiedtypes"), 2, argv);
}

bool pt_expression_result_answers_on_scope(zval *result, zval *scope, bool useNativeTypes, bool &out)
{
	if (isNativeResult(result)) {
		zend_long answers = ExpressionResult(Z_OBJ_P(result)).answersOnScope(scope, useNativeTypes);
		if (UNEXPECTED(answers < 0)) return false;
		out = answers == 1;
		return true;
	}
	zv::Args argv{scope, useNativeTypes};
	zv::Val value = pt_type_call(Z_OBJ_P(result), PT_LC("answersonscope"), 2, argv);
	if (UNEXPECTED(value.isUndef())) return false;
	out = Z_TYPE_P(value.raw()) == IS_TRUE;
	return true;
}

/* }}} */

/* {{{ direct entries for the statement handlers (IfHandler.cpp):
 * $result->getTruthyScope() / ->getFalseyScope() — the native body for a
 * native result, the method otherwise (the result borrowed) */

zv::Val pt_expression_result_get_truthy_scope(zval *result)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getTruthyScope();
	return pt_type_call(Z_OBJ_P(result), PT_LC("gettruthyscope"), 0, NULL);
}

zv::Val pt_expression_result_get_falsey_scope(zval *result)
{
	if (isNativeResult(result)) return ExpressionResult(Z_OBJ_P(result)).getFalseyScope();
	return pt_type_call(Z_OBJ_P(result), PT_LC("getfalseyscope"), 0, NULL);
}

/* }}} */
