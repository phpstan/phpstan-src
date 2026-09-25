/*
 * PHPStanTurbo\MatchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\MatchHandler.
 *
 * A DI service (#[AutowiredService]) implementing PerFileAnalysisResettable:
 * the constructor keeps the twin's arginfo (the #[AutowiredParameter]
 * $treatPhpDocTypesAsCertain first) so Nette autowires it. processExpr() is
 * registered as the class's handler entry (Engine.h); getCapturedArmScopesAndTypes()
 * — which AssignHandler calls across handlers — is exported as
 * pt_match_handler_get_captured_arm_scopes_and_types(). The captured arm
 * results live in the twin's private $capturedArmResults array slot, keyed
 * by the match node's object id and pinning the node, exactly as the twin
 * keeps them.
 *
 * The twin's closures are native closures capturing what the PHP closures
 * capture: the typeCallback ($armTypeResults), the specifyTypesCallback
 * ($this, $expr) and the identical-verdict callback handed to
 * IdenticalNarrowingHelper::specifyIdentical() ($this, $armCondResultScope,
 * $armCondExpr, $nodeScopeResolver, $expr, $armCondResult); the
 * $specifyArmCond arrow function is only ever called right where it is
 * created, so its body runs inline.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * ExpressionResultStorage, InternalThrowPoint, VariableFlow, SpecifiedTypes,
 * TypeSpecifierContext, DefaultNarrowingHelper, IdenticalNarrowingHelper,
 * TypeCombinator and the Type kernel are called through their direct
 * entries; RicherScopeGetTypeHelper::getIdenticalResult(),
 * AlwaysRememberedExpr::getExpr() and NodeAbstract::getStartLine() stay PHP
 * (one cached method site each); the match virtual nodes and the fabricated
 * Identical / in_array() nodes are instantiated through the class map.
 */

#include "support.h"
#include "generated/MatchHandler.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::MatchHandler::slot;
namespace sigs = ptdecl::MatchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_match_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_mh_closure_name = "PHPStan\\Analyser\\ExprHandler\\MatchHandler::{closure}";

#define MH_VAL(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return zv::Val()

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_mh_get_expr_site;
pt_method_site pt_mh_get_start_line_site;

/* $richerScopeGetTypeHelper->getIdenticalResult($scope, $expr,
 * $nodeScopeResolver, $leftType, $rightType)->type */
zv::Val identicalResultType(zval *richerScopeGetTypeHelper, zval *scope, zval *expr, zval *nodeScopeResolver, zval *leftType, zval *rightType)
{
	zv::Val result = pt_richer_scope_get_type_helper_get_identical_result(richerScopeGetTypeHelper, scope, expr, nodeScopeResolver, leftType, rightType);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	if (EXPECTED(Z_TYPE_P(result.raw()) == IS_OBJECT && Z_OBJCE_P(result.raw()) == pt_ce_type_result)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(result.raw()), ptdecl::TypeResult::slot::type)));
	if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result.raw()));
		return zv::Val();
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *type = zend_read_property(Z_OBJCE_P(result.raw()), Z_OBJ_P(result.raw()), PT_LC("type"), 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return zv::Val();
	}
	if (type == &rv) return zv::Val::adopt(rv);
	return zv::Val::copyOf(zv::Ref(type));
}

/* $alwaysRememberedExpr->getExpr() */
zv::Val alwaysRememberedExprGetExpr(zval *expr)
{
	return pt_call_method_cached(pt_mh_get_expr_site, Z_OBJ_P(expr), PT_LC("getexpr"), 0, NULL);
}

/* $node->getStartLine() */
zv::Val nodeGetStartLine(zval *node)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getStartLine() on %s", zend_zval_value_name(node));
		return zv::Val();
	}
	return pt_call_method_cached(pt_mh_get_start_line_site, Z_OBJ_P(node), PT_LC("getstartline"), 0, NULL);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_mh_cond_site;
pt_property_site pt_mh_arms_site;
pt_property_site pt_mh_arm_conds_site;
pt_property_site pt_mh_arm_body_site;
pt_property_site pt_mh_fetch_class_site;
pt_property_site pt_mh_fetch_name_site;
pt_property_site pt_mh_identifier_name_site;

zval *exprCond(zval *expr) { return nodeProperty(pt_mh_cond_site, expr, PT_LC("cond")); }
zval *exprArms(zval *expr) { return nodeProperty(pt_mh_arms_site, expr, PT_LC("arms")); }
zval *armConds(zval *arm) { return nodeProperty(pt_mh_arm_conds_site, arm, PT_LC("conds")); }
zval *armBody(zval *arm) { return nodeProperty(pt_mh_arm_body_site, arm, PT_LC("body")); }
zval *fetchClass(zval *fetch) { return nodeProperty(pt_mh_fetch_class_site, fetch, PT_LC("class")); }
zval *fetchName(zval *fetch) { return nodeProperty(pt_mh_fetch_name_site, fetch, PT_LC("name")); }
/* $identifier->toString() */
zval *identifierName(zval *identifier) { return nodeProperty(pt_mh_identifier_name_site, identifier, PT_LC("name")); }

/* }}} */

/* {{{ small value helpers */

/* the permanent interned literals (module startup) */
zend_string *pt_mh_unhandled_match_error = nullptr;
zend_string *pt_mh_in_array = nullptr;
zend_string *pt_mh_true = nullptr;

/* the TypeSpecifierContext singleton as a zval (borrowed); false = pending
 * exception */
[[nodiscard]] bool contextZval(zend_object *context, zval &out)
{
	if (UNEXPECTED(context == NULL)) return false;
	ZVAL_OBJ(&out, context);
	return true;
}

/* the result object's method call on a non-object */
zv::Val callOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return zv::Val();
}

/* $scope->applySpecifiedTypes($specifiedTypes) */
zv::Val applySpecifiedTypes(zval *scope, zv::Val specifiedTypes)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return callOnNonObject("applySpecifiedTypes", scope);
	return pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), specifiedTypes.raw());
}

/* $result->getSpecifiedTypesForScope($scope, TypeSpecifierContext::create<ctx>()) */
zv::Val specifiedTypesForScope(zv::Val result, zval *scope, zend_object *context)
{
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	zval contextZv;
	if (UNEXPECTED(!contextZval(context, contextZv))) return zv::Val();
	if (UNEXPECTED(!result.ref().isObject())) return callOnNonObject("getSpecifiedTypesForScope", result.raw());
	return pt_expression_result_get_specified_types_for_scope(result.raw(), scope, &contextZv);
}

/* $scope->addTemplateArgumentConstraints($other->getTemplateArgumentConstraints()) */
zv::Val addTemplateArgumentConstraintsOf(zval *scope, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(other) != IS_OBJECT)) return callOnNonObject("getTemplateArgumentConstraints", other);
	MH_VAL(constraints, pt_mutating_scope_get_template_argument_constraints(Z_OBJ_P(other)));
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return callOnNonObject("addTemplateArgumentConstraints", scope);
	return pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(scope), constraints.raw());
}

/* $table[$i][$j] of a two-level integer-keyed array, NULL when absent */
zval *lookup2(HashTable *table, zend_ulong i, zend_ulong j)
{
	zval *row = zend_hash_index_find(table, i);
	if (row == NULL || Z_TYPE_P(row) != IS_ARRAY) return NULL;
	return zend_hash_index_find(Z_ARRVAL_P(row), j);
}

/* $table[$i][$j] = $value (the value consumed) */
void store2(HashTable *table, zend_ulong i, zend_ulong j, zv::Val value)
{
	zval *row = zend_hash_index_find(table, i);
	if (row == NULL) {
		zval empty;
		array_init(&empty);
		row = zend_hash_index_update(table, i, &empty);
	}
	zval item = value.take();
	zend_hash_index_update(Z_ARRVAL_P(row), j, &item);
}

/* $key of a list entry; false (with ShouldNotHappenException) for a string
 * key the twin's integer-keyed bookkeeping never sees */
[[nodiscard]] bool indexKeyOf(zv::ArrayEntry &entry, zend_ulong &out)
{
	if (UNEXPECTED(entry.hasStringKey())) {
		pt_throw_should_not_happen();
		return false;
	}
	out = entry.indexKey();
	return true;
}

/* ksort($armNodes, SORT_NUMERIC) over the integer arm indices */
int armKeyCompare(Bucket *a, Bucket *b)
{
	return a->h < b->h ? -1 : (a->h > b->h ? 1 : 0);
}

/* new ObjectType(UnhandledMatchError::class) */
zv::Val newUnhandledMatchErrorType()
{
	zval out;
	if (UNEXPECTED(!pt_object_type_new(&out, pt_mh_unhandled_match_error))) return zv::Val();
	return zv::Val::adopt(out);
}

/* new UnionType($types) of a list, or its only member / NeverType as the
 * twin's count() switch picks */
zv::Val caseTypeOf(zv::Arr &cases, bool neverWhenEmpty)
{
	uint32_t count = zend_hash_num_elements(cases.table());
	if (count == 0) {
		if (!neverWhenEmpty) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval never;
		if (UNEXPECTED(!pt_never_type_new(&never))) return zv::Val();
		return zv::Val::adopt(never);
	}
	if (count == 1) {
		zval *only = zend_hash_index_find(cases.table(), 0);
		if (UNEXPECTED(only == NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(only));
	}
	zval casesValue;
	ZVAL_ARR(&casesValue, cases.table());
	zval union_;
	if (UNEXPECTED(!pt_union_type_new(&union_, &casesValue))) return zv::Val();
	return zv::Val::adopt(union_);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\MatchHandler; UNDEF = pending
 * exception. */
class MatchHandler
{
public:
	explicit MatchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(bool treatPhpDocTypesAsCertain, zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *identicalNarrowingHelper, zval *richerScopeGetTypeHelper) const
	{
		zval flag = {};
		ZVAL_BOOL(&flag, treatPhpDocTypesAsCertain);
		pt_write_slot(self, slots::treatPhpDocTypesAsCertain, &flag);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::identicalNarrowingHelper, identicalNarrowingHelper);
		pt_write_slot(self, slots::richerScopeGetTypeHelper, richerScopeGetTypeHelper);
	}

	/* Mirrors resetFileAnalysisState(). */
	void resetFileAnalysisState() const
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		pt_write_slot(self, slots::capturedArmResults, &empty);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_MATCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors getCapturedArmScopesAndTypes(). */
	zv::Val getCapturedArmScopesAndTypes(zval *expr) const
	{
		zval *captured = pt_typed_slot(self, slots::capturedArmResults, self->ce, "capturedArmResults");
		if (UNEXPECTED(captured == NULL)) return zv::Val();
		zval *entry = Z_TYPE_P(captured) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(captured), Z_OBJ_HANDLE_P(expr)) : NULL;
		if (entry == NULL || Z_TYPE_P(entry) == IS_NULL) return zv::Val::null();
		zval *pinned = Z_TYPE_P(entry) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(entry), 0) : NULL;
		if (pinned == NULL || Z_TYPE_P(pinned) != IS_OBJECT || Z_OBJ_P(pinned) != Z_OBJ_P(expr)) return zv::Val::null();

		zval *armTypeResults = zend_hash_index_find(Z_ARRVAL_P(entry), 1);
		if (UNEXPECTED(armTypeResults == NULL || Z_TYPE_P(armTypeResults) != IS_ARRAY)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val held = zv::Val::copyOf(zv::Ref(armTypeResults));
		zv::Arr pairs = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(held.raw())));
		for (zv::ArrayEntry item : zv::TableRef(Z_ARRVAL_P(held.raw()))) {
			zval *triple = item.value().deref().raw();
			zval *armResult = zend_hash_index_find(Z_ARRVAL_P(triple), 0);
			zval *bodyScope = zend_hash_index_find(Z_ARRVAL_P(triple), 1);
			MH_VAL(type, pt_expression_result_get_type(armResult));
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Ref(bodyScope));
			pair.push(std::move(type));
			pairs.push(zv::Val(std::move(pair)));
		}

		return zv::Val(std::move(pairs));
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *richerScopeGetTypeHelper = OBJ_PROP_NUM(self, slots::richerScopeGetTypeHelper);
		zval *identicalNarrowingHelper = OBJ_PROP_NUM(self, slots::identicalNarrowingHelper);
		MH_VAL(deepContext, pt_expression_context_enter_deep(context));
		zval *cond = exprCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		MH_VAL(condResult, pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, cond, scopeArg, storage, nodeCallback, deepContext.raw()));
		// the subject was just processed on this scope; read its result
		MH_VAL(condType, pt_expression_result_get_type(condResult.raw()));
		MH_VAL(condNativeType, pt_expression_result_get_native_type(condResult.raw()));
		zv::Val hold;
		zval *borrowed = pt_expression_result_scope(condResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(borrowed));
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(condResult.raw(), hasYield))) return zv::Val();
		borrowed = pt_expression_result_throw_points(condResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(condResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(condResult.raw(), isAlwaysTerminating))) return zv::Val();
		if (UNEXPECTED(!scope.ref().isObject())) return callOnNonObject("enterMatch", scope.raw());
		MH_VAL(matchScope, pt_mutating_scope_enter_match(Z_OBJ_P(scope.raw()), Z_OBJ_P(expr), condType.raw(), condNativeType.raw()));

		zval *armsSlot = exprArms(expr);
		if (UNEXPECTED(armsSlot == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(armsSlot) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(armsSlot));
			return zv::Val();
		}
		// $arms = $expr->arms (the enum fast path unsets the arms it handled)
		zv::Val arms = zv::Val::copyOf(zv::Ref(armsSlot));
		uint32_t armCount = zend_hash_num_elements(Z_ARRVAL_P(arms.raw()));
		zv::Arr armNodes = zv::Arr::create(armCount);
		zv::Arr armFlows = zv::Arr::create(armCount);
		zv::Arr conditionFlows = zv::Arr::create(armCount);
		bool hasDefaultCond = false;
		bool hasAlwaysTrueCond = false;
		zv::Arr armCondsToSkip = zv::Arr::create(0);
		zv::Arr armBodyScopes = zv::Arr::create(armCount);
		// for each reachable arm: the body's result, the scope it was processed
		// on and the body node
		zv::Arr armTypeResults = zv::Arr::create(armCount);

		if (UNEXPECTED(!condType.ref().isObject())) return callOnNonObject("isEnum", condType.raw());
		zend_long isEnum = pt_type_call_trinary(Z_OBJ_P(condType.raw()), PT_LC("isenum"), 0, NULL);
		if (UNEXPECTED(isEnum < 0)) return zv::Val();
		if (isEnum == PT_TRI_YES) {
			// enum match analysis would work even without this branch but would
			// be much slower
			MH_VAL(enumCases, pt_type_call(Z_OBJ_P(condType.raw()), PT_LC("getenumcases"), 0, NULL));
			if (UNEXPECTED(!enumCases.ref().isArray())) {
				zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(enumCases.raw()));
				return zv::Val();
			}
			if (zend_hash_num_elements(Z_ARRVAL_P(enumCases.raw())) > 0) {
				if (UNEXPECTED(!processEnumArms(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context, deepContext.raw(), enumCases.raw(), matchScope, arms, armNodes, armFlows, conditionFlows, hasAlwaysTrueCond, armCondsToSkip, armBodyScopes, hasYield, throwPoints, impurePoints, armTypeResults))) return zv::Val();
			}
		}

		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(arms.raw()))) {
			zend_ulong i;
			if (UNEXPECTED(!indexKeyOf(entry, i))) return zv::Val();
			zval *arm = entry.value().deref().raw();
			zval *conds = armConds(arm);
			if (UNEXPECTED(conds == NULL)) return zv::Val();
			if (Z_TYPE_P(conds) == IS_NULL) {
				hasDefaultCond = true;
				zv::Val defaultArmBodyScope = zv::Val::copyOf(zv::Ref(matchScope.raw()));
				zval *body = armBody(arm);
				if (UNEXPECTED(body == NULL)) return zv::Val();
				{
					zv::Args bodyArgv{matchScope.raw(), body};
					MH_VAL(matchArmBody, pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM_BODY, 2, bodyArgv));
					MH_VAL(startLine, nodeGetStartLine(arm));
					zval noConds;
					ZVAL_EMPTY_ARRAY(&noConds);
					zv::Args armArgv{matchArmBody.raw(), &noConds, startLine.raw()};
					MH_VAL(armNode, pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM, 3, armArgv));
					zval armNodeZv = armNode.take();
					zend_hash_index_update(armNodes.table(), i, &armNodeZv);
				}
				MH_VAL(armContext, pt_expression_context_enter_match_arm(context));
				body = armBody(arm);
				if (UNEXPECTED(body == NULL)) return zv::Val();
				MH_VAL(armResult, pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, body, matchScope.raw(), storage, nodeCallback, armContext.raw()));
				{
					MH_VAL(flow, pt_expression_result_variable_flow(armResult.raw()));
					zval flowZv = flow.take();
					zend_hash_index_update(armFlows.table(), i, &flowZv);
				}
				borrowed = pt_expression_result_scope(armResult.raw(), hold);
				if (UNEXPECTED(borrowed == NULL)) return zv::Val();
				matchScope = zv::Val::copyOf(zv::Ref(borrowed));
				scope = addTemplateArgumentConstraintsOf(scope.raw(), matchScope.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
				if (UNEXPECTED(!foldArmResult(armResult.raw(), hasYield, throwPoints, impurePoints, hold))) return zv::Val();
				bool armIsAlwaysTerminating;
				if (UNEXPECTED(!pt_expression_result_is_always_terminating(armResult.raw(), armIsAlwaysTerminating))) return zv::Val();
				if (!armIsAlwaysTerminating) armBodyScopes.push(zv::Ref(matchScope.raw()));
				body = armBody(arm);
				if (UNEXPECTED(body == NULL)) return zv::Val();
				armTypeResults.push(triple(armResult.raw(), defaultArmBodyScope.raw(), body));
				continue;
			}

			if (UNEXPECTED(Z_TYPE_P(conds) != IS_ARRAY)) {
				zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(conds));
				return zv::Val();
			}
			if (UNEXPECTED(zend_hash_num_elements(Z_ARRVAL_P(conds)) == 0)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}

			zv::Arr filteringExprs = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(conds)));
			zv::Arr filteringCondData = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(conds)));
			zv::Val armCondScope = zv::Val::copyOf(zv::Ref(matchScope.raw()));
			zv::Arr condNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(conds)));
			zv::Val bodyScope = zv::Val::null();
			cond = exprCond(expr);
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			MH_VAL(condArgResult, pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper, cond, storage));
			zv::Val heldConds = zv::Val::copyOf(zv::Ref(conds));
			for (zv::ArrayEntry condEntry : zv::TableRef(Z_ARRVAL_P(heldConds.raw()))) {
				zend_ulong j;
				if (UNEXPECTED(!indexKeyOf(condEntry, j))) return zv::Val();
				zval *skip = lookup2(armCondsToSkip.table(), i, j);
				if (skip != NULL && Z_TYPE_P(skip) != IS_NULL) continue;
				zval *armCond = condEntry.value().deref().raw();
				{
					MH_VAL(startLine, nodeGetStartLine(armCond));
					zv::Args condNodeArgv{armCond, armCondScope.raw(), startLine.raw()};
					MH_VAL(condNode, pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM_CONDITION, 3, condNodeArgv));
					condNodes.push(std::move(condNode));
				}
				MH_VAL(armCondResult, pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, armCond, armCondScope.raw(), storage, nodeCallback, deepContext.raw()));
				{
					MH_VAL(flow, pt_expression_result_variable_flow(armCondResult.raw()));
					store2(conditionFlows.table(), i, j, std::move(flow));
				}
				if (UNEXPECTED(!foldArmResult(armCondResult.raw(), hasYield, throwPoints, impurePoints, hold))) return zv::Val();
				cond = exprCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				zv::Val armCondExpr;
				{
					zv::Args identicalArgv{cond, armCond};
					armCondExpr = pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
					if (UNEXPECTED(armCondExpr.isUndef())) return zv::Val();
				}
				borrowed = pt_expression_result_scope(armCondResult.raw(), hold);
				if (UNEXPECTED(borrowed == NULL)) return zv::Val();
				zv::Val armCondResultScope = zv::Val::copyOf(zv::Ref(borrowed));
				if (UNEXPECTED(!armCondResultScope.ref().isObject())) return callOnNonObject("getStateType", armCondResultScope.raw());
				// the `subject === cond` verdict, composed from the subject's
				// threaded per-arm state and the condition's walk result
				cond = exprCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				MH_VAL(armSubjectType, pt_mutating_scope_get_state_type(Z_OBJ_P(armCondResultScope.raw()), Z_OBJ_P(cond)));
				zv::Val armCondType;
				if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::treatPhpDocTypesAsCertain)) == IS_TRUE) {
					MH_VAL(armCondResultType, pt_expression_result_get_type(armCondResult.raw()));
					armCondType = identicalResultType(richerScopeGetTypeHelper, armCondResultScope.raw(), armCondExpr.raw(), nodeScopeResolver, armSubjectType.raw(), armCondResultType.raw());
				} else {
					MH_VAL(nativeScope, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(armCondResultScope.raw())));
					MH_VAL(nativeScope2, pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(armCondResultScope.raw())));
					if (UNEXPECTED(!nativeScope2.ref().isObject())) return callOnNonObject("getStateType", nativeScope2.raw());
					cond = exprCond(expr);
					if (UNEXPECTED(cond == NULL)) return zv::Val();
					MH_VAL(nativeSubjectType, pt_mutating_scope_get_state_type(Z_OBJ_P(nativeScope2.raw()), Z_OBJ_P(cond)));
					MH_VAL(armCondResultNativeType, pt_expression_result_get_native_type(armCondResult.raw()));
					armCondType = identicalResultType(richerScopeGetTypeHelper, nativeScope.raw(), armCondExpr.raw(), nodeScopeResolver, nativeSubjectType.raw(), armCondResultNativeType.raw());
				}
				if (UNEXPECTED(armCondType.isUndef())) return zv::Val();
				if (UNEXPECTED(!armCondType.ref().isObject())) return callOnNonObject("isTrue", armCondType.raw());
				zend_long armCondIsTrue = pt_type_call_trinary(Z_OBJ_P(armCondType.raw()), PT_LC("istrue"), 0, NULL);
				if (UNEXPECTED(armCondIsTrue < 0)) return zv::Val();
				if (armCondIsTrue == PT_TRI_YES) hasAlwaysTrueCond = true;
				MH_VAL(armCondArgResult, pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper, armCond, storage));

				// $specifyArmCond(TypeSpecifierContext::createFalsey()) /
				// (TypeSpecifierContext::createTruthy())
				zval identicalCaptures[6];
				ZVAL_OBJ(&identicalCaptures[0], self);
				ZVAL_COPY_VALUE(&identicalCaptures[1], armCondResultScope.raw());
				ZVAL_COPY_VALUE(&identicalCaptures[2], armCondExpr.raw());
				ZVAL_COPY_VALUE(&identicalCaptures[3], nodeScopeResolver);
				ZVAL_COPY_VALUE(&identicalCaptures[4], expr);
				ZVAL_COPY_VALUE(&identicalCaptures[5], armCondResult.raw());
				MH_VAL(falseyTypes, specifyArmCond(nodeScopeResolver, expr, armCond, condResult.raw(), armCondResult.raw(), pt_type_specifier_context_create_falsey(), armCondResultScope.raw(), condArgResult.raw(), armCondArgResult.raw(), armCondExpr.raw(), identicalCaptures));
				armCondScope = applySpecifiedTypes(armCondResultScope.raw(), std::move(falseyTypes));
				if (UNEXPECTED(armCondScope.isUndef())) return zv::Val();
				MH_VAL(truthyTypes, specifyArmCond(nodeScopeResolver, expr, armCond, condResult.raw(), armCondResult.raw(), pt_type_specifier_context_create_truthy(), armCondResultScope.raw(), condArgResult.raw(), armCondArgResult.raw(), armCondExpr.raw(), identicalCaptures));
				MH_VAL(armCondTruthyScope, applySpecifiedTypes(armCondResultScope.raw(), std::move(truthyTypes)));
				if (bodyScope.isNull()) {
					bodyScope = std::move(armCondTruthyScope);
				} else {
					if (UNEXPECTED(!bodyScope.ref().isObject())) return callOnNonObject("mergeWith", bodyScope.raw());
					bodyScope = pt_mutating_scope_merge_with(Z_OBJ_P(bodyScope.raw()), armCondTruthyScope.raw());
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				}
				filteringExprs.push(zv::Ref(armCond));
				zv::Arr condData = zv::Arr::create(2);
				condData.push(zv::Ref(armCond));
				condData.push(zv::Ref(armCondResult.raw()));
				filteringCondData.push(zv::Val(std::move(condData)));
			}

			zv::Val filteringExprType;
			zv::Val filteringFalseyTypes;
			if (zend_hash_num_elements(filteringCondData.table()) == 1) {
				// single-condition arm: compose the verdict from the walk results
				if (UNEXPECTED(bodyScope.isNull())) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				zval *condData = zend_hash_index_find(filteringCondData.table(), 0);
				zval *filteringCond = zend_hash_index_find(Z_ARRVAL_P(condData), 0);
				zval *filteringCondResult = zend_hash_index_find(Z_ARRVAL_P(condData), 1);
				cond = exprCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				zv::Args identicalArgv{cond, filteringCond};
				MH_VAL(filteringIdentical, pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv));
				if (UNEXPECTED(!matchScope.ref().isObject())) return callOnNonObject("getStateType", matchScope.raw());
				cond = exprCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				MH_VAL(subjectStateType, pt_mutating_scope_get_state_type(Z_OBJ_P(matchScope.raw()), Z_OBJ_P(cond)));
				MH_VAL(filteringCondType, pt_expression_result_get_type(filteringCondResult));
				filteringExprType = identicalResultType(richerScopeGetTypeHelper, matchScope.raw(), filteringIdentical.raw(), nodeScopeResolver, subjectStateType.raw(), filteringCondType.raw());
				if (UNEXPECTED(filteringExprType.isUndef())) return zv::Val();
				// the falsey narrowing stays a synthetic walk (bug-6064)
				filteringFalseyTypes = specifiedTypesForScope(pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, filteringIdentical.raw(), armCondScope.raw()), armCondScope.raw(), pt_type_specifier_context_create_falsey());
				if (UNEXPECTED(filteringFalseyTypes.isUndef())) return zv::Val();
			} else {
				// multi-condition arms compose through in_array
				MH_VAL(filteringExpr, getFilteringExprForMatchArm(expr, filteringExprs.table(), filteringCondData.table()));
				MH_VAL(filteringExprResult, pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, filteringExpr.raw(), matchScope.raw()));
				if (bodyScope.isNull()) {
					bodyScope = applySpecifiedTypes(matchScope.raw(), specifiedTypesForScope(zv::Val::copyOf(zv::Ref(filteringExprResult.raw())), matchScope.raw(), pt_type_specifier_context_create_truthy()));
					if (UNEXPECTED(bodyScope.isUndef())) return zv::Val();
				}
				if (UNEXPECTED(!filteringExprResult.ref().isObject())) return callOnNonObject("getTypeOnScope", filteringExprResult.raw());
				filteringExprType = pt_expression_result_get_type_on_scope(filteringExprResult.raw(), matchScope.raw(), false);
				if (UNEXPECTED(filteringExprType.isUndef())) return zv::Val();
				filteringFalseyTypes = specifiedTypesForScope(pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, filteringExpr.raw(), armCondScope.raw()), armCondScope.raw(), pt_type_specifier_context_create_falsey());
				if (UNEXPECTED(filteringFalseyTypes.isUndef())) return zv::Val();
			}
			zval *body = armBody(arm);
			if (UNEXPECTED(body == NULL)) return zv::Val();
			{
				zv::Args bodyArgv{bodyScope.raw(), body};
				MH_VAL(matchArmBody, pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM_BODY, 2, bodyArgv));
				MH_VAL(startLine, nodeGetStartLine(arm));
				zv::Val condNodesValue(std::move(condNodes));
				zv::Args armArgv{matchArmBody.raw(), condNodesValue.raw(), startLine.raw()};
				MH_VAL(armNode, pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM, 3, armArgv));
				zval armNodeZv = armNode.take();
				zend_hash_index_update(armNodes.table(), i, &armNodeZv);
			}

			MH_VAL(armContext, pt_expression_context_enter_match_arm(context));
			body = armBody(arm);
			if (UNEXPECTED(body == NULL)) return zv::Val();
			MH_VAL(armResult, pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, body, bodyScope.raw(), storage, nodeCallback, armContext.raw()));
			{
				MH_VAL(flow, pt_expression_result_variable_flow(armResult.raw()));
				zval flowZv = flow.take();
				zend_hash_index_update(armFlows.table(), i, &flowZv);
			}
			borrowed = pt_expression_result_scope(armResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			zv::Val armScope = zv::Val::copyOf(zv::Ref(borrowed));
			scope = addTemplateArgumentConstraintsOf(scope.raw(), armScope.raw());
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
			bool armIsAlwaysTerminating;
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(armResult.raw(), armIsAlwaysTerminating))) return zv::Val();
			if (!armIsAlwaysTerminating) armBodyScopes.push(std::move(armScope));
			if (UNEXPECTED(!foldArmResult(armResult.raw(), hasYield, throwPoints, impurePoints, hold))) return zv::Val();
			// an arm whose filtering expression is always false is unreachable
			// and does not contribute to the result type
			if (UNEXPECTED(!filteringExprType.ref().isObject())) return callOnNonObject("isFalse", filteringExprType.raw());
			zend_long filteringIsFalse = pt_type_call_trinary(Z_OBJ_P(filteringExprType.raw()), PT_LC("isfalse"), 0, NULL);
			if (UNEXPECTED(filteringIsFalse < 0)) return zv::Val();
			if (filteringIsFalse != PT_TRI_YES) {
				body = armBody(arm);
				if (UNEXPECTED(body == NULL)) return zv::Val();
				armTypeResults.push(triple(armResult.raw(), bodyScope.raw(), body));
			}
			matchScope = applySpecifiedTypes(armCondScope.raw(), std::move(filteringFalseyTypes));
			if (UNEXPECTED(matchScope.isUndef())) return zv::Val();
		}

		if (!hasDefaultCond && !hasAlwaysTrueCond) {
			zend_long isBoolean = pt_type_op_trinary(Z_OBJ_P(condType.raw()), PT_OP_IS_BOOLEAN, 0, NULL);
			if (UNEXPECTED(isBoolean < 0)) return zv::Val();
			if (isBoolean == PT_TRI_YES) {
				zend_long isConstant = pt_type_op_trinary(Z_OBJ_P(condType.raw()), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
				if (UNEXPECTED(isConstant < 0)) return zv::Val();
				if (isConstant == PT_TRI_YES) {
					bool impossible;
					if (UNEXPECTED(!isScopeConditionallyImpossible(matchScope.raw(), impossible))) return zv::Val();
					if (impossible) {
						hasAlwaysTrueCond = true;
						zval never;
						if (UNEXPECTED(!pt_never_type_new(&never))) return zv::Val();
						zv::Val neverType = zv::Val::adopt(never);
						cond = exprCond(expr);
						if (UNEXPECTED(cond == NULL)) return zv::Val();
						if (UNEXPECTED(!matchScope.ref().isObject())) return callOnNonObject("addTypeToExpression", matchScope.raw());
						matchScope = pt_mutating_scope_add_type_to_expression(Z_OBJ_P(matchScope.raw()), Z_OBJ_P(cond), neverType.raw());
						if (UNEXPECTED(matchScope.isUndef())) return zv::Val();
					}
				}
			}
		}

		zv::Val scopeForMatchNodeCallback = zv::Val::copyOf(zv::Ref(scope.raw()));

		bool isExhaustive = hasDefaultCond || hasAlwaysTrueCond;
		if (!isExhaustive) {
			// $matchScope is the subject narrowed by "no arm matched"
			bool answers;
			if (UNEXPECTED(!pt_expression_result_answers_on_scope(condResult.raw(), matchScope.raw(), false, answers))) return zv::Val();
			zv::Val remainingType;
			if (answers) {
				remainingType = pt_expression_result_get_type_on_scope(condResult.raw(), matchScope.raw(), false);
			} else {
				MH_VAL(onDemandStorage, pt_expression_result_storage_new());
				cond = exprCond(expr);
				if (UNEXPECTED(cond == NULL)) return zv::Val();
				MH_VAL(onDemandResult, pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, cond, matchScope.raw(), onDemandStorage.raw()));
				if (UNEXPECTED(!onDemandResult.ref().isObject())) return callOnNonObject("getType", onDemandResult.raw());
				remainingType = pt_expression_result_get_type(onDemandResult.raw());
			}
			if (UNEXPECTED(remainingType.isUndef())) return zv::Val();
			if (remainingType.ref().instanceOf(pt_ce_never_type)) isExhaustive = true;
		}

		zv::Val armBodyFinalScope = zv::Val::null();
		for (zv::ArrayEntry entry : zv::TableRef(armBodyScopes.table())) {
			zval *armBodyScope = entry.value().raw();
			if (UNEXPECTED(Z_TYPE_P(armBodyScope) != IS_OBJECT)) return callOnNonObject("mergeWith", armBodyScope);
			armBodyFinalScope = pt_mutating_scope_merge_with(Z_OBJ_P(armBodyScope), armBodyFinalScope.raw());
			if (UNEXPECTED(armBodyFinalScope.isUndef())) return zv::Val();
		}
		if (isExhaustive) {
			if (!armBodyFinalScope.isNull()) scope = std::move(armBodyFinalScope);
		} else {
			if (!armBodyFinalScope.isNull()) {
				if (UNEXPECTED(!scope.ref().isObject())) return callOnNonObject("mergeWith", scope.raw());
				scope = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), armBodyFinalScope.raw());
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
			MH_VAL(unhandledMatchError, newUnhandledMatchErrorType());
			MH_VAL(throwPoint, pt_internal_throw_point_create_explicit(scope.raw(), unhandledMatchError.raw(), expr, false));
			appendTo(throwPoints, std::move(throwPoint));
		}

		scope = addTemplateArgumentConstraintsOf(scope.raw(), scopeForMatchNodeCallback.raw());
		if (UNEXPECTED(scope.isUndef())) return zv::Val();

		// ksort($armNodes, SORT_NUMERIC) + array_values(): the arms' own order
		{
			zv::Arr sortedArmNodes = zv::Arr::create(zend_hash_num_elements(armNodes.table()));
			zend_hash_sort(armNodes.table(), armKeyCompare, false);
			for (zv::ArrayEntry entry : zv::TableRef(armNodes.table())) {
				sortedArmNodes.push(entry.value());
			}
			cond = exprCond(expr);
			if (UNEXPECTED(cond == NULL)) return zv::Val();
			zv::Val sortedArmNodesValue(std::move(sortedArmNodes));
			zv::Args nodeArgv{cond, sortedArmNodesValue.raw(), expr, matchScope.raw()};
			MH_VAL(matchNode, pt_type_new(PT_CLASS_MATCH_EXPRESSION_NODE, 4, nodeArgv));
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, matchNode.raw(), scopeForMatchNodeCallback.raw(), storage))) return zv::Val();
		}

		cond = exprCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zend_class_entry *alwaysRememberedExprCe = pt_class(PT_CLASS_ALWAYS_REMEMBERED_EXPR);
		if (UNEXPECTED(alwaysRememberedExprCe == NULL)) return zv::Val();
		if (Z_TYPE_P(cond) == IS_OBJECT && instanceof_function(Z_OBJCE_P(cond), alwaysRememberedExprCe)) {
			MH_VAL(innerCond, alwaysRememberedExprGetExpr(cond));
			zval *slot = pt_property_cached(pt_mh_cond_site, Z_OBJ_P(expr), PT_LC("cond"));
			if (UNEXPECTED(slot == NULL)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Ref(slot).assign(std::move(innerCond));
		}

		{
			// $this->capturedArmResults[spl_object_id($expr)] = [$expr, $armTypeResults]
			zv::Arr entry = zv::Arr::create(2);
			entry.push(zv::Ref(expr));
			entry.push(zv::Val(std::move(armTypeResults)));
			zval *captured = pt_typed_slot(self, slots::capturedArmResults, self->ce, "capturedArmResults");
			if (UNEXPECTED(captured == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(captured) != IS_ARRAY)) {
				zval empty;
				array_init(&empty);
				pt_write_slot(self, slots::capturedArmResults, &empty);
				zval_ptr_dtor(&empty);
				captured = OBJ_PROP_NUM(self, slots::capturedArmResults);
			}
			SEPARATE_ARRAY(captured);
			zval entryZv = zv::Val(std::move(entry)).take();
			zend_hash_index_update(Z_ARRVAL_P(captured), Z_OBJ_HANDLE_P(expr), &entryZv);
		}

		MH_VAL(unhandledMatchErrorType, newUnhandledMatchErrorType());
		MH_VAL(variableFlow, pt_variable_flow_throwing(unhandledMatchErrorType.raw(), false, false));
		{
			armsSlot = exprArms(expr);
			if (UNEXPECTED(armsSlot == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(armsSlot) != IS_ARRAY)) {
				zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(armsSlot));
				return zv::Val();
			}
			zv::Val finalArms = zv::Val::copyOf(zv::Ref(armsSlot));
			HashTable *finalArmsTable = Z_ARRVAL_P(finalArms.raw());
			for (zv::ArrayEntry entry : zv::TableRef(finalArmsTable)) {
				zend_ulong i;
				if (UNEXPECTED(!indexKeyOf(entry, i))) return zv::Val();
				zval *conds = armConds(entry.value().deref().raw());
				if (UNEXPECTED(conds == NULL)) return zv::Val();
				if (Z_TYPE_P(conds) != IS_NULL) continue;

				zval *flow = zend_hash_index_find(armFlows.table(), i);
				variableFlow = flow != NULL ? zv::Val::copyOf(zv::Ref(flow)) : zv::Val::null();
			}
			// foreach (array_reverse($expr->arms, true) as $i => $arm)
			zend_ulong armIndex;
			zend_string *armKey;
			zval *armZv;
			ZEND_HASH_REVERSE_FOREACH_KEY_VAL(finalArmsTable, armIndex, armKey, armZv) {
				if (UNEXPECTED(armKey != NULL)) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				zval *arm = armZv;
				ZVAL_DEREF(arm);
				zval *conds = armConds(arm);
				if (UNEXPECTED(conds == NULL)) return zv::Val();
				if (Z_TYPE_P(conds) == IS_NULL) continue;
				if (UNEXPECTED(Z_TYPE_P(conds) != IS_ARRAY)) {
					zend_type_error("array_keys(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(conds));
					return zv::Val();
				}
				zv::Val heldConds = zv::Val::copyOf(zv::Ref(conds));
				// foreach (array_reverse(array_keys($arm->conds)) as $j)
				zend_ulong condIndex;
				zend_string *condKey;
				zval *condZv;
				ZEND_HASH_REVERSE_FOREACH_KEY_VAL(Z_ARRVAL_P(heldConds.raw()), condIndex, condKey, condZv) {
					(void) condZv;
					if (UNEXPECTED(condKey != NULL)) {
						pt_throw_should_not_happen();
						return zv::Val();
					}
					zval *conditionFlow = lookup2(conditionFlows.table(), armIndex, condIndex);
					zval *armFlow = zend_hash_index_find(armFlows.table(), armIndex);
					zval null;
					ZVAL_NULL(&null);
					zv::Args choiceArgv{armFlow != NULL ? armFlow : &null, variableFlow.raw()};
					MH_VAL(choice, pt_variable_flow_choice(2, choiceArgv));
					zv::Args sequenceArgv{conditionFlow != NULL ? conditionFlow : &null, choice.raw()};
					variableFlow = pt_variable_flow_sequence(2, sequenceArgv);
					if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
				} ZEND_HASH_FOREACH_END();
			} ZEND_HASH_FOREACH_END();
		}

		MH_VAL(condFlow, pt_expression_result_variable_flow(condResult.raw()));
		zv::Args flowArgv{condFlow.raw(), variableFlow.raw()};
		MH_VAL(resultFlow, pt_variable_flow_sequence(2, flowArgv));

		zval *capturedEntry = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::capturedArmResults)), Z_OBJ_HANDLE_P(expr));
		zval *capturedTypeResults = capturedEntry != NULL ? zend_hash_index_find(Z_ARRVAL_P(capturedEntry), 1) : NULL;
		if (UNEXPECTED(capturedTypeResults == NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, capturedTypeResults);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(resultFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return MatchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* [$armResult, $scope, $body] */
	static zv::Val triple(zval *armResult, zval *scope, zval *body)
	{
		zv::Arr entry = zv::Arr::create(3);
		entry.push(zv::Ref(armResult));
		entry.push(zv::Ref(scope));
		entry.push(zv::Ref(body));
		return zv::Val(std::move(entry));
	}

	/* $hasYield = $hasYield || $result->hasYield(); $throwPoints =
	 * array_merge($throwPoints, $result->getThrowPoints()); $impurePoints =
	 * array_merge($impurePoints, $result->getImpurePoints()); false = pending
	 * exception */
	[[nodiscard]] static bool foldArmResult(zval *result, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, zv::Val &hold)
	{
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		zval *borrowed = pt_expression_result_throw_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_expression_result_impure_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		return true;
	}

	/* The enum fast path of processExpr() (the `if (count($enumCases) > 0)`
	 * block); false = pending exception */
	[[nodiscard]] bool processEnumArms(zval *nodeScopeResolver, zval *stmt, zval *expr, zv::Val &scope, zval *storage, zval *nodeCallback, zval *context, zval *deepContext, zval *enumCases, zv::Val &matchScope, zv::Val &arms, zv::Arr &armNodes, zv::Arr &armFlows, zv::Arr &conditionFlows, bool &hasAlwaysTrueCond, zv::Arr &armCondsToSkip, zv::Arr &armBodyScopes, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, zv::Arr &armTypeResults) const
	{
		zend_class_entry *classConstFetchCe = pt_class(PT_CLASS_CLASS_CONST_FETCH);
		zend_class_entry *nameCe = pt_class(PT_CLASS_NAME);
		zend_class_entry *identifierCe = pt_class(PT_CLASS_IDENTIFIER);
		if (UNEXPECTED(classConstFetchCe == NULL || nameCe == NULL || identifierCe == NULL)) return false;

		// $indexedEnumCases[strtolower($enumCase->getClassName())][$enumCase->getEnumCaseName()] = $enumCase
		zv::Arr indexedEnumCases = zv::Arr::create(1);
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(enumCases))) {
			zval *enumCase = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(enumCase) != IS_OBJECT)) return !callOnNonObject("getClassName", enumCase).isUndef();
			zv::Val className = pt_type_call(Z_OBJ_P(enumCase), PT_LC("getclassname"), 0, NULL);
			if (UNEXPECTED(className.isUndef())) return false;
			if (UNEXPECTED(!className.ref().isString())) {
				zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(className.raw()));
				return false;
			}
			zend_string *lowered = zend_string_tolower(Z_STR_P(className.raw()));
			zv::Val caseName = pt_type_call(Z_OBJ_P(enumCase), PT_LC("getenumcasename"), 0, NULL);
			if (UNEXPECTED(caseName.isUndef())) {
				zend_string_release(lowered);
				return false;
			}
			zval *row = zend_symtable_find(indexedEnumCases.table(), lowered);
			if (row == NULL) {
				zval empty;
				array_init(&empty);
				row = zend_symtable_update(indexedEnumCases.table(), lowered, &empty);
			}
			zend_string_release(lowered);
			zval caseValue;
			ZVAL_COPY(&caseValue, enumCase);
			if (Z_TYPE_P(caseName.raw()) == IS_STRING) {
				zend_symtable_update(Z_ARRVAL_P(row), Z_STR_P(caseName.raw()), &caseValue);
			} else {
				zend_result updated = array_set_zval_key(Z_ARRVAL_P(row), caseName.raw(), &caseValue);
				zval_ptr_dtor(&caseValue);
				if (UNEXPECTED(updated != SUCCESS)) return false;
			}
		}
		// $unusedIndexedEnumCases = $indexedEnumCases (copied on write)
		zv::Arr unusedIndexedEnumCases = zv::Arr::create(zend_hash_num_elements(indexedEnumCases.table()));
		for (zv::ArrayEntry entry : zv::TableRef(indexedEnumCases.table())) {
			zval row;
			ZVAL_ARR(&row, zend_array_dup(Z_ARRVAL_P(entry.value().raw())));
			zend_symtable_update(unusedIndexedEnumCases.table(), entry.stringKey(), &row);
		}

		zv::Val hold;
		HashTable *armsTable = Z_ARRVAL_P(arms.raw());
		zv::Val iterated = zv::Val::copyOf(zv::Ref(arms.raw()));
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(iterated.raw()))) {
			zend_ulong i;
			if (UNEXPECTED(!indexKeyOf(entry, i))) return false;
			zval *arm = entry.value().deref().raw();
			zval *conds = armConds(arm);
			if (UNEXPECTED(conds == NULL)) return false;
			if (Z_TYPE_P(conds) == IS_NULL) continue;
			if (UNEXPECTED(Z_TYPE_P(conds) != IS_ARRAY)) {
				zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(conds));
				return false;
			}
			zv::Val heldConds = zv::Val::copyOf(zv::Ref(conds));

			// pre-validate every condition before processing any (break 2 stops
			// the fast path for this and every later arm)
			bool stop = false;
			for (zv::ArrayEntry condEntry : zv::TableRef(Z_ARRVAL_P(heldConds.raw()))) {
				zval *cond = condEntry.value().deref().raw();
				zv::Val loweredClassName;
				zval *caseName = NULL;
				if (UNEXPECTED(!resolveEnumCondition(cond, scope.raw(), classConstFetchCe, nameCe, identifierCe, loweredClassName, caseName, stop))) return false;
				if (stop) break;
				zval *row = zend_symtable_find(indexedEnumCases.table(), Z_STR_P(loweredClassName.raw()));
				if (row == NULL || !hasCaseName(Z_ARRVAL_P(row), caseName)) {
					stop = true;
					break;
				}
			}
			if (stop) break;

			zv::Arr condNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(heldConds.raw())));
			zv::Arr conditionCases = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(heldConds.raw())));
			zv::Arr conditionExprs = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(heldConds.raw())));
			for (zv::ArrayEntry condEntry : zv::TableRef(Z_ARRVAL_P(heldConds.raw()))) {
				zend_ulong j;
				if (UNEXPECTED(!indexKeyOf(condEntry, j))) return false;
				zval *cond = condEntry.value().deref().raw();
				// the pre-validation guaranteed an enum-case ClassConstFetch
				zv::Val loweredClassName;
				zval *caseName = NULL;
				bool invalid = false;
				if (UNEXPECTED(!resolveEnumCondition(cond, scope.raw(), classConstFetchCe, nameCe, identifierCe, loweredClassName, caseName, invalid))) return false;
				if (UNEXPECTED(invalid)) {
					pt_throw_should_not_happen();
					return false;
				}
				zval *indexedRow = zend_symtable_find(indexedEnumCases.table(), Z_STR_P(loweredClassName.raw()));
				zval *unusedRow = zend_symtable_find(unusedIndexedEnumCases.table(), Z_STR_P(loweredClassName.raw()));
				if (UNEXPECTED(indexedRow == NULL || unusedRow == NULL || !hasCaseName(Z_ARRVAL_P(indexedRow), caseName))) {
					pt_throw_should_not_happen();
					return false;
				}
				zval *enumCase = findCase(Z_ARRVAL_P(indexedRow), caseName);
				zv::Val heldCase = zv::Val::copyOf(zv::Ref(enumCase));
				conditionCases.push(zv::Ref(heldCase.raw()));
				zv::Val armConditionScope = zv::Val::copyOf(zv::Ref(matchScope.raw()));
				zval *cond0 = exprCond(expr);
				if (UNEXPECTED(cond0 == NULL)) return false;
				if (UNEXPECTED(!armConditionScope.ref().isObject())) return !callOnNonObject("removeTypeFromExpression", armConditionScope.raw()).isUndef();
				if (!hasCaseName(Z_ARRVAL_P(unusedRow), caseName)) {
					// force "always false"
					armConditionScope = pt_mutating_scope_remove_type_from_expression(Z_OBJ_P(armConditionScope.raw()), Z_OBJ_P(cond0), heldCase.raw());
					if (UNEXPECTED(armConditionScope.isUndef())) return false;
				} else {
					zend_long unusedCasesCount = 0;
					for (zv::ArrayEntry cases : zv::TableRef(unusedIndexedEnumCases.table())) {
						unusedCasesCount += zend_hash_num_elements(Z_ARRVAL_P(cases.value().raw()));
					}
					if (unusedCasesCount == 1) {
						hasAlwaysTrueCond = true;
						// force "always true"
						armConditionScope = pt_mutating_scope_add_type_to_expression(Z_OBJ_P(armConditionScope.raw()), Z_OBJ_P(cond0), heldCase.raw());
						if (UNEXPECTED(armConditionScope.isUndef())) return false;
					}
				}

				zv::Val conditionResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, cond, armConditionScope.raw(), storage, nodeCallback, deepContext);
				if (UNEXPECTED(conditionResult.isUndef())) return false;
				zv::Val conditionFlow = pt_expression_result_variable_flow(conditionResult.raw());
				if (UNEXPECTED(conditionFlow.isUndef())) return false;
				store2(conditionFlows.table(), i, j, std::move(conditionFlow));

				zv::Val startLine = nodeGetStartLine(cond);
				if (UNEXPECTED(startLine.isUndef())) return false;
				zv::Args condNodeArgv{cond, armConditionScope.raw(), startLine.raw()};
				zv::Val condNode = pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM_CONDITION, 3, condNodeArgv);
				if (UNEXPECTED(condNode.isUndef())) return false;
				condNodes.push(std::move(condNode));
				conditionExprs.push(zv::Ref(cond));

				// unset($unusedIndexedEnumCases[$loweredFetchedClassName][$caseName])
				unusedRow = zend_symtable_find(unusedIndexedEnumCases.table(), Z_STR_P(loweredClassName.raw()));
				if (unusedRow != NULL) {
					SEPARATE_ARRAY(unusedRow);
					if (Z_TYPE_P(caseName) == IS_STRING) {
						zend_symtable_del(Z_ARRVAL_P(unusedRow), Z_STR_P(caseName));
					} else {
						zend_hash_index_del(Z_ARRVAL_P(unusedRow), zval_get_long(caseName));
					}
				}
				zval skip;
				ZVAL_TRUE(&skip);
				store2(armCondsToSkip.table(), i, j, zv::Val::adopt(skip));
			}

			zv::Val conditionCaseType = caseTypeOf(conditionCases, false);
			if (UNEXPECTED(conditionCaseType.isUndef())) return false;

			zv::Val filteringExpr = getFilteringExprForMatchArm(expr, conditionExprs.table(), NULL);
			if (UNEXPECTED(filteringExpr.isUndef())) return false;
			zval *cond0 = exprCond(expr);
			if (UNEXPECTED(cond0 == NULL)) return false;
			if (UNEXPECTED(!matchScope.ref().isObject())) return !callOnNonObject("addTypeToExpression", matchScope.raw()).isUndef();
			zv::Val condNarrowedScope = pt_mutating_scope_add_type_to_expression(Z_OBJ_P(matchScope.raw()), Z_OBJ_P(cond0), conditionCaseType.raw());
			if (UNEXPECTED(condNarrowedScope.isUndef())) return false;
			zv::Val matchArmBodyScope = applySpecifiedTypes(condNarrowedScope.raw(), specifiedTypesForScope(pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver, filteringExpr.raw(), condNarrowedScope.raw()), condNarrowedScope.raw(), pt_type_specifier_context_create_truthy()));
			if (UNEXPECTED(matchArmBodyScope.isUndef())) return false;
			zval *body = armBody(arm);
			if (UNEXPECTED(body == NULL)) return false;
			{
				zv::Args bodyArgv{matchArmBodyScope.raw(), body};
				zv::Val matchArmBody = pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM_BODY, 2, bodyArgv);
				if (UNEXPECTED(matchArmBody.isUndef())) return false;
				zv::Val startLine = nodeGetStartLine(arm);
				if (UNEXPECTED(startLine.isUndef())) return false;
				zv::Val condNodesValue(std::move(condNodes));
				zv::Args armArgv{matchArmBody.raw(), condNodesValue.raw(), startLine.raw()};
				zv::Val armNode = pt_type_new(PT_CLASS_MATCH_EXPRESSION_ARM, 3, armArgv);
				if (UNEXPECTED(armNode.isUndef())) return false;
				zval armNodeZv = armNode.take();
				zend_hash_index_update(armNodes.table(), i, &armNodeZv);
			}

			zv::Val armContext = pt_expression_context_enter_match_arm(context);
			if (UNEXPECTED(armContext.isUndef())) return false;
			body = armBody(arm);
			if (UNEXPECTED(body == NULL)) return false;
			zv::Val armResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, body, matchArmBodyScope.raw(), storage, nodeCallback, armContext.raw());
			if (UNEXPECTED(armResult.isUndef())) return false;
			{
				zv::Val flow = pt_expression_result_variable_flow(armResult.raw());
				if (UNEXPECTED(flow.isUndef())) return false;
				zval flowZv = flow.take();
				zend_hash_index_update(armFlows.table(), i, &flowZv);
			}
			zval *borrowed = pt_expression_result_scope(armResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return false;
			zv::Val armScope = zv::Val::copyOf(zv::Ref(borrowed));
			scope = addTemplateArgumentConstraintsOf(scope.raw(), armScope.raw());
			if (UNEXPECTED(scope.isUndef())) return false;
			bool armIsAlwaysTerminating;
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(armResult.raw(), armIsAlwaysTerminating))) return false;
			if (!armIsAlwaysTerminating) armBodyScopes.push(std::move(armScope));
			if (UNEXPECTED(!foldArmResult(armResult.raw(), hasYield, throwPoints, impurePoints, hold))) return false;
			body = armBody(arm);
			if (UNEXPECTED(body == NULL)) return false;
			armTypeResults.push(triple(armResult.raw(), matchArmBodyScope.raw(), body));

			// unset($arms[$i])
			SEPARATE_ARRAY(arms.raw());
			armsTable = Z_ARRVAL_P(arms.raw());
			zend_hash_index_del(armsTable, i);
		}

		zv::Arr remainingCases = zv::Arr::create(0);
		for (zv::ArrayEntry cases : zv::TableRef(unusedIndexedEnumCases.table())) {
			for (zv::ArrayEntry enumCase : zv::TableRef(Z_ARRVAL_P(cases.value().raw()))) {
				remainingCases.push(enumCase.value());
			}
		}
		zv::Val remainingType = caseTypeOf(remainingCases, true);
		if (UNEXPECTED(remainingType.isUndef())) return false;
		zval *cond0 = exprCond(expr);
		if (UNEXPECTED(cond0 == NULL)) return false;
		if (UNEXPECTED(!matchScope.ref().isObject())) return !callOnNonObject("addTypeToExpression", matchScope.raw()).isUndef();
		matchScope = pt_mutating_scope_add_type_to_expression(Z_OBJ_P(matchScope.raw()), Z_OBJ_P(cond0), remainingType.raw());
		return !matchScope.isUndef();
	}

	/* the enum-case ClassConstFetch facts of a condition: strtolower($scope->resolveName($cond->class))
	 * and $cond->name (the Identifier's string, borrowed); invalid = the
	 * condition is not such a fetch; false = pending exception */
	[[nodiscard]] static bool resolveEnumCondition(zval *cond, zval *scope, zend_class_entry *classConstFetchCe, zend_class_entry *nameCe, zend_class_entry *identifierCe, zv::Val &loweredClassName, zval *&caseName, bool &invalid)
	{
		invalid = false;
		if (Z_TYPE_P(cond) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(cond), classConstFetchCe)) {
			invalid = true;
			return true;
		}
		zval *classNode = fetchClass(cond);
		if (UNEXPECTED(classNode == NULL)) return false;
		if (Z_TYPE_P(classNode) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(classNode), nameCe)) {
			invalid = true;
			return true;
		}
		zval *nameNode = fetchName(cond);
		if (UNEXPECTED(nameNode == NULL)) return false;
		if (Z_TYPE_P(nameNode) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(nameNode), identifierCe)) {
			invalid = true;
			return true;
		}
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return !callOnNonObject("resolveName", scope).isUndef();
		zv::Val fetchedClassName = pt_mutating_scope_resolve_name(Z_OBJ_P(scope), Z_OBJ_P(classNode));
		if (UNEXPECTED(fetchedClassName.isUndef())) return false;
		if (UNEXPECTED(!fetchedClassName.ref().isString())) {
			zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(fetchedClassName.raw()));
			return false;
		}
		loweredClassName = zv::Val::adoptString(zend_string_tolower(Z_STR_P(fetchedClassName.raw())));
		caseName = identifierName(nameNode);
		if (UNEXPECTED(caseName == NULL)) return false;
		return true;
	}

	/* array_key_exists($caseName, $row) */
	static bool hasCaseName(HashTable *row, zval *caseName)
	{
		return findCase(row, caseName) != NULL;
	}

	static zval *findCase(HashTable *row, zval *caseName)
	{
		if (EXPECTED(Z_TYPE_P(caseName) == IS_STRING)) return zend_symtable_find(row, Z_STR_P(caseName));
		if (Z_TYPE_P(caseName) == IS_LONG) return zend_hash_index_find(row, Z_LVAL_P(caseName));
		return NULL;
	}

	/* $specifyArmCond($specifyContext): ($this->identicalNarrowingHelper->specifyIdentical(...)
	 * ?? $this->defaultNarrowingHelper->specifyDefaultTypes($armCondExpr,
	 * $specifyContext))->setRootExpr($armCondExpr) */
	zv::Val specifyArmCond(zval *nodeScopeResolver, zval *expr, zval *armCond, zval *condResult, zval *armCondResult, zend_object *specifyContext, zval *armCondResultScope, zval *condArgResult, zval *armCondArgResult, zval *armCondExpr, zval *identicalCaptures) const
	{
		zval contextZv;
		if (UNEXPECTED(!contextZval(specifyContext, contextZv))) return zv::Val();
		zval *cond = exprCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		zv::Val identicalTypeCallback = pt_native_closure_new(&identicalTypeBody, 6, identicalCaptures);
		MH_VAL(specified, pt_identical_narrowing_helper_specify_identical(OBJ_PROP_NUM(self, slots::identicalNarrowingHelper), nodeScopeResolver, cond, armCond, condResult, armCondResult, &contextZv, armCondResultScope, condArgResult, armCondArgResult, identicalTypeCallback.raw()));
		if (specified.isNull()) {
			specified = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), armCondExpr, &contextZv);
			if (UNEXPECTED(specified.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(!specified.ref().isObject())) return callOnNonObject("setRootExpr", specified.raw());
		return pt_specified_types_set_root_expr(Z_OBJ_P(specified.raw()), armCondExpr);
	}

	/* fn (): Type => $this->richerScopeGetTypeHelper->getIdenticalResult($armCondResultScope,
	 * $armCondExpr, $nodeScopeResolver, $armCondResultScope->getStateType($expr->cond),
	 * $armCondResult->getType())->type — captures: $this, $armCondResultScope,
	 * $armCondExpr, $nodeScopeResolver, $expr, $armCondResult */
	static void identicalTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zval *armCondResultScope = &captures[1];
		zval *cond = exprCond(&captures[4]);
		if (UNEXPECTED(cond == NULL)) return;
		if (UNEXPECTED(Z_TYPE_P(armCondResultScope) != IS_OBJECT)) {
			(void) callOnNonObject("getStateType", armCondResultScope);
			return;
		}
		zv::Val subjectType = pt_mutating_scope_get_state_type(Z_OBJ_P(armCondResultScope), Z_OBJ_P(cond));
		if (UNEXPECTED(subjectType.isUndef())) return;
		zv::Val condType = pt_expression_result_get_type(&captures[5]);
		if (UNEXPECTED(condType.isUndef())) return;
		zv::Val type = identicalResultType(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::richerScopeGetTypeHelper), armCondResultScope, &captures[2], &captures[3], subjectType.raw(), condType.raw());
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* Mirrors the private getFilteringExprForMatchArm(); $condData NULL for
	 * the [] default */
	static zv::Val getFilteringExprForMatchArm(zval *expr, HashTable *conditions, HashTable *condData)
	{
		zval *cond = exprCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		if (zend_hash_num_elements(conditions) == 1) {
			zval *condition = zend_hash_index_find(conditions, 0);
			if (UNEXPECTED(condition == NULL)) {
				// the conditions are appended lists
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Args identicalArgv{cond, condition};
			return pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
		}

		// the haystack carries the conditions' walked types, not their nodes
		zv::Arr items = zv::Arr::create(zend_hash_num_elements(conditions));
		for (zv::ArrayEntry entry : zv::TableRef(conditions)) {
			zval *filteringExpr = entry.value().deref().raw();
			zval *condResult = NULL;
			if (condData != NULL) {
				zval *data = entry.hasStringKey() ? zend_symtable_find(condData, entry.stringKey()) : zend_hash_index_find(condData, entry.indexKey());
				if (data != NULL && Z_TYPE_P(data) == IS_ARRAY) {
					condResult = zend_hash_index_find(Z_ARRVAL_P(data), 1);
					if (condResult != NULL && Z_TYPE_P(condResult) == IS_NULL) condResult = NULL;
				}
			}
			zv::Val itemValue;
			if (condResult != NULL) {
				MH_VAL(condResultType, pt_expression_result_get_type(condResult));
				itemValue = pt_type_new(PT_CLASS_TYPE_EXPR, 1, condResultType.raw());
				if (UNEXPECTED(itemValue.isUndef())) return zv::Val();
			} else {
				itemValue = zv::Val::copyOf(zv::Ref(filteringExpr));
			}
			MH_VAL(item, pt_type_new(PT_CLASS_ARRAY_ITEM, 1, itemValue.raw()));
			items.push(std::move(item));
		}

		MH_VAL(inArrayName, pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, zv::Args{pt_mh_in_array}));
		cond = exprCond(expr);
		if (UNEXPECTED(cond == NULL)) return zv::Val();
		MH_VAL(subjectArg, pt_type_new(PT_CLASS_ARG, 1, cond));
		zv::Val itemsValue(std::move(items));
		MH_VAL(haystack, pt_type_new(PT_CLASS_ARRAY_EXPR, 1, itemsValue.raw()));
		MH_VAL(haystackArg, pt_type_new(PT_CLASS_ARG, 1, haystack.raw()));
		MH_VAL(trueName, pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, zv::Args{pt_mh_true}));
		MH_VAL(trueFetch, pt_type_new(PT_CLASS_CONST_FETCH, 1, trueName.raw()));
		MH_VAL(strictArg, pt_type_new(PT_CLASS_ARG, 1, trueFetch.raw()));
		zv::Arr callArgs = zv::Arr::create(3);
		callArgs.push(std::move(subjectArg));
		callArgs.push(std::move(haystackArg));
		callArgs.push(std::move(strictArg));
		zv::Val callArgsValue(std::move(callArgs));
		zv::Args callArgv{inArrayName.raw(), callArgsValue.raw()};
		return pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
	}

	/* Mirrors the private isScopeConditionallyImpossible(); false = pending
	 * exception */
	[[nodiscard]] bool isScopeConditionallyImpossible(zval *scope, bool &out) const
	{
		out = false;
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return !callOnNonObject("getDefinedVariables", scope).isUndef();
		zv::Val definedVariables = pt_mutating_scope_get_defined_variables(Z_OBJ_P(scope));
		if (UNEXPECTED(definedVariables.isUndef())) return false;
		if (UNEXPECTED(!definedVariables.ref().isArray())) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(definedVariables.raw()));
			return false;
		}
		zv::Arr boolVars = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(definedVariables.raw()))) {
			zval *varName = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(varName) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::getVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(varName));
				return false;
			}
			zv::Val varType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), Z_STR_P(varName));
			if (UNEXPECTED(varType.isUndef())) return false;
			if (UNEXPECTED(!varType.ref().isObject())) return !callOnNonObject("isBoolean", varType.raw()).isUndef();
			zend_long isBoolean = pt_type_op_trinary(Z_OBJ_P(varType.raw()), PT_OP_IS_BOOLEAN, 0, NULL);
			if (UNEXPECTED(isBoolean < 0)) return false;
			if (isBoolean != PT_TRI_YES) continue;
			zend_long isConstant = pt_type_op_trinary(Z_OBJ_P(varType.raw()), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
			if (UNEXPECTED(isConstant < 0)) return false;
			if (isConstant == PT_TRI_YES) continue;

			boolVars.push(zv::Ref(varName));
		}

		if (zend_hash_num_elements(boolVars.table()) == 0) return true;

		// whether any boolean variable's both truth values lead to contradictions
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		for (zv::ArrayEntry entry : zv::TableRef(boolVars.table())) {
			zval *varName = entry.value().raw();
			zv::Val varExpr = pt_type_new(PT_CLASS_VARIABLE, 1, varName);
			if (UNEXPECTED(varExpr.isUndef())) return false;
			// a walked Variable's specify callback is exactly the default narrowing

			zval truthy;
			if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_truthy(), truthy))) return false;
			zv::Val truthyScope = applySpecifiedTypes(scope, pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, varExpr.raw(), &truthy));
			if (UNEXPECTED(truthyScope.isUndef())) return false;
			bool truthyContradiction;
			if (UNEXPECTED(!scopeHasNeverVariable(truthyScope.raw(), boolVars.table(), truthyContradiction))) return false;
			if (!truthyContradiction) continue;

			zval falsey;
			if (UNEXPECTED(!contextZval(pt_type_specifier_context_create_falsey(), falsey))) return false;
			zv::Val falseyScope = applySpecifiedTypes(scope, pt_default_narrowing_helper_specify_default_types(defaultNarrowingHelper, varExpr.raw(), &falsey));
			if (UNEXPECTED(falseyScope.isUndef())) return false;
			bool falseyContradiction;
			if (UNEXPECTED(!scopeHasNeverVariable(falseyScope.raw(), boolVars.table(), falseyContradiction))) return false;
			if (falseyContradiction) {
				out = true;
				return true;
			}
		}

		return true;
	}

	/* Mirrors the private scopeHasNeverVariable(); false = pending exception */
	[[nodiscard]] static bool scopeHasNeverVariable(zval *scope, HashTable *varNames, bool &out)
	{
		out = false;
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) return !callOnNonObject("getVariableType", scope).isUndef();
		for (zv::ArrayEntry entry : zv::TableRef(varNames)) {
			zv::Val type = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), Z_STR_P(entry.value().raw()));
			if (UNEXPECTED(type.isUndef())) return false;
			if (type.ref().instanceOf(pt_ce_never_type)) {
				out = true;
				return true;
			}
		}

		return true;
	}

	/* static function (bool $nativeTypesPromoted) use ($armTypeResults): Type
	 * — captures: $armTypeResults */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_mh_closure_name))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		HashTable *armTypeResults = Z_ARRVAL(captures[0]);
		zv::Arr types = zv::Arr::create(zend_hash_num_elements(armTypeResults));
		for (zv::ArrayEntry entry : zv::TableRef(armTypeResults)) {
			zval *armResult = zend_hash_index_find(Z_ARRVAL_P(entry.value().raw()), 0);
			zv::Val type = pt_expression_result_get_keep_void_type(armResult, nativeTypesPromoted);
			if (UNEXPECTED(type.isUndef())) return;
			types.push(std::move(type));
		}
		HashTable *typesTable = types.table();
		zv::Val union_ = HT_IS_PACKED(typesTable) && typesTable->nNumUsed == zend_hash_num_elements(typesTable)
			? pt_type_combinator_union(zend_hash_num_elements(typesTable), typesTable->arPacked)
			: pt_type_combinator_union(0, NULL);
		if (UNEXPECTED(union_.isUndef())) return;
		union_.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_mh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::MatchHandler;

zv::Val pt_match_handler_get_captured_arm_scopes_and_types(zval *handler, zval *expr)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_match_handler)) {
		zend_class_entry *matchCe = pt_class(PT_CLASS_MATCH);
		if (UNEXPECTED(matchCe == NULL)) return zv::Val();
		if (EXPECTED(Z_TYPE_P(expr) == IS_OBJECT && instanceof_function(Z_OBJCE_P(expr), matchCe))) return MatchHandler(Z_OBJ_P(handler)).getCapturedArmScopesAndTypes(expr);
	}
	return pt_type_call(Z_OBJ_P(handler), PT_LC("getcapturedarmscopesandtypes"), 1, expr);
}

#undef MH_VAL

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_match_handler)
{
	pt_mh_unhandled_match_error = zend_string_init_interned(PT_LC("UnhandledMatchError"), 1);
	pt_mh_in_array = zend_string_init_interned(PT_LC("in_array"), 1);
	pt_mh_true = zend_string_init_interned(PT_LC("true"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\MatchHandler");
	ptdecl::MatchHandler::declareClass(cls);
	ptdecl::MatchHandler::declareProperties(cls);

	cls.method<&MatchHandler::resetFileAnalysisState>(sigs::resetFileAnalysisState);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool treatPhpDocTypesAsCertain;
		zval *expressionResultFactory, *defaultNarrowingHelper, *identicalNarrowingHelper, *richerScopeGetTypeHelper;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_BOOL(treatPhpDocTypesAsCertain)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
			Z_PARAM_OBJECT(identicalNarrowingHelper)
			Z_PARAM_OBJECT(richerScopeGetTypeHelper)
		ZEND_PARSE_PARAMETERS_END();
		MatchHandler(Z_OBJ_P(ZEND_THIS)).construct(treatPhpDocTypesAsCertain, expressionResultFactory, defaultNarrowingHelper, identicalNarrowingHelper, richerScopeGetTypeHelper);
	});

	cls.method<&MatchHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::getCapturedArmScopesAndTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		zend_class_entry *matchCe = pt_class(PT_CLASS_MATCH);
		if (UNEXPECTED(matchCe == NULL)) RETURN_THROWS();
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(expr, matchCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(MatchHandler(Z_OBJ_P(ZEND_THIS)).getCapturedArmScopesAndTypes(expr));
	});

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
		PT_RETURN_VAL(MatchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_match_handler);
	pt_expr_handler_entry_register(&pt_ce_match_handler, &MatchHandler::processExprEntry);
}

/* }}} */
