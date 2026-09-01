/*
 * PHPStanTurbo\ScopeOps — native implementations of MutatingScope's hottest
 * scope-table loops: merging, conditional-expression bookkeeping, expression
 * invalidation (including a native replacement for the NodeFinder-based AST
 * walk) and clone-based scope creation.
 *
 * The ScopeOps handle class below mirrors src/Analyser/ScopeOps.php method
 * for method, in the same order; where a body departs from the twin's shape
 * for performance the comment on the method says so. The PHP_METHOD functions
 * at the bottom are only the engine ABI glue (parameter parsing + delegation).
 * Not final: a PHP stub subclass extends this class, so PHP code calls the
 * methods through the shadowed class.
 */

#include "support.h"
#include "zv.h"

#include <cstring>

static zend_class_entry *pt_ce_scope_ops;

/* {{{ scopeWith's cached property layout (owned by the request lifecycle) */

typedef struct _pt_scope_offsets {
	int32_t expression_types;
	int32_t native_expression_types;
	int32_t conditional_expressions;
	int32_t currently_assigned;
	int32_t currently_allowed_undefined;
	int32_t in_function_calls_stack;
	int32_t in_first_level_statement;
	int32_t after_extract_call;
	/* memo props to reset (missing => -1) */
	int32_t resolved_types;
	int32_t truthy_scopes;
	int32_t falsey_scopes;
	int32_t node_callback_scope;
	int32_t scope_out_of_first_level;
	int32_t scope_with_promoted_native;
	int32_t walk_scope;          /* NodeCallbackScope */
	int32_t seeded_walk_scope;   /* NodeCallbackScope */
	int32_t truthy_value_exprs;  /* NodeCallbackScope */
	int32_t falsey_value_exprs;  /* NodeCallbackScope */
} pt_scope_offsets;

static HashTable pt_scope_offsets_cache;
static bool pt_scope_offsets_cache_inited = false;

static void pt_scope_offsets_free(zval *zv)
{
	efree(Z_PTR_P(zv));
}

static pt_scope_offsets *pt_scope_offsets_for(zend_class_entry *ce)
{
	pt_scope_offsets *off;

	if (!pt_scope_offsets_cache_inited) {
		zend_hash_init(&pt_scope_offsets_cache, 4, NULL, pt_scope_offsets_free, 0);
		pt_scope_offsets_cache_inited = true;
	}
	off = (pt_scope_offsets *) zend_hash_find_ptr(&pt_scope_offsets_cache, ce->name);
	if (EXPECTED(off != NULL)) {
		return off;
	}

	off = (pt_scope_offsets *) emalloc(sizeof(pt_scope_offsets));
	off->expression_types = pt_instance_prop_offset(ce, "expressionTypes", sizeof("expressionTypes") - 1);
	off->native_expression_types = pt_instance_prop_offset(ce, "nativeExpressionTypes", sizeof("nativeExpressionTypes") - 1);
	off->conditional_expressions = pt_instance_prop_offset(ce, "conditionalExpressions", sizeof("conditionalExpressions") - 1);
	off->currently_assigned = pt_instance_prop_offset(ce, "currentlyAssignedExpressions", sizeof("currentlyAssignedExpressions") - 1);
	off->currently_allowed_undefined = pt_instance_prop_offset(ce, "currentlyAllowedUndefinedExpressions", sizeof("currentlyAllowedUndefinedExpressions") - 1);
	off->in_function_calls_stack = pt_instance_prop_offset(ce, "inFunctionCallsStack", sizeof("inFunctionCallsStack") - 1);
	off->in_first_level_statement = pt_instance_prop_offset(ce, "inFirstLevelStatement", sizeof("inFirstLevelStatement") - 1);
	off->after_extract_call = pt_instance_prop_offset(ce, "afterExtractCall", sizeof("afterExtractCall") - 1);
	off->resolved_types = pt_instance_prop_offset(ce, "resolvedTypes", sizeof("resolvedTypes") - 1);
	off->truthy_scopes = pt_instance_prop_offset(ce, "truthyScopes", sizeof("truthyScopes") - 1);
	off->falsey_scopes = pt_instance_prop_offset(ce, "falseyScopes", sizeof("falseyScopes") - 1);
	off->node_callback_scope = pt_instance_prop_offset(ce, "nodeCallbackScope", sizeof("nodeCallbackScope") - 1);
	off->scope_out_of_first_level = pt_instance_prop_offset(ce, "scopeOutOfFirstLevelStatement", sizeof("scopeOutOfFirstLevelStatement") - 1);
	off->scope_with_promoted_native = pt_instance_prop_offset(ce, "scopeWithPromotedNativeTypes", sizeof("scopeWithPromotedNativeTypes") - 1);
	off->walk_scope = pt_instance_prop_offset(ce, "walkScope", sizeof("walkScope") - 1);
	off->seeded_walk_scope = pt_instance_prop_offset(ce, "seededWalkScope", sizeof("seededWalkScope") - 1);
	off->truthy_value_exprs = pt_instance_prop_offset(ce, "truthyValueExprs", sizeof("truthyValueExprs") - 1);
	off->falsey_value_exprs = pt_instance_prop_offset(ce, "falseyValueExprs", sizeof("falseyValueExprs") - 1);

	zend_hash_add_ptr(&pt_scope_offsets_cache, ce->name, off);
	return off;
}

/* }}} */

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Analyser\ScopeOps. Methods returning zv::Val use UNDEF to
 * signal a pending exception; a legitimate PHP null is zv::Val::null().
 */
class ScopeOps
{
public:
	/* Mirrors ScopeOps::nodeKey() (pt_node_key carries the key caching). */
	static zv::Val nodeKey(zend_object *node, zval *exprPrinter)
	{
		zend_string *key = pt_node_key(node, exprPrinter);
		if (UNEXPECTED(key == NULL)) {
			return zv::Val();
		}
		return zv::Val::adoptString(key);
	}

	/*
	 * Mirrors ScopeOps::getTypeFromCache(). *keyOut receives the computed key
	 * (owned by the caller) on both hit and miss, like the twin's
	 * unconditional `$key = ...` assignment; it stays NULL only when an
	 * exception is pending. A stored null counts as a miss — the twin's
	 * `?? null` cannot distinguish it from an absent entry either.
	 */
	static zv::Val getTypeFromCache(zval *scope, zend_object *node, zend_string **keyOut)
	{
		*keyOut = NULL;

		zval *exprPrinter = scopeProp(scope, "exprPrinter", sizeof("exprPrinter") - 1);
		if (UNEXPECTED(exprPrinter == NULL)) {
			return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(exprPrinter) != IS_OBJECT)) {
			zend_throw_error(NULL, "phpstan_turbo: exprPrinter is not an object");
			return zv::Val();
		}

		zv::Str key = zv::Str::adopt(pt_node_key(node, exprPrinter));
		if (UNEXPECTED(key.isNull())) {
			return zv::Val();
		}

		zval *table = scopeProp(scope, "resolvedTypes", sizeof("resolvedTypes") - 1);
		if (UNEXPECTED(table == NULL)) {
			return zv::Val();
		}
		if (EXPECTED(Z_TYPE_P(table) == IS_ARRAY)) {
			zval *found = zend_symtable_find(Z_ARRVAL_P(table), key.get());
			if (found != NULL && Z_TYPE_P(found) != IS_NULL) {
				zv::Val result = zv::Val::copyOf(zv::Ref(found));
				*keyOut = key.take();
				return result;
			}
		}

		*keyOut = key.take();
		return zv::Val::null();
	}

	/*
	 * Mirrors ScopeOps::expressionTypeByKey(): for non-Variable/Closure/
	 * ArrowFunction nodes whose entry in expressionTypes has certainty Yes,
	 * returns the tracked type; null otherwise.
	 */
	static zv::Val expressionTypeByKey(zval *scope, zend_object *node, zend_string *exprString)
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		zend_class_entry *arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
		if (UNEXPECTED(variableCe == NULL || closureCe == NULL || arrowFunctionCe == NULL)) {
			return zv::Val();
		}

		if (instanceof_function(node->ce, variableCe)
			|| instanceof_function(node->ce, closureCe)
			|| instanceof_function(node->ce, arrowFunctionCe)) {
			return zv::Val::null();
		}

		zval *table = scopeArrayProp(scope, "expressionTypes", sizeof("expressionTypes") - 1);
		if (UNEXPECTED(table == NULL)) {
			return zv::Val();
		}
		zval *found = zend_symtable_find(Z_ARRVAL_P(table), exprString);
		if (found == NULL) {
			return zv::Val::null();
		}
		zv::Ref holder = zv::Ref(found).deref();
		if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
			return zv::Val();
		}
		if (pt_holder_certainty_value(holder.asObject()) != PT_TRI_YES) {
			return zv::Val::null();
		}
		return zv::Val::copyOf(zv::ObjRef(holder.asObject()).propAt(PT_ETH_PROP_TYPE));
	}

	/* Mirrors ScopeOps::hasExpressionType(). */
	static zv::Val hasExpressionType(zval *scope, zend_object *node, zval *exprPrinter)
	{
		pt_node_class_info *info = pt_get_node_class_info(node->ce);
		if (UNEXPECTED(info == NULL)) {
			return zv::Val();
		}
		if (info->is_variable && info->name_offset >= 0) {
			zv::Ref name = zv::ObjRef(node).propAtOffset((uint32_t) info->name_offset).deref();
			if (name.isString()) {
				return hasVariableType(scope, name.asString());
			}
		}

		zv::Str key = zv::Str::adopt(pt_node_key(node, exprPrinter));
		if (UNEXPECTED(key.isNull())) {
			return zv::Val();
		}
		zval *table = scopeArrayProp(scope, "expressionTypes", sizeof("expressionTypes") - 1);
		if (UNEXPECTED(table == NULL)) {
			return zv::Val();
		}
		zval *found = zend_symtable_find(Z_ARRVAL_P(table), key.get());
		if (found == NULL) {
			return trinarySingleton(PT_TRI_NO);
		}
		zv::Ref holder = zv::Ref(found).deref();
		if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
			return zv::Val();
		}
		return zv::Val::copyOf(zv::ObjRef(holder.asObject()).propAt(PT_ETH_PROP_CERTAINTY));
	}

	/* Mirrors ScopeOps::hasVariableType(). */
	static zv::Val hasVariableType(zval *scope, zend_string *variableName)
	{
		if (pt_is_superglobal_name(variableName)) {
			return trinarySingleton(PT_TRI_YES);
		}

		zval *table = scopeProp(scope, "expressionTypes", sizeof("expressionTypes") - 1);
		if (UNEXPECTED(table == NULL)) {
			return zv::Val();
		}
		if (EXPECTED(Z_TYPE_P(table) == IS_ARRAY)) {
			zv::Str varKey = dollarPrefixed(variableName);
			zval *found = zend_hash_find(Z_ARRVAL_P(table), varKey.get());
			if (found != NULL) {
				zv::Ref holder = zv::Ref(found).deref();
				if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
					return zv::Val();
				}
				return zv::Val::copyOf(zv::ObjRef(holder.asObject()).propAt(PT_ETH_PROP_CERTAINTY));
			}
		}

		bool canExist;
		if (UNEXPECTED(!pt_call_scope_bool(scope, "cananyvariableexist", sizeof("cananyvariableexist") - 1, 0, NULL, &canExist))) {
			return zv::Val();
		}
		return trinarySingleton(canExist ? PT_TRI_MAYBE : PT_TRI_NO);
	}

	/*
	 * Mirrors ScopeOps::scopeWith(), but natively: the hot MutatingScope
	 * operations end in $this->scopeFactory->create() with ~30 arguments
	 * where only the expression tables and a couple of flags differ from the
	 * current scope. Cloning the scope and overwriting exactly those
	 * properties produces an identical object (the constructor is pure
	 * property promotion) while skipping the factory call, 30-argument
	 * dispatch and re-verification. Per-instance memo properties are reset
	 * to their fresh-constructor defaults.
	 */
	static zv::Val scopeWith(
		zval *scope,
		HashTable *expressionTypes,
		HashTable *nativeExpressionTypes,
		HashTable *conditionalExpressions,
		HashTable *currentlyAssignedExpressions,
		HashTable *currentlyAllowedUndefinedExpressions,
		HashTable *inFunctionCallsStack,
		bool inFirstLevelStatement,
		bool afterExtractCall)
	{
		pt_scope_offsets *off = pt_scope_offsets_for(Z_OBJCE_P(scope));
		if (UNEXPECTED(off->expression_types < 0 || off->native_expression_types < 0
			|| off->conditional_expressions < 0 || off->currently_assigned < 0
			|| off->currently_allowed_undefined < 0 || off->in_function_calls_stack < 0
			|| off->in_first_level_statement < 0 || off->after_extract_call < 0)) {
			zend_throw_error(NULL, "phpstan_turbo: scope property layout mismatch");
			return zv::Val();
		}

		zend_object *clone = zend_objects_clone_obj(Z_OBJ_P(scope));
		if (UNEXPECTED(EG(exception))) {
			return zv::Val();
		}
		zv::ObjRef cloneObj(clone);

		setTableProp(cloneObj, off->expression_types, expressionTypes);
		setTableProp(cloneObj, off->native_expression_types, nativeExpressionTypes);
		setTableProp(cloneObj, off->conditional_expressions, conditionalExpressions);
		setTableProp(cloneObj, off->currently_assigned, currentlyAssignedExpressions);
		setTableProp(cloneObj, off->currently_allowed_undefined, currentlyAllowedUndefinedExpressions);
		setTableProp(cloneObj, off->in_function_calls_stack, inFunctionCallsStack);
		cloneObj.propAtOffset((uint32_t) off->in_first_level_statement).assign(zv::Val::boolean(inFirstLevelStatement));
		cloneObj.propAtOffset((uint32_t) off->after_extract_call).assign(zv::Val::boolean(afterExtractCall));

		/* fresh-constructor defaults for per-instance memos */
		resetToEmptyArray(cloneObj, off->resolved_types);
		resetToEmptyArray(cloneObj, off->truthy_scopes);
		resetToEmptyArray(cloneObj, off->falsey_scopes);
		resetToNull(cloneObj, off->node_callback_scope);
		resetToNull(cloneObj, off->scope_out_of_first_level);
		resetToNull(cloneObj, off->scope_with_promoted_native);
		resetToNull(cloneObj, off->walk_scope);
		resetToNull(cloneObj, off->seeded_walk_scope);
		resetToEmptyArray(cloneObj, off->truthy_value_exprs);
		resetToEmptyArray(cloneObj, off->falsey_value_exprs);

		zval out;
		ZVAL_OBJ(&out, clone);
		return zv::Val::adopt(out);
	}

	/*
	 * Mirrors ScopeOps::mergeVariableHolders(). differing (nullable) receives
	 * a true marker for every key that is not one shared holder on both sides
	 * — the twin's &$differingKeys out-parameter.
	 */
	static zv::Val mergeVariableHolders(zv::TableRef ours, zv::TableRef theirs, HashTable *differing)
	{
		zv::Arr merged = zv::Arr::create(ours.size());
		if (UNEXPECTED(!mergeVariableHoldersInto(merged, ours, theirs, differing))) {
			return zv::Val();
		}
		return zv::Val(std::move(merged));
	}

	/* Mirrors ScopeOps::finishMerge(). Returns [filteredMerged, mergedNative]. */
	static zv::Val finishMerge(zv::TableRef merged, zv::TableRef oursExpr, zv::TableRef theirsExpr, zv::TableRef oursNative, zv::TableRef theirsNative)
	{
		zv::Arr filteredMerged;
		if (UNEXPECTED(!filterHolders(merged, filteredMerged))) {
			return zv::Val();
		}

		zv::Arr oursNativeRemaining = zv::Arr::adoptTable(zend_array_dup(oursNative.table()));
		zv::Arr theirsNativeRemaining = zv::Arr::adoptTable(zend_array_dup(theirsNative.table()));
		zv::Arr mergedNative = zv::Arr::create(0);

		for (auto entry : oursNative) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();
			zv::Ref holder = entry.value().deref();

			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return zv::Val();
			}

			zval *theirNativeSlot = pt_ht_find(theirsNative.table(), key, idx);
			if (theirNativeSlot == NULL) {
				continue;
			}
			zval *ourExprSlot = pt_ht_find(oursExpr.table(), key, idx);
			if (ourExprSlot == NULL) {
				continue;
			}
			zval *theirExprSlot = pt_ht_find(theirsExpr.table(), key, idx);
			if (theirExprSlot == NULL) {
				continue;
			}

			bool equal;
			{
				zv::Ref ourExprHolder = zv::Ref(ourExprSlot).deref();
				if (UNEXPECTED(!pt_check_holder(ourExprHolder.raw()))) {
					return zv::Val();
				}
				if (UNEXPECTED(!pt_holder_equals(holder.raw(), ourExprHolder.raw(), &equal))) {
					return zv::Val();
				}
			}
			if (!equal) {
				continue;
			}
			{
				zv::Ref theirNativeHolder = zv::Ref(theirNativeSlot).deref();
				zv::Ref theirExprHolder = zv::Ref(theirExprSlot).deref();
				if (UNEXPECTED(!pt_check_holder(theirNativeHolder.raw())) || UNEXPECTED(!pt_check_holder(theirExprHolder.raw()))) {
					return zv::Val();
				}
				if (UNEXPECTED(!pt_holder_equals(theirNativeHolder.raw(), theirExprHolder.raw(), &equal))) {
					return zv::Val();
				}
			}
			if (!equal) {
				continue;
			}

			zval *mergedHolder = pt_ht_find(filteredMerged.table(), key, idx);
			if (mergedHolder == NULL) {
				continue;
			}

			tableUpdateCopy(mergedNative.table(), key, idx, zv::Ref(mergedHolder));
			pt_ht_del(oursNativeRemaining.table(), key, idx);
			pt_ht_del(theirsNativeRemaining.table(), key, idx);
		}

		/* mergedNative += filter(mergeVariableHolders(oursRemaining, theirsRemaining)) */
		{
			zv::Val remainingMerged = mergeVariableHolders(zv::TableRef(oursNativeRemaining.table()), zv::TableRef(theirsNativeRemaining.table()), NULL);
			if (UNEXPECTED(remainingMerged.isUndef())) {
				return zv::Val();
			}
			zv::Arr remainingFiltered;
			if (UNEXPECTED(!filterHolders(zv::TableRef(Z_ARRVAL_P(remainingMerged.raw())), remainingFiltered))) {
				return zv::Val();
			}
			for (auto entry : zv::ArrRef(remainingFiltered.raw())) {
				tableUpdateCopy(mergedNative.table(), entry.stringKeyOrNull(), entry.indexKey(), entry.value());
			}
		}

		zv::Arr result = zv::Arr::create(2);
		result.push(std::move(filteredMerged));
		result.push(std::move(mergedNative));
		return zv::Val(std::move(result));
	}

	/* Mirrors ScopeOps::intersectConditionalExpressions(). */
	static zv::Val intersectConditionalExpressions(zv::TableRef ours, zv::TableRef theirs)
	{
		zv::Arr result = zv::Arr::create(0);

		for (auto entry : ours) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();

			zval *otherHoldersSlot = pt_ht_find(theirs.table(), key, idx);
			if (otherHoldersSlot == NULL) {
				continue;
			}
			zv::Ref holders = entry.value().deref();
			zv::Ref otherHolders = zv::Ref(otherHoldersSlot).deref();
			if (!holders.isArray() || !otherHolders.isArray()) {
				continue;
			}
			HashTable *otherTable = otherHolders.asArrayTable();

			zv::Arr intersected; /* stays UNDEF until the first shared holder */
			for (auto holderEntry : zv::TableRef(holders.asArrayTable())) {
				zend_string *holderKey = holderEntry.stringKeyOrNull();
				zend_ulong holderIdx = holderEntry.indexKey();
				if (!pt_ht_exists(otherTable, holderKey, holderIdx)) {
					continue;
				}
				if (intersected.isUndef()) {
					intersected = zv::Arr::create(0);
				}
				tableAddNewCopy(intersected.table(), holderKey, holderIdx, holderEntry.value());
			}

			if (intersected.isUndef()) {
				continue;
			}
			tableAddNew(result.table(), key, idx, std::move(intersected));
		}

		return zv::Val(std::move(result));
	}

	/*
	 * Mirrors ScopeOps::createConditionalExpressions(). The isSuperTypeOf /
	 * isConstantArray results are cached per guard, exactly like the twin's
	 * per-guard caches; the input array is only duplicated once the first
	 * conditional is actually appended.
	 */
	static zv::Val createConditionalExpressions(zv::TableRef conditional, zv::TableRef ours, zv::TableRef theirs, zv::TableRef merged, zv::TableRef differingKeys)
	{
		zend_class_entry *virtualNodeCe = pt_class(PT_CLASS_VIRTUAL_NODE);
		if (UNEXPECTED(virtualNodeCe == NULL)) {
			return zv::Val();
		}

		zv::ScratchTable guardsToExclude(8);
		zv::ScratchTable typeGuards(8);

		/* guardsToExclude: subtype-absorbed their-branch variables are poor
		 * guards but stay valid conditional targets. Only the merge's differing
		 * keys can qualify — iterate those (in their insertion order, like the
		 * twin) instead of the whole holder maps. */
		for (auto diffEntry : differingKeys) {
			zend_string *key = diffEntry.stringKeyOrNull();
			zend_ulong idx = diffEntry.indexKey();

			zval *theirSlot = pt_ht_find(theirs.table(), key, idx);
			if (theirSlot == NULL) {
				continue;
			}
			zval *mergedSlot = pt_ht_find(merged.table(), key, idx);
			if (mergedSlot == NULL) {
				continue;
			}
			zv::Ref holder = zv::Ref(theirSlot).deref();
			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return zv::Val();
			}
			bool equalTypes;
			{
				zv::Ref mergedHolder = zv::Ref(mergedSlot).deref();
				if (UNEXPECTED(!pt_check_holder(mergedHolder.raw()))) {
					return zv::Val();
				}
				if (UNEXPECTED(!pt_holder_equal_types(mergedHolder.raw(), holder.raw(), &equalTypes))) {
					return zv::Val();
				}
			}
			if (!equalTypes) {
				continue;
			}

			zval *ourSlot = pt_ht_find(ours.table(), key, idx);
			if (ourSlot != NULL) {
				zv::Ref ourHolder = zv::Ref(ourSlot).deref();
				if (UNEXPECTED(!pt_check_holder(ourHolder.raw()))) {
					return zv::Val();
				}
				if (pt_holder_certainty_value(ourHolder.asObject()) != pt_holder_certainty_value(holder.asObject())) {
					bool ourEqualTypes;
					if (UNEXPECTED(!pt_holder_equal_types(ourHolder.raw(), holder.raw(), &ourEqualTypes))) {
						return zv::Val();
					}
					if (ourEqualTypes) {
						continue;
					}
				}
			}

			zval trueZv;
			ZVAL_TRUE(&trueZv);
			pt_ht_update(guardsToExclude.table(), key, idx, &trueZv);
		}

		/* typeGuards */
		for (auto diffEntry : differingKeys) {
			zend_string *key = diffEntry.stringKeyOrNull();
			zend_ulong idx = diffEntry.indexKey();

			zval *ourSlot = pt_ht_find(ours.table(), key, idx);
			if (ourSlot == NULL) {
				continue;
			}
			zv::Ref holder = zv::Ref(ourSlot).deref();
			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return zv::Val();
			}
			if (instanceof_function(holderExpr(holder)->ce, virtualNodeCe)) {
				continue;
			}
			zval *mergedSlot = pt_ht_find(merged.table(), key, idx);
			if (mergedSlot == NULL) {
				continue;
			}
			if (pt_holder_certainty_value(holder.asObject()) != PT_TRI_YES) {
				continue;
			}
			if (pt_ht_exists(guardsToExclude.table(), key, idx)) {
				continue;
			}
			zval *theirSlot = pt_ht_find(theirs.table(), key, idx);
			if (theirSlot != NULL) {
				zv::Ref theirHolder = zv::Ref(theirSlot).deref();
				if (UNEXPECTED(!pt_check_holder(theirHolder.raw()))) {
					return zv::Val();
				}
				if (pt_holder_certainty_value(theirHolder.asObject()) != PT_TRI_YES) {
					continue;
				}
			}
			bool equalTypes;
			{
				zv::Ref mergedHolder = zv::Ref(mergedSlot).deref();
				if (UNEXPECTED(!pt_check_holder(mergedHolder.raw()))) {
					return zv::Val();
				}
				if (UNEXPECTED(!pt_holder_equal_types(mergedHolder.raw(), holder.raw(), &equalTypes))) {
					return zv::Val();
				}
			}
			if (equalTypes) {
				continue;
			}

			/* borrowed entry — the scratch table has no destructor */
			zval borrowed;
			ZVAL_COPY_VALUE(&borrowed, holder.raw());
			pt_ht_update(typeGuards.table(), key, idx, &borrowed);
		}

		if (typeGuards.size() == 0) {
			return zv::Arr::copyOfTable(conditional.table());
		}

		/* Both isSuperTypeOf() results depend only on the guard, not on the
		 * target expression — cache them per guard across the target loop. */
		zv::ScratchTable guardIsSuperTypeOfTheirExprCache(8);
		zv::ScratchTable theirExprIsSuperTypeOfGuardCache(8);
		zv::ScratchTable guardIsConstantArrayCache(8);
		zv::Arr result; /* stays UNDEF until the first append duplicates the input */

		/* main loop: pair non-merged expressions with guards */
		for (auto diffEntry : differingKeys) {
			zend_string *key = diffEntry.stringKeyOrNull();
			zend_ulong idx = diffEntry.indexKey();

			zval *ourSlot = pt_ht_find(ours.table(), key, idx);
			if (ourSlot == NULL) {
				continue;
			}
			zv::Ref holder = zv::Ref(ourSlot).deref();

			if (instanceof_function(holderExpr(holder)->ce, virtualNodeCe)) {
				continue;
			}
			zval *mergedSlot = pt_ht_find(merged.table(), key, idx);
			if (mergedSlot != NULL) {
				zv::Ref mergedHolder = zv::Ref(mergedSlot).deref();
				bool equal;
				if (UNEXPECTED(!pt_holder_equals(mergedHolder.raw(), holder.raw(), &equal))) {
					return zv::Val();
				}
				if (equal) {
					continue;
				}
			}

			bool hasSelfGuard = pt_ht_exists(typeGuards.table(), key, idx);
			if (typeGuards.size() - (hasSelfGuard ? 1 : 0) == 0) {
				continue;
			}
			bool exprIsGuardExcluded = pt_ht_exists(guardsToExclude.table(), key, idx);

			for (auto guardEntry : zv::TableRef(typeGuards.table())) {
				zend_string *guardKey = guardEntry.stringKeyOrNull();
				zend_ulong guardIdx = guardEntry.indexKey();
				zv::Ref guardHolder = guardEntry.value();

				if (sameDualKey(guardKey, guardIdx, key, idx)) {
					continue;
				}

				if (exprIsGuardExcluded) {
					/* a subtype-absorbed target paired with a constant-array
					 * guard never helps — skip before pricing supertype checks */
					zval *cached = pt_ht_find(guardIsConstantArrayCache.table(), guardKey, guardIdx);
					bool isConstantArray;
					if (cached != NULL) {
						isConstantArray = Z_TYPE_P(cached) == IS_TRUE;
					} else {
						if (UNEXPECTED(!isConstantArrayYes(holderType(guardHolder), &isConstantArray))) {
							return zv::Val();
						}
						zval cacheVal;
						ZVAL_BOOL(&cacheVal, isConstantArray);
						pt_ht_update(guardIsConstantArrayCache.table(), guardKey, guardIdx, &cacheVal);
					}
					if (isConstantArray) {
						continue;
					}
				}

				zval *theirGuardSlot = pt_ht_find(theirs.table(), guardKey, guardIdx);
				if (theirGuardSlot != NULL) {
					zv::Ref theirGuard = zv::Ref(theirGuardSlot).deref();
					if (pt_holder_certainty_value(theirGuard.asObject()) == PT_TRI_YES) {
						zend_long guardIsSuperTypeOfTheirExpr;
						zval *cached = pt_ht_find(guardIsSuperTypeOfTheirExprCache.table(), guardKey, guardIdx);
						if (cached != NULL) {
							guardIsSuperTypeOfTheirExpr = Z_LVAL_P(cached);
						} else {
							if (UNEXPECTED(!isSuperTypeOfValue(holderType(guardHolder), holderType(theirGuard), &guardIsSuperTypeOfTheirExpr))) {
								return zv::Val();
							}
							zval cacheVal;
							ZVAL_LONG(&cacheVal, guardIsSuperTypeOfTheirExpr);
							pt_ht_update(guardIsSuperTypeOfTheirExprCache.table(), guardKey, guardIdx, &cacheVal);
						}

						if (guardIsSuperTypeOfTheirExpr == PT_TRI_YES) {
							continue;
						}

						bool skip = false;
						zval *theirExprSlot = pt_ht_find(theirs.table(), key, idx);
						if (theirExprSlot != NULL) {
							zv::Ref theirExprHolder = zv::Ref(theirExprSlot).deref();
							if (pt_holder_certainty_value(theirExprHolder.asObject()) == PT_TRI_YES && guardIsSuperTypeOfTheirExpr != PT_TRI_NO) {
								skip = true;
							}
						} else if (guardIsSuperTypeOfTheirExpr != PT_TRI_NO) {
							bool typesEqual = pt_types_identical_or_equal(holderType(holder), holderType(guardHolder));
							if (UNEXPECTED(EG(exception))) {
								return zv::Val();
							}
							if (typesEqual) {
								skip = true;
							}
						}

						/* the reverse isSuperTypeOf() is priced last, only
						 * when the cheaper conditions did not already decide */
						if (!skip) {
							zend_long theirExprIsSuperTypeOfGuard;
							zval *cachedReverse = pt_ht_find(theirExprIsSuperTypeOfGuardCache.table(), guardKey, guardIdx);
							if (cachedReverse != NULL) {
								theirExprIsSuperTypeOfGuard = Z_LVAL_P(cachedReverse);
							} else {
								if (UNEXPECTED(!isSuperTypeOfValue(holderType(theirGuard), holderType(guardHolder), &theirExprIsSuperTypeOfGuard))) {
									return zv::Val();
								}
								zval cacheVal;
								ZVAL_LONG(&cacheVal, theirExprIsSuperTypeOfGuard);
								pt_ht_update(theirExprIsSuperTypeOfGuardCache.table(), guardKey, guardIdx, &cacheVal);
							}
							if (theirExprIsSuperTypeOfGuard == PT_TRI_YES) {
								skip = true;
							}
						}

						if (skip) {
							continue;
						}
					}
				}

				if (UNEXPECTED(!appendConditional(result, conditional, key, idx, guardKey, guardIdx, guardHolder, holder))) {
					return zv::Val();
				}
			}
		}

		/* their-only expressions: record certainty-No conditionals per guard */
		for (auto diffEntry : differingKeys) {
			zend_string *key = diffEntry.stringKeyOrNull();
			zend_ulong idx = diffEntry.indexKey();

			zval *mergedSlot = pt_ht_find(merged.table(), key, idx);
			if (mergedSlot == NULL) {
				continue;
			}
			if (pt_ht_exists(ours.table(), key, idx)) {
				continue;
			}
			zv::Ref mergedHolder = zv::Ref(mergedSlot).deref();
			if (UNEXPECTED(!pt_check_holder(mergedHolder.raw()))) {
				return zv::Val();
			}
			if (instanceof_function(holderExpr(mergedHolder)->ce, virtualNodeCe)) {
				continue;
			}

			for (auto guardEntry : zv::TableRef(typeGuards.table())) {
				zv::Val noHolder = createNoErrorHolder(zv::ObjRef(mergedHolder.asObject()).propAt(PT_ETH_PROP_EXPR).raw());
				if (UNEXPECTED(noHolder.isUndef())) {
					return zv::Val();
				}
				if (UNEXPECTED(!appendConditional(result, conditional, key, idx, guardEntry.stringKeyOrNull(), guardEntry.indexKey(), guardEntry.value(), noHolder.ref()))) {
					return zv::Val();
				}
			}
		}

		if (!result.isUndef()) {
			return zv::Val(std::move(result));
		}
		return zv::Arr::copyOfTable(conditional.table());
	}

	/*
	 * Mirrors ScopeOps::invalidateMethodsOnExpression(): drops tracked
	 * MethodCall expressions whose var matches the invalidated key. Returns
	 * null when nothing changed.
	 */
	static zv::Val invalidateMethodsOnExpression(zval *exprPrinter, zend_string *exprStringToInvalidate, zv::TableRef expressionTypes, zv::TableRef nativeExpressionTypes)
	{
		zend_class_entry *methodCallCe = pt_class(PT_CLASS_METHOD_CALL);
		if (UNEXPECTED(methodCallCe == NULL)) {
			return zv::Val();
		}

		bool invalidated = false;
		zv::Arr resultExpr, resultNative; /* stay UNDEF until the first hit */

		/* Same compositional-key shortcut as in invalidateExpressionEntries(): a
		 * method call's key embeds its receiver's key verbatim, so when the
		 * invalidated key does not occur in the entry's key, the receiver cannot
		 * match and the entry can be kept without re-printing the receiver. */
		const bool canUseKeyPrefilter = !keyMayHideSubExpressions(exprStringToInvalidate)
			&& !strContains(exprStringToInvalidate, "/*", 2);

		for (auto entry : expressionTypes) {
			zend_string *entryKey = entry.stringKeyOrNull();
			if (canUseKeyPrefilter && entryKey != NULL
				&& !strContainsStr(entryKey, exprStringToInvalidate)
				&& !keyMayHideSubExpressions(entryKey)) {
				continue;
			}
			zv::Ref holder = entry.value().deref();
			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return zv::Val();
			}
			zend_object *expr = holderExpr(holder);
			if (!instanceof_function(expr->ce, methodCallCe)) {
				continue;
			}
			int32_t varOffset = pt_instance_prop_offset(expr->ce, "var", sizeof("var") - 1);
			if (varOffset < 0) {
				continue;
			}
			zv::Ref var = zv::ObjRef(expr).propAtOffset((uint32_t) varOffset).deref();
			if (!var.isObject()) {
				continue;
			}
			zv::Str varKey = zv::Str::adopt(pt_node_key(var.asObject(), exprPrinter));
			if (UNEXPECTED(varKey.isNull())) {
				return zv::Val();
			}
			if (!zend_string_equals(varKey.get(), exprStringToInvalidate)) {
				continue;
			}

			if (resultExpr.isUndef()) {
				resultExpr = zv::Arr::adoptTable(zend_array_dup(expressionTypes.table()));
				resultNative = zv::Arr::adoptTable(zend_array_dup(nativeExpressionTypes.table()));
			}
			pt_ht_del(resultExpr.table(), entry.stringKeyOrNull(), entry.indexKey());
			pt_ht_del(resultNative.table(), entry.stringKeyOrNull(), entry.indexKey());
			invalidated = true;
		}

		if (!invalidated) {
			return zv::Val::null();
		}

		zv::Arr result = zv::Arr::create(2);
		result.push(std::move(resultExpr));
		result.push(std::move(resultNative));
		return zv::Val(std::move(result));
	}

	/*
	 * Mirrors ScopeOps::invalidateExpressionEntries(). Returns
	 * [expressionTypes, nativeExpressionTypes, conditionalExpressions] with
	 * the invalidated entries removed, or null when nothing changed; the
	 * expression tables are only duplicated once an entry is invalidated.
	 */
	static zv::Val invalidateExpressionEntries(
		zval *scope,
		zval *exprPrinter,
		zend_string *exprStringToInvalidate,
		zval *expressionToInvalidate,
		bool requireMoreCharacters,
		zval *invalidatingClass,
		zv::TableRef expressionTypes,
		zv::TableRef nativeExpressionTypes,
		zv::TableRef conditionalExpressions)
	{
		InvalidationQuery query = {
			scope,
			exprPrinter,
			exprStringToInvalidate,
			expressionToInvalidate,
			invalidatingClass,
			zend_string_equals_literal(exprStringToInvalidate, "$this"),
		};

		/* Mirrors the twin's $canUseKeyPrefilter: outside shouldInvalidate()'s
		 * compositional-key carve-outs, a key that does not contain the
		 * invalidated key as a substring cannot belong to an expression
		 * containing the invalidated one, so the much more expensive
		 * per-expression check can be skipped without being called. */
		const bool canUseKeyPrefilter = !query.isThis
			&& !keyMayHideSubExpressions(exprStringToInvalidate)
			&& !strContains(exprStringToInvalidate, "/*", 2);

		bool invalidated = false;
		zv::Arr resultExpr, resultNative; /* stay UNDEF until the first hit */

		for (auto entry : expressionTypes) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();

			if (canUseKeyPrefilter && key != NULL
				&& !strContainsStr(key, exprStringToInvalidate)
				&& !keyMayHideSubExpressions(key)) {
				continue;
			}

			zv::Ref holder = entry.value().deref();

			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return zv::Val();
			}
			zend_object *expr = holderExpr(holder);
			zend_string *entryKey = key != NULL ? key : zend_long_to_str((zend_long) idx);
			bool failed = false;
			bool should = shouldInvalidate(query, entryKey, expr, requireMoreCharacters, &failed);
			if (key == NULL) {
				zend_string_release(entryKey);
			}
			if (!should) {
				if (UNEXPECTED(failed)) {
					return zv::Val();
				}
				continue;
			}
			if (resultExpr.isUndef()) {
				resultExpr = zv::Arr::adoptTable(zend_array_dup(expressionTypes.table()));
				resultNative = zv::Arr::adoptTable(zend_array_dup(nativeExpressionTypes.table()));
			}
			pt_ht_del(resultExpr.table(), key, idx);
			pt_ht_del(resultNative.table(), key, idx);
			invalidated = true;
		}

		zv::Arr resultConditional = zv::Arr::create(0);
		for (auto entry : conditionalExpressions) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();
			zv::Ref holders = entry.value().deref();

			if (!holders.isArray()) {
				continue;
			}
			zv::TableRef holdersTable(holders.asArrayTable());
			if (holdersTable.size() == 0) {
				continue;
			}

			/* first holder's type-holder expr decides whole-group invalidation */
			if (!canUseKeyPrefilter
				|| key == NULL
				|| strContainsStr(key, exprStringToInvalidate)
				|| keyMayHideSubExpressions(key)) {
				zv::Ref firstHolder = (*holdersTable.begin()).value().deref();
				if (UNEXPECTED(!firstHolder.instanceOf(pt_ce_cond_expr_holder))) {
					zend_type_error("phpstan_turbo: expected ConditionalExpressionHolder");
					return zv::Val();
				}
				zv::Ref firstTypeHolder = zv::ObjRef(firstHolder.asObject()).propAt(PT_CEH_PROP_TYPEHOLDER).deref();
				if (UNEXPECTED(!pt_check_holder(firstTypeHolder.raw()))) {
					return zv::Val();
				}
				zend_object *firstExpr = holderExpr(firstTypeHolder);
				zv::Str firstKey = zv::Str::adopt(pt_node_key(firstExpr, exprPrinter));
				if (UNEXPECTED(firstKey.isNull())) {
					return zv::Val();
				}
				bool failed = false;
				bool drop = shouldInvalidate(query, firstKey.get(), firstExpr, requireMoreCharacters, &failed);
				if (UNEXPECTED(failed)) {
					return zv::Val();
				}
				if (drop) {
					invalidated = true;
					continue;
				}
			}

			/* Lazily materialized: stays UNDEF while every holder seen so far
			 * is kept, so the common no-drop case reuses the original array
			 * instead of rebuilding it holder by holder. */
			zv::Arr filtered;
			uint32_t keptCount = 0;
			for (auto holderEntry : holdersTable) {
				zend_string *holderKey = holderEntry.stringKeyOrNull();
				zv::Ref holder = holderEntry.value().deref();

				/* The holder's array key (ConditionalExpressionHolder::getKey())
				 * embeds every condition's expression key verbatim, so when the
				 * invalidated key does not occur in it, none of the conditions
				 * can contain the invalidated expression and the holder can be
				 * kept without inspecting its conditions. */
				if (canUseKeyPrefilter && holderKey != NULL
					&& !strContainsStr(holderKey, exprStringToInvalidate)
					&& !keyMayHideSubExpressions(holderKey)) {
					if (!filtered.isUndef()) {
						tableAddNewCopy(filtered.table(), holderKey, holderEntry.indexKey(), holder);
					} else {
						keptCount++;
					}
					continue;
				}
				if (UNEXPECTED(!holder.instanceOf(pt_ce_cond_expr_holder))) {
					zend_type_error("phpstan_turbo: expected ConditionalExpressionHolder");
					return zv::Val();
				}
				bool keep = true;
				zv::Ref conditions = zv::ObjRef(holder.asObject()).propAt(PT_CEH_PROP_CONDS);
				if (conditions.isArray()) {
					for (auto conditionEntry : zv::TableRef(conditions.asArrayTable())) {
						zv::Ref conditionHolder = conditionEntry.value().deref();
						if (UNEXPECTED(!pt_check_holder(conditionHolder.raw()))) {
							return zv::Val();
						}
						zend_object *conditionExpr = holderExpr(conditionHolder);
						zend_string *conditionKey = conditionEntry.stringKeyOrNull();
						zend_string *conditionKeyStr = conditionKey != NULL ? conditionKey : zend_long_to_str((zend_long) conditionEntry.indexKey());
						bool failed = false;
						bool should = shouldInvalidate(query, conditionKeyStr, conditionExpr, false, &failed);
						if (conditionKey == NULL) {
							zend_string_release(conditionKeyStr);
						}
						if (should) {
							invalidated = true;
							keep = false;
							break;
						}
						if (UNEXPECTED(failed)) {
							return zv::Val();
						}
					}
				}
				if (keep) {
					if (!filtered.isUndef()) {
						tableAddNewCopy(filtered.table(), holderKey, holderEntry.indexKey(), holder);
					} else {
						keptCount++;
					}
					continue;
				}

				if (filtered.isUndef()) {
					/* copy the kept prefix (mirrors the twin's array_slice) */
					filtered = zv::Arr::create(keptCount);
					uint32_t copied = 0;
					for (auto keptEntry : holdersTable) {
						if (copied == keptCount) {
							break;
						}
						tableAddNewCopy(filtered.table(), keptEntry.stringKeyOrNull(), keptEntry.indexKey(), keptEntry.value().deref());
						copied++;
					}
				}
			}

			if (filtered.isUndef()) {
				/* nothing dropped — share the original holders array */
				tableAddNewCopy(resultConditional.table(), key, idx, holders);
				continue;
			}
			if (zend_hash_num_elements(filtered.table()) == 0) {
				continue;
			}
			tableAddNew(resultConditional.table(), key, idx, std::move(filtered));
		}

		if (!invalidated) {
			return zv::Val::null();
		}

		if (resultExpr.isUndef()) {
			/* only conditional expressions were invalidated */
			resultExpr = zv::Arr::adoptTable(zend_array_dup(expressionTypes.table()));
			resultNative = zv::Arr::adoptTable(zend_array_dup(nativeExpressionTypes.table()));
		}

		zv::Arr result = zv::Arr::create(3);
		result.push(std::move(resultExpr));
		result.push(std::move(resultNative));
		result.push(std::move(resultConditional));
		return zv::Val(std::move(result));
	}

	/* Mirrors ScopeOps::shouldInvalidateExpression(). */
	static bool shouldInvalidateExpression(zval *scope, zval *exprPrinter, zend_string *exprStringToInvalidate, zval *exprToInvalidate, zend_object *expr, zend_string *exprString, bool requireMoreCharacters, zval *invalidatingClass, bool *failed)
	{
		InvalidationQuery query = {
			scope,
			exprPrinter,
			exprStringToInvalidate,
			exprToInvalidate,
			invalidatingClass,
			zend_string_equals_literal(exprStringToInvalidate, "$this"),
		};
		return shouldInvalidate(query, exprString, expr, requireMoreCharacters, failed);
	}

	/* Mirrors ScopeOps::getIntertwinedRefRootVariableName(). */
	static zv::Val getIntertwinedRefRootVariableName(zend_object *expr)
	{
		zend_string *name = intertwinedRootVariableName(expr);
		if (name != NULL) {
			/* borrowed (points into a property) — copy for the caller */
			return zv::Val::string(name);
		}
		if (UNEXPECTED(EG(exception))) {
			return zv::Val();
		}
		return zv::Val::null();
	}

	/*
	 * Mirrors ScopeOps::matchConditionalExpressions(): the fixed-point loop
	 * with an exact-match pass and a supertype-match pass per expression.
	 * Returns [conditions, specifiedExpressions].
	 */
	static zv::Val matchConditionalExpressions(zv::TableRef conditionalExpressions, zv::TableRef specifiedInput)
	{
		zv::Arr conditions = zv::Arr::create(0);
		zv::Arr specified = zv::Arr::adoptTable(zend_array_dup(specifiedInput.table()));

		/* Every holder has at least one condition (enforced by the
		 * ConditionalExpressionHolder constructor) and both passes require all
		 * of a holder's conditions to be among the specified expressions, so
		 * with nothing specified nothing can ever match. */
		if (zend_hash_num_elements(specified.table()) == 0) {
			zv::Arr result = zv::Arr::create(2);
			result.push(std::move(conditions));
			result.push(std::move(specified));
			return zv::Val(std::move(result));
		}

		uint32_t previousCount = UINT32_MAX;

		while (zend_hash_num_elements(specified.table()) != previousCount) {
			previousCount = zend_hash_num_elements(specified.table());

			for (auto entry : conditionalExpressions) {
				zend_string *conditionalKey = entry.stringKeyOrNull();
				zend_ulong conditionalIdx = entry.indexKey();

				if (pt_ht_exists(conditions.table(), conditionalKey, conditionalIdx)) {
					continue;
				}
				zv::Ref holders = entry.value().deref();
				if (UNEXPECTED(!holders.isArray())) {
					continue;
				}
				zv::TableRef holdersTable(holders.asArrayTable());

				/* Pass 1: prefer exact matches */
				for (auto holderEntry : holdersTable) {
					zv::Ref holder = holderEntry.value().deref();
					if (UNEXPECTED(!holder.instanceOf(pt_ce_cond_expr_holder))) {
						zend_type_error("phpstan_turbo: expected ConditionalExpressionHolder");
						return zv::Val();
					}
					zv::Ref typeHolder = zv::ObjRef(holder.asObject()).propAt(PT_CEH_PROP_TYPEHOLDER);
					if (pt_holder_certainty_value(typeHolder.asObject()) == PT_TRI_NO
						&& pt_ht_exists(specifiedInput.table(), conditionalKey, conditionalIdx)) {
						continue;
					}
					zv::Ref conditionHolders = zv::ObjRef(holder.asObject()).propAt(PT_CEH_PROP_CONDS);
					if (UNEXPECTED(!conditionHolders.isArray())) {
						continue;
					}
					bool all = true;
					for (auto conditionEntry : zv::TableRef(conditionHolders.asArrayTable())) {
						zval *specifiedSlot = pt_ht_find(specified.table(), conditionEntry.stringKeyOrNull(), conditionEntry.indexKey());
						if (specifiedSlot == NULL) {
							all = false;
							break;
						}
						zv::Ref conditionHolder = conditionEntry.value().deref();
						zv::Ref specifiedHolder = zv::Ref(specifiedSlot).deref();
						if (UNEXPECTED(!pt_check_holder(conditionHolder.raw())) || UNEXPECTED(!pt_check_holder(specifiedHolder.raw()))) {
							return zv::Val();
						}
						bool equal;
						if (UNEXPECTED(!pt_holder_equals(conditionHolder.raw(), specifiedHolder.raw(), &equal))) {
							return zv::Val();
						}
						if (!equal) {
							all = false;
							break;
						}
					}
					if (!all) {
						continue;
					}

					recordMatchedCondition(conditions, specified, conditionalKey, conditionalIdx, holder, typeHolder);
				}

				if (pt_ht_exists(conditions.table(), conditionalKey, conditionalIdx)) {
					continue;
				}

				/* Pass 2: supertype match, only when Pass 1 found nothing */
				for (auto holderEntry : holdersTable) {
					zv::Ref holder = holderEntry.value().deref();
					zv::Ref typeHolder = zv::ObjRef(holder.asObject()).propAt(PT_CEH_PROP_TYPEHOLDER);
					if (pt_holder_certainty_value(typeHolder.asObject()) == PT_TRI_NO) {
						continue;
					}
					zv::Ref conditionHolders = zv::ObjRef(holder.asObject()).propAt(PT_CEH_PROP_CONDS);
					if (UNEXPECTED(!conditionHolders.isArray())) {
						continue;
					}
					bool all = true;
					for (auto conditionEntry : zv::TableRef(conditionHolders.asArrayTable())) {
						zval *specifiedSlot = pt_ht_find(specified.table(), conditionEntry.stringKeyOrNull(), conditionEntry.indexKey());
						if (specifiedSlot == NULL) {
							all = false;
							break;
						}
						zv::Ref conditionHolder = conditionEntry.value().deref();
						zv::Ref specifiedHolder = zv::Ref(specifiedSlot).deref();
						/* Pass 1 validates only the entries it reaches before
						 * its first mismatch, so these can be unchecked here;
						 * the twin raises a catchable Error on wrong types */
						if (UNEXPECTED(!pt_check_holder(conditionHolder.raw()) || !pt_check_holder(specifiedHolder.raw()))) {
							return zv::Val();
						}
						if (pt_holder_certainty_value(conditionHolder.asObject()) != pt_holder_certainty_value(specifiedHolder.asObject())) {
							all = false;
							break;
						}
						zend_long superTypeOf;
						if (UNEXPECTED(!isSuperTypeOfValue(holderType(conditionHolder), holderType(specifiedHolder), &superTypeOf))) {
							return zv::Val();
						}
						if (superTypeOf != PT_TRI_YES) {
							all = false;
							break;
						}
					}
					if (!all) {
						continue;
					}

					recordMatchedCondition(conditions, specified, conditionalKey, conditionalIdx, holder, typeHolder);
				}
			}
		}

		zv::Arr result = zv::Arr::create(2);
		result.push(std::move(conditions));
		result.push(std::move(specified));
		return zv::Val(std::move(result));
	}

private:
	static zv::Val trinarySingleton(zend_long value)
	{
		return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
	}

	/* Scope property slot by name, deref'd; NULL + throw when missing. */
	static zval *scopeProp(zval *scope, const char *name, size_t len)
	{
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&Z_OBJCE_P(scope)->properties_info, name, len);
		if (UNEXPECTED(info == NULL || (info->flags & ZEND_ACC_STATIC) != 0)) {
			zend_throw_error(NULL, "phpstan_turbo: %s property not found", name);
			return NULL;
		}
		zval *slot = OBJ_PROP(Z_OBJ_P(scope), info->offset);
		ZVAL_DEREF(slot);
		return slot;
	}

	/* Like scopeProp(), but the property must hold an array. */
	static zval *scopeArrayProp(zval *scope, const char *name, size_t len)
	{
		zval *table = scopeProp(scope, name, len);
		if (UNEXPECTED(table == NULL)) {
			return NULL;
		}
		if (UNEXPECTED(Z_TYPE_P(table) != IS_ARRAY)) {
			zend_throw_error(NULL, "phpstan_turbo: %s is not an array", name);
			return NULL;
		}
		return table;
	}

	/* '$' . $name */
	static zv::Str dollarPrefixed(zend_string *name)
	{
		zend_string *key = zend_string_alloc(ZSTR_LEN(name) + 1, 0);
		ZSTR_VAL(key)[0] = '$';
		memcpy(ZSTR_VAL(key) + 1, ZSTR_VAL(name), ZSTR_LEN(name));
		ZSTR_VAL(key)[ZSTR_LEN(key)] = '\0';
		return zv::Str::adopt(key);
	}

	/* $table[$key] = $value for a key known absent (addref-copy) */
	static void tableAddNewCopy(HashTable *table, zend_string *skey, zend_ulong idx, zv::Ref value)
	{
		Z_TRY_ADDREF_P(value.raw());
		pt_ht_add_new(table, skey, idx, value.raw());
	}

	/* $table[$key] = $value for a key known absent (consumes the value) */
	static void tableAddNew(HashTable *table, zend_string *skey, zend_ulong idx, zv::Val value)
	{
		zval v = value.take();
		pt_ht_add_new(table, skey, idx, &v);
	}

	/* $table[$key] = $value (addref-copy, overwrites) */
	static void tableUpdateCopy(HashTable *table, zend_string *skey, zend_ulong idx, zv::Ref value)
	{
		Z_TRY_ADDREF_P(value.raw());
		pt_ht_update(table, skey, idx, value.raw());
	}

	/* $obj->prop = $table (addref-copy; immutable [] handled by copyOfTable) */
	static void setTableProp(zv::ObjRef obj, int32_t offset, HashTable *value)
	{
		obj.propAtOffset((uint32_t) offset).assign(zv::Arr::copyOfTable(value));
	}

	/* $obj->prop = [] — a memo reset to the fresh-constructor default */
	static void resetToEmptyArray(zv::ObjRef obj, int32_t offset)
	{
		if (offset < 0) {
			return;
		}
		obj.propAtOffset((uint32_t) offset).assign(zv::Arr::empty());
	}

	/* $obj->prop = null — a memo reset to the fresh-constructor default */
	static void resetToNull(zv::ObjRef obj, int32_t offset)
	{
		if (offset < 0) {
			return;
		}
		obj.propAtOffset((uint32_t) offset).assign(zv::Val::null());
	}

	/* $holder->expr for a checked ExpressionTypeHolder */
	static zend_object *holderExpr(zv::Ref holder)
	{
		return zv::ObjRef(holder.asObject()).propAt(PT_ETH_PROP_EXPR).asObject();
	}

	/* &$holder->type slot for a checked ExpressionTypeHolder */
	static zval *holderType(zv::Ref holder)
	{
		return zv::ObjRef(holder.asObject()).propAt(PT_ETH_PROP_TYPE).raw();
	}

	static bool sameDualKey(zend_string *aKey, zend_ulong aIdx, zend_string *bKey, zend_ulong bIdx)
	{
		return aKey != NULL ? (bKey != NULL && zend_string_equals(aKey, bKey)) : (bKey == NULL && aIdx == bIdx);
	}

	/* ExpressionTypeHolder::createMaybe($holder->expr, $holder->type) */
	static zv::Val createMaybeHolder(zv::Ref holder)
	{
		zv::ObjRef holderObj(holder.asObject());
		zval created;
		pt_holder_create(&created, holderObj.propAt(PT_ETH_PROP_EXPR).raw(), holderObj.propAt(PT_ETH_PROP_TYPE).raw(), PT_TRI_MAYBE);
		return zv::Val::adopt(created);
	}

	/* $differing[$key] = true (marker insert, overwrites) */
	static void markDiffering(HashTable *differing, zend_string *skey, zend_ulong idx)
	{
		if (differing == NULL) {
			return;
		}
		zval trueZv;
		ZVAL_TRUE(&trueZv);
		pt_ht_update(differing, skey, idx, &trueZv);
	}

	/* The two loops of mergeVariableHolders(), filling a caller-owned table. */
	static bool mergeVariableHoldersInto(zv::Arr &merged, zv::TableRef ours, zv::TableRef theirs, HashTable *differing)
	{
		for (auto entry : ours) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();
			zv::Ref holder = entry.value().deref();

			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return false;
			}

			zval *theirSlot = pt_ht_find(theirs.table(), key, idx);
			if (theirSlot != NULL) {
				zv::Ref theirHolder = zv::Ref(theirSlot).deref();
				if (UNEXPECTED(!pt_check_holder(theirHolder.raw()))) {
					return false;
				}
				if (holder.asObject() == theirHolder.asObject()) {
					tableAddNewCopy(merged.table(), key, idx, holder);
				} else {
					markDiffering(differing, key, idx);
					zval andHolder;
					if (UNEXPECTED(!pt_holder_and(holder.raw(), theirHolder.raw(), &andHolder))) {
						return false;
					}
					tableAddNew(merged.table(), key, idx, zv::Val::adopt(andHolder));
				}
			} else {
				markDiffering(differing, key, idx);
				bool containsSuperGlobal = pt_expr_contains_superglobal(holderExpr(holder));
				if (UNEXPECTED(EG(exception))) {
					return false;
				}
				if (containsSuperGlobal) {
					continue;
				}
				tableAddNew(merged.table(), key, idx, createMaybeHolder(holder));
			}
		}

		for (auto entry : theirs) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong idx = entry.indexKey();

			if (pt_ht_exists(merged.table(), key, idx)) {
				continue;
			}
			markDiffering(differing, key, idx);
			zv::Ref holder = entry.value().deref();
			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return false;
			}
			bool containsSuperGlobal = pt_expr_contains_superglobal(holderExpr(holder));
			if (UNEXPECTED(EG(exception))) {
				return false;
			}
			if (containsSuperGlobal) {
				continue;
			}
			tableAddNew(merged.table(), key, idx, createMaybeHolder(holder));
		}

		return true;
	}

	/* finishMerge()'s $filter closure for one holder */
	static bool filterKeepsHolder(zend_object *holder, bool *keep)
	{
		if (pt_holder_certainty_value(holder) == PT_TRI_YES) {
			*keep = true;
			return true;
		}
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *funcCallCe = pt_class(PT_CLASS_FUNC_CALL);
		zend_class_entry *virtualNodeCe = pt_class(PT_CLASS_VIRTUAL_NODE);
		if (UNEXPECTED(variableCe == NULL || funcCallCe == NULL || virtualNodeCe == NULL)) {
			return false;
		}
		zend_class_entry *exprCe = zv::ObjRef(holder).propAt(PT_ETH_PROP_EXPR).asObject()->ce;
		*keep = instanceof_function(exprCe, variableCe)
			|| instanceof_function(exprCe, funcCallCe)
			|| instanceof_function(exprCe, virtualNodeCe);
		return true;
	}

	/* array_filter($holders, $filter) of finishMerge() */
	static bool filterHolders(zv::TableRef input, zv::Arr &filtered)
	{
		filtered = zv::Arr::create(input.size());
		for (auto entry : input) {
			zv::Ref holder = entry.value().deref();
			if (UNEXPECTED(!pt_check_holder(holder.raw()))) {
				return false;
			}
			bool keep;
			if (UNEXPECTED(!filterKeepsHolder(holder.asObject(), &keep))) {
				return false;
			}
			if (keep) {
				tableAddNewCopy(filtered.table(), entry.stringKeyOrNull(), entry.indexKey(), holder);
			}
		}
		return true;
	}

	/* Calls a (possibly private) method on the object with the given args. */
	static bool callObjectMethod(zval *obj, const char *lcname, size_t len, uint32_t argc, zval *argv, zval *retval)
	{
		zend_class_entry *ce = Z_OBJCE_P(obj);
		zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
		if (UNEXPECTED(fn == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", ZSTR_VAL(ce->name), lcname);
			return false;
		}
		zend_call_known_function(fn, Z_OBJ_P(obj), ce, retval, argc, argv, NULL);
		return !EG(exception);
	}

	/* $type->isSuperTypeOf($otherType)->result->value */
	static bool isSuperTypeOfValue(zval *type, zval *otherType, zend_long *out)
	{
		zval arg, retval;
		ZVAL_COPY_VALUE(&arg, otherType);
		if (UNEXPECTED(!callObjectMethod(type, "issupertypeof", sizeof("issupertypeof") - 1, 1, &arg, &retval))) {
			return false;
		}
		zv::Val result = zv::Val::adopt(retval);
		if (UNEXPECTED(!result.ref().isObject())) {
			zend_throw_error(NULL, "phpstan_turbo: isSuperTypeOf did not return an object");
			return false;
		}
		int32_t resultOffset = pt_instance_prop_offset(Z_OBJCE_P(result.raw()), "result", sizeof("result") - 1);
		if (UNEXPECTED(resultOffset < 0)) {
			zend_throw_error(NULL, "phpstan_turbo: IsSuperTypeOfResult::result not found");
			return false;
		}
		zv::Ref resultProp = zv::ObjRef(result.ref().asObject()).propAtOffset((uint32_t) resultOffset).deref();
		if (UNEXPECTED(!resultProp.instanceOf(pt_ce_trinary))) {
			zend_throw_error(NULL, "phpstan_turbo: IsSuperTypeOfResult::result is not a TrinaryLogic");
			return false;
		}
		*out = pt_trinary_value(resultProp.asObject());
		return true;
	}

	/* $type->isConstantArray()->yes() */
	static bool isConstantArrayYes(zval *type, bool *out)
	{
		zval retval;
		if (UNEXPECTED(!callObjectMethod(type, "isconstantarray", sizeof("isconstantarray") - 1, 0, NULL, &retval))) {
			return false;
		}
		zv::Val result = zv::Val::adopt(retval);
		if (UNEXPECTED(!result.ref().instanceOf(pt_ce_trinary))) {
			zend_throw_error(NULL, "phpstan_turbo: isConstantArray did not return a TrinaryLogic");
			return false;
		}
		*out = pt_trinary_value(result.ref().asObject()) == PT_TRI_YES;
		return true;
	}

	/* new ExpressionTypeHolder($expr, new ErrorType(), TrinaryLogic::createNo()) */
	static zv::Val createNoErrorHolder(zval *exprSlot)
	{
		zend_class_entry *errorTypeCe = pt_class(PT_CLASS_ERROR_TYPE);
		if (UNEXPECTED(errorTypeCe == NULL)) {
			return zv::Val();
		}
		zval errorTypeRaw;
		object_init_ex(&errorTypeRaw, errorTypeCe);
		zv::Val errorType = zv::Val::adopt(errorTypeRaw);
		if (errorTypeCe->constructor != NULL) {
			zend_call_known_instance_method(errorTypeCe->constructor, errorType.ref().asObject(), NULL, 0, NULL);
			if (UNEXPECTED(EG(exception))) {
				return zv::Val();
			}
		}
		/* pt_holder_create copies the type; the local ErrorType ref is released */
		zval holder;
		pt_holder_create(&holder, exprSlot, errorType.raw(), PT_TRI_NO);
		return zv::Val::adopt(holder);
	}

	/*
	 * Appends new ConditionalExpressionHolder([$guardKey => $guardHolder], $typeHolder)
	 * under $result[$exprKey][getKey()]. Duplicates the input array into
	 * $result lazily on the first append.
	 */
	static bool appendConditional(zv::Arr &result, zv::TableRef input, zend_string *skey, zend_ulong idx, zend_string *guardKey, zend_ulong guardIdx, zv::Ref guardHolder, zv::Ref typeHolder)
	{
		zv::Arr conditions = zv::Arr::create(1);
		tableAddNewCopy(conditions.table(), guardKey, guardIdx, guardHolder);

		zv::Str cehKey = zv::Str::adopt(pt_ceh_key_build(conditions.table(), typeHolder.raw()));
		if (UNEXPECTED(cehKey.isNull())) {
			return false;
		}

		zval cehRaw;
		object_init_ex(&cehRaw, pt_impl_class(PT_CLASS_CEH, pt_ce_cond_expr_holder));
		zv::Val ceh = zv::Val::adopt(cehRaw);
		zv::ObjRef cehObj(ceh.ref().asObject());
		cehObj.propAtWrite(PT_CEH_PROP_CONDS, std::move(conditions));
		cehObj.propAtWrite(PT_CEH_PROP_TYPEHOLDER, zv::Val::copyOf(typeHolder));

		if (result.isUndef()) {
			result = zv::Arr::adoptTable(zend_array_dup(input.table()));
		}

		zval *inner = pt_ht_find(result.table(), skey, idx);
		if (inner == NULL) {
			zval newInner;
			array_init(&newInner);
			pt_ht_add_new(result.table(), skey, idx, &newInner);
			inner = pt_ht_find(result.table(), skey, idx);
		} else {
			ZVAL_DEREF(inner);
			if (UNEXPECTED(Z_TYPE_P(inner) != IS_ARRAY)) {
				zend_throw_error(NULL, "phpstan_turbo: conditional expressions entry is not an array");
				return false;
			}
			SEPARATE_ARRAY(inner);
		}

		zval cehOut = ceh.take();
		zend_hash_update(Z_ARRVAL_P(inner), cehKey.get(), &cehOut);
		return true;
	}

	/* Everything shouldInvalidate() needs to know about one invalidation. */
	struct InvalidationQuery
	{
		zval *scope;
		zval *exprPrinter;
		zend_string *exprStringToInvalidate;
		zval *expressionToInvalidate;
		zval *invalidatingClass; /* may be NULL */
		bool isThis;
	};

	static bool strContains(zend_string *haystack, const char *needle, size_t len)
	{
		return zend_memnstr(ZSTR_VAL(haystack), needle, len, ZSTR_VAL(haystack) + ZSTR_LEN(haystack)) != NULL;
	}

	/*
	 * Mirrors ScopeOps::keyMayHideSubExpressions(): a '__phpstan' occurrence
	 * that does not start one of the compositional virtual-node wrappers
	 * signals a key that may textually hide its sub-expressions.
	 */
	static bool keyMayHideSubExpressions(zend_string *key)
	{
		/* Mirror of ScopeOps::COMPOSITIONAL_VIRTUAL_KEY_PREFIXES - a prefix may
		 * be listed only when the printer emits every getSubNodeNames() sub-node
		 * verbatim (or the node walks no sub-nodes at all); the foreach/parameter
		 * original-value markers hide a synthesized Variable child on purpose. */
		static const struct { const char *prefix; size_t len; } compositionalPrefixes[] = {
			{ "__phpstanForeachValueByRef(", sizeof("__phpstanForeachValueByRef(") - 1 },
			{ "__phpstanIntertwinedVariableByReference(", sizeof("__phpstanIntertwinedVariableByReference(") - 1 },
			{ "__phpstanPossiblyImpure(", sizeof("__phpstanPossiblyImpure(") - 1 },
			{ "__phpstanPropertyInitialization(", sizeof("__phpstanPropertyInitialization(") - 1 },
			{ "__phpstanRemembered(", sizeof("__phpstanRemembered(") - 1 },
			{ "__phpstanVariableWritten(", sizeof("__phpstanVariableWritten(") - 1 },
		};

		const char *pos = ZSTR_VAL(key);
		const char *end = pos + ZSTR_LEN(key);
		for (;;) {
			const char *found = zend_memnstr(pos, "__phpstan", sizeof("__phpstan") - 1, end);
			if (found == NULL) {
				return false;
			}
			bool isCompositional = false;
			for (const auto &candidate : compositionalPrefixes) {
				if ((size_t) (end - found) >= candidate.len && memcmp(found, candidate.prefix, candidate.len) == 0) {
					pos = found + candidate.len;
					isCompositional = true;
					break;
				}
			}
			if (!isCompositional) {
				return true;
			}
		}
	}

	static bool strContainsStr(zend_string *haystack, zend_string *needle)
	{
		return ZSTR_LEN(needle) <= ZSTR_LEN(haystack)
			&& zend_memnstr(ZSTR_VAL(haystack), ZSTR_VAL(needle), ZSTR_LEN(needle), ZSTR_VAL(haystack) + ZSTR_LEN(haystack)) != NULL;
	}

	/* shouldInvalidate()'s per-node callback for pt_find_first_recursive() */
	static bool invalidationMatcher(zend_object *node, void *rawCtx)
	{
		pt_find_ctx *ctx = (pt_find_ctx *) rawCtx;

		if (ctx->is_this) {
			zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
			if (UNEXPECTED(nameCe == NULL)) {
				ctx->failed = true;
				return false;
			}
			if (instanceof_function(node->ce, nameCe)) {
				/* toLowerString() in [self, static, parent]? */
				zend_function *toLower = (zend_function *) zend_hash_str_find_ptr(&node->ce->function_table, "tolowerstring", sizeof("tolowerstring") - 1);
				if (UNEXPECTED(toLower == NULL)) {
					ctx->failed = true;
					return false;
				}
				zval lowerRaw;
				zend_call_known_function(toLower, node, node->ce, &lowerRaw, 0, NULL, NULL);
				if (UNEXPECTED(EG(exception))) {
					ctx->failed = true;
					return false;
				}
				{
					zv::Val lower = zv::Val::adopt(lowerRaw);
					if (lower.ref().stringEquals("self")
						|| lower.ref().stringEquals("static")
						|| lower.ref().stringEquals("parent")) {
						return true;
					}
				}

				/* getClassReflection() !== null && getClassReflection()->is(resolveName($node)) */
				if (!ctx->class_reflection_fetched) {
					zend_class_entry *scopeCe = Z_OBJCE_P(ctx->scope);
					zend_function *getClassReflection = (zend_function *) zend_hash_str_find_ptr(&scopeCe->function_table, "getclassreflection", sizeof("getclassreflection") - 1);
					if (UNEXPECTED(getClassReflection == NULL)) {
						ctx->failed = true;
						return false;
					}
					zend_call_known_function(getClassReflection, Z_OBJ_P(ctx->scope), scopeCe, &ctx->class_reflection, 0, NULL, NULL);
					if (UNEXPECTED(EG(exception))) {
						ctx->failed = true;
						return false;
					}
					ctx->class_reflection_fetched = true;
				}
				if (Z_TYPE(ctx->class_reflection) == IS_OBJECT) {
					zend_class_entry *scopeCe = Z_OBJCE_P(ctx->scope);
					zend_function *resolveName = (zend_function *) zend_hash_str_find_ptr(&scopeCe->function_table, "resolvename", sizeof("resolvename") - 1);
					if (UNEXPECTED(resolveName == NULL)) {
						ctx->failed = true;
						return false;
					}
					zval resolvedRaw, nodeZv;
					ZVAL_OBJ(&nodeZv, node);
					zend_call_known_function(resolveName, Z_OBJ_P(ctx->scope), scopeCe, &resolvedRaw, 1, &nodeZv, NULL);
					if (UNEXPECTED(EG(exception))) {
						ctx->failed = true;
						return false;
					}
					zv::Val resolved = zv::Val::adopt(resolvedRaw);

					zend_class_entry *reflectionCe = Z_OBJCE(ctx->class_reflection);
					zend_function *isFn = (zend_function *) zend_hash_str_find_ptr(&reflectionCe->function_table, "is", sizeof("is") - 1);
					if (UNEXPECTED(isFn == NULL)) {
						ctx->failed = true;
						return false;
					}
					zval isRetRaw;
					zend_call_known_function(isFn, Z_OBJ(ctx->class_reflection), reflectionCe, &isRetRaw, 1, resolved.raw(), NULL);
					if (UNEXPECTED(EG(exception))) {
						ctx->failed = true;
						return false;
					}
					zv::Val isRet = zv::Val::adopt(isRetRaw);
					if (zend_is_true(isRet.raw())) {
						return true;
					}
				}
			}
		}

		if (!instanceof_function(node->ce, ctx->target_ce)) {
			return false;
		}

		zv::Str nodeKey = zv::Str::adopt(pt_node_key(node, ctx->expr_printer));
		if (UNEXPECTED(nodeKey.isNull())) {
			ctx->failed = true;
			return false;
		}
		return zend_string_equals(nodeKey.get(), ctx->invalidate_str);
	}

	/*
	 * The core of shouldInvalidateExpression(); $requireMoreCharacters is
	 * per-call (the conditional-holder scan passes false). Returns false and
	 * sets *failed on exception.
	 */
	static bool shouldInvalidate(const InvalidationQuery &query, zend_string *exprString, zend_object *expr, bool requireMoreCharacters, bool *failed)
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *intertwinedCe = pt_class(PT_CLASS_INTERTWINED_VAR);

		if (UNEXPECTED(variableCe == NULL || intertwinedCe == NULL)) {
			*failed = true;
			return false;
		}

		/* Intertwined by-reference variables are never invalidated by their root variable */
		if (instanceof_function(expr->ce, intertwinedCe)) {
			zend_class_entry *invalidateCe = Z_OBJCE_P(query.expressionToInvalidate);
			if (instanceof_function(invalidateCe, variableCe)) {
				pt_node_class_info *invalidateInfo = pt_get_node_class_info(invalidateCe);
				if (invalidateInfo != NULL && invalidateInfo->name_offset >= 0) {
					zv::Ref invalidateName = zv::ObjRef(query.expressionToInvalidate).propAtOffset((uint32_t) invalidateInfo->name_offset).deref();
					if (invalidateName.isString()) {
						zend_string *name = invalidateName.asString();
						/* $expr->getVariableName() === name */
						int32_t variableNameOffset = pt_instance_prop_offset(expr->ce, "variableName", sizeof("variableName") - 1);
						int32_t exprOffset = pt_instance_prop_offset(expr->ce, "expr", sizeof("expr") - 1);
						int32_t assignedExprOffset = pt_instance_prop_offset(expr->ce, "assignedExpr", sizeof("assignedExpr") - 1);
						if (variableNameOffset >= 0) {
							zv::Ref variableName = zv::ObjRef(expr).propAtOffset((uint32_t) variableNameOffset).deref();
							if (variableName.isString() && zend_string_equals(variableName.asString(), name)) {
								return false;
							}
						}
						if (exprOffset >= 0) {
							zv::Ref innerExpr = zv::ObjRef(expr).propAtOffset((uint32_t) exprOffset).deref();
							if (innerExpr.isObject()) {
								zend_string *root = intertwinedRootVariableName(innerExpr.asObject());
								if (root != NULL && zend_string_equals(root, name)) {
									return false;
								}
							}
						}
						if (assignedExprOffset >= 0) {
							zv::Ref assignedExpr = zv::ObjRef(expr).propAtOffset((uint32_t) assignedExprOffset).deref();
							if (assignedExpr.isObject()) {
								zend_string *root = intertwinedRootVariableName(assignedExpr.asObject());
								if (root != NULL && zend_string_equals(root, name)) {
									return false;
								}
							}
						}
					}
				}
			}
		}

		if (requireMoreCharacters && zend_string_equals(query.exprStringToInvalidate, exprString)) {
			return false;
		}

		/* Variables will not contain traversable expressions: direct compare */
		{
			pt_node_class_info *info = pt_get_node_class_info(expr->ce);
			if (info != NULL && info->is_variable && info->name_offset >= 0) {
				zv::Ref name = zv::ObjRef(expr).propAtOffset((uint32_t) info->name_offset).deref();
				if (name.isString()) {
					if (requireMoreCharacters) {
						/* a variable cannot contain more than itself, and the
						 * exact match was already rejected above - this also
						 * covers the '$this' invalidation run for every impure
						 * method call, where the substring gate cannot be used */
						return false;
					}
					return zend_string_equals(query.exprStringToInvalidate, exprString);
				}
			}
		}

		/* Compositional-key substring gate */
		if (!query.isThis
			&& !keyMayHideSubExpressions(query.exprStringToInvalidate)
			&& !strContains(query.exprStringToInvalidate, "/*", 2)
			&& !strContainsStr(exprString, query.exprStringToInvalidate)
			&& !keyMayHideSubExpressions(exprString)) {
			return false;
		}

		/* AST walk */
		{
			pt_find_ctx ctx;
			memset(&ctx, 0, sizeof(ctx));
			ctx.target_ce = Z_OBJCE_P(query.expressionToInvalidate);
			ctx.invalidate_str = query.exprStringToInvalidate;
			ctx.expr_printer = query.exprPrinter;
			ctx.is_this = query.isThis;
			ctx.scope = query.scope;
			ZVAL_UNDEF(&ctx.class_reflection);
			ctx.class_reflection_fetched = false;
			ctx.failed = false;

			zend_object *found = pt_find_first_recursive(expr, invalidationMatcher, &ctx);
			if (ctx.class_reflection_fetched) {
				zval_ptr_dtor(&ctx.class_reflection);
			}
			if (UNEXPECTED(ctx.failed)) {
				*failed = true;
				return false;
			}
			if (found == NULL) {
				return false;
			}
		}

		/* Post-checks calling back into the scope (rare paths) */
		if (requireMoreCharacters) {
			zend_class_entry *propertyFetchCe = pt_class(PT_CLASS_PROPERTY_FETCH);
			if (UNEXPECTED(propertyFetchCe == NULL)) {
				*failed = true;
				return false;
			}
			if (instanceof_function(expr->ce, propertyFetchCe)) {
				zval argv[2];
				bool isReadonly;
				ZVAL_OBJ(&argv[0], expr);
				ZVAL_FALSE(&argv[1]);
				if (UNEXPECTED(!pt_call_scope_bool(query.scope, "isreadonlypropertyfetch", sizeof("isreadonlypropertyfetch") - 1, 2, argv, &isReadonly))) {
					*failed = true;
					return false;
				}
				if (isReadonly) {
					return false;
				}
			}

			if (query.invalidatingClass != NULL && Z_TYPE_P(query.invalidatingClass) == IS_OBJECT) {
				zval argv[2];
				bool isPrivateOfOtherClass;
				ZVAL_OBJ(&argv[0], expr);
				ZVAL_COPY_VALUE(&argv[1], query.invalidatingClass);
				if (UNEXPECTED(!pt_call_scope_bool(query.scope, "isprivatepropertyofdifferentclass", sizeof("isprivatepropertyofdifferentclass") - 1, 2, argv, &isPrivateOfOtherClass))) {
					*failed = true;
					return false;
				}
				if (isPrivateOfOtherClass) {
					return false;
				}
			}
		}

		return true;
	}

	/* getIntertwinedRefRootVariableName()'s walk; returns a borrowed string */
	static zend_string *intertwinedRootVariableName(zend_object *expr)
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *arrayDimFetchCe = pt_class(PT_CLASS_ARRAY_DIM_FETCH);

		if (UNEXPECTED(variableCe == NULL || arrayDimFetchCe == NULL)) {
			return NULL;
		}

		for (;;) {
			if (instanceof_function(expr->ce, variableCe)) {
				pt_node_class_info *info = pt_get_node_class_info(expr->ce);
				if (info == NULL || info->name_offset < 0) {
					return NULL;
				}
				zv::Ref name = zv::ObjRef(expr).propAtOffset((uint32_t) info->name_offset).deref();
				return name.isString() ? name.asString() : NULL; /* borrowed */
			}
			if (instanceof_function(expr->ce, arrayDimFetchCe)) {
				int32_t varOffset = pt_instance_prop_offset(expr->ce, "var", sizeof("var") - 1);
				if (varOffset < 0) {
					return NULL;
				}
				zv::Ref var = zv::ObjRef(expr).propAtOffset((uint32_t) varOffset).deref();
				if (!var.isObject()) {
					return NULL;
				}
				expr = var.asObject();
				continue;
			}
			return NULL;
		}
	}

	/*
	 * matchConditionalExpressions()' shared tail of both passes:
	 * $conditions[$exprString][] = $conditionalExpression and
	 * $specifiedExpressions[$exprString] = its type holder.
	 */
	static void recordMatchedCondition(zv::Arr &conditions, zv::Arr &specified, zend_string *condKey, zend_ulong condIdx, zv::Ref conditionalExpression, zv::Ref typeHolder)
	{
		zval *group = pt_ht_find(conditions.table(), condKey, condIdx);
		if (group == NULL) {
			zval newGroup;
			array_init(&newGroup);
			pt_ht_add_new(conditions.table(), condKey, condIdx, &newGroup);
			group = pt_ht_find(conditions.table(), condKey, condIdx);
		}
		Z_ADDREF_P(conditionalExpression.raw());
		add_next_index_zval(group, conditionalExpression.raw());
		Z_TRY_ADDREF_P(typeHolder.raw());
		pt_ht_update(specified.table(), condKey, condIdx, typeHolder.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::ScopeOps;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* {{{ lifecycle (owns the scope-offsets cache) */

void pt_scope_ops_rinit()
{
	pt_scope_offsets_cache_inited = false;
}

void pt_scope_ops_rshutdown()
{
	if (pt_scope_offsets_cache_inited) {
		zend_hash_destroy(&pt_scope_offsets_cache);
		pt_scope_offsets_cache_inited = false;
	}
}

/* }}} */

void pt_register_scope_ops()
{
	reg::Class cls("PHPStanTurbo\\ScopeOps");

	cls.method("mergeVariableHolders", reg::PublicStatic, 2, { reg::arrayArg("ourVariableTypeHolders"), reg::arrayArg("theirVariableTypeHolders"), reg::any("differingKeys", true) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *ours, *theirs;
		zval *differing_zv = NULL;
		ZEND_PARSE_PARAMETERS_START(2, 3)
			Z_PARAM_ARRAY_HT(ours)
			Z_PARAM_ARRAY_HT(theirs)
			Z_PARAM_OPTIONAL
			Z_PARAM_ZVAL(differing_zv)
		ZEND_PARSE_PARAMETERS_END();
		HashTable *differing = NULL;
		if (differing_zv != NULL && Z_ISREF_P(differing_zv)) {
			/* the twin declares `array &$differingKeys = []`; vivify like PHP
			 * would and write through the reference */
			zval *inner = Z_REFVAL_P(differing_zv);
			if (Z_TYPE_P(inner) != IS_ARRAY) {
				convert_to_array(inner);
			}
			SEPARATE_ARRAY(inner);
			differing = Z_ARRVAL_P(inner);
		}
		zv::Val result = ScopeOps::mergeVariableHolders(zv::TableRef(ours), zv::TableRef(theirs), differing);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("finishMerge", reg::PublicStatic, 5, { reg::arrayArg("mergedExpressionTypes"), reg::arrayArg("ourExpressionTypes"), reg::arrayArg("theirExpressionTypes"), reg::arrayArg("ourNativeExpressionTypes"), reg::arrayArg("theirNativeExpressionTypes") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *merged, *ours_expr, *theirs_expr, *ours_native, *theirs_native;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_ARRAY_HT(merged)
			Z_PARAM_ARRAY_HT(ours_expr)
			Z_PARAM_ARRAY_HT(theirs_expr)
			Z_PARAM_ARRAY_HT(ours_native)
			Z_PARAM_ARRAY_HT(theirs_native)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::finishMerge(zv::TableRef(merged), zv::TableRef(ours_expr), zv::TableRef(theirs_expr), zv::TableRef(ours_native), zv::TableRef(theirs_native));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("intersectConditionalExpressions", reg::PublicStatic, 2, { reg::arrayArg("ourConditionalExpressions"), reg::arrayArg("theirConditionalExpressions") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *ours, *theirs;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY_HT(ours)
			Z_PARAM_ARRAY_HT(theirs)
		ZEND_PARSE_PARAMETERS_END();
		ScopeOps::intersectConditionalExpressions(zv::TableRef(ours), zv::TableRef(theirs)).intoReturnValue(return_value);
	});

	cls.method("invalidateExpressionEntries", reg::PublicStatic, 9, { reg::objectArg("scope"), reg::objectArg("exprPrinter"), reg::stringArg("exprStringToInvalidate"), reg::objectArg("expressionToInvalidate"), reg::boolArg("requireMoreCharacters"), reg::objectArg("invalidatingClass", true), reg::arrayArg("expressionTypes"), reg::arrayArg("nativeExpressionTypes"), reg::arrayArg("conditionalExpressions") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr_printer, *expr_to_invalidate, *invalidating_class = NULL;
		zend_string *invalidate_str;
		bool require_more_characters;
		HashTable *expression_types, *native_expression_types, *conditional_expressions;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr_printer)
			Z_PARAM_STR(invalidate_str)
			Z_PARAM_OBJECT(expr_to_invalidate)
			Z_PARAM_BOOL(require_more_characters)
			Z_PARAM_OBJECT_OR_NULL(invalidating_class)
			Z_PARAM_ARRAY_HT(expression_types)
			Z_PARAM_ARRAY_HT(native_expression_types)
			Z_PARAM_ARRAY_HT(conditional_expressions)
		ZEND_PARSE_PARAMETERS_END();
		pt_init_strs();
		zv::Val result = ScopeOps::invalidateExpressionEntries(scope, expr_printer, invalidate_str, expr_to_invalidate, require_more_characters, invalidating_class, zv::TableRef(expression_types), zv::TableRef(native_expression_types), zv::TableRef(conditional_expressions));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("shouldInvalidateExpression", reg::PublicStatic, 6, { reg::objectArg("scope"), reg::objectArg("exprPrinter"), reg::stringArg("exprStringToInvalidate"), reg::objectArg("exprToInvalidate"), reg::objectArg("expr"), reg::stringArg("exprString"), reg::boolArg("requireMoreCharacters"), reg::objectArg("invalidatingClass", true) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr_printer, *expr_to_invalidate, *expr, *invalidating_class = NULL;
		zend_string *invalidate_str, *expr_string;
		bool require_more_characters = false;
		ZEND_PARSE_PARAMETERS_START(6, 8)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr_printer)
			Z_PARAM_STR(invalidate_str)
			Z_PARAM_OBJECT(expr_to_invalidate)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_STR(expr_string)
			Z_PARAM_OPTIONAL
			Z_PARAM_BOOL(require_more_characters)
			Z_PARAM_OBJECT_OR_NULL(invalidating_class)
		ZEND_PARSE_PARAMETERS_END();
		pt_init_strs();
		bool failed = false;
		bool result = ScopeOps::shouldInvalidateExpression(scope, expr_printer, invalidate_str, expr_to_invalidate, Z_OBJ_P(expr), expr_string, require_more_characters, invalidating_class, &failed);
		if (UNEXPECTED(failed)) {
			RETURN_THROWS();
		}
		RETURN_BOOL(result);
	});

	cls.method("getIntertwinedRefRootVariableName", reg::PublicStatic, 1, { reg::objectArg("expr") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::getIntertwinedRefRootVariableName(Z_OBJ_P(expr));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("matchConditionalExpressions", reg::PublicStatic, 2, { reg::arrayArg("conditionalExpressions"), reg::arrayArg("specifiedExpressions") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *conditional, *specified_input;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY_HT(conditional)
			Z_PARAM_ARRAY_HT(specified_input)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::matchConditionalExpressions(zv::TableRef(conditional), zv::TableRef(specified_input));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("createConditionalExpressions", reg::PublicStatic, 5, { reg::arrayArg("conditionalExpressions"), reg::arrayArg("ourExpressionTypes"), reg::arrayArg("theirExpressionTypes"), reg::arrayArg("mergedExpressionTypes"), reg::arrayArg("differingKeys") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *conditional, *ours, *theirs, *merged, *differing_keys;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_ARRAY_HT(conditional)
			Z_PARAM_ARRAY_HT(ours)
			Z_PARAM_ARRAY_HT(theirs)
			Z_PARAM_ARRAY_HT(merged)
			Z_PARAM_ARRAY_HT(differing_keys)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::createConditionalExpressions(zv::TableRef(conditional), zv::TableRef(ours), zv::TableRef(theirs), zv::TableRef(merged), zv::TableRef(differing_keys));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("nodeKey", reg::PublicStatic, 2, { reg::objectArg("node"), reg::objectArg("exprPrinter") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *expr_printer;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OBJECT(expr_printer)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::nodeKey(Z_OBJ_P(node), expr_printer);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("getTypeFromCache", reg::PublicStatic, 3, { reg::objectArg("scope"), reg::objectArg("node"), reg::any("key", true) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *key_out;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(node)
			Z_PARAM_ZVAL(key_out)
		ZEND_PARSE_PARAMETERS_END();
		zend_string *key = NULL;
		zv::Val result = ScopeOps::getTypeFromCache(scope, Z_OBJ_P(node), &key);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		if (key != NULL) {
			/* hand the computed key to the by-ref parameter (hit and miss) */
			if (Z_ISREF_P(key_out)) {
				ZEND_TRY_ASSIGN_REF_STR(key_out, key);
			} else {
				zend_string_release(key);
			}
		}
		result.intoReturnValue(return_value);
	});

	cls.method("hasVariableType", reg::PublicStatic, 2, { reg::objectArg("scope"), reg::stringArg("variableName") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		zend_string *variable_name;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_STR(variable_name)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::hasVariableType(scope, variable_name);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("scopeWith", reg::PublicStatic, 9, { reg::objectArg("scope"), reg::arrayArg("expressionTypes"), reg::arrayArg("nativeExpressionTypes"), reg::arrayArg("conditionalExpressions"), reg::arrayArg("currentlyAssignedExpressions"), reg::arrayArg("currentlyAllowedUndefinedExpressions"), reg::arrayArg("inFunctionCallsStack"), reg::boolArg("inFirstLevelStatement"), reg::boolArg("afterExtractCall") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope;
		HashTable *expression_types, *native_expression_types, *conditional_expressions;
		HashTable *currently_assigned, *currently_allowed_undefined, *in_function_calls_stack;
		bool in_first_level_statement, after_extract_call;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY_HT(expression_types)
			Z_PARAM_ARRAY_HT(native_expression_types)
			Z_PARAM_ARRAY_HT(conditional_expressions)
			Z_PARAM_ARRAY_HT(currently_assigned)
			Z_PARAM_ARRAY_HT(currently_allowed_undefined)
			Z_PARAM_ARRAY_HT(in_function_calls_stack)
			Z_PARAM_BOOL(in_first_level_statement)
			Z_PARAM_BOOL(after_extract_call)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::scopeWith(scope, expression_types, native_expression_types, conditional_expressions, currently_assigned, currently_allowed_undefined, in_function_calls_stack, in_first_level_statement, after_extract_call);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("invalidateMethodsOnExpression", reg::PublicStatic, 4, { reg::objectArg("exprPrinter"), reg::stringArg("exprStringToInvalidate"), reg::arrayArg("expressionTypes"), reg::arrayArg("nativeExpressionTypes") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr_printer;
		zend_string *invalidate_str;
		HashTable *expression_types, *native_expression_types;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(expr_printer)
			Z_PARAM_STR(invalidate_str)
			Z_PARAM_ARRAY_HT(expression_types)
			Z_PARAM_ARRAY_HT(native_expression_types)
		ZEND_PARSE_PARAMETERS_END();
		pt_init_strs();
		zv::Val result = ScopeOps::invalidateMethodsOnExpression(expr_printer, invalidate_str, zv::TableRef(expression_types), zv::TableRef(native_expression_types));
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("expressionTypeByKey", reg::PublicStatic, 3, { reg::objectArg("scope"), reg::objectArg("node"), reg::stringArg("exprString") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node;
		zend_string *expr_string;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(node)
			Z_PARAM_STR(expr_string)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::expressionTypeByKey(scope, Z_OBJ_P(node), expr_string);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("hasExpressionType", reg::PublicStatic, 3, { reg::objectArg("scope"), reg::objectArg("node"), reg::objectArg("exprPrinter") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *expr_printer;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OBJECT(expr_printer)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ScopeOps::hasExpressionType(scope, Z_OBJ_P(node), expr_printer);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	pt_ce_scope_ops = cls.register_();
	(void) pt_ce_scope_ops;
}

/* }}} */
