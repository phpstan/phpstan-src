/*
 * PHPStanTurbo\LoopWrittenVariableNames — native implementation of
 * PHPStan\Analyser\LoopWrittenVariableNames.
 *
 * The names a loop can write while its scope converges, asked on every
 * generalization pass of the loop handlers: the syntactic names found once
 * per loop node (a whole-subtree walk, memoized in a node attribute) plus the
 * writes the pass's variable flow records. The subtree walk reads the
 * subnodes through the node class's memoized subnode offsets (the vendored
 * getSubNodeNames() lists, as the native node traverser reads them) and the
 * flows through the native VariableFlow slots. Native callers use
 * pt_loop_written_variable_names_collect() (support.h).
 */

#include "support.h"
#include "generated/LoopWrittenVariableNames.h"
#include "generated/VariableFlow.h"
#include "generated/VariableAccessFlow.h"
#include "generated/VariableSequenceFlow.h"
#include "generated/VariableControlFlow.h"

namespace sigs = ptdecl::LoopWrittenVariableNames::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "ParserVisitors.h"

#include <vector>

zend_class_entry *pt_ce_loop_written_variable_names = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

constexpr const char *pt_lwvn_attribute = "phpstanLoopWrittenVariableNames";

NodeProp pt_lwvn_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");
NodeProp pt_lwvn_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
NodeProp pt_lwvn_name_name = PT_NODE_PROP(PT_CLASS_NAME, "name");
NodeProp pt_lwvn_assign_var = PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "var");
NodeProp pt_lwvn_assign_op_var = PT_NODE_PROP(PT_CLASS_ASSIGN_OP_EXPR, "var");
NodeProp pt_lwvn_assign_ref_var = PT_NODE_PROP(PT_CLASS_ASSIGN_REF_EXPR, "var");
NodeProp pt_lwvn_assign_ref_expr = PT_NODE_PROP(PT_CLASS_ASSIGN_REF_EXPR, "expr");
NodeProp pt_lwvn_pre_inc_var = PT_NODE_PROP(PT_CLASS_PRE_INC, "var");
NodeProp pt_lwvn_pre_dec_var = PT_NODE_PROP(PT_CLASS_PRE_DEC, "var");
NodeProp pt_lwvn_post_inc_var = PT_NODE_PROP(PT_CLASS_POST_INC, "var");
NodeProp pt_lwvn_post_dec_var = PT_NODE_PROP(PT_CLASS_POST_DEC, "var");
NodeProp pt_lwvn_foreach_by_ref = PT_NODE_PROP(PT_CLASS_FOREACH_STMT, "byRef");
NodeProp pt_lwvn_foreach_expr = PT_NODE_PROP(PT_CLASS_FOREACH_STMT, "expr");
NodeProp pt_lwvn_foreach_key_var = PT_NODE_PROP(PT_CLASS_FOREACH_STMT, "keyVar");
NodeProp pt_lwvn_foreach_value_var = PT_NODE_PROP(PT_CLASS_FOREACH_STMT, "valueVar");
NodeProp pt_lwvn_catch_var = PT_NODE_PROP(PT_CLASS_CATCH_STMT, "var");
NodeProp pt_lwvn_static_vars = PT_NODE_PROP(PT_CLASS_STATIC_STMT, "vars");
NodeProp pt_lwvn_global_vars = PT_NODE_PROP(PT_CLASS_GLOBAL_STMT, "vars");
NodeProp pt_lwvn_unset_vars = PT_NODE_PROP(PT_CLASS_UNSET_STMT, "vars");
NodeProp pt_lwvn_closure_uses = PT_NODE_PROP(PT_CLASS_CLOSURE_EXPR, "uses");
NodeProp pt_lwvn_array_dim_fetch_var = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "var");
NodeProp pt_lwvn_property_fetch_var = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "var");
NodeProp pt_lwvn_nullsafe_property_fetch_var = PT_NODE_PROP(PT_CLASS_NULLSAFE_PROPERTY_FETCH, "var");
NodeProp pt_lwvn_list_items = PT_NODE_PROP(PT_CLASS_LIST_EXPR, "items");
NodeProp pt_lwvn_array_items = PT_NODE_PROP(PT_CLASS_ARRAY_EXPR, "items");

pt_property_site pt_lwvn_static_var_var_site;
pt_property_site pt_lwvn_closure_use_by_ref_site;
pt_property_site pt_lwvn_closure_use_var_site;
pt_property_site pt_lwvn_item_value_site;
pt_method_site pt_lwvn_get_sub_node_names_site;
pt_method_site pt_lwvn_get_variable_name_site;

inline bool isOf(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	zend_class_entry *ce = pt_class_loaded(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

/* $node->$property through the memoized offset, the engine's read (and its
 * Error for an uninitialized typed property) otherwise; NULL = pending
 * exception */
zval *prop(NodeProp &nodeProp, zend_object *node, zval &rv)
{
	zval *slot = nodeProp.of(node);
	if (EXPECTED(slot != NULL && Z_TYPE_P(slot) != IS_UNDEF)) return slot;
	if (UNEXPECTED(EG(exception))) return NULL;
	zval *value = zend_read_property(node->ce, node, nodeProp.name, nodeProp.nameLen, 0, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

zval *siteProp(pt_property_site &site, zend_object *object, const char *name, size_t len, zval &rv)
{
	zval *slot = pt_property_cached(site, object, name, len);
	if (EXPECTED(slot != NULL)) {
		ZVAL_DEREF(slot);
		if (EXPECTED(Z_TYPE_P(slot) != IS_UNDEF)) return slot;
	}
	zval *value = zend_read_property(object->ce, object, name, len, 0, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* a `foreach ($value as ...)` over a readonly array slot of a native flow */
inline zval *flowArray(zend_object *flow, uint32_t slot, const char *name)
{
	return pt_typed_slot(flow, slot, flow->ce, name);
}

/* the getTargetNames() parameter `Expr $target`; false = TypeError raised */
[[nodiscard]] bool requireExpr(zval *target)
{
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(target) == IS_OBJECT && instanceof_function(Z_OBJCE_P(target), exprCe))) return true;
	zend_type_error("PHPStan\\Analyser\\LoopWrittenVariableNames::getTargetNames(): Argument #1 ($target) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(target));
	return false;
}

/* $names[$name] = true */
void addName(zv::Arr &names, zval *name)
{
	zval marked;
	ZVAL_TRUE(&marked);
	switch (Z_TYPE_P(name)) {
		case IS_STRING:
			zend_symtable_update(names.table(), Z_STR_P(name), &marked);
			return;
		case IS_LONG:
			zend_hash_index_update(names.table(), (zend_ulong) Z_LVAL_P(name), &marked);
			return;
		case IS_NULL:
			zend_hash_update(names.table(), ZSTR_EMPTY_ALLOC(), &marked);
			return;
		default: {
			/* any other key the engine's array write coerces */
			zval key;
			ZVAL_COPY(&key, name);
			zend_hash_index_update(names.table(), (zend_ulong) zval_get_long(&key), &marked);
			zval_ptr_dtor(&key);
		}
	}
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\LoopWrittenVariableNames; UNDEF = pending exception. */
class LoopWrittenVariableNames
{
public:
	/* Mirrors collect() ($passFlow IS_NULL for null). */
	static zv::Val collect(zval *loop, zval *passFlow)
	{
		zv::Val names = getSyntacticNames(Z_OBJ_P(loop));
		if (UNEXPECTED(names.isUndef())) return zv::Val();
		if (Z_TYPE_P(names.raw()) == IS_NULL) return names;
		SEPARATE_ARRAY(names.raw());
		zv::Arr namesArr = zv::Arr::adoptVal(std::move(names));

		std::vector<zend_object *> flows;
		pushFlow(flows, passFlow);
		while (!flows.empty()) {
			zend_object *flow = flows.back();
			flows.pop_back();
			if (flow == NULL) continue;
			if (flow->ce == pt_ce_variable_access_flow) {
				zval *kind = pt_typed_slot(flow, ptdecl::VariableFlow::slot::kind, pt_ce_variable_flow, "kind");
				if (UNEXPECTED(kind == NULL)) return zv::Val();
				if (Z_TYPE_P(kind) == IS_STRING && (zend_string_equals_literal(Z_STR_P(kind), "write") || zend_string_equals_literal(Z_STR_P(kind), "define") || zend_string_equals_literal(Z_STR_P(kind), "discard") || zend_string_equals_literal(Z_STR_P(kind), "escape"))) {
					zval *name = pt_typed_slot(flow, ptdecl::VariableAccessFlow::slot::name, flow->ce, "name");
					if (UNEXPECTED(name == NULL)) return zv::Val();
					addName(namesArr, name);
				}
				continue;
			}
			if (flow->ce == pt_ce_variable_sequence_flow) {
				zval *children = flowArray(flow, ptdecl::VariableSequenceFlow::slot::children, "children");
				if (UNEXPECTED(children == NULL)) return zv::Val();
				if (UNEXPECTED(!pushFlows(flows, children))) return zv::Val();
				continue;
			}
			if (flow->ce != pt_ce_variable_control_flow) continue;

			zval *children = flowArray(flow, ptdecl::VariableControlFlow::slot::children, "children");
			if (UNEXPECTED(children == NULL)) return zv::Val();
			if (UNEXPECTED(!pushFlows(flows, children))) return zv::Val();
			zval *catches = flowArray(flow, ptdecl::VariableControlFlow::slot::catches, "catches");
			if (UNEXPECTED(catches == NULL)) return zv::Val();
			for (auto entry : zv::ArrRef(catches)) {
				/* foreach ($flow->catches as [, $catchFlow]) */
				zval *pair = entry.value().deref().raw();
				zval *catchFlow = listElement(pair, 1);
				pushFlow(flows, catchFlow);
			}
			zval *cases = flowArray(flow, ptdecl::VariableControlFlow::slot::cases, "cases");
			if (UNEXPECTED(cases == NULL)) return zv::Val();
			for (auto entry : zv::ArrRef(cases)) {
				zval *pair = entry.value().deref().raw();
				zval *caseCondition = listElement(pair, 0);
				zval *caseBody = listElement(pair, 1);
				pushFlow(flows, caseCondition);
				pushFlow(flows, caseBody);
			}
			for (uint32_t slot : {ptdecl::VariableControlFlow::slot::bindings, ptdecl::VariableControlFlow::slot::ownWrites}) {
				zval *writes = flowArray(flow, slot, slot == ptdecl::VariableControlFlow::slot::bindings ? "bindings" : "ownWrites");
				if (UNEXPECTED(writes == NULL)) return zv::Val();
				for (auto entry : zv::ArrRef(writes)) {
					zval *write = entry.value().deref().raw();
					zv::Val name = variableName(write);
					if (UNEXPECTED(name.isUndef())) return zv::Val();
					addName(namesArr, name.raw());
				}
			}
		}
		return zv::Val(std::move(namesArr));
	}

	/* Mirrors getSyntacticNames(). */
	static zv::Val getSyntacticNames(zend_object *loop)
	{
		zv::Val cached = pt_engine_node_get_attribute(loop, pt_lwvn_attribute, strlen(pt_lwvn_attribute));
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		if (Z_TYPE_P(cached.raw()) == IS_ARRAY) return cached;
		if (Z_TYPE_P(cached.raw()) == IS_FALSE) return zv::Val::null();

		zv::Val names = findSyntacticNames(loop);
		if (UNEXPECTED(names.isUndef())) return zv::Val();
		zval stored;
		if (Z_TYPE_P(names.raw()) == IS_NULL) {
			ZVAL_FALSE(&stored);
		} else {
			ZVAL_COPY_VALUE(&stored, names.raw());
		}
		if (UNEXPECTED(!pt_engine_node_set_attribute(loop, pt_lwvn_attribute, strlen(pt_lwvn_attribute), &stored))) return zv::Val();
		return names;
	}

	/* Mirrors findSyntacticNames(). */
	static zv::Val findSyntacticNames(zend_object *loop)
	{
		zv::Arr names = zv::Arr::create(0);
		/* $nodes: the pending nodes, borrowed from the tree the loop holds */
		std::vector<zend_object *> nodes;
		nodes.push_back(loop);
		while (!nodes.empty()) {
			zend_object *node = nodes.back();
			nodes.pop_back();
			zval nodeZv;
			ZVAL_OBJ(&nodeZv, node);
			if (isOf(&nodeZv, PT_CLASS_FUNCTION_STMT) || isOf(&nodeZv, PT_CLASS_CLASS_LIKE_STMT)) continue;

			bool unknown;
			if (UNEXPECTED(!writesUnknownNames(node, unknown))) return zv::Val();
			if (unknown) return zv::Val::null();

			zv::Val hold1, hold2, hold3;
			zval &rv1 = *hold1.raw();
			zval &rv2 = *hold2.raw();
			zval &rv3 = *hold3.raw();
			zval *targets[3] = {NULL, NULL, NULL};
			uint32_t targetCount = 0;
			zval *targetList = NULL; /* a list of targets (Static_ / Global_ / Unset_ / Closure) */
			int listKind = 0;       /* 1: StaticVar->var, 2: the element, 3: ClosureUse (byRef) */
			if (isOf(&nodeZv, PT_CLASS_ASSIGN_EXPR)) {
				targets[targetCount++] = prop(pt_lwvn_assign_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_ASSIGN_OP_EXPR)) {
				targets[targetCount++] = prop(pt_lwvn_assign_op_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_ASSIGN_REF_EXPR)) {
				targets[targetCount++] = prop(pt_lwvn_assign_ref_var, node, rv1);
				if (targets[0] != NULL) targets[targetCount++] = prop(pt_lwvn_assign_ref_expr, node, rv2);
			} else if (isOf(&nodeZv, PT_CLASS_PRE_INC)) {
				targets[targetCount++] = prop(pt_lwvn_pre_inc_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_PRE_DEC)) {
				targets[targetCount++] = prop(pt_lwvn_pre_dec_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_POST_INC)) {
				targets[targetCount++] = prop(pt_lwvn_post_inc_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_POST_DEC)) {
				targets[targetCount++] = prop(pt_lwvn_post_dec_var, node, rv1);
			} else if (isOf(&nodeZv, PT_CLASS_FOREACH_STMT)) {
				zval byRefRv;
				ZVAL_UNDEF(&byRefRv);
				zval *byRef = prop(pt_lwvn_foreach_by_ref, node, byRefRv);
				zv::Val byRefHold = zv::Val::adopt(byRefRv);
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				if (zend_is_true(byRef)) {
					targets[targetCount] = prop(pt_lwvn_foreach_expr, node, rv1);
					if (UNEXPECTED(targets[targetCount] == NULL)) return zv::Val();
					targetCount++;
				}
				zval *keyVar = prop(pt_lwvn_foreach_key_var, node, rv2);
				if (UNEXPECTED(keyVar == NULL)) return zv::Val();
				if (Z_TYPE_P(keyVar) != IS_NULL) targets[targetCount++] = keyVar;
				targets[targetCount] = prop(pt_lwvn_foreach_value_var, node, rv3);
				targetCount++;
			} else if (isOf(&nodeZv, PT_CLASS_CATCH_STMT)) {
				zval *var = prop(pt_lwvn_catch_var, node, rv1);
				if (UNEXPECTED(var == NULL)) return zv::Val();
				if (Z_TYPE_P(var) != IS_NULL) targets[targetCount++] = var;
			} else if (isOf(&nodeZv, PT_CLASS_STATIC_STMT)) {
				targetList = prop(pt_lwvn_static_vars, node, rv1);
				listKind = 1;
			} else if (isOf(&nodeZv, PT_CLASS_GLOBAL_STMT)) {
				targetList = prop(pt_lwvn_global_vars, node, rv1);
				listKind = 2;
			} else if (isOf(&nodeZv, PT_CLASS_UNSET_STMT)) {
				targetList = prop(pt_lwvn_unset_vars, node, rv1);
				listKind = 2;
			} else if (isOf(&nodeZv, PT_CLASS_CLOSURE_EXPR)) {
				targetList = prop(pt_lwvn_closure_uses, node, rv1);
				listKind = 3;
			}
			for (uint32_t i = 0; i < targetCount; i++) {
				if (UNEXPECTED(targets[i] == NULL)) return zv::Val();
			}
			if (UNEXPECTED(listKind != 0 && targetList == NULL)) return zv::Val();

			/* foreach ($targets as $target) — the collected targets first,
			 * in the twin's order */
			zv::Arr listTargets = zv::Arr::create(0);
			if (listKind != 0) {
				if (UNEXPECTED(Z_TYPE_P(targetList) != IS_ARRAY)) {
					zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(targetList));
					return zv::Val();
				}
				for (auto entry : zv::ArrRef(targetList)) {
					zval *item = entry.value().deref().raw();
					zval itemRv;
					ZVAL_UNDEF(&itemRv);
					zval *target = item;
					if (listKind == 1) {
						if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) return nonObjectRead("var", item);
						target = siteProp(pt_lwvn_static_var_var_site, Z_OBJ_P(item), PT_LC("var"), itemRv);
					} else if (listKind == 3) {
						if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) return nonObjectRead("byRef", item);
						zval byRefRv;
						ZVAL_UNDEF(&byRefRv);
						zval *byRef = siteProp(pt_lwvn_closure_use_by_ref_site, Z_OBJ_P(item), PT_LC("byRef"), byRefRv);
						zv::Val byRefHold = zv::Val::adopt(byRefRv);
						if (UNEXPECTED(byRef == NULL)) return zv::Val();
						if (!zend_is_true(byRef)) continue;
						target = siteProp(pt_lwvn_closure_use_var_site, Z_OBJ_P(item), PT_LC("var"), itemRv);
					}
					zv::Val itemHold = zv::Val::adopt(itemRv);
					if (UNEXPECTED(target == NULL)) return zv::Val();
					listTargets.push(zv::Ref(target));
				}
			}

			zv::Arr targetsInOrder = zv::Arr::create(targetCount);
			for (uint32_t i = 0; i < targetCount; i++) {
				targetsInOrder.push(zv::Ref(targets[i]));
			}
			for (auto entry : zv::ArrRef(listTargets.raw())) {
				targetsInOrder.push(entry.value());
			}
			for (auto entry : zv::ArrRef(targetsInOrder.raw())) {
				zval *target = entry.value().raw();
				if (UNEXPECTED(!requireExpr(target))) return zv::Val();
				zv::Arr targetNames = zv::Arr::create(0);
				bool known;
				if (UNEXPECTED(!getTargetNames(target, targetNames, known))) return zv::Val();
				if (!known) return zv::Val::null();
				for (auto nameEntry : zv::ArrRef(targetNames.raw())) {
					addName(names, nameEntry.value().raw());
				}
			}

			if (UNEXPECTED(!pushSubNodes(node, nodes))) return zv::Val();
		}
		return zv::Val(std::move(names));
	}

	/* Mirrors getTargetNames() into $out (appended); known = false for the
	 * twin's null; false = pending exception */
	[[nodiscard]] static bool getTargetNames(zval *target, zv::Arr &out, bool &known)
	{
		known = true;
		zv::Val current = zv::Val::copyOf(zv::Ref(target));
		for (;;) {
			NodeProp *var = NULL;
			if (isOf(current.raw(), PT_CLASS_ARRAY_DIM_FETCH)) {
				var = &pt_lwvn_array_dim_fetch_var;
			} else if (isOf(current.raw(), PT_CLASS_PROPERTY_FETCH)) {
				var = &pt_lwvn_property_fetch_var;
			} else if (isOf(current.raw(), PT_CLASS_NULLSAFE_PROPERTY_FETCH)) {
				var = &pt_lwvn_nullsafe_property_fetch_var;
			}
			if (var == NULL) break;
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *next = prop(*var, Z_OBJ_P(current.raw()), rv);
			zv::Val rvHold = zv::Val::adopt(rv);
			if (UNEXPECTED(next == NULL)) return false;
			current = zv::Val::copyOf(zv::Ref(next));
		}
		if (isOf(current.raw(), PT_CLASS_VARIABLE)) {
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *name = prop(pt_lwvn_variable_name, Z_OBJ_P(current.raw()), rv);
			zv::Val rvHold = zv::Val::adopt(rv);
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_STRING) {
				known = false;
				return true;
			}
			out.push(zv::Ref(name));
			return true;
		}
		NodeProp *items = isOf(current.raw(), PT_CLASS_LIST_EXPR) ? &pt_lwvn_list_items : (isOf(current.raw(), PT_CLASS_ARRAY_EXPR) ? &pt_lwvn_array_items : NULL);
		if (items == NULL) return true;

		zval rv;
		ZVAL_UNDEF(&rv);
		zval *itemList = prop(*items, Z_OBJ_P(current.raw()), rv);
		zv::Val rvHold = zv::Val::adopt(rv);
		if (UNEXPECTED(itemList == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(itemList) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(itemList));
			return false;
		}
		zv::Val itemListHold = zv::Val::copyOf(zv::Ref(itemList));
		for (auto entry : zv::ArrRef(itemListHold.raw())) {
			zval *item = entry.value().deref().raw();
			if (Z_TYPE_P(item) == IS_NULL) continue;
			if (UNEXPECTED(Z_TYPE_P(item) != IS_OBJECT)) {
				(void) nonObjectRead("value", item);
				return false;
			}
			zval valueRv;
			ZVAL_UNDEF(&valueRv);
			zval *value = siteProp(pt_lwvn_item_value_site, Z_OBJ_P(item), PT_LC("value"), valueRv);
			zv::Val valueHold = zv::Val::adopt(valueRv);
			if (UNEXPECTED(value == NULL)) return false;
			if (UNEXPECTED(!requireExpr(value))) return false;
			zv::Arr itemNames = zv::Arr::create(0);
			bool itemKnown;
			if (UNEXPECTED(!getTargetNames(value, itemNames, itemKnown))) return false;
			if (!itemKnown) {
				known = false;
				return true;
			}
			for (auto nameEntry : zv::ArrRef(itemNames.raw())) {
				out.push(nameEntry.value());
			}
		}
		return true;
	}

private:
	/* ($node instanceof Variable && !is_string($node->name)) || Include_ ||
	 * Eval_ || (FuncCall && $node->name instanceof Name &&
	 * in_array($node->name->toLowerString(), ['extract', 'parse_str'], true));
	 * false = pending exception */
	[[nodiscard]] static bool writesUnknownNames(zend_object *node, bool &out)
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		out = false;
		if (isOf(&nodeZv, PT_CLASS_VARIABLE)) {
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *name = prop(pt_lwvn_variable_name, node, rv);
			zv::Val hold = zv::Val::adopt(rv);
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) != IS_STRING) {
				out = true;
				return true;
			}
		}
		if (isOf(&nodeZv, PT_CLASS_INCLUDE_EXPR) || isOf(&nodeZv, PT_CLASS_EVAL_EXPR)) {
			out = true;
			return true;
		}
		if (isOf(&nodeZv, PT_CLASS_FUNC_CALL)) {
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *name = prop(pt_lwvn_func_call_name, node, rv);
			zv::Val hold = zv::Val::adopt(rv);
			if (UNEXPECTED(name == NULL)) return false;
			if (isOf(name, PT_CLASS_NAME)) {
				zval nameRv;
				ZVAL_UNDEF(&nameRv);
				zval *nameString = prop(pt_lwvn_name_name, Z_OBJ_P(name), nameRv);
				zv::Val nameHold = zv::Val::adopt(nameRv);
				if (UNEXPECTED(nameString == NULL)) return false;
				if (Z_TYPE_P(nameString) == IS_STRING) {
					out = phpstanturbo::visitors::lowerEquals(Z_STR_P(nameString), "extract") || phpstanturbo::visitors::lowerEquals(Z_STR_P(nameString), "parse_str");
					return true;
				}
				zv::Val lower = pt_type_call(Z_OBJ_P(name), PT_LC("tolowerstring"), 0, NULL);
				if (UNEXPECTED(lower.isUndef())) return false;
				out = Z_TYPE_P(lower.raw()) == IS_STRING && (zend_string_equals_literal(Z_STR_P(lower.raw()), "extract") || zend_string_equals_literal(Z_STR_P(lower.raw()), "parse_str"));
			}
		}
		return true;
	}

	/* foreach ($node->getSubNodeNames() as $subNodeName): the subnode nodes and
	 * the nodes inside subnode arrays, pushed in order; false = pending
	 * exception */
	[[nodiscard]] static bool pushSubNodes(zend_object *node, std::vector<zend_object *> &nodes)
	{
		zend_class_entry *nodeCe = pt_class(PT_CLASS_NODE);
		if (UNEXPECTED(nodeCe == NULL)) return false;
		pt_node_class_info *info = pt_node_class_info_for_object(node);
		if (UNEXPECTED(EG(exception))) return false;
		if (EXPECTED(info != NULL && PT_HAS_SUBNODES(info))) {
			bool complete = true;
			for (uint32_t i = 0; i < info->subnode_count; i++) {
				zval *subNode = OBJ_PROP(node, info->subnode_offsets[i]);
				ZVAL_DEREF(subNode);
				if (UNEXPECTED(Z_TYPE_P(subNode) == IS_UNDEF)) {
					complete = false;
					break;
				}
			}
			if (EXPECTED(complete)) {
				for (uint32_t i = 0; i < info->subnode_count; i++) {
					zval *subNode = OBJ_PROP(node, info->subnode_offsets[i]);
					ZVAL_DEREF(subNode);
					pushSubNode(nodes, subNode, nodeCe);
				}
				return true;
			}
		}
		/* the names by the method, the values by the engine's reads */
		zv::Val subNodeNames = pt_call_method_cached(pt_lwvn_get_sub_node_names_site, node, PT_LC("getsubnodenames"), 0, NULL);
		if (UNEXPECTED(subNodeNames.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(subNodeNames.raw()) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(subNodeNames.raw()));
			return false;
		}
		for (auto entry : zv::ArrRef(subNodeNames.raw())) {
			zval *name = entry.value().deref().raw();
			zend_string *nameString = zval_try_get_string(name);
			if (UNEXPECTED(nameString == NULL)) return false;
			zval rv;
			ZVAL_UNDEF(&rv);
			zval *subNode = zend_read_property_ex(node->ce, node, nameString, 0, &rv);
			zend_string_release(nameString);
			if (UNEXPECTED(EG(exception))) {
				zval_ptr_dtor(&rv);
				return false;
			}
			ZVAL_DEREF(subNode);
			pushSubNode(nodes, subNode, nodeCe);
			zval_ptr_dtor(&rv);
		}
		return true;
	}

	static void pushSubNode(std::vector<zend_object *> &nodes, zval *subNode, zend_class_entry *nodeCe)
	{
		if (Z_TYPE_P(subNode) == IS_OBJECT) {
			if (instanceof_function(Z_OBJCE_P(subNode), nodeCe)) nodes.push_back(Z_OBJ_P(subNode));
			return;
		}
		if (Z_TYPE_P(subNode) != IS_ARRAY) return;
		for (auto entry : zv::ArrRef(subNode)) {
			zval *item = entry.value().deref().raw();
			if (Z_TYPE_P(item) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(item), nodeCe)) continue;
			nodes.push_back(Z_OBJ_P(item));
		}
	}

	/* $flows[] = $flow of every element (NULL for a non-flow) */
	[[nodiscard]] static bool pushFlows(std::vector<zend_object *> &flows, zval *children)
	{
		if (UNEXPECTED(Z_TYPE_P(children) != IS_ARRAY)) {
			zend_type_error("foreach() argument must be of type array|object, %s given", zend_zval_value_name(children));
			return false;
		}
		for (auto entry : zv::ArrRef(children)) {
			pushFlow(flows, entry.value().deref().raw());
		}
		return true;
	}

	static void pushFlow(std::vector<zend_object *> &flows, zval *flow)
	{
		flows.push_back(flow != NULL && Z_TYPE_P(flow) == IS_OBJECT ? Z_OBJ_P(flow) : NULL);
	}

	/* $pair[$index] of a destructured list element (NULL when absent) */
	static zval *listElement(zval *pair, zend_ulong index)
	{
		if (Z_TYPE_P(pair) != IS_ARRAY) return NULL;
		zval *element = zend_hash_index_find(Z_ARRVAL_P(pair), index);
		if (element != NULL) {
			ZVAL_DEREF(element);
		}
		return element;
	}

	/* $write->getVariableName() */
	static zv::Val variableName(zval *write)
	{
		if (UNEXPECTED(Z_TYPE_P(write) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getVariableName() on %s", zend_zval_value_name(write));
			return zv::Val();
		}
		bool error;
		const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
		if (EXPECTED(slots != NULL)) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(slots->variableName));
		if (UNEXPECTED(error)) return zv::Val();
		return pt_call_method_cached(pt_lwvn_get_variable_name_site, Z_OBJ_P(write), PT_LC("getvariablename"), 0, NULL);
	}

	[[nodiscard]] static zv::Val nonObjectRead(const char *property, zval *value)
	{
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", property, zend_zval_value_name(value));
		return zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::LoopWrittenVariableNames;

/* {{{ direct entries (support.h) */

zv::Val pt_loop_written_variable_names_collect(zval *loop, zval *passFlow)
{
	zval null;
	ZVAL_NULL(&null);
	return LoopWrittenVariableNames::collect(loop, passFlow != NULL ? passFlow : &null);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_loop_written_variable_names()
{
	reg::Class cls("PHPStan\\Analyser\\LoopWrittenVariableNames");
	ptdecl::LoopWrittenVariableNames::declareClass(cls);
	cls.privateClassConstantString("SYNTACTIC_NAMES_ATTRIBUTE", pt_lwvn_attribute);
	ptdecl::LoopWrittenVariableNames::declareProperties(cls);

	cls.method(sigs::collect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *loop, *passFlow;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OF_CLASS(loop, pt_class(PT_CLASS_NODE))
			Z_PARAM_OBJECT_OF_CLASS_OR_NULL(passFlow, pt_ce_variable_flow)
		ZEND_PARSE_PARAMETERS_END();
		zval null;
		ZVAL_NULL(&null);
		PT_RETURN_VAL(LoopWrittenVariableNames::collect(loop, passFlow != NULL ? passFlow : &null));
	});

	cls.shadow(&pt_ce_loop_written_variable_names);
}

/* }}} */
