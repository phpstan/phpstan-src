/*
 * PHPStanTurbo\ForeachHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ForeachHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo (the Container / VarAnnotationProcessor / AssignHandler classes
 * and the #[AutowiredParameter] bools by name) so Nette autowires it.
 * processStmt() is registered as the class's statement-handler entry
 * (Engine.h).
 *
 * The iteratee, the convergence passes over the body, the unrolled
 * constant-array iterations and the final walk go through NodeScopeResolver's
 * direct entries (the fresh-stack guard stays on the recursion);
 * VarAnnotationProcessor, AssignHandler::processVirtualAssign(),
 * IdenticalNarrowingHelper, ExpressionResult, MutatingScope,
 * ExpressionResultStorage, the contexts, the holders, VariableFlow,
 * VariableFlowBuilder, InternalThrowPoint and the statement results through
 * theirs; the Type queries through the native type dispatch. The twin's
 * closures (the `iteratee !== []` type callback, the mapValueType() /
 * mapKeyType() replacements) are native closures; the private
 * tryProcessUnrolledConstantArrayForeach()'s array shape is a C++ struct.
 * $stmt->getDocComment() is read off the comments attribute when the node
 * class inherits NodeAbstract's method.
 */

#include "support.h"
#include "generated/ForeachHandler.h"

namespace slots = ptdecl::ForeachHandler::slot;
namespace sigs = ptdecl::ForeachHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

#include "zend_smart_str.h"

#include <vector>

zend_class_entry *pt_ce_foreach_handler = nullptr;

/* ForeachHandler::FOREACH_UNROLL_LIMIT / ::FOREACH_UNROLL_NESTED_LIMIT */
#define PT_FEH_FOREACH_UNROLL_LIMIT 16
#define PT_FEH_FOREACH_UNROLL_NESTED_LIMIT 8

namespace {

pt_property_site pt_feh_expr_site;
pt_property_site pt_feh_key_var_site;
pt_property_site pt_feh_value_var_site;
pt_property_site pt_feh_by_ref_site;
pt_property_site pt_feh_stmts_site;
pt_property_site pt_feh_variable_name_site;
pt_property_site pt_feh_call_name_site;
pt_property_site pt_feh_arg_value_site;
pt_property_site pt_feh_list_items_site;
pt_property_site pt_feh_item_value_site;
pt_property_site pt_feh_item_key_site;
pt_property_site pt_feh_scalar_value_site;
pt_method_site pt_feh_get_attributes_site;
pt_method_site pt_feh_set_attributes_site;
pt_method_site pt_feh_get_doc_comment_site;
pt_method_site pt_feh_get_method_site;
pt_method_site pt_feh_get_throw_type_site;

/* the permanent interned strings of the twin's literals (module startup) */
zend_string *pt_feh_identical_narrowing_helper_class = nullptr;
zend_string *pt_feh_traversable_class = nullptr;
zend_string *pt_feh_iterator_aggregate_class = nullptr;
zend_string *pt_feh_get_iterator = nullptr;
zend_string *pt_feh_is_object = nullptr;

/* $node->getAttributes() / ->setAttributes($attributes) */
zv::Val getAttributes(zval *node)
{
	return pt_call_method_cached(pt_feh_get_attributes_site, Z_OBJ_P(node), PT_LC("getattributes"), 0, NULL);
}

[[nodiscard]] bool setAttributes(zval *node, zval *attributes)
{
	return !pt_call_method_cached(pt_feh_set_attributes_site, Z_OBJ_P(node), PT_LC("setattributes"), 1, attributes).isUndef();
}

/* $type->getMethod($name, $scope) / $method->getThrowType() */
zv::Val getMethod(zval *type, zend_string *name, zval *scope)
{
	zv::Args argv{name, scope};
	return pt_call_method_cached(pt_feh_get_method_site, Z_OBJ_P(type), PT_LC("getmethod"), 2, argv);
}

zv::Val getThrowType(zval *method)
{
	if (UNEXPECTED(Z_TYPE_P(method) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getThrowType() on %s", zend_zval_value_name(method));
		return zv::Val();
	}
	return pt_call_method_cached(pt_feh_get_throw_type_site, Z_OBJ_P(method), PT_LC("getthrowtype"), 0, NULL);
}

/* $stmt->getDocComment() === null: NodeAbstract's loop over getComments()
 * for a class inheriting both methods, the method otherwise; false =
 * pending exception */
struct DocCommentMethodCache
{
	zend_class_entry *ce;
	uint32_t generation;
	bool inherits;
	zend_function *nodeAbstractMethod;
};
DocCommentMethodCache pt_feh_doc_comment_cache;

[[nodiscard]] bool hasNoDocComment(zval *stmt, bool &out)
{
	zend_class_entry *ce = Z_OBJCE_P(stmt);
	DocCommentMethodCache &cache = pt_feh_doc_comment_cache;
	if (UNEXPECTED(cache.ce != ce || cache.generation != pt_engine_generation)) {
		zend_class_entry *nodeAbstract = pt_class(PT_CLASS_NODE_ABSTRACT);
		if (UNEXPECTED(nodeAbstract == NULL)) return false;
		zend_function *base = (zend_function *) zend_hash_str_find_ptr(&nodeAbstract->function_table, PT_LC("getdoccomment"));
		zend_function *own = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, PT_LC("getdoccomment"));
		cache = { ce, pt_engine_generation, base != NULL && own == base, base };
	}
	if (EXPECTED(cache.inherits)) {
		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(stmt));
		if (UNEXPECTED(comments.isUndef())) return false;
		out = true;
		if (Z_TYPE_P(comments.raw()) != IS_ARRAY) return true;
		HashTable *table = Z_ARRVAL_P(comments.raw());
		uint32_t count = zend_hash_num_elements(table);
		if (count == 0) return true;
		zend_class_entry *docCe = pt_class_loaded(PT_CLASS_DOC_COMMENT);
		if (docCe == NULL) return EG(exception) == NULL;
		for (uint32_t i = count; i-- > 0;) {
			zval *comment = zend_hash_index_find(table, i);
			if (UNEXPECTED(comment == NULL)) {
				zend_error(E_WARNING, "Undefined array key %u", i);
				if (UNEXPECTED(EG(exception))) return false;
				continue;
			}
			ZVAL_DEREF(comment);
			if (Z_TYPE_P(comment) == IS_OBJECT && instanceof_function(Z_OBJCE_P(comment), docCe)) {
				out = false;
				return true;
			}
		}
		return true;
	}
	zv::Val docComment = pt_call_method_cached(pt_feh_get_doc_comment_site, Z_OBJ_P(stmt), PT_LC("getdoccomment"), 0, NULL);
	if (UNEXPECTED(docComment.isUndef())) return false;
	out = docComment.isNull();
	return true;
}

/* ExpressionTypeHolder::createYes($expr, $type) */
zv::Val expressionTypeHolderYes(zval *expr, zval *type)
{
	zval holder;
	pt_holder_create(&holder, expr, type, PT_TRI_YES);
	return zv::Val::adopt(holder);
}

/* new ConditionalExpressionHolder($conditions, $typeHolder) */
zv::Val newConditionalExpressionHolder(zval *conditions, zval *typeHolder)
{
	if (UNEXPECTED(Z_TYPE_P(conditions) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(conditions)) == 0)) {
		pt_throw_should_not_happen();
		return zv::Val();
	}
	zval raw;
	object_init_ex(&raw, pt_ce_cond_expr_holder);
	zv::Val holder = zv::Val::adopt(raw);
	zv::ObjRef object(holder.ref().asObject());
	object.propAtWrite(PT_CEH_PROP_CONDS, zv::Val::copyOf(zv::Ref(conditions)));
	object.propAtWrite(PT_CEH_PROP_TYPEHOLDER, zv::Val::copyOf(zv::Ref(typeHolder)));
	return holder;
}

/* $holder->getKey() of a native ConditionalExpressionHolder (owned; NULL =
 * pending exception) */
zend_string *conditionalHolderKey(zval *holder)
{
	zv::ObjRef object(Z_OBJ_P(holder));
	return pt_ceh_key_build(object.propAt(PT_CEH_PROP_CONDS).deref().asArrayTable(), object.propAt(PT_CEH_PROP_TYPEHOLDER).deref().raw());
}

/* new Variable($name) */
zv::Val newVariable(zend_string *name)
{
	zval nameZv;
	ZVAL_STR(&nameZv, name);
	return pt_type_new(PT_CLASS_VARIABLE, 1, &nameZv);
}

/* new ArrayDimFetch($var, $dim) */
zv::Val newArrayDimFetch(zval *var, zval *dim)
{
	zv::Args argv{var, dim};
	return pt_type_new(PT_CLASS_ARRAY_DIM_FETCH, 2, argv);
}

/* new NativeTypeExpr($scope->getIterableKeyType($type), $scope->getIterableKeyType($nativeType))
 * (or the value types), the PHPDoc flavour asked first */
zv::Val newIterableTypeExpr(zval *scope, zval *type, zval *nativeType, bool key)
{
	zv::Val phpDocType = key ? pt_mutating_scope_get_iterable_key_type(Z_OBJ_P(scope), type) : pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(scope), type);
	if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
	zv::Val nativeIterableType = key ? pt_mutating_scope_get_iterable_key_type(Z_OBJ_P(scope), nativeType) : pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(scope), nativeType);
	if (UNEXPECTED(nativeIterableType.isUndef())) return zv::Val();
	zv::Args argv{phpDocType.raw(), nativeIterableType.raw()};
	return pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, argv);
}

/* new Array_([]) */
zv::Val newEmptyArrayExpr()
{
	zval items;
	ZVAL_EMPTY_ARRAY(&items);
	return pt_type_new(PT_CLASS_ARRAY_EXPR, 1, &items);
}

/* $type->isSuperTypeOf($other) as a PT_TRI_* value; -1 = pending exception */
zend_long isSuperTypeOf(zval *type, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(type));
		return -1;
	}
	zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
	if (UNEXPECTED(result.isUndef())) return -1;
	if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(result.raw()));
		return -1;
	}
	return pt_result_value(Z_OBJ_P(result.raw()));
}

/* a TrinaryLogic-returning op on a type value; -1 = pending exception */
zend_long typeTrinary(zval *type, pt_type_op_id op, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(type));
		return -1;
	}
	return pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
}

/* a Type-returning op on a type value */
zv::Val typeOp(zval *type, pt_type_op_id op, const char *method, uint32_t argc = 0, zval *argv = NULL)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_op(Z_OBJ_P(type), op, argc, argv);
}

/* the element of a list at `index` (borrowed), NULL when missing */
inline zval *listAt(zval *list, zend_ulong index)
{
	if (UNEXPECTED(Z_TYPE_P(list) != IS_ARRAY)) return NULL;
	zval *found = zend_hash_index_find(Z_ARRVAL_P(list), index);
	if (found != NULL) {
		ZVAL_DEREF(found);
	}
	return found;
}

/* TypeCombinator::union(...$list) over a list built here */
zv::Val unionOfList(zv::Arr &list)
{
	HashTable *table = list.table();
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return pt_type_combinator_union(0, NULL);
	if (HT_IS_PACKED(table) && table->nNumUsed == count) return pt_type_combinator_union(count, table->arPacked);
	return pt_type_combinator_call_spread(PT_LC("union"), table);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ForeachHandler; UNDEF = pending
 * exception. */
class ForeachHandler
{
public:
	explicit ForeachHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *container, bool implicitThrows, zval *varAnnotationProcessor, zval *assignHandler, bool polluteScopeWithAlwaysIterableForeach)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::container, zv::Val::copyOf(zv::Ref(container)));
		object.propAtWrite(slots::implicitThrows, zv::Val::boolean(implicitThrows));
		object.propAtWrite(slots::varAnnotationProcessor, zv::Val::copyOf(zv::Ref(varAnnotationProcessor)));
		object.propAtWrite(slots::assignHandler, zv::Val::copyOf(zv::Ref(assignHandler)));
		object.propAtWrite(slots::polluteScopeWithAlwaysIterableForeach, zv::Val::boolean(polluteScopeWithAlwaysIterableForeach));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_FOREACH_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *entryScope, zval *originalStorage, zval *nodeCallback, zval *context) const
	{
		bool polluteScopeWithAlwaysIterableForeach = ptlh::boolSlot(self, slots::polluteScopeWithAlwaysIterableForeach);
		StmtParts parts;
		if (UNEXPECTED(!parts.read(stmt))) return zv::Val();
		zval *iteratee = parts.expr.raw();

		zv::Val scope = zv::Val::copyOf(zv::Ref(entryScope));
		if (parts.exprName != NULL) {
			scope = processVarAnnotation(scope.raw(), parts.exprName, stmt);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val condResult;
		{
			zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
			if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
			condResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, iteratee, scope.raw(), originalStorage, nodeCallback, expressionContext.raw());
			if (UNEXPECTED(condResult.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, entryScope, originalStorage))) return zv::Val();
		zv::Arr throwPoints, impurePoints;
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_throw_points(condResult.raw(), hold), throwPoints))) return zv::Val();
		}
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_expression_result_impure_points(condResult.raw(), hold), impurePoints))) return zv::Val();
		}
		{
			zv::Val hold;
			zval *resultScope = pt_expression_result_scope(condResult.raw(), hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		/* $arrayComparisonExpr = new BinaryOp\NotIdentical($stmt->expr, new Array_([])) */
		zv::Val comparisonRight = newEmptyArrayExpr();
		if (UNEXPECTED(comparisonRight.isUndef())) return zv::Val();
		zv::Val arrayComparisonExpr;
		{
			zv::Args argv{iteratee, comparisonRight.raw()};
			arrayComparisonExpr = pt_type_new(PT_CLASS_BINARY_OP_NOT_IDENTICAL, 2, argv);
			if (UNEXPECTED(arrayComparisonExpr.isUndef())) return zv::Val();
		}
		{
			zv::Val inForeachNode = pt_type_new(PT_CLASS_IN_FOREACH_NODE, 1, stmt);
			if (UNEXPECTED(inForeachNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inForeachNode.raw(), scope.raw(), originalStorage))) return zv::Val();
		}
		zv::Val originalScope = zv::Val::copyOf(scope.ref());
		zv::Val bodyScope = zv::Val::copyOf(scope.ref());

		zv::Val foreachIterateeType = pt_expression_result_get_type(condResult.raw());
		if (UNEXPECTED(foreachIterateeType.isUndef())) return zv::Val();
		zv::Val foreachNativeIterateeType = pt_expression_result_get_native_type(condResult.raw());
		if (UNEXPECTED(foreachNativeIterateeType.isUndef())) return zv::Val();

		if (parts.keyVarIsVariable) {
			zv::Val keyTypeExpr = newIterableTypeExpr(originalScope.raw(), foreachIterateeType.raw(), foreachNativeIterateeType.raw(), true);
			if (UNEXPECTED(keyTypeExpr.isUndef())) return zv::Val();
			zv::Args argv{parts.keyVar.raw(), keyTypeExpr.raw()};
			zv::Val assignNode = pt_type_new(PT_CLASS_VARIABLE_ASSIGN_NODE, 2, argv);
			if (UNEXPECTED(assignNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, assignNode.raw(), originalScope.raw(), originalStorage))) return zv::Val();
		}

		if (parts.valueVarIsVariable) {
			zv::Val valueTypeExpr = newIterableTypeExpr(originalScope.raw(), foreachIterateeType.raw(), foreachNativeIterateeType.raw(), false);
			if (UNEXPECTED(valueTypeExpr.isUndef())) return zv::Val();
			zv::Args argv{parts.valueVar.raw(), valueTypeExpr.raw()};
			zv::Val assignNode = pt_type_new(PT_CLASS_VARIABLE_ASSIGN_NODE, 2, argv);
			if (UNEXPECTED(assignNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, assignNode.raw(), originalScope.raw(), originalStorage))) return zv::Val();
		} else if (parts.valueVarIsList) {
			zv::Val valueTypeExpr = newIterableTypeExpr(originalScope.raw(), foreachIterateeType.raw(), foreachNativeIterateeType.raw(), false);
			if (UNEXPECTED(valueTypeExpr.isUndef())) return zv::Val();
			zv::Args argv{parts.valueVar.raw(), valueTypeExpr.raw()};
			zv::Val virtualAssign = pt_type_new(PT_CLASS_ASSIGN_EXPR, 2, argv);
			if (UNEXPECTED(virtualAssign.isUndef())) return zv::Val();
			zv::Val attributes = getAttributes(parts.valueVar.raw());
			if (UNEXPECTED(attributes.isUndef())) return zv::Val();
			if (UNEXPECTED(!setAttributes(virtualAssign.raw(), attributes.raw()))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, virtualAssign.raw(), scope.raw(), originalStorage))) return zv::Val();
		}

		// the "iteratee !== []" narrowing every loop pass merges in - composed
		// once from the iteratee's result (the same sentinel comparison the
		// walked synthetic would delegate to); the walk is the composition's
		// miss seam
		zv::Val nonEmptyIterateeScope = zv::Val::copyOf(scope.ref());
		if (polluteScopeWithAlwaysIterableForeach) {
			zv::Val identicalNarrowingHelper = ptlh::containerGetByType(OBJ_PROP_NUM(self, slots::container), pt_feh_identical_narrowing_helper_class);
			if (UNEXPECTED(identicalNarrowingHelper.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(identicalNarrowingHelper.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function specifyIdenticalAgainstType() on %s", zend_zval_value_name(identicalNarrowingHelper.raw()));
				return zv::Val();
			}
			zv::Val emptyArrayType;
			{
				zval keyTypes, valueTypes, out;
				ZVAL_EMPTY_ARRAY(&keyTypes);
				ZVAL_EMPTY_ARRAY(&valueTypes);
				if (UNEXPECTED(!pt_constant_array_type_new(&out, &keyTypes, &valueTypes))) return zv::Val();
				emptyArrayType = zv::Val::adopt(out);
			}
			zend_object *falseContextObject = pt_type_specifier_context_create_false();
			if (UNEXPECTED(falseContextObject == NULL)) return zv::Val();
			zval falseContext;
			ZVAL_OBJ(&falseContext, falseContextObject);
			zv::Val subjectArgResult = pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper.raw(), iteratee, originalStorage);
			if (UNEXPECTED(subjectArgResult.isUndef())) return zv::Val();
			zv::Val identicalTypeCallback = pt_native_closure(&nonEmptyIterateeTypeBody, condResult.raw(), emptyArrayType.raw());
			zv::Val nonEmptyTypes = pt_identical_narrowing_helper_specify_identical_against_type(identicalNarrowingHelper.raw(), iteratee, condResult.raw(), comparisonRight.raw(), emptyArrayType.raw(), &falseContext, scope.raw(), subjectArgResult.raw(), identicalTypeCallback.raw());
			if (UNEXPECTED(nonEmptyTypes.isUndef())) return zv::Val();
			if (!nonEmptyTypes.isNull()) {
				nonEmptyIterateeScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope.raw()), nonEmptyTypes.raw());
			} else {
				zend_object *truthy = pt_type_specifier_context_create_truthy();
				if (UNEXPECTED(truthy == NULL)) return zv::Val();
				zval truthyContext;
				ZVAL_OBJ(&truthyContext, truthy);
				nonEmptyIterateeScope = pt_node_scope_resolver_narrow_scope_with_condition(nodeScopeResolver, scope.raw(), arrayComparisonExpr.raw(), &truthyContext);
			}
			if (UNEXPECTED(nonEmptyIterateeScope.isUndef())) return zv::Val();
		}

		zv::Val replayBodyRecording, replayPassStorage, replayPassResult, replayEntryScope;
		zv::Val unrolledEndScope;
		bool unrolled = false;
		zend_long unrolledTotalKeys = 0;
		bool isTopLevel;
		if (UNEXPECTED(!pt_statement_context_is_top_level(context, isTopLevel))) return zv::Val();
		if (isTopLevel) {
			zv::Val storage = pt_expression_result_storage_duplicate(originalStorage);
			if (UNEXPECTED(storage.isUndef())) return zv::Val();

			originalScope = zv::Val::copyOf(nonEmptyIterateeScope.ref());
			// $originalScope may narrow the iteratee to a non-empty array - a genuinely
			// different scope than its own. The narrowing is tracked by the scope
			// (getTypeOnScope's authoritative read), so the iteratee only needs
			// reprocessing there when the scope neither owns nor matches its state.
			bool answers;
			if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), originalScope.raw(), false, answers))) return zv::Val();
			if (answers) {
				if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), originalScope.raw(), true, answers))) return zv::Val();
			}
			if (answers) {
				foreachIterateeType = pt_expression_result_get_type_on_scope(condResult.raw(), originalScope.raw(), false);
				if (UNEXPECTED(foreachIterateeType.isUndef())) return zv::Val();
				foreachNativeIterateeType = pt_expression_result_get_type_on_scope(condResult.raw(), originalScope.raw(), true);
				if (UNEXPECTED(foreachNativeIterateeType.isUndef())) return zv::Val();
			} else {
				// the duplicate lets subresults whose state matches answer from
				// the already-processed iteratee instead of being re-priced
				zv::Val duplicate = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(duplicate.isUndef())) return zv::Val();
				zv::Val iterateeResult = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, iteratee, originalScope.raw(), duplicate.raw());
				if (UNEXPECTED(iterateeResult.isUndef())) return zv::Val();
				foreachIterateeType = pt_expression_result_get_type(iterateeResult.raw());
				if (UNEXPECTED(foreachIterateeType.isUndef())) return zv::Val();
				foreachNativeIterateeType = pt_expression_result_get_native_type(iterateeResult.raw());
				if (UNEXPECTED(foreachNativeIterateeType.isUndef())) return zv::Val();
			}
			UnrolledResult unrolledResult;
			if (UNEXPECTED(!tryProcessUnrolledConstantArrayForeach(nodeScopeResolver, stmt, parts, originalScope.raw(), originalStorage, context, foreachIterateeType.raw(), foreachNativeIterateeType.raw(), unrolledResult))) return zv::Val();
			if (unrolledResult.found) {
				bodyScope = std::move(unrolledResult.bodyScope);
				unrolledEndScope = std::move(unrolledResult.endScope);
				unrolled = true;
				unrolledTotalKeys = unrolledResult.totalKeys;
			} else {
				if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return zv::Val();
				bodyScope = enterForeach(nodeScopeResolver, originalScope.raw(), storage.raw(), originalScope.raw(), stmt, parts, foreachIterateeType.raw(), foreachNativeIterateeType.raw(), nodeCallback);
				pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw())); });
				if (UNEXPECTED(EG(exception))) return zv::Val();
				zend_long count = 0;
				zv::Val prevEntryScope;
				bool bodyIsReplayable;
				if (UNEXPECTED(!pt_node_scope_resolver_is_replayable_convergence_body(nodeScopeResolver, stmt, parts.stmts.raw(), bodyIsReplayable))) return zv::Val();
				do {
					zv::Val prevScope = zv::Val::copyOf(bodyScope.ref());
					bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), nonEmptyIterateeScope.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
					if (!prevEntryScope.isNull()) {
						bool equal;
						if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevEntryScope.raw(), equal))) return zv::Val();
						if (equal) {
							// walking is deterministic in the entry scope - an unchanged entry
							// reproduces the previous pass's exit, so the verification walk is skipped
							bodyScope = std::move(prevScope);
							break;
						}
					}
					prevEntryScope = zv::Val::copyOf(bodyScope.ref());
					storage = pt_expression_result_storage_duplicate(originalStorage);
					if (UNEXPECTED(storage.isUndef())) return zv::Val();
					zv::Val bodyRecording = ptlh::newPassNodeCallback(bodyIsReplayable);
					if (UNEXPECTED(bodyRecording.isUndef())) return zv::Val();
					if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope.raw()), storage.raw()))) return zv::Val();
					zv::Val bodyScopeResult;
					bool brokeOut = false;
					[&]() {
						zv::Val entered = enterForeach(nodeScopeResolver, bodyScope.raw(), storage.raw(), originalScope.raw(), stmt, parts, foreachIterateeType.raw(), foreachNativeIterateeType.raw(), nodeCallback);
						if (UNEXPECTED(entered.isUndef())) return;
						bodyScope = std::move(entered);
						zv::Val deepContext = pt_statement_context_enter_deep(context);
						if (UNEXPECTED(deepContext.isUndef())) return;
						zv::Val passContext = pt_statement_context_without_template_argument_resolution(deepContext.raw());
						if (UNEXPECTED(passContext.isUndef())) return;
						zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, parts.stmts.raw(), bodyScope.raw(), storage.raw(), bodyRecording.raw(), passContext.raw());
						if (UNEXPECTED(walked.isUndef())) return;
						bodyScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
						if (UNEXPECTED(bodyScopeResult.isUndef())) return;
						zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(bodyScopeResult.raw());
						if (UNEXPECTED(backEdgeScope.isUndef())) return;
						if (backEdgeScope.isNull()) {
							bodyScope = std::move(prevScope);
							brokeOut = true;
							return;
						}
						bodyScope = std::move(backEdgeScope);
					}();
					pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope.raw())); });
					if (UNEXPECTED(EG(exception))) return zv::Val();
					if (brokeOut) break;
					// the candidate to replace the final walk when this pass's
					// entry turns out to be the fixpoint
					if (bodyIsReplayable) {
						replayBodyRecording = std::move(bodyRecording);
						replayPassStorage = zv::Val::copyOf(storage.ref());
						replayPassResult = zv::Val::copyOf(bodyScopeResult.ref());
						replayEntryScope = zv::Val::copyOf(prevEntryScope.ref());
					}
					bool equal;
					if (UNEXPECTED(!ptlh::scopesEqual(bodyScope.raw(), prevScope.raw(), equal))) return zv::Val();
					if (equal) break;

					if (count >= PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT) {
						zv::Val flowHold;
						zval *passFlow = pt_internal_statement_result_variable_flow(bodyScopeResult.raw(), flowHold);
						if (UNEXPECTED(passFlow == NULL)) return zv::Val();
						bodyScope = ptlh::generalizeWithWrittenNames(prevScope.raw(), bodyScope.raw(), stmt, passFlow);
						if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
					}
					count++;
				} while (count < PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT);
			}
		}

		bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), nonEmptyIterateeScope.raw());
		if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
		zv::Val finalEntryScope = zv::Val::copyOf(bodyScope.ref());
		zval *storage = originalStorage;
		bodyScope = enterForeach(nodeScopeResolver, bodyScope.raw(), storage, originalScope.raw(), stmt, parts, foreachIterateeType.raw(), foreachNativeIterateeType.raw(), nodeCallback);
		if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
		bool replay = !replayBodyRecording.isNull() && !replayPassStorage.isNull() && !replayPassResult.isNull() && !replayEntryScope.isNull();
		if (replay) {
			if (UNEXPECTED(!ptlh::scopesEqual(finalEntryScope.raw(), replayEntryScope.raw(), replay))) return zv::Val();
		}
		zv::Val finalScopeResult;
		if (replay) {
			// the final walk would repeat the recorded fixpoint pass exactly
			// (same entry scope, deterministic walk) - adopt the pass's results
			// and replay its emissions through the real callback instead
			if (UNEXPECTED(!pt_expression_result_storage_merge_results(originalStorage, replayPassStorage.raw()))) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_replay_recording(nodeScopeResolver, replayBodyRecording.raw(), nodeCallback, originalStorage, scope.raw()))) return zv::Val();
			finalScopeResult = std::move(replayPassResult);
		} else {
			zv::Val finalPassContext;
			if (unrolled) {
				finalPassContext = pt_statement_context_enter_unrolled_foreach(context, unrolledTotalKeys);
				if (UNEXPECTED(finalPassContext.isUndef())) return zv::Val();
			} else {
				finalPassContext = zv::Val::copyOf(zv::Ref(context));
			}
			zval *stmts = ptsh::readNodeProperty(pt_feh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
			zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), bodyScope.raw(), storage, nodeCallback, finalPassContext.raw());
			if (UNEXPECTED(walked.isUndef())) return zv::Val();
			finalScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
			if (UNEXPECTED(finalScopeResult.isUndef())) return zv::Val();
		}
		zval *result = finalScopeResult.raw();
		zv::Val finalScope = zv::Val::null();
		{
			bool endReachable;
			if (UNEXPECTED(!pt_internal_statement_result_is_end_reachable(result, endReachable))) return zv::Val();
			if (endReachable) {
				zv::Val hold;
				zval *resultScope = pt_internal_statement_result_scope(result, hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				finalScope = zv::Val::copyOf(zv::Ref(resultScope));
			}
		}
		zv::Arr scopesWithIterableValueType = zv::Arr::empty();

		/* re-read, like the twin's property reads after the walks */
		if (UNEXPECTED(!parts.read(stmt))) return zv::Val();
		iteratee = parts.expr.raw();
		zval *keyVarExpr = NULL;
		zv::Val originalKeyVarExpr;
		if (parts.keyVarName != NULL) {
			keyVarExpr = parts.keyVar.raw();
			zval nameZv;
			ZVAL_STR(&nameZv, parts.keyVarName);
			originalKeyVarExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_KEY_EXPR, 1, &nameZv);
			if (UNEXPECTED(originalKeyVarExpr.isUndef())) return zv::Val();
		}
		zv::Val originalValueExpr;
		if (parts.valueVarName != NULL) {
			zval nameZv;
			ZVAL_STR(&nameZv, parts.valueVarName);
			originalValueExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_VALUE_EXPR, 1, &nameZv);
			if (UNEXPECTED(originalValueExpr.isUndef())) return zv::Val();
		}

		// With a key variable, each iteration is tracked through the original key
		// expression and the narrowed element is projected onto the array dim fetch.
		// Without one (`foreach ($a as $v)`) we instead track the original value
		// expression and rewrite the array value type directly from the value var.
		zval *trackingExpr = !originalKeyVarExpr.isNull() ? originalKeyVarExpr.raw() : (!originalValueExpr.isNull() ? originalValueExpr.raw() : NULL);

		bool continueExitPointHasUnoriginalKeyType = false;
		if (trackingExpr != NULL && !finalScope.isNull()) {
			zend_long has = pt_mutating_scope_has_expression_type(Z_OBJ_P(finalScope.raw()), trackingExpr);
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has == PT_TRI_YES) {
				scopesWithIterableValueType.push(zv::Ref(finalScope.raw()));
			} else {
				continueExitPointHasUnoriginalKeyType = true;
			}
		}

		{
			zv::Val continues = ptlh::continueExitPoints(result);
			if (UNEXPECTED(continues.isUndef())) return zv::Val();
			for (auto entry : zv::ArrRef(continues.raw())) {
				zv::Val hold;
				zval *continueScopeSlot = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
				if (UNEXPECTED(continueScopeSlot == NULL)) return zv::Val();
				zv::Val continueScope = zv::Val::copyOf(zv::Ref(continueScopeSlot));
				if (finalScope.isNull()) {
					finalScope = zv::Val::copyOf(continueScope.ref());
				} else if (UNEXPECTED(!ptlh::otherMergeWith(continueScope.raw(), finalScope))) {
					return zv::Val();
				}
				bool tracked = false;
				if (trackingExpr != NULL) {
					zend_long has = pt_mutating_scope_has_expression_type(Z_OBJ_P(continueScope.raw()), trackingExpr);
					if (UNEXPECTED(has < 0)) return zv::Val();
					tracked = has == PT_TRI_YES;
				}
				if (!tracked) {
					continueExitPointHasUnoriginalKeyType = true;
					continue;
				}
				scopesWithIterableValueType.push(std::move(continueScope));
			}
		}
		zv::Val breakExitPoints = ptlh::breakExitPoints(result);
		if (UNEXPECTED(breakExitPoints.isUndef())) return zv::Val();
		for (auto entry : zv::ArrRef(breakExitPoints.raw())) {
			zv::Val hold;
			zval *breakScope = pt_internal_statement_exit_point_scope(entry.value().deref().raw(), hold);
			if (UNEXPECTED(breakScope == NULL)) return zv::Val();
			if (finalScope.isNull()) {
				finalScope = zv::Val::copyOf(zv::Ref(breakScope));
			} else if (UNEXPECTED(!ptlh::otherMergeWith(breakScope, finalScope))) {
				return zv::Val();
			}
		}
		if (finalScope.isNull()) {
			zv::Val hold;
			zval *resultScope = pt_internal_statement_result_scope(result, hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			finalScope = zv::Val::copyOf(zv::Ref(resultScope));
		}

		if (!unrolledEndScope.isNull()) {
			finalScope = zv::Val::copyOf(unrolledEndScope.ref());
		}

		// $scope is the post-loop scope; the body may have modified the iteratee
		// (e.g. $arr[] = ...). A tracked iteratee reads the modified type off the
		// scope (getTypeOnScope's authoritative read); only an untracked one whose
		// inputs the body changed needs reprocessing there to observe it.
		zv::Val exprType, exprNativeType;
		{
			bool answers;
			if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), scope.raw(), false, answers))) return zv::Val();
			if (answers) {
				if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), scope.raw(), true, answers))) return zv::Val();
			}
			if (answers) {
				exprType = pt_expression_result_get_type_on_scope(condResult.raw(), scope.raw(), false);
				if (UNEXPECTED(exprType.isUndef())) return zv::Val();
				exprNativeType = pt_expression_result_get_type_on_scope(condResult.raw(), scope.raw(), true);
				if (UNEXPECTED(exprNativeType.isUndef())) return zv::Val();
			} else {
				zv::Val freshStorage = pt_expression_result_storage_new();
				if (UNEXPECTED(freshStorage.isUndef())) return zv::Val();
				zv::Val postLoopIterateeResult = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, iteratee, scope.raw(), freshStorage.raw());
				if (UNEXPECTED(postLoopIterateeResult.isUndef())) return zv::Val();
				exprType = pt_expression_result_get_type(postLoopIterateeResult.raw());
				if (UNEXPECTED(exprType.isUndef())) return zv::Val();
				exprNativeType = pt_expression_result_get_native_type(postLoopIterateeResult.raw());
				if (UNEXPECTED(exprNativeType.isUndef())) return zv::Val();
			}
		}
		zend_long hasExpr = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope.raw()), iteratee);
		if (UNEXPECTED(hasExpr < 0)) return zv::Val();
		if (UNEXPECTED(!rewriteIterateeAfterLoop(nodeScopeResolver, parts, keyVarExpr, originalValueExpr, breakExitPoints.raw(), scopesWithIterableValueType, continueExitPointHasUnoriginalKeyType, hasExpr, exprType.raw(), exprNativeType.raw(), finalScope))) return zv::Val();

		zend_long isIterableAtLeastOnce = typeTrinary(exprType.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, "isIterableAtLeastOnce");
		if (UNEXPECTED(isIterableAtLeastOnce < 0)) return zv::Val();
		bool narrowToEmpty = isIterableAtLeastOnce == PT_TRI_MAYBE;
		if (!narrowToEmpty) {
			zend_long isIterable = pt_type_call_trinary(Z_OBJ_P(exprType.raw()), PT_LC("isiterable"), 0, NULL);
			if (UNEXPECTED(isIterable < 0)) return zv::Val();
			narrowToEmpty = isIterable == PT_TRI_NO;
		}
		bool resultAlwaysTerminating;
		if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(result, resultAlwaysTerminating))) return zv::Val();
		if (narrowToEmpty) {
			zv::Val emptyScope = narrowToEmptyOrObject(nodeScopeResolver, scope.raw(), iteratee);
			if (UNEXPECTED(emptyScope.isUndef())) return zv::Val();
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), emptyScope.raw());
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			finalScope = std::move(merged);
		} else if (isIterableAtLeastOnce == PT_TRI_NO || resultAlwaysTerminating) {
			finalScope = zv::Val::copyOf(scope.ref());
		} else if (!polluteScopeWithAlwaysIterableForeach) {
			zv::Val processed = pt_mutating_scope_process_always_iterable_foreach_scope_without_pollute(Z_OBJ_P(scope.raw()), Z_OBJ_P(finalScope.raw()));
			if (UNEXPECTED(processed.isUndef())) return zv::Val();
			finalScope = std::move(processed);
			// get types from finalScope, but don't create new variables
		}

		if (isIterableAtLeastOnce != PT_TRI_NO) {
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_throw_points(result, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPoints, more))) return zv::Val();
			}
			zv::Val hold;
			zval *more = pt_internal_statement_result_impure_points(result, hold);
			if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
		}
		zv::Val traversableThrowPoint = getTraversableForeachThrowPoint(scope.raw(), iteratee, exprType.raw());
		if (UNEXPECTED(traversableThrowPoint.isUndef())) return zv::Val();
		if (!traversableThrowPoint.isNull()) {
			throwPoints.push(zv::Ref(traversableThrowPoint.raw()));
		}
		if (isTopLevel && parts.byRef) {
			zv::Val byRefExpr = pt_type_new(PT_CLASS_FOREACH_VALUE_BY_REF_EXPR, 1, parts.valueVar.raw());
			if (UNEXPECTED(byRefExpr.isUndef())) return zv::Val();
			zv::Val mixed = pt_type_new_mixed_type();
			if (UNEXPECTED(mixed.isUndef())) return zv::Val();
			zv::Val nativeMixed = pt_type_new_mixed_type();
			if (UNEXPECTED(nativeMixed.isUndef())) return zv::Val();
			zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(finalScope.raw()), Z_OBJ_P(byRefExpr.raw()), mixed.raw(), nativeMixed.raw());
			if (UNEXPECTED(assigned.isUndef())) return zv::Val();
			finalScope = std::move(assigned);
		}

		zv::Val bindingFlow;
		{
			zval flows[5];
			for (int i = 0; i < 5; i++) {
				ZVAL_NULL(&flows[i]);
			}
			zv::Val keyRead, keyWrite, valueRead, valueWrite, escape;
			if (!parts.keyVar.isNull()) {
				keyRead = pt_variable_flow_builder_target_read(parts.keyVar.raw(), storage, false, NULL);
				if (UNEXPECTED(keyRead.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&flows[0], keyRead.raw());
				keyWrite = pt_variable_flow_builder_target_write(parts.keyVar.raw(), ptlh::PT_LH_WRITE_KIND_FOREACH_KEY, finalScope.raw(), storage, NULL);
				if (UNEXPECTED(keyWrite.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&flows[1], keyWrite.raw());
			}
			valueRead = pt_variable_flow_builder_target_read(parts.valueVar.raw(), storage, false, NULL);
			if (UNEXPECTED(valueRead.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&flows[2], valueRead.raw());
			valueWrite = pt_variable_flow_builder_target_write(parts.valueVar.raw(), ptlh::PT_LH_WRITE_KIND_FOREACH_VALUE, finalScope.raw(), storage, NULL);
			if (UNEXPECTED(valueWrite.isUndef())) return zv::Val();
			ZVAL_COPY_VALUE(&flows[3], valueWrite.raw());
			if (parts.byRef && parts.valueVarName != NULL) {
				escape = pt_variable_flow_escape(parts.valueVarName);
				if (UNEXPECTED(escape.isUndef())) return zv::Val();
				ZVAL_COPY_VALUE(&flows[4], escape.raw());
			}
			bindingFlow = pt_variable_flow_sequence(5, flows);
			if (UNEXPECTED(bindingFlow.isUndef())) return zv::Val();
		}
		zv::Val loopFlow;
		{
			zv::Val throwingFlow = zv::Val::null();
			if (!traversableThrowPoint.isNull()) {
				zv::Val hold;
				zval *throwType = pt_internal_throw_point_type(traversableThrowPoint.raw(), hold);
				if (UNEXPECTED(throwType == NULL)) return zv::Val();
				throwingFlow = pt_variable_flow_throwing(throwType, true, false);
				if (UNEXPECTED(throwingFlow.isUndef())) return zv::Val();
			}
			zv::Val bodyFlowHold;
			zval *bodyFlow = pt_internal_statement_result_variable_flow(result, bodyFlowHold);
			if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
			zv::Args bodyFlows{bindingFlow.raw(), bodyFlow};
			zv::Val body = pt_variable_flow_sequence(2, bodyFlows);
			if (UNEXPECTED(body.isUndef())) return zv::Val();
			loopFlow = pt_variable_flow_loop(throwingFlow.raw(), body.raw(), NULL, isIterableAtLeastOnce == PT_TRI_YES && polluteScopeWithAlwaysIterableForeach, true);
			if (UNEXPECTED(loopFlow.isUndef())) return zv::Val();
		}
		zv::Val bindingWrites = pt_variable_flow_builder_writes(bindingFlow.raw());
		if (UNEXPECTED(bindingWrites.isUndef())) return zv::Val();
		zv::Arr bindings = zv::Arr::empty();
		for (auto entry : zv::ArrRef(bindingWrites.raw())) {
			zval *write = entry.value().deref().raw();
			bool isOffsetWrite;
			zend_long id;
			if (UNEXPECTED(!ptlh::variableWriteInfo(write, isOffsetWrite, id))) return zv::Val();
			if (isOffsetWrite) continue;
			bindings.push(zv::Ref(write));
		}
		loopFlow = pt_variable_flow_loop_statement(stmt, loopFlow.raw(), bindings.raw(), bindingWrites.raw());
		if (UNEXPECTED(loopFlow.isUndef())) return zv::Val();

		zv::Val resultScope;
		{
			zv::Val hold;
			zval *bodyScopeSlot = pt_internal_statement_result_scope(result, hold);
			if (UNEXPECTED(bodyScopeSlot == NULL)) return zv::Val();
			zv::Val constraints = pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(bodyScopeSlot));
			if (UNEXPECTED(constraints.isUndef())) return zv::Val();
			resultScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(finalScope.raw()), constraints.raw());
			if (UNEXPECTED(resultScope.isUndef())) return zv::Val();
		}
		bool hasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(result, hasYield))) return zv::Val();
		if (!hasYield) {
			if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
		}
		zv::Val exitPoints = pt_internal_statement_result_exit_points_for_outer_loop(result);
		if (UNEXPECTED(exitPoints.isUndef())) return zv::Val();
		zv::Val variableFlow;
		{
			zv::Val condFlow = pt_expression_result_variable_flow(condResult.raw());
			if (UNEXPECTED(condFlow.isUndef())) return zv::Val();
			zv::Val statementLoopFlow;
			if (isIterableAtLeastOnce == PT_TRI_NO) {
				statementLoopFlow = pt_variable_flow_dead(loopFlow.raw());
				if (UNEXPECTED(statementLoopFlow.isUndef())) return zv::Val();
			} else {
				statementLoopFlow = std::move(loopFlow);
			}
			zv::Args flows{condFlow.raw(), statementLoopFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		return pt_internal_statement_result_new(resultScope.raw(), hasYield, isIterableAtLeastOnce == PT_TRI_YES && resultAlwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ForeachHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the Foreach_ properties the handler reads, owned for the duration of
	 * a step, with the twin's `instanceof Variable && is_string(->name)`
	 * answers */
	struct StmtParts
	{
		zv::Val expr;
		zv::Val keyVar;
		zv::Val valueVar;
		zv::Val stmts;
		bool byRef = false;
		bool keyVarIsVariable = false;
		bool valueVarIsVariable = false;
		bool valueVarIsList = false;
		/* the string names (borrowed from the held nodes), NULL otherwise */
		zend_string *exprName = NULL;
		zend_string *keyVarName = NULL;
		zend_string *valueVarName = NULL;

		/* false = pending exception */
		[[nodiscard]] bool read(zval *stmt)
		{
			zval *slot = ptsh::readNodeProperty(pt_feh_expr_site, stmt, PT_LC("expr"));
			if (UNEXPECTED(slot == NULL)) return false;
			expr = zv::Val::copyOf(zv::Ref(slot));
			slot = ptsh::readNodeProperty(pt_feh_key_var_site, stmt, PT_LC("keyVar"));
			if (UNEXPECTED(slot == NULL)) return false;
			keyVar = zv::Val::copyOf(zv::Ref(slot));
			slot = ptsh::readNodeProperty(pt_feh_value_var_site, stmt, PT_LC("valueVar"));
			if (UNEXPECTED(slot == NULL)) return false;
			valueVar = zv::Val::copyOf(zv::Ref(slot));
			slot = ptsh::readNodeProperty(pt_feh_by_ref_site, stmt, PT_LC("byRef"));
			if (UNEXPECTED(slot == NULL)) return false;
			byRef = zend_is_true(slot);
			slot = ptsh::readNodeProperty(pt_feh_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(slot == NULL)) return false;
			stmts = zv::Val::copyOf(zv::Ref(slot));

			bool error = false;
			exprName = variableName(expr.raw(), NULL, error);
			if (UNEXPECTED(error)) return false;
			keyVarName = variableName(keyVar.raw(), &keyVarIsVariable, error);
			if (UNEXPECTED(error)) return false;
			valueVarName = variableName(valueVar.raw(), &valueVarIsVariable, error);
			if (UNEXPECTED(error)) return false;
			valueVarIsList = !valueVarIsVariable && ptsh::isInstanceOf(valueVar.raw(), PT_CLASS_LIST_EXPR, error);
			return !error;
		}

		/* the string name of a Variable node, NULL otherwise */
		static zend_string *variableName(zval *node, bool *isVariable, bool &error)
		{
			bool variable = ptsh::isInstanceOf(node, PT_CLASS_VARIABLE, error);
			if (isVariable != NULL) *isVariable = variable;
			if (!variable) return NULL;
			zval *name = ptsh::readNodeProperty(pt_feh_variable_name_site, node, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) {
				error = true;
				return NULL;
			}
			return Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
		}
	};

	/* $this->varAnnotationProcessor->processVarAnnotation($scope, [$name], $stmt) */
	zv::Val processVarAnnotation(zval *scope, zend_string *name, zval *stmt) const
	{
		zv::Arr names = zv::Arr::create(1);
		names.push(zv::Val::string(name));
		return pt_var_annotation_processor_process_var_annotation(OBJ_PROP_NUM(self, slots::varAnnotationProcessor), scope, names.raw(), stmt, NULL);
	}

	/* static function () use ($condResult, $emptyArrayType): Type — captures:
	 * $condResult, $emptyArrayType */
	static void nonEmptyIterateeTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *condResult = &captures[0];
		zval *emptyArrayType = &captures[1];
		zv::Val iterateeType = pt_expression_result_get_type(condResult);
		if (UNEXPECTED(iterateeType.isUndef())) return;
		if (UNEXPECTED(Z_TYPE_P(iterateeType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(iterateeType.raw()));
			return;
		}
		zv::Val equals = pt_type_op(Z_OBJ_P(iterateeType.raw()), PT_OP_EQUALS, 1, emptyArrayType);
		if (UNEXPECTED(equals.isUndef())) return;
		zval out;
		if (zend_is_true(equals.raw())) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&out, true))) return;
			ZVAL_COPY_VALUE(return_value, &out);
			return;
		}
		zend_long superType = isSuperTypeOf(emptyArrayType, iterateeType.raw());
		if (UNEXPECTED(superType < 0)) return;
		if (superType == PT_TRI_NO) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&out, false))) return;
			ZVAL_COPY_VALUE(return_value, &out);
			return;
		}
		if (UNEXPECTED(!pt_boolean_type_new(&out))) return;
		ZVAL_COPY_VALUE(return_value, &out);
	}

	/* static fn (Type $type): Type => $replacement — captures: $replacement */
	static void replacementTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\StmtHandler\\ForeachHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		(void) argv;
		ZVAL_COPY(return_value, &captures[0]);
	}

	/* $type->mapValueType(static fn (Type $type): Type => $replacement) (or
	 * mapKeyType()) */
	static zv::Val mapWithReplacement(zval *type, const char *lcname, size_t len, zval *replacement)
	{
		zv::Val callback = pt_native_closure(&replacementTypeBody, replacement);
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(type));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(type), lcname, len, 1, callback.raw());
	}

	/* the post-loop rewrite of the iteratee's array type from the narrowed
	 * element (and key) types of the scopes the loop leaves through; false =
	 * pending exception */
	[[nodiscard]] bool rewriteIterateeAfterLoop(zval *nodeScopeResolver, StmtParts &parts, zval *keyVarExpr, zv::Val &originalValueExpr, zval *breakExitPoints, zv::Arr &scopesWithIterableValueType, bool continueExitPointHasUnoriginalKeyType, zend_long hasExpr, zval *exprType, zval *exprNativeType, zv::Val &finalScope) const
	{
		if (ptlh::countOf(breakExitPoints) != 0) return true;
		if (zend_hash_num_elements(scopesWithIterableValueType.table()) == 0) return true;
		if (continueExitPointHasUnoriginalKeyType) return true;
		if (keyVarExpr == NULL && originalValueExpr.isNull()) return true;
		if (hasExpr == PT_TRI_NO) {
			bool error = false;
			bool isVariable = ptsh::isInstanceOf(parts.expr.raw(), PT_CLASS_VARIABLE, error);
			if (UNEXPECTED(error)) return false;
			if (isVariable) return true;
		}
		zend_long isArray = typeTrinary(exprType, PT_OP_IS_ARRAY, "isArray");
		if (UNEXPECTED(isArray < 0)) return false;
		if (isArray != PT_TRI_YES) return true;
		zend_long isConstantArray = pt_type_op_trinary(Z_OBJ_P(exprType), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(isConstantArray < 0)) return false;
		if (isConstantArray != PT_TRI_NO) return true;

		zv::Arr arrayDimFetchLoopTypes = zv::Arr::empty();
		zv::Arr arrayDimFetchLoopNativeTypes = zv::Arr::empty();
		zv::Arr keyLoopTypes = zv::Arr::empty();
		zv::Arr keyLoopNativeTypes = zv::Arr::empty();
		for (auto entry : zv::ArrRef(scopesWithIterableValueType.raw())) {
			zval *scopeWithIterableValueType = entry.value().deref().raw();
			zv::Val dimFetchType, dimFetchNativeType;
			if (keyVarExpr != NULL) {
				zv::Val arrayExprDimFetch = newArrayDimFetch(parts.expr.raw(), keyVarExpr);
				if (UNEXPECTED(arrayExprDimFetch.isUndef())) return false;
				// enterForeach tracks this exact dim fetch - the tracked-holder
				// fast path answers without pricing the synthetic node
				dimFetchType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, arrayExprDimFetch.raw(), scopeWithIterableValueType);
				if (UNEXPECTED(dimFetchType.isUndef())) return false;
				{
					zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeWithIterableValueType));
					if (UNEXPECTED(nativeScope.isUndef())) return false;
					dimFetchNativeType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, arrayExprDimFetch.raw(), nativeScope.raw());
					if (UNEXPECTED(dimFetchNativeType.isUndef())) return false;
				}
				// Condition-based narrowings like `is_string($type)` apply to the value
				// variable but not automatically to the array dim fetch, even though the
				// two describe the same element for a given iteration. If the value var
				// hasn't been reassigned (OriginalForeachValueExpr still tracked) we use
				// the narrowed value-var type in place of the broader dim fetch type so
				// the loop's final array rewrite below picks up the sharper element type.
				bool valueTracked = false;
				if (!originalValueExpr.isNull()) {
					zend_long has = pt_mutating_scope_has_expression_type(Z_OBJ_P(scopeWithIterableValueType), originalValueExpr.raw());
					if (UNEXPECTED(has < 0)) return false;
					valueTracked = has == PT_TRI_YES;
				}
				if (valueTracked) {
					// read the loop value variable's narrowed type directly by name -
					// it is an assigned (not processExprNode-processed) variable, so
					// getVariableType() consumes its tracked type without pricing the
					// unprocessed node on demand. ($originalValueExpr !== null implies
					// the value var is a string-named Variable.)
					zv::Val valueVarType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scopeWithIterableValueType), parts.valueVarName);
					if (UNEXPECTED(valueVarType.isUndef())) return false;
					zend_long superType = isSuperTypeOf(dimFetchType.raw(), valueVarType.raw());
					if (UNEXPECTED(superType < 0)) return false;
					if (superType == PT_TRI_YES) {
						dimFetchType = std::move(valueVarType);
					}
					zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeWithIterableValueType));
					if (UNEXPECTED(nativeScope.isUndef())) return false;
					zv::Val valueVarNativeType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), parts.valueVarName);
					if (UNEXPECTED(valueVarNativeType.isUndef())) return false;
					superType = isSuperTypeOf(dimFetchNativeType.raw(), valueVarNativeType.raw());
					if (UNEXPECTED(superType < 0)) return false;
					if (superType == PT_TRI_YES) {
						dimFetchNativeType = std::move(valueVarNativeType);
					}
				}
				zv::Val keyType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, keyVarExpr, scopeWithIterableValueType);
				if (UNEXPECTED(keyType.isUndef())) return false;
				keyLoopTypes.push(std::move(keyType));
				zv::Val keyNativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeWithIterableValueType));
				if (UNEXPECTED(keyNativeScope.isUndef())) return false;
				zv::Val keyNativeType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, keyVarExpr, keyNativeScope.raw());
				if (UNEXPECTED(keyNativeType.isUndef())) return false;
				keyLoopNativeTypes.push(std::move(keyNativeType));
			} else {
				// No key variable: the narrowed value var is the array element type
				// directly. Read it by name (assigned, not processExprNode-processed);
				// no key var implies $originalValueExpr !== null, so the value var is
				// a string-named Variable.
				dimFetchType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scopeWithIterableValueType), parts.valueVarName);
				if (UNEXPECTED(dimFetchType.isUndef())) return false;
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeWithIterableValueType));
				if (UNEXPECTED(nativeScope.isUndef())) return false;
				dimFetchNativeType = pt_mutating_scope_get_variable_type(Z_OBJ_P(nativeScope.raw()), parts.valueVarName);
				if (UNEXPECTED(dimFetchNativeType.isUndef())) return false;
			}
			arrayDimFetchLoopTypes.push(std::move(dimFetchType));
			arrayDimFetchLoopNativeTypes.push(std::move(dimFetchNativeType));
		}

		zv::Val arrayDimFetchLoopType = unionOfList(arrayDimFetchLoopTypes);
		if (UNEXPECTED(arrayDimFetchLoopType.isUndef())) return false;
		zv::Val arrayDimFetchLoopNativeType = unionOfList(arrayDimFetchLoopNativeTypes);
		if (UNEXPECTED(arrayDimFetchLoopNativeType.isUndef())) return false;

		bool valueTypeChanged;
		{
			zv::Val iterableValueType = pt_type_op(Z_OBJ_P(exprType), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
			if (UNEXPECTED(iterableValueType.isUndef())) return false;
			zv::Val equals = typeOp(arrayDimFetchLoopType.raw(), PT_OP_EQUALS, "equals", 1, iterableValueType.raw());
			if (UNEXPECTED(equals.isUndef())) return false;
			valueTypeChanged = !zend_is_true(equals.raw());
		}
		bool keyTypeChanged = false;
		zv::Val keyLoopType = pt_type_op(Z_OBJ_P(exprType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(keyLoopType.isUndef())) return false;
		zv::Val keyLoopNativeType = typeOp(exprNativeType, PT_OP_GET_ITERABLE_KEY_TYPE, "getIterableKeyType");
		if (UNEXPECTED(keyLoopNativeType.isUndef())) return false;
		if (keyVarExpr != NULL) {
			keyLoopType = unionOfList(keyLoopTypes);
			if (UNEXPECTED(keyLoopType.isUndef())) return false;
			keyLoopNativeType = unionOfList(keyLoopNativeTypes);
			if (UNEXPECTED(keyLoopNativeType.isUndef())) return false;
			zv::Val iterableKeyType = pt_type_op(Z_OBJ_P(exprType), PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
			if (UNEXPECTED(iterableKeyType.isUndef())) return false;
			zv::Val equals = typeOp(keyLoopType.raw(), PT_OP_EQUALS, "equals", 1, iterableKeyType.raw());
			if (UNEXPECTED(equals.isUndef())) return false;
			keyTypeChanged = !zend_is_true(equals.raw());
		}

		if (!valueTypeChanged && !keyTypeChanged) return true;

		zv::Val newExprType = zv::Val::copyOf(zv::Ref(exprType));
		if (valueTypeChanged) {
			newExprType = mapWithReplacement(newExprType.raw(), PT_LC("mapvaluetype"), arrayDimFetchLoopType.raw());
			if (UNEXPECTED(newExprType.isUndef())) return false;
		}
		if (keyTypeChanged) {
			newExprType = mapWithReplacement(newExprType.raw(), PT_LC("mapkeytype"), keyLoopType.raw());
			if (UNEXPECTED(newExprType.isUndef())) return false;
		}

		zv::Val newExprNativeType = zv::Val::copyOf(zv::Ref(exprNativeType));
		if (valueTypeChanged) {
			newExprNativeType = mapWithReplacement(newExprNativeType.raw(), PT_LC("mapvaluetype"), arrayDimFetchLoopNativeType.raw());
			if (UNEXPECTED(newExprNativeType.isUndef())) return false;
		}
		if (keyTypeChanged) {
			newExprNativeType = mapWithReplacement(newExprNativeType.raw(), PT_LC("mapkeytype"), keyLoopNativeType.raw());
			if (UNEXPECTED(newExprNativeType.isUndef())) return false;
		}

		zv::Val assigned;
		if (parts.exprName != NULL) {
			assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(finalScope.raw()), parts.exprName, newExprType.raw(), newExprNativeType.raw(), pt_trinary_singleton(hasExpr));
		} else {
			assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(finalScope.raw()), Z_OBJ_P(parts.expr.raw()), newExprType.raw(), newExprNativeType.raw());
		}
		if (UNEXPECTED(assigned.isUndef())) return false;
		finalScope = std::move(assigned);
		return true;
	}

	/* $nodeScopeResolver->narrowScopeWithCondition($scope, new BooleanOr(new
	 * Identical($iteratee, new Array_([])), new FuncCall(new
	 * Name\FullyQualified('is_object'), [new Arg($iteratee)])),
	 * TypeSpecifierContext::createTruthy()) — the nodes created in the
	 * twin's order */
	static zv::Val narrowToEmptyOrObject(zval *nodeScopeResolver, zval *scope, zval *iteratee)
	{
		zv::Val emptyArray = newEmptyArrayExpr();
		if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
		zv::Args identicalArgv{iteratee, emptyArray.raw()};
		zv::Val identical = pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
		if (UNEXPECTED(identical.isUndef())) return zv::Val();
		zval isObject;
		ZVAL_INTERNED_STR(&isObject, pt_feh_is_object);
		zv::Val name = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, &isObject);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, iteratee);
		if (UNEXPECTED(arg.isUndef())) return zv::Val();
		zv::Arr args = zv::Arr::create(1);
		args.push(std::move(arg));
		zv::Args callArgv{name.raw(), args.raw()};
		zv::Val call = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
		if (UNEXPECTED(call.isUndef())) return zv::Val();
		zv::Args orArgv{identical.raw(), call.raw()};
		zv::Val booleanOr = pt_type_new(PT_CLASS_BOOLEAN_OR_EXPR, 2, orArgv);
		if (UNEXPECTED(booleanOr.isUndef())) return zv::Val();
		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return zv::Val();
		zval truthyContext;
		ZVAL_OBJ(&truthyContext, truthy);
		return pt_node_scope_resolver_narrow_scope_with_condition(nodeScopeResolver, scope, booleanOr.raw(), &truthyContext);
	}

	/* Mirrors the private enterForeach(). */
	zv::Val enterForeach(zval *nodeScopeResolver, zval *scopeArg, zval *storage, zval *originalScope, zval *stmt, StmtParts &parts, zval *iterateeType, zval *nativeIterateeType, zval *nodeCallback) const
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *iteratee = parts.expr.raw();
		if (parts.exprName != NULL) {
			scope = processVarAnnotation(scope.raw(), parts.exprName, stmt);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}

		zv::Arr vars = zv::Arr::empty();
		if (parts.valueVarName != NULL && (parts.keyVar.isNull() || parts.keyVarName != NULL)) {
			scope = pt_mutating_scope_enter_foreach(Z_OBJ_P(scope.raw()), Z_OBJ_P(originalScope), iteratee, iterateeType, nativeIterateeType, parts.valueVarName, parts.keyVarIsVariable ? parts.keyVarName : NULL, parts.byRef);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			vars = zv::Arr::create(2);
			vars.push(zv::Val::string(parts.valueVarName));
			if (parts.keyVarIsVariable && parts.keyVarName != NULL) {
				vars.push(zv::Val::string(parts.keyVarName));
			}
		} else {
			zv::Val valueTypeExpr = newIterableTypeExpr(originalScope, iterateeType, nativeIterateeType, false);
			if (UNEXPECTED(valueTypeExpr.isUndef())) return zv::Val();
			zv::Val assignResult = pt_assign_handler_process_virtual_assign(OBJ_PROP_NUM(self, slots::assignHandler), nodeScopeResolver, scope.raw(), storage, stmt, parts.valueVar.raw(), valueTypeExpr.raw(), nodeCallback, NULL);
			if (UNEXPECTED(assignResult.isUndef())) return zv::Val();
			{
				zv::Val hold;
				zval *resultScope = pt_expression_result_scope(assignResult.raw(), hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				scope = zv::Val::copyOf(zv::Ref(resultScope));
			}
			zv::Val assigned = pt_node_scope_resolver_get_assigned_variables(nodeScopeResolver, parts.valueVar.raw());
			if (UNEXPECTED(assigned.isUndef())) return zv::Val();
			if (UNEXPECTED(!ptlh::arrayOf(assigned.raw(), vars))) return zv::Val();
			if (parts.keyVarName != NULL) {
				scope = pt_mutating_scope_enter_foreach_key(Z_OBJ_P(scope.raw()), Z_OBJ_P(originalScope), iteratee, iterateeType, nativeIterateeType, parts.keyVarName);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
				vars.push(zv::Val::string(parts.keyVarName));
			} else if (!parts.keyVar.isNull()) {
				zv::Val keyTypeExpr = newIterableTypeExpr(originalScope, iterateeType, nativeIterateeType, true);
				if (UNEXPECTED(keyTypeExpr.isUndef())) return zv::Val();
				zv::Val keyAssignResult = pt_assign_handler_process_virtual_assign(OBJ_PROP_NUM(self, slots::assignHandler), nodeScopeResolver, scope.raw(), storage, stmt, parts.keyVar.raw(), keyTypeExpr.raw(), nodeCallback, NULL);
				if (UNEXPECTED(keyAssignResult.isUndef())) return zv::Val();
				{
					zv::Val hold;
					zval *resultScope = pt_expression_result_scope(keyAssignResult.raw(), hold);
					if (UNEXPECTED(resultScope == NULL)) return zv::Val();
					scope = zv::Val::copyOf(zv::Ref(resultScope));
				}
				zv::Val keyAssigned = pt_node_scope_resolver_get_assigned_variables(nodeScopeResolver, parts.keyVar.raw());
				if (UNEXPECTED(keyAssigned.isUndef())) return zv::Val();
				if (UNEXPECTED(!ptlh::mergeInto(vars, keyAssigned.raw()))) return zv::Val();
			}

			if (parts.valueVarIsList) {
				zv::Val iterableValueType = pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(originalScope), iterateeType);
				if (UNEXPECTED(iterableValueType.isUndef())) return zv::Val();
				scope = addDestructureTaggedUnionConditionalHolders(scope.raw(), iterableValueType.raw(), parts.valueVar.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		zv::Val constantArrays = typeOp(iterateeType, PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		bool noDocComment;
		if (UNEXPECTED(!hasNoDocComment(stmt, noDocComment))) return zv::Val();
		if (noDocComment) {
			zend_long isConstantArray = pt_type_op_trinary(Z_OBJ_P(iterateeType), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
			if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
			if (isConstantArray == PT_TRI_YES && ptlh::countOf(constantArrays.raw()) == 1 && parts.valueVarName != NULL && parts.keyVarName != NULL) {
				scope = addConstantArrayConditionalHolders(scope.raw(), parts, listAt(constantArrays.raw(), 0));
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		{
			bool error = false;
			if (ptsh::isInstanceOf(iteratee, PT_CLASS_FUNC_CALL, error) && parts.valueVarIsVariable) {
				scope = assignArrayKeysElement(nodeScopeResolver, scope.raw(), storage, iteratee, parts.valueVar.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
			if (UNEXPECTED(error)) return zv::Val();
		}

		return pt_var_annotation_processor_process_var_annotation(OBJ_PROP_NUM(self, slots::varAnnotationProcessor), scope.raw(), vars.raw(), stmt, NULL);
	}

	/* enterForeach()'s `foreach ($constantArray as $key => $value)` holders;
	 * UNDEF = pending exception */
	static zv::Val addConstantArrayConditionalHolders(zval *scopeArg, StmtParts &parts, zval *constantArray)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zv::Arr valueConditionalHolders = zv::Arr::empty();
		zv::Arr arrayDimFetchConditionalHolders = zv::Arr::empty();
		zv::Str keyExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(parts.keyVarName), ZSTR_LEN(parts.keyVarName)));
		zv::Val keyTypes = typeOp(constantArray, PT_OP_GET_KEY_TYPES, "getKeyTypes");
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		if (Z_TYPE_P(keyTypes.raw()) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = entry.value().deref().raw();
				zv::Val valueTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
				if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
				zval *valueType = listAt(valueTypes.raw(), entry.indexKey());
				zval nullZv;
				if (UNEXPECTED(valueType == NULL)) {
					zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, entry.indexKey());
					if (UNEXPECTED(EG(exception))) return zv::Val();
					ZVAL_NULL(&nullZv);
					valueType = &nullZv;
				}
				zv::Val keyVariable = newVariable(parts.keyVarName);
				if (UNEXPECTED(keyVariable.isUndef())) return zv::Val();
				zv::Val keyExpressionTypeHolder = expressionTypeHolderYes(keyVariable.raw(), keyType);

				zv::Arr conditions = zv::Arr::create(1);
				conditions.set(keyExprString.get(), zv::Val::copyOf(keyExpressionTypeHolder.ref()));
				zv::Val valueHolder = expressionTypeHolderYes(parts.valueVar.raw(), valueType);
				zv::Val holder = newConditionalExpressionHolder(conditions.raw(), valueHolder.raw());
				if (UNEXPECTED(holder.isUndef())) return zv::Val();
				zv::Str holderKey = zv::Str::adopt(conditionalHolderKey(holder.raw()));
				if (UNEXPECTED(holderKey.isNull())) return zv::Val();
				valueConditionalHolders.set(holderKey.get(), std::move(holder));

				zv::Arr dimConditions = zv::Arr::create(1);
				dimConditions.set(keyExprString.get(), zv::Val::copyOf(keyExpressionTypeHolder.ref()));
				zv::Val dimFetch = newArrayDimFetch(parts.expr.raw(), parts.keyVar.raw());
				if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
				zv::Val dimHolder = expressionTypeHolderYes(dimFetch.raw(), valueType);
				zv::Val arrayDimFetchHolder = newConditionalExpressionHolder(dimConditions.raw(), dimHolder.raw());
				if (UNEXPECTED(arrayDimFetchHolder.isUndef())) return zv::Val();
				zv::Str dimHolderKey = zv::Str::adopt(conditionalHolderKey(arrayDimFetchHolder.raw()));
				if (UNEXPECTED(dimHolderKey.isNull())) return zv::Val();
				arrayDimFetchConditionalHolders.set(dimHolderKey.get(), std::move(arrayDimFetchHolder));
			}
		}

		{
			zv::Str valueExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(parts.valueVarName), ZSTR_LEN(parts.valueVarName)));
			scope = pt_mutating_scope_add_conditional_expressions(Z_OBJ_P(scope.raw()), valueExprString.get(), valueConditionalHolders.table());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}
		if (parts.exprName != NULL) {
			smart_str dimExprString = {};
			smart_str_appendc(&dimExprString, '$');
			smart_str_append(&dimExprString, parts.exprName);
			smart_str_appends(&dimExprString, "[$");
			smart_str_append(&dimExprString, parts.keyVarName);
			smart_str_appendc(&dimExprString, ']');
			zv::Str dimKey = zv::Str::adopt(smart_str_extract(&dimExprString));
			scope = pt_mutating_scope_add_conditional_expressions(Z_OBJ_P(scope.raw()), dimKey.get(), arrayDimFetchConditionalHolders.table());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}
		return scope;
	}

	/* enterForeach()'s `foreach (array_keys($array) as $key)` element
	 * tracking (the iteratee is a FuncCall and the value var a Variable);
	 * UNDEF = pending exception */
	static zv::Val assignArrayKeysElement(zval *nodeScopeResolver, zval *scopeArg, zval *storage, zval *call, zval *valueVar)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *name = ptsh::readNodeProperty(pt_feh_call_name_site, call, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		bool error = false;
		if (!ptsh::isInstanceOf(name, PT_CLASS_NAME, error)) return error ? zv::Val() : std::move(scope);
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable))) return zv::Val();
		if (firstClassCallable) return scope;
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
		zv::Val lower = ptlh::nameToLowerString(nameHold.raw());
		if (UNEXPECTED(lower.isUndef())) return zv::Val();
		if (!lower.ref().stringEquals("array_keys")) return scope;

		zv::Val argsHold;
		zval *args = pt_call_like_args(Z_OBJ_P(call), argsHold);
		if (UNEXPECTED(args == NULL)) return zv::Val();
		zv::Val argsValue = zv::Val::copyOf(zv::Ref(args));
		if (ptlh::countOf(argsValue.raw()) < 1) return scope;
		zval *firstArg = listAt(argsValue.raw(), 0);
		if (UNEXPECTED(firstArg == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			return zv::Val();
		}
		if (UNEXPECTED(Z_TYPE_P(firstArg) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(firstArg));
			return zv::Val();
		}
		zval *arrayArgSlot = ptsh::readNodeProperty(pt_feh_arg_value_site, firstArg, PT_LC("value"));
		if (UNEXPECTED(arrayArgSlot == NULL)) return zv::Val();
		zv::Val arrayArg = zv::Val::copyOf(zv::Ref(arrayArgSlot));

		zv::Val dimFetch = newArrayDimFetch(arrayArg.raw(), valueVar);
		if (UNEXPECTED(dimFetch.isUndef())) return zv::Val();
		zv::Val stored = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, arrayArg.raw(), storage);
		if (UNEXPECTED(stored.isUndef())) return zv::Val();
		zv::Val arrayType = pt_expression_result_get_type_on_scope(stored.raw(), scope.raw(), false);
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();
		zv::Val valueType = typeOp(arrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, "getIterableValueType");
		if (UNEXPECTED(valueType.isUndef())) return zv::Val();
		zv::Val storedAgain = pt_node_scope_resolver_read_stored_result(nodeScopeResolver, arrayArg.raw(), storage);
		if (UNEXPECTED(storedAgain.isUndef())) return zv::Val();
		zv::Val nativeArrayType = pt_expression_result_get_type_on_scope(storedAgain.raw(), scope.raw(), true);
		if (UNEXPECTED(nativeArrayType.isUndef())) return zv::Val();
		zv::Val nativeValueType = typeOp(nativeArrayType.raw(), PT_OP_GET_ITERABLE_VALUE_TYPE, "getIterableValueType");
		if (UNEXPECTED(nativeValueType.isUndef())) return zv::Val();
		return pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(dimFetch.raw()), valueType.raw(), nativeValueType.raw());
	}

	/* the array shape of the private tryProcessUnrolledConstantArrayForeach()
	 * (found = false for its null) */
	struct UnrolledResult
	{
		bool found = false;
		zv::Val bodyScope;
		zv::Val endScope;
		zend_long totalKeys = 0;
	};

	/* Mirrors the private tryProcessUnrolledConstantArrayForeach(); false =
	 * pending exception */
	[[nodiscard]] bool tryProcessUnrolledConstantArrayForeach(zval *nodeScopeResolver, zval *stmt, StmtParts &parts, zval *originalScope, zval *originalStorage, zval *context, zval *iterateeType, zval *nativeIterateeType, UnrolledResult &out) const
	{
		if (parts.byRef) return true;
		if (parts.valueVarName == NULL) return true;
		if (!parts.keyVar.isNull() && parts.keyVarName == NULL) return true;

		zend_long isConstantArray = typeTrinary(iterateeType, PT_OP_IS_CONSTANT_ARRAY, "isConstantArray");
		if (UNEXPECTED(isConstantArray < 0)) return false;
		if (isConstantArray != PT_TRI_YES) return true;
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(iterateeType), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return false;
		uint32_t constantArrayCount = ptlh::countOf(constantArrays.raw());
		if (constantArrayCount == 0) return true;

		zend_long totalKeys = 0;
		bool hasUnsealed = false;
		for (auto entry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = entry.value().deref().raw();
			zv::Val keyTypes = typeOp(constantArray, PT_OP_GET_KEY_TYPES, "getKeyTypes");
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			totalKeys += ptlh::countOf(keyTypes.raw());
			zend_long isUnsealed = pt_type_op_trinary(Z_OBJ_P(constantArray), PT_OP_IS_UNSEALED, 0, NULL);
			if (UNEXPECTED(isUnsealed < 0)) return false;
			if (isUnsealed != PT_TRI_YES) continue;
			hasUnsealed = true;
		}
		if (totalKeys == 0 || totalKeys > PT_FEH_FOREACH_UNROLL_LIMIT) return true;
		zend_long foreachUnrollFactor;
		if (UNEXPECTED(!pt_statement_context_get_foreach_unroll_factor(context, foreachUnrollFactor))) return false;
		if (foreachUnrollFactor > 1 && foreachUnrollFactor * totalKeys > PT_FEH_FOREACH_UNROLL_NESTED_LIMIT) return true;

		zv::Val nativeConstantArrays = typeOp(nativeIterateeType, PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
		if (UNEXPECTED(nativeConstantArrays.isUndef())) return false;
		bool matchedNativeArrays = ptlh::countOf(nativeConstantArrays.raw()) == constantArrayCount;
		// the native fallback must not be the PHPDoc key/value type - the shape the
		// unrolling is driven by can be PHPDoc-only, in which case natively we only
		// know what the native iteratee type says about its keys and values
		zv::Val nativeIterateeKeyType = pt_mutating_scope_get_iterable_key_type(Z_OBJ_P(originalScope), nativeIterateeType);
		if (UNEXPECTED(nativeIterateeKeyType.isUndef())) return false;
		zv::Val nativeIterateeValueType = pt_mutating_scope_get_iterable_value_type(Z_OBJ_P(originalScope), nativeIterateeType);
		if (UNEXPECTED(nativeIterateeValueType.isUndef())) return false;

		zend_string *valueVarName = parts.valueVarName;
		zend_string *keyVarName = parts.keyVarIsVariable ? parts.keyVarName : NULL;

		zv::Arr allBodyScopes = zv::Arr::empty();
		zv::Arr allChainScopes = zv::Arr::empty();
		zv::Arr allBreakScopes = zv::Arr::empty();

		zv::Val bodyContext = pt_statement_context_enter_unrolled_foreach(context, totalKeys);
		if (UNEXPECTED(bodyContext.isUndef())) return false;

		for (auto arrayEntry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = arrayEntry.value().deref().raw();
			zv::Val keyTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_KEY_TYPES, 0, NULL);
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			zv::Val valueTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
			if (UNEXPECTED(valueTypes.isUndef())) return false;
			if (ptlh::countOf(keyTypes.raw()) == 0) continue;

			zval *nativeConstantArray = NULL;
			if (matchedNativeArrays) {
				nativeConstantArray = listAt(nativeConstantArrays.raw(), arrayEntry.indexKey());
				if (UNEXPECTED(nativeConstantArray == NULL)) {
					zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, arrayEntry.indexKey());
					if (UNEXPECTED(EG(exception))) return false;
				}
			}
			/* $optionalKeys = array_fill_keys($constantArray->getOptionalKeys(), true) */
			zv::Val optionalKeyList = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_OPTIONAL_KEYS, 0, NULL);
			if (UNEXPECTED(optionalKeyList.isUndef())) return false;
			zv::ScratchTable optionalKeys(8);
			if (Z_TYPE_P(optionalKeyList.raw()) == IS_ARRAY) {
				for (auto optionalEntry : zv::ArrRef(optionalKeyList.raw())) {
					zval flag;
					ZVAL_TRUE(&flag);
					zval *key = optionalEntry.value().deref().raw();
					if (Z_TYPE_P(key) == IS_LONG) {
						zend_hash_index_update(optionalKeys.table(), (zend_ulong) Z_LVAL_P(key), &flag);
					} else if (Z_TYPE_P(key) == IS_STRING) {
						zend_symtable_update(optionalKeys.table(), Z_STR_P(key), &flag);
					}
				}
			}

			zv::Val chainScope = zv::Val::copyOf(zv::Ref(originalScope));
			zv::Arr entryScopes = zv::Arr::empty();

			for (auto keyEntry : zv::ArrRef(keyTypes.raw())) {
				zend_ulong i = keyEntry.indexKey();
				zval *keyType = keyEntry.value().deref().raw();
				zval *valueType = listAt(valueTypes.raw(), i);
				zval nullValue;
				if (UNEXPECTED(valueType == NULL)) {
					zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, i);
					if (UNEXPECTED(EG(exception))) return false;
					ZVAL_NULL(&nullValue);
					valueType = &nullValue;
				}
				bool isOptional = zend_hash_index_find(optionalKeys.table(), i) != NULL;

				zv::Val nativeKeyType = zv::Val::copyOf(nativeIterateeKeyType.ref());
				zv::Val nativeValueType = zv::Val::copyOf(nativeIterateeValueType.ref());
				if (nativeConstantArray != NULL) {
					zv::Val nativeKeyTypes = typeOp(nativeConstantArray, PT_OP_GET_KEY_TYPES, "getKeyTypes");
					if (UNEXPECTED(nativeKeyTypes.isUndef())) return false;
					zval *nativeKey = listAt(nativeKeyTypes.raw(), i);
					if (nativeKey != NULL && Z_TYPE_P(nativeKey) != IS_NULL) {
						zv::Val againKeyTypes = pt_type_op(Z_OBJ_P(nativeConstantArray), PT_OP_GET_KEY_TYPES, 0, NULL);
						if (UNEXPECTED(againKeyTypes.isUndef())) return false;
						zval *againKey = listAt(againKeyTypes.raw(), i);
						nativeKeyType = againKey != NULL ? zv::Val::copyOf(zv::Ref(againKey)) : zv::Val::null();
					}
					zv::Val nativeValueTypes = pt_type_op(Z_OBJ_P(nativeConstantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
					if (UNEXPECTED(nativeValueTypes.isUndef())) return false;
					zval *nativeValue = listAt(nativeValueTypes.raw(), i);
					if (nativeValue != NULL && Z_TYPE_P(nativeValue) != IS_NULL) {
						zv::Val againValueTypes = pt_type_op(Z_OBJ_P(nativeConstantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
						if (UNEXPECTED(againValueTypes.isUndef())) return false;
						zval *againValue = listAt(againValueTypes.raw(), i);
						nativeValueType = againValue != NULL ? zv::Val::copyOf(zv::Ref(againValue)) : zv::Val::null();
					}
				}

				zv::Val iterScope = assignVariableYes(chainScope.raw(), valueVarName, valueType, nativeValueType.raw());
				if (UNEXPECTED(iterScope.isUndef())) return false;
				{
					zval nameZv;
					ZVAL_STR(&nameZv, valueVarName);
					zv::Val originalValueExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_VALUE_EXPR, 1, &nameZv);
					if (UNEXPECTED(originalValueExpr.isUndef())) return false;
					iterScope = pt_mutating_scope_assign_expression(Z_OBJ_P(iterScope.raw()), Z_OBJ_P(originalValueExpr.raw()), valueType, nativeValueType.raw());
					if (UNEXPECTED(iterScope.isUndef())) return false;
				}
				if (keyVarName != NULL) {
					iterScope = assignVariableYes(iterScope.raw(), keyVarName, keyType, nativeKeyType.raw());
					if (UNEXPECTED(iterScope.isUndef())) return false;
					zval nameZv;
					ZVAL_STR(&nameZv, keyVarName);
					zv::Val originalKeyExpr = pt_type_new(PT_CLASS_ORIGINAL_FOREACH_KEY_EXPR, 1, &nameZv);
					if (UNEXPECTED(originalKeyExpr.isUndef())) return false;
					iterScope = pt_mutating_scope_assign_expression(Z_OBJ_P(iterScope.raw()), Z_OBJ_P(originalKeyExpr.raw()), keyType, nativeKeyType.raw());
					if (UNEXPECTED(iterScope.isUndef())) return false;
					zv::Val dimFetch = newArrayDimFetch(parts.expr.raw(), parts.keyVar.raw());
					if (UNEXPECTED(dimFetch.isUndef())) return false;
					iterScope = pt_mutating_scope_assign_expression(Z_OBJ_P(iterScope.raw()), Z_OBJ_P(dimFetch.raw()), valueType, nativeValueType.raw());
					if (UNEXPECTED(iterScope.isUndef())) return false;
				}

				entryScopes.push(zv::Ref(iterScope.raw()));

				zv::Val iterStorage = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(iterStorage.isUndef())) return false;
				zv::Val noop = ptlh::newNoopNodeCallback();
				if (UNEXPECTED(noop.isUndef())) return false;
				zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, parts.stmts.raw(), iterScope.raw(), iterStorage.raw(), noop.raw(), bodyContext.raw());
				if (UNEXPECTED(walked.isUndef())) return false;
				zv::Val bodyResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
				if (UNEXPECTED(bodyResult.isUndef())) return false;

				zv::Val iterEndScope = pt_internal_statement_result_loop_back_edge_scope(bodyResult.raw());
				if (UNEXPECTED(iterEndScope.isUndef())) return false;
				{
					zv::Val breaks = ptlh::breakExitPoints(bodyResult.raw());
					if (UNEXPECTED(breaks.isUndef())) return false;
					for (auto breakEntry : zv::ArrRef(breaks.raw())) {
						zv::Val hold;
						zval *breakScope = pt_internal_statement_exit_point_scope(breakEntry.value().deref().raw(), hold);
						if (UNEXPECTED(breakScope == NULL)) return false;
						allBreakScopes.push(zv::Ref(breakScope));
					}
				}

				if (iterEndScope.isNull()) {
					if (isOptional) {
						// the key may be missing, the next iteration then starts from the previous one
						continue;
					}

					// no later iteration runs, the loop is left only through its break statements
					chainScope = zv::Val::null();
					break;
				}

				if (isOptional) {
					zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(iterEndScope.raw()), chainScope.raw());
					if (UNEXPECTED(merged.isUndef())) return false;
					chainScope = std::move(merged);
				} else {
					chainScope = std::move(iterEndScope);
				}
			}

			uint32_t entryCount = zend_hash_num_elements(entryScopes.table());
			zval *first = listAt(entryScopes.raw(), 0);
			if (UNEXPECTED(first == NULL)) {
				zend_error(E_WARNING, "Undefined array key 0");
				return false;
			}
			zv::Val arrayBodyScope = zv::Val::copyOf(zv::Ref(first));
			for (uint32_t i = 1; i < entryCount; i++) {
				zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(arrayBodyScope.raw()), listAt(entryScopes.raw(), i));
				if (UNEXPECTED(merged.isUndef())) return false;
				arrayBodyScope = std::move(merged);
			}
			if (entryCount == 1 && !chainScope.isNull()) {
				zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(arrayBodyScope.raw()), chainScope.raw());
				if (UNEXPECTED(merged.isUndef())) return false;
				arrayBodyScope = std::move(merged);
			}

			allBodyScopes.push(std::move(arrayBodyScope));
			if (chainScope.isNull()) continue;

			allChainScopes.push(std::move(chainScope));
		}

		uint32_t bodyScopeCount = zend_hash_num_elements(allBodyScopes.table());
		if (bodyScopeCount == 0) return true;

		zv::Val bodyScope = zv::Val::copyOf(zv::Ref(listAt(allBodyScopes.raw(), 0)));
		for (uint32_t i = 1; i < bodyScopeCount; i++) {
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), listAt(allBodyScopes.raw(), i));
			if (UNEXPECTED(merged.isUndef())) return false;
			bodyScope = std::move(merged);
		}

		zv::Val chainEndScope = zv::Val::null();
		for (auto entry : zv::ArrRef(allChainScopes.raw())) {
			if (UNEXPECTED(!ptlh::mergeOrTake(chainEndScope, entry.value().deref().raw()))) return false;
		}

		zv::Val endScope = chainEndScope.isNull() ? zv::Val::null() : zv::Val::copyOf(chainEndScope.ref());
		for (auto entry : zv::ArrRef(allBreakScopes.raw())) {
			if (UNEXPECTED(!ptlh::mergeOrTake(endScope, entry.value().deref().raw()))) return false;
		}

		// Unsealed shapes describe zero-or-more additional entries beyond the
		// explicit keys. Run the scope-generalizing loop on top of the
		// unrolled explicit iterations so body-scope variables (e.g. counters)
		// account for the extra iterations while keeping the lower bound
		// established by the non-optional explicit keys.
		if (hasUnsealed && !chainEndScope.isNull() && !endScope.isNull()) {
			zv::Val loopScope = zv::Val::copyOf(endScope.ref());
			zend_long count = 0;
			do {
				zv::Val prevLoopScope = zv::Val::copyOf(loopScope.ref());
				zv::Val iterStorage = pt_expression_result_storage_duplicate(originalStorage);
				if (UNEXPECTED(iterStorage.isUndef())) return false;
				zv::Val iterBodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(loopScope.raw()), endScope.raw());
				if (UNEXPECTED(iterBodyScope.isUndef())) return false;
				{
					zv::Val noop = ptlh::newNoopNodeCallback();
					if (UNEXPECTED(noop.isUndef())) return false;
					iterBodyScope = enterForeach(nodeScopeResolver, iterBodyScope.raw(), iterStorage.raw(), originalScope, stmt, parts, iterateeType, nativeIterateeType, noop.raw());
					if (UNEXPECTED(iterBodyScope.isUndef())) return false;
				}
				zv::Val noop = ptlh::newNoopNodeCallback();
				if (UNEXPECTED(noop.isUndef())) return false;
				zv::Val deepContext = pt_statement_context_enter_deep(context);
				if (UNEXPECTED(deepContext.isUndef())) return false;
				zv::Val passContext = pt_statement_context_without_template_argument_resolution(deepContext.raw());
				if (UNEXPECTED(passContext.isUndef())) return false;
				zv::Val walked = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, parts.stmts.raw(), iterBodyScope.raw(), iterStorage.raw(), noop.raw(), passContext.raw());
				if (UNEXPECTED(walked.isUndef())) return false;
				zv::Val iterBodyScopeResult = pt_internal_statement_result_filter_out_loop_exit_points(walked.raw());
				if (UNEXPECTED(iterBodyScopeResult.isUndef())) return false;
				zv::Val backEdgeScope = pt_internal_statement_result_loop_back_edge_scope(iterBodyScopeResult.raw());
				if (UNEXPECTED(backEdgeScope.isUndef())) return false;
				{
					zv::Val breaks = ptlh::breakExitPoints(iterBodyScopeResult.raw());
					if (UNEXPECTED(breaks.isUndef())) return false;
					for (auto breakEntry : zv::ArrRef(breaks.raw())) {
						zv::Val hold;
						zval *breakScope = pt_internal_statement_exit_point_scope(breakEntry.value().deref().raw(), hold);
						if (UNEXPECTED(breakScope == NULL)) return false;
						zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(endScope.raw()), breakScope);
						if (UNEXPECTED(merged.isUndef())) return false;
						endScope = std::move(merged);
					}
				}
				if (backEdgeScope.isNull()) {
					loopScope = std::move(prevLoopScope);
					break;
				}
				loopScope = std::move(backEdgeScope);
				{
					zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), loopScope.raw());
					if (UNEXPECTED(merged.isUndef())) return false;
					bodyScope = std::move(merged);
				}
				bool equal;
				if (UNEXPECTED(!ptlh::scopesEqual(loopScope.raw(), prevLoopScope.raw(), equal))) return false;
				if (equal) break;
				if (count >= PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT) {
					zv::Val flowHold;
					zval *passFlow = pt_internal_statement_result_variable_flow(iterBodyScopeResult.raw(), flowHold);
					if (UNEXPECTED(passFlow == NULL)) return false;
					loopScope = ptlh::generalizeWithWrittenNames(prevLoopScope.raw(), loopScope.raw(), stmt, passFlow);
					if (UNEXPECTED(loopScope.isUndef())) return false;
				}
				count++;
			} while (count < PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT);

			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(endScope.raw()), loopScope.raw());
			if (UNEXPECTED(merged.isUndef())) return false;
			endScope = std::move(merged);
		}

		out.found = true;
		out.bodyScope = std::move(bodyScope);
		out.endScope = std::move(endScope);
		out.totalKeys = totalKeys;
		return true;
	}

	/* $scope->assignVariable($name, $type, $nativeType, TrinaryLogic::createYes(), []) */
	static zv::Val assignVariableYes(zval *scope, zend_string *name, zval *type, zval *nativeType)
	{
		return pt_mutating_scope_assign_variable(Z_OBJ_P(scope), name, type, nativeType, pt_trinary_singleton(PT_TRI_YES));
	}

	/* Mirrors the private getTraversableForeachThrowPoint(); the throw point
	 * or null, UNDEF = pending exception */
	zv::Val getTraversableForeachThrowPoint(zval *scope, zval *iteratee, zval *exprType) const
	{
		zv::Val traversableType;
		{
			zval out;
			if (UNEXPECTED(!pt_object_type_new(&out, pt_feh_traversable_class))) return zv::Val();
			traversableType = zv::Val::adopt(out);
		}

		zend_long superType = isSuperTypeOf(traversableType.raw(), exprType);
		if (UNEXPECTED(superType < 0)) return zv::Val();
		if (superType == PT_TRI_NO) return zv::Val::null();

		zv::Args intersectArgv{exprType, traversableType.raw()};
		zv::Val traversablePart = pt_type_combinator_intersect(2, intersectArgv);
		if (UNEXPECTED(traversablePart.isUndef())) return zv::Val();
		zv::Val iteratorAggregateType;
		{
			zval out;
			if (UNEXPECTED(!pt_object_type_new(&out, pt_feh_iterator_aggregate_class))) return zv::Val();
			iteratorAggregateType = zv::Val::adopt(out);
		}

		zend_long aggregate = isSuperTypeOf(iteratorAggregateType.raw(), traversablePart.raw());
		if (UNEXPECTED(aggregate < 0)) return zv::Val();
		bool hasGetIterator = false;
		if (aggregate == PT_TRI_YES) {
			zval methodName;
			ZVAL_INTERNED_STR(&methodName, pt_feh_get_iterator);
			zend_long hasMethod = typeTrinaryWithArg(traversablePart.raw(), PT_OP_HAS_METHOD, &methodName);
			if (UNEXPECTED(hasMethod < 0)) return zv::Val();
			hasGetIterator = hasMethod == PT_TRI_YES;
		}
		if (hasGetIterator) {
			zv::Val method = getMethod(traversablePart.raw(), pt_feh_get_iterator, scope);
			if (UNEXPECTED(method.isUndef())) return zv::Val();
			zv::Val throwType = getThrowType(method.raw());
			if (UNEXPECTED(throwType.isUndef())) return zv::Val();
			if (!throwType.isNull()) {
				zend_long isVoid = typeTrinary(throwType.raw(), PT_OP_IS_VOID, "isVoid");
				if (UNEXPECTED(isVoid < 0)) return zv::Val();
				if (isVoid == PT_TRI_YES) return zv::Val::null();
				return pt_internal_throw_point_create_explicit(scope, throwType.raw(), iteratee, true, false);
			}

			if (!ptlh::boolSlot(self, slots::implicitThrows)) return zv::Val::null();
		}

		return pt_internal_throw_point_create_implicit(scope, iteratee);
	}

	/* a TrinaryLogic-returning op with one argument; -1 = pending exception */
	static zend_long typeTrinaryWithArg(zval *type, pt_type_op_id op, zval *arg)
	{
		zv::Val result = pt_type_op(Z_OBJ_P(type), op, 1, arg);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_trinary_value(result.raw());
	}

	/* Mirrors the private addDestructureTaggedUnionConditionalHolders(). */
	static zv::Val addDestructureTaggedUnionConditionalHolders(zval *scopeArg, zval *iterableValueType, zval *list)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zv::Val constantArrays = typeOp(iterableValueType, PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		uint32_t variantCount = ptlh::countOf(constantArrays.raw());
		if (variantCount < 2) return scope;

		// Collect each list item's array-key value and target variable.
		struct Item
		{
			zv::Val key;
			zend_string *name;
		};
		std::vector<Item> items;
		zval *itemsSlot = ptsh::readNodeProperty(pt_feh_list_items_site, list, PT_LC("items"));
		if (UNEXPECTED(itemsSlot == NULL)) return zv::Val();
		zv::Val listItems = zv::Val::copyOf(zv::Ref(itemsSlot));
		if (UNEXPECTED(Z_TYPE_P(listItems.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(listItems.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return scope;
		}
		for (auto entry : zv::ArrRef(listItems.raw())) {
			zval *item = entry.value().deref().raw();
			if (Z_TYPE_P(item) == IS_NULL) continue;
			if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) {
				zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(item));
				return zv::Val();
			}
			zval *value = ptsh::readNodeProperty(pt_feh_item_value_site, item, PT_LC("value"));
			if (UNEXPECTED(value == NULL)) return zv::Val();
			bool error = false;
			zend_string *name = StmtParts::variableName(value, NULL, error);
			if (UNEXPECTED(error)) return zv::Val();
			if (name == NULL) return scope;
			zval *key = ptsh::readNodeProperty(pt_feh_item_key_site, item, PT_LC("key"));
			if (UNEXPECTED(key == NULL)) return zv::Val();
			zv::Val keyValue;
			if (Z_TYPE_P(key) == IS_NULL) {
				keyValue = entry.hasStringKey() ? zv::Val::string(entry.stringKey()) : zv::Val::integer((zend_long) entry.indexKey());
			} else if (ptsh::isInstanceOf(key, PT_CLASS_SCALAR_STRING, error) || ptsh::isInstanceOf(key, PT_CLASS_SCALAR_INT, error)) {
				zval *scalarValue = ptsh::readNodeProperty(pt_feh_scalar_value_site, key, PT_LC("value"));
				if (UNEXPECTED(scalarValue == NULL)) return zv::Val();
				keyValue = zv::Val::copyOf(zv::Ref(scalarValue));
			} else {
				if (UNEXPECTED(error)) return zv::Val();
				return scope;
			}
			if (UNEXPECTED(error)) return zv::Val();
			/* the name is borrowed from the list node, which the handler holds */
			items.push_back(Item{std::move(keyValue), name});
		}

		uint32_t itemCount = (uint32_t) items.size();
		if (itemCount < 2) return scope;

		// For every variant, every item must have a matching key with a single
		// value type at it; otherwise the variants don't all describe the same
		// destructure shape and we can't form a sound holder set.
		std::vector<zv::Val> variantValuesByItem;
		variantValuesByItem.reserve((size_t) itemCount * variantCount);
		for (uint32_t itemIdx = 0; itemIdx < itemCount; itemIdx++) {
			for (auto variantEntry : zv::ArrRef(constantArrays.raw())) {
				zval *variant = variantEntry.value().deref().raw();
				zval keyTypeZv;
				zval *keyInfo = items[itemIdx].key.raw();
				bool created = Z_TYPE_P(keyInfo) == IS_LONG
					? pt_constant_integer_type_new(&keyTypeZv, Z_LVAL_P(keyInfo))
					: pt_constant_string_type_new(&keyTypeZv, Z_STR_P(keyInfo));
				if (UNEXPECTED(!created)) return zv::Val();
				zv::Val keyType = zv::Val::adopt(keyTypeZv);
				zend_long hasOffset = typeTrinaryWithArg(variant, PT_OP_HAS_OFFSET_VALUE_TYPE, keyType.raw());
				if (UNEXPECTED(hasOffset < 0)) return zv::Val();
				if (hasOffset != PT_TRI_YES) return scope;
				zv::Val offsetType = pt_type_op(Z_OBJ_P(variant), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType.raw());
				if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
				variantValuesByItem.push_back(std::move(offsetType));
			}
		}

		// For each item × variant, build a holder: "when item is variant's value
		// at this position, the *other* items are the variant's values at their
		// positions". Skip the variant if the condition value is too wide to be
		// a useful discriminator (i.e. equal to the union of all the variant
		// values at this position — narrowing it back wouldn't pick a variant).
		for (uint32_t itemIdx = 0; itemIdx < itemCount; itemIdx++) {
			zv::Str exprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(items[itemIdx].name), ZSTR_LEN(items[itemIdx].name)));
			zv::Arr variantConditionTypes = zv::Arr::create(variantCount);
			for (uint32_t variantIdx = 0; variantIdx < variantCount; variantIdx++) {
				variantConditionTypes.push(zv::Ref(variantValuesByItem[(size_t) itemIdx * variantCount + variantIdx].raw()));
			}
			zv::Val itemUnionType = unionOfList(variantConditionTypes);
			if (UNEXPECTED(itemUnionType.isUndef())) return zv::Val();
			zv::Arr holders = zv::Arr::empty();
			for (uint32_t variantIdx = 0; variantIdx < variantCount; variantIdx++) {
				zval *conditionType = variantValuesByItem[(size_t) itemIdx * variantCount + variantIdx].raw();
				zv::Val equals = typeOp(conditionType, PT_OP_EQUALS, "equals", 1, itemUnionType.raw());
				if (UNEXPECTED(equals.isUndef())) return zv::Val();
				if (zend_is_true(equals.raw())) continue;
				zv::Val conditionVariable = newVariable(items[itemIdx].name);
				if (UNEXPECTED(conditionVariable.isUndef())) return zv::Val();
				zv::Arr conditions = zv::Arr::create(1);
				conditions.set(exprString.get(), expressionTypeHolderYes(conditionVariable.raw(), conditionType));
				for (uint32_t otherIdx = 0; otherIdx < itemCount; otherIdx++) {
					if (otherIdx == itemIdx) continue;
					zval *otherType = variantValuesByItem[(size_t) otherIdx * variantCount + variantIdx].raw();
					zv::Val otherVariable = newVariable(items[otherIdx].name);
					if (UNEXPECTED(otherVariable.isUndef())) return zv::Val();
					zv::Val typeHolder = expressionTypeHolderYes(otherVariable.raw(), otherType);
					zv::Val holder = newConditionalExpressionHolder(conditions.raw(), typeHolder.raw());
					if (UNEXPECTED(holder.isUndef())) return zv::Val();
					zv::Str holderKey = zv::Str::adopt(conditionalHolderKey(holder.raw()));
					if (UNEXPECTED(holderKey.isNull())) return zv::Val();
					zv::Str targetExprString = zv::Str::adopt(zend_string_concat2(PT_LC("$"), ZSTR_VAL(items[otherIdx].name), ZSTR_LEN(items[otherIdx].name)));
					/* $holders['$' . $name][$holder->getKey()] = $holder */
					holders.separate();
					zval *group = zend_symtable_find(holders.table(), targetExprString.get());
					if (group == NULL) {
						zval emptyGroup;
						array_init(&emptyGroup);
						group = zend_symtable_update(holders.table(), targetExprString.get(), &emptyGroup);
					}
					SEPARATE_ARRAY(group);
					zval holderZv = holder.take();
					zend_symtable_update(Z_ARRVAL_P(group), holderKey.get(), &holderZv);
				}
			}

			for (auto entry : zv::ArrRef(holders.raw())) {
				zend_string *targetExprString = entry.hasStringKey() ? zend_string_copy(entry.stringKey()) : zend_long_to_str((zend_long) entry.indexKey());
				zv::Str target = zv::Str::adopt(targetExprString);
				scope = pt_mutating_scope_add_conditional_expressions(Z_OBJ_P(scope.raw()), target.get(), Z_ARRVAL_P(entry.value().deref().raw()));
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		return scope;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ForeachHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_foreach_handler()
{
	pt_feh_identical_narrowing_helper_class = zend_string_init_interned(PT_LC("PHPStan\\Analyser\\ExprHandler\\Helper\\IdenticalNarrowingHelper"), 1);
	pt_feh_traversable_class = zend_string_init_interned(PT_LC("Traversable"), 1);
	pt_feh_iterator_aggregate_class = zend_string_init_interned(PT_LC("IteratorAggregate"), 1);
	pt_feh_get_iterator = zend_string_init_interned(PT_LC("getIterator"), 1);
	pt_feh_is_object = zend_string_init_interned(PT_LC("is_object"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ForeachHandler");
	ptdecl::ForeachHandler::declareClass(cls);
	ptdecl::ForeachHandler::declareProperties(cls);

	/* the real parameter class names and #[AutowiredParameter] names: the DI
	 * container autowires the service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *container, *varAnnotationProcessor, *assignHandler;
		bool implicitThrows, polluteScopeWithAlwaysIterableForeach;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Obj, zp::Obj, zp::Bool>(execute_data, container, implicitThrows, varAnnotationProcessor, assignHandler, polluteScopeWithAlwaysIterableForeach)) RETURN_THROWS();
		ForeachHandler(Z_OBJ_P(ZEND_THIS)).construct(container, implicitThrows, varAnnotationProcessor, assignHandler, polluteScopeWithAlwaysIterableForeach);
	});

	cls.method<&ForeachHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ForeachHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_foreach_handler);
	pt_stmt_handler_entry_register(&pt_ce_foreach_handler, &ForeachHandler::processStmtEntry);
}

/* }}} */
