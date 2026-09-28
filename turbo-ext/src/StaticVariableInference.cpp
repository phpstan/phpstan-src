/*
 * PHPStanTurbo\StaticVariableInference — native implementation of
 * PHPStan\Analyser\Generics\StaticVariableInference.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getSites() scans a function-like body once
 * (cached in a node attribute) for the `static` variables whose type is
 * inferred and getRuns() for the runs of `static` statements among them;
 * isInferred() / getResolvedTypes() / getResolvedConditionalExpressions()
 * answer StaticVariableHandler from the current template argument frame, and
 * canRunUserCode() StatementsHandler. The direct entries
 * pt_static_variable_inference_*() serve the native StaticVariableHandler and
 * StatementsHandler.
 */

#include "support.h"
#include "generated/StaticVariableInference.h"

namespace slots = ptdecl::StaticVariableInference::slot;
namespace sigs = ptdecl::StaticVariableInference::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "ClosureSupport.h"

zend_class_entry *pt_ce_static_variable_inference = nullptr;

namespace {

/* the twin's SITES_ATTRIBUTE, a permanent interned string (module startup) */
zend_string *pt_svi_sites_attribute = nullptr;
/* the twin's LAST_FUNCTION_LIKE_ATTRIBUTE, a permanent interned string (module startup) */
zend_string *pt_svi_last_function_like_attribute = nullptr;

pt_method_site pt_svi_get_sub_node_names_site;
pt_method_site pt_svi_to_lower_string_site;
pt_property_site pt_svi_variable_name_site;
pt_property_site pt_svi_func_call_name_site;
pt_property_site pt_svi_assign_var_site;
pt_property_site pt_svi_assign_expr_site;
pt_property_site pt_svi_global_vars_site;
pt_property_site pt_svi_static_vars_site;
pt_property_site pt_svi_static_var_var_site;
pt_property_site pt_svi_static_var_default_site;
pt_property_site pt_svi_fetch_var_site;
pt_property_site pt_svi_items_site;
pt_property_site pt_svi_item_value_site;

/* $value instanceof <class-map class>; false = pending exception */
[[nodiscard]] bool isA(zval *value, int classIdx, bool &out)
{
	int is = ptclosure::instanceOf(value, classIdx);
	if (UNEXPECTED(is < 0)) return false;
	out = is == 1;
	return true;
}

inline zval *read(pt_property_site &site, zval *node, const char *name, size_t len)
{
	return ptclosure::prop(site, node, name, len);
}

/* the twin's `foreach ($node->getSubNodeNames() as $subNodeName) { ... }`
 * pushing every Node found (directly or in an array) onto the stack; false =
 * pending exception */
[[nodiscard]] bool pushSubNodes(zval *node, zv::Arr &stack)
{
	zv::Val names = pt_call_method_cached(pt_svi_get_sub_node_names_site, Z_OBJ_P(node), PT_LC("getsubnodenames"), 0, NULL);
	if (UNEXPECTED(names.isUndef())) return false;
	if (UNEXPECTED(!names.ref().isArray())) {
		zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(names.raw()));
		return EG(exception) == NULL;
	}
	zend_class_entry *nodeCe = pt_class(PT_CLASS_NODE);
	if (UNEXPECTED(nodeCe == NULL)) return false;
	zend_object *nodeObject = Z_OBJ_P(node);
	for (auto entry : zv::ArrRef(names.raw())) {
		zend_string *subNodeName = zval_get_string(entry.value().deref().raw());
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *subNode = zend_read_property_ex(nodeObject->ce, nodeObject, subNodeName, 0, &rv);
		zend_string_release(subNodeName);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&rv);
			return false;
		}
		zv::Val held = zv::Val::copyOf(zv::Ref(subNode).deref());
		zval_ptr_dtor(&rv);
		if (held.ref().isObject() && instanceof_function(Z_OBJCE_P(held.raw()), nodeCe)) {
			stack.push(std::move(held));
		} else if (held.ref().isArray()) {
			for (auto item : zv::ArrRef(held.raw())) {
				zval *value = item.value().deref().raw();
				if (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), nodeCe)) continue;
				stack.push(zv::Val::copyOf(zv::Ref(value)));
			}
		}
	}
	return true;
}

/* array_pop($stack), or UNDEF for an empty stack */
zv::Val popNode(zv::Arr &stack)
{
	HashTable *table = stack.table();
	uint32_t count = zend_hash_num_elements(table);
	if (count == 0) return zv::Val();
	stack.separate();
	table = stack.table();
	zval *last = zend_hash_index_find(table, count - 1);
	zv::Val value = zv::Val::copyOf(zv::Ref(last));
	zend_hash_index_del(table, count - 1);
	table->nNextFreeElement = count - 1;
	return value;
}

/* preg_match('~@(?:phpstan-|psalm-)?var\s~', $text) === 1 */
bool declaresVar(zend_string *text)
{
	const char *s = ZSTR_VAL(text);
	size_t n = ZSTR_LEN(text);
	auto startsWith = [&](size_t at, const char *prefix, size_t len) {
		return at + len <= n && memcmp(s + at, prefix, len) == 0;
	};
	for (size_t i = 0; i < n; i++) {
		if (s[i] != '@') continue;
		// the regex backtracks from a prefix to the bare tag
		size_t candidates[] = {i + 1, i + 9, i + 7};
		bool variants[] = {true, startsWith(i + 1, "phpstan-", 8), startsWith(i + 1, "psalm-", 6)};
		for (int k = 0; k < 3; k++) {
			size_t at = candidates[k];
			if (!variants[k] || !startsWith(at, "var", 3) || at + 3 >= n) continue;
			char c = s[at + 3];
			if (c == ' ' || c == '\t' || c == '\n' || c == '\r' || c == '\v' || c == '\f') return true;
		}
	}
	return false;
}

/* Mirrors the private hasVarTag(); false = pending exception */
[[nodiscard]] bool hasVarTag(zval *stmt, bool &out)
{
	zv::Val text = pt_node_doc_comment_text(stmt);
	if (UNEXPECTED(text.isUndef())) return false;
	out = text.ref().isString() && declaresVar(Z_STR_P(text.raw()));
	return true;
}

/* Mirrors the private collectRootNames(); false = pending exception */
[[nodiscard]] bool collectRootNames(zval *exprArg, zv::Arr &names)
{
	zv::Val expr = zv::Val::copyOf(zv::Ref(exprArg));
	for (;;) {
		bool fetch;
		if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_ARRAY_DIM_FETCH, fetch))) return false;
		if (!fetch && UNEXPECTED(!isA(expr.raw(), PT_CLASS_PROPERTY_FETCH, fetch))) return false;
		if (!fetch && UNEXPECTED(!isA(expr.raw(), PT_CLASS_NULLSAFE_PROPERTY_FETCH, fetch))) return false;
		bool staticFetch = false;
		if (!fetch && UNEXPECTED(!isA(expr.raw(), PT_CLASS_STATIC_PROPERTY_FETCH, staticFetch))) return false;
		if (staticFetch) return true;
		if (!fetch) break;
		zval *var = read(pt_svi_fetch_var_site, expr.raw(), PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		expr = zv::Val::copyOf(zv::Ref(var));
	}
	bool isVariable;
	if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_VARIABLE, isVariable))) return false;
	if (isVariable) {
		zval *name = read(pt_svi_variable_name_site, expr.raw(), PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) == IS_STRING) {
			names.set(Z_STR_P(name), zv::Val::boolean(true));
		}
		return true;
	}
	bool isList, isArray = false;
	if (UNEXPECTED(!isA(expr.raw(), PT_CLASS_LIST_EXPR, isList))) return false;
	if (!isList && UNEXPECTED(!isA(expr.raw(), PT_CLASS_ARRAY_EXPR, isArray))) return false;
	if (!isList && !isArray) return true;

	zval *items = read(pt_svi_items_site, expr.raw(), PT_LC("items"));
	if (UNEXPECTED(items == NULL)) return false;
	if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) return true;
	zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
	for (auto entry : zv::ArrRef(itemsHold.raw())) {
		zval *item = entry.value().deref().raw();
		if (Z_TYPE_P(item) == IS_NULL) continue;
		zval *value = read(pt_svi_item_value_site, item, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
		bool ok = true;
		pt_engine_with_stack([&]() { ok = collectRootNames(valueHold.raw(), names); });
		if (UNEXPECTED(!ok)) return false;
	}
	return true;
}

/* one node of scanSites()'s walk: false = pending exception; $opaque set
 * when the body's variables can change behind the analysis' back */
[[nodiscard]] bool scanNode(zval *n, zend_long index, zv::Arr &candidates, zv::Arr &excludedNames, bool &skip, bool &opaque)
{
	skip = false;
	opaque = false;
	bool is;
	if (UNEXPECTED(!isA(n, PT_CLASS_FUNCTION_LIKE, is))) return false;
	if (!is && UNEXPECTED(!isA(n, PT_CLASS_CLASS_LIKE_STMT, is))) return false;
	if (is) {
		skip = true;
		return true;
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_YIELD, is))) return false;
	if (!is && UNEXPECTED(!isA(n, PT_CLASS_YIELD_FROM, is))) return false;
	if (!is && UNEXPECTED(!isA(n, PT_CLASS_INCLUDE_EXPR, is))) return false;
	if (!is && UNEXPECTED(!isA(n, PT_CLASS_EVAL_EXPR, is))) return false;
	if (is) {
		opaque = true;
		return true;
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_VARIABLE, is))) return false;
	if (is) {
		zval *name = read(pt_svi_variable_name_site, n, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) != IS_STRING) {
			opaque = true;
			return true;
		}
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_FUNC_CALL, is))) return false;
	if (is) {
		zval *name = read(pt_svi_func_call_name_site, n, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		bool isName;
		if (UNEXPECTED(!isA(name, PT_CLASS_NAME, isName))) return false;
		if (isName) {
			zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
			zv::Val lower = pt_call_method_cached(pt_svi_to_lower_string_site, Z_OBJ_P(nameHold.raw()), PT_LC("tolowerstring"), 0, NULL);
			if (UNEXPECTED(lower.isUndef())) return false;
			if (lower.ref().stringEquals("extract") || lower.ref().stringEquals("parse_str")) {
				opaque = true;
				return true;
			}
		}
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_ASSIGN_REF_EXPR, is))) return false;
	if (is) {
		zval *var = read(pt_svi_assign_var_site, n, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
		if (UNEXPECTED(!collectRootNames(varHold.raw(), excludedNames))) return false;
		zval *expr = read(pt_svi_assign_expr_site, n, PT_LC("expr"));
		if (UNEXPECTED(expr == NULL)) return false;
		zv::Val exprHold = zv::Val::copyOf(zv::Ref(expr));
		if (UNEXPECTED(!collectRootNames(exprHold.raw(), excludedNames))) return false;
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_GLOBAL_STMT, is))) return false;
	if (is) {
		zval *vars = read(pt_svi_global_vars_site, n, PT_LC("vars"));
		if (UNEXPECTED(vars == NULL)) return false;
		if (Z_TYPE_P(vars) == IS_ARRAY) {
			zv::Val varsHold = zv::Val::copyOf(zv::Ref(vars));
			for (auto entry : zv::ArrRef(varsHold.raw())) {
				if (UNEXPECTED(!collectRootNames(entry.value().deref().raw(), excludedNames))) return false;
			}
		}
	}
	if (UNEXPECTED(!isA(n, PT_CLASS_STATIC_STMT, is))) return false;
	if (is) {
		bool varTag;
		if (UNEXPECTED(!hasVarTag(n, varTag))) return false;
		if (!varTag) {
			zval *vars = read(pt_svi_static_vars_site, n, PT_LC("vars"));
			if (UNEXPECTED(vars == NULL)) return false;
			if (Z_TYPE_P(vars) == IS_ARRAY) {
				zv::Val varsHold = zv::Val::copyOf(zv::Ref(vars));
				for (auto entry : zv::ArrRef(varsHold.raw())) {
					zval *var = read(pt_svi_static_var_var_site, entry.value().deref().raw(), PT_LC("var"));
					if (UNEXPECTED(var == NULL)) return false;
					zv::Arr candidate = zv::Arr::create(2);
					candidate.push(zv::Ref(var));
					candidate.push(zv::Val::integer(index));
					candidates.push(std::move(candidate));
				}
			}
		}
	}
	return true;
}

/* Mirrors the private static scanSites(); UNDEF = pending exception */
zv::Val scanSites(zval *stmts)
{
	zv::Arr candidates = zv::Arr::empty();
	zv::Arr excludedNames = zv::Arr::create(0);
	if (Z_TYPE_P(stmts) == IS_ARRAY) {
		for (auto stmtEntry : zv::ArrRef(stmts)) {
			zend_long index = stmtEntry.stringKeyOrNull() == NULL ? (zend_long) stmtEntry.indexKey() : 0;
			zv::Arr stack = zv::Arr::create(0);
			stack.push(zv::Val::copyOf(stmtEntry.value().deref()));
			for (;;) {
				zv::Val node = popNode(stack);
				if (node.isUndef()) break;
				bool skip, opaque;
				bool ok = true;
				pt_engine_with_stack([&]() { ok = scanNode(node.raw(), index, candidates, excludedNames, skip, opaque); });
				if (UNEXPECTED(!ok)) return zv::Val();
				if (opaque) return zv::Val(zv::Arr::empty());
				if (skip) continue;
				if (UNEXPECTED(!pushSubNodes(node.raw(), stack))) return zv::Val();
			}
		}
	}

	zv::Arr sites = zv::Arr::empty();
	for (auto entry : zv::ArrRef(candidates.raw())) {
		zval *candidate = entry.value().raw();
		zval *var = zend_hash_index_find(Z_ARRVAL_P(candidate), 0);
		zval *index = zend_hash_index_find(Z_ARRVAL_P(candidate), 1);
		zval *name = read(pt_svi_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (Z_TYPE_P(name) != IS_STRING || zend_symtable_exists(excludedNames.table(), Z_STR_P(name))) continue;
		zv::Arr site = zv::Arr::create(3);
		site.push(zv::Ref(var));
		site.push(zv::Ref(index));
		site.push(zv::Ref(name));
		sites.push(std::move(site));
	}
	return zv::Val(std::move(sites));
}

/* Mirrors the private static findLastFunctionLikeStatement(); false =
 * pending exception */
[[nodiscard]] bool findLastFunctionLikeStatement(zval *stmts, zend_long &last)
{
	last = -1;
	if (Z_TYPE_P(stmts) != IS_ARRAY) return true;
	for (auto stmtEntry : zv::ArrRef(stmts)) {
		zend_long index = stmtEntry.stringKeyOrNull() == NULL ? (zend_long) stmtEntry.indexKey() : 0;
		zv::Arr stack = zv::Arr::create(0);
		stack.push(zv::Val::copyOf(stmtEntry.value().deref()));
		for (;;) {
			zv::Val node = popNode(stack);
			if (node.isUndef()) break;
			bool is;
			if (UNEXPECTED(!isA(node.raw(), PT_CLASS_FUNCTION_LIKE, is))) return false;
			if (!is && UNEXPECTED(!isA(node.raw(), PT_CLASS_CLASS_LIKE_STMT, is))) return false;
			if (is) {
				last = index;
				break;
			}
			if (UNEXPECTED(!pushSubNodes(node.raw(), stack))) return false;
		}
	}
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\StaticVariableInference; UNDEF / false =
 * pending exception. */
class StaticVariableInference
{
public:
	explicit StaticVariableInference(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *reflectionProvider, bool enabled)
	{
		zv::ObjRef(self).propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::reflectionProvider)) = 0;
		zv::ObjRef(self).propAtWrite(slots::enabled, zv::Val::boolean(enabled));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::enabled)) = 0;
	}

	/* Mirrors getSites() */
	zv::Val getSites(zval *functionLike, zval *stmts) const
	{
		if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::enabled)) != IS_TRUE) return zv::Val(zv::Arr::empty());

		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_svi_sites_attribute), ZSTR_LEN(pt_svi_sites_attribute));
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		if (!cached.isNull()) return cached;

		zv::Val sites = scanSites(stmts);
		if (UNEXPECTED(sites.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_svi_sites_attribute), ZSTR_LEN(pt_svi_sites_attribute), sites.raw()))) return zv::Val();
		return sites;
	}

	/* Mirrors hasFunctionLikeFrom(); false = pending exception */
	[[nodiscard]] bool hasFunctionLikeFrom(zval *functionLike, zval *stmts, zend_long index, bool &out) const
	{
		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_svi_last_function_like_attribute), ZSTR_LEN(pt_svi_last_function_like_attribute));
		if (UNEXPECTED(cached.isUndef())) return false;
		zend_long last;
		if (cached.isNull()) {
			if (UNEXPECTED(!findLastFunctionLikeStatement(stmts, last))) return false;
			zval lastZv;
			ZVAL_LONG(&lastZv, last);
			if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_svi_last_function_like_attribute), ZSTR_LEN(pt_svi_last_function_like_attribute), &lastZv))) return false;
		} else {
			last = zval_get_long(cached.raw());
		}
		out = last >= index;
		return true;
	}

	/* Mirrors isInferred(); false = pending exception */
	[[nodiscard]] bool isInferred(zval *scope, zval *var, bool &out) const
	{
		out = false;
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return false;
		if (frame.isNull()) return true;
		zv::Val body = pt_template_argument_frame_get_closure_signature_body(frame.raw());
		if (UNEXPECTED(body.isUndef())) return false;
		if (body.isNull()) return true;
		zv::Val stmts = pt_template_argument_frame_get_closure_signature_stmts(frame.raw());
		if (UNEXPECTED(stmts.isUndef())) return false;
		zv::Val sites = getSites(body.raw(), stmts.raw());
		if (UNEXPECTED(sites.isUndef())) return false;
		for (auto entry : zv::ArrRef(sites.raw())) {
			zval *site = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 0);
			if (site != NULL && Z_TYPE_P(site) == IS_OBJECT && Z_OBJ_P(site) == Z_OBJ_P(var)) {
				out = true;
				return true;
			}
		}
		return true;
	}

	/* Mirrors getResolvedTypes() */
	static zv::Val getResolvedTypes(zval *scope, zval *var)
	{
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (frame.isNull()) return zv::Val::null();
		return pt_template_argument_frame_get_static_variable_types(frame.raw(), var);
	}

	/* Mirrors getRuns() */
	zv::Val getRuns(zval *functionLike, zval *stmts) const
	{
		zv::Val sites = getSites(functionLike, stmts);
		if (UNEXPECTED(sites.isUndef())) return zv::Val();
		zv::ScratchTable siteIds(8);
		for (auto entry : zv::ArrRef(sites.raw())) {
			zval *var = zend_hash_index_find(Z_ARRVAL_P(entry.value().deref().raw()), 0);
			if (UNEXPECTED(var == NULL || Z_TYPE_P(var) != IS_OBJECT)) continue;
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_update(siteIds.table(), Z_OBJ_HANDLE_P(var), &marked);
		}
		if (zend_hash_num_elements(siteIds.table()) < 2) return zv::Val(zv::Arr::empty());

		zv::Arr runs = zv::Arr::empty();
		zv::Arr run = zv::Arr::create(0);
		zv::Val last = zv::Val::null();
		auto flush = [&]() {
			if (!last.isNull() && zend_hash_num_elements(run.table()) >= 2) {
				zv::Arr entry = zv::Arr::create(2);
				entry.push(std::move(last));
				entry.push(std::move(run));
				runs.push(std::move(entry));
			}
			run = zv::Arr::create(0);
			last = zv::Val::null();
		};
		for (auto entry : zv::ArrRef(stmts)) {
			zval *stmt = entry.value().deref().raw();
			bool is;
			if (UNEXPECTED(!isA(stmt, PT_CLASS_NOP_STMT, is))) return zv::Val();
			if (is) continue;
			if (UNEXPECTED(!isA(stmt, PT_CLASS_STATIC_STMT, is))) return zv::Val();
			bool onlySites = false;
			zval *vars = NULL;
			if (is) {
				vars = read(pt_svi_static_vars_site, stmt, PT_LC("vars"));
				if (UNEXPECTED(vars == NULL)) return zv::Val();
				onlySites = Z_TYPE_P(vars) == IS_ARRAY;
				if (onlySites) {
					for (auto varEntry : zv::ArrRef(vars)) {
						zval *var = read(pt_svi_static_var_var_site, varEntry.value().deref().raw(), PT_LC("var"));
						if (UNEXPECTED(var == NULL)) return zv::Val();
						if (Z_TYPE_P(var) != IS_OBJECT || !zend_hash_index_exists(siteIds.table(), Z_OBJ_HANDLE_P(var))) {
							onlySites = false;
							break;
						}
					}
				}
			}
			if (!onlySites) {
				flush();
				continue;
			}
			for (auto varEntry : zv::ArrRef(vars)) {
				zval *staticVar = varEntry.value().deref().raw();
				zval *var = read(pt_svi_static_var_var_site, staticVar, PT_LC("var"));
				if (UNEXPECTED(var == NULL)) return zv::Val();
				zval *name = read(pt_svi_variable_name_site, var, PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) continue;
				zval *defaultValue = read(pt_svi_static_var_default_site, staticVar, PT_LC("default"));
				if (UNEXPECTED(defaultValue == NULL)) return zv::Val();
				run.set(Z_STR_P(name), zv::Val::copyOf(zv::Ref(defaultValue)));
			}
			last = zv::Val::copyOf(zv::Ref(stmt));
		}
		flush();
		return zv::Val(std::move(runs));
	}

	/* Mirrors getResolvedConditionalExpressions() */
	static zv::Val getResolvedConditionalExpressions(zval *scope, zval *stmt)
	{
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return zv::Val();
		if (frame.isNull()) return zv::Val(zv::Arr::empty());
		return pt_template_argument_frame_get_static_variable_conditional_expressions(frame.raw(), stmt);
	}

	/* Mirrors canRunUserCode(); false = pending exception */
	[[nodiscard]] bool canRunUserCode(zval *node, zval *scope, bool &out) const
	{
		out = false;
		bool is;
		if (UNEXPECTED(!isA(node, PT_CLASS_CALL_LIKE, is))) return false;
		if (!is) return true;
		out = true;
		if (UNEXPECTED(!isA(node, PT_CLASS_FUNC_CALL, is))) return false;
		if (!is) return true;
		zval *name = read(pt_svi_func_call_name_site, node, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (UNEXPECTED(!isA(name, PT_CLASS_NAME, is))) return false;
		if (!is) return true;
		zend_object *provider = Z_OBJ_P(OBJ_PROP_NUM(self, slots::reflectionProvider));
		zv::Args argv{name, scope};
		zv::Val has = pt_type_call(provider, PT_LC("hasfunction"), 2, argv);
		if (UNEXPECTED(has.isUndef())) return false;
		if (!zend_is_true(has.raw())) return true;
		zv::Val function = pt_type_call(provider, PT_LC("getfunction"), 2, argv);
		if (UNEXPECTED(function.isUndef())) return false;
		zv::Val builtin = pt_type_call(Z_OBJ_P(function.raw()), PT_LC("isbuiltin"), 0, NULL);
		if (UNEXPECTED(builtin.isUndef())) return false;
		if (!zend_is_true(builtin.raw())) return true;
		zval className;
		ZVAL_STR(&className, zend_string_init_interned(PT_LC("Closure"), 0));
		zv::Val closureType = pt_type_new_object_type(&className);
		if (UNEXPECTED(closureType.isUndef())) return false;
		zv::Val variants = pt_type_call(Z_OBJ_P(function.raw()), PT_LC("getvariants"), 0, NULL);
		if (UNEXPECTED(variants.isUndef())) return false;
		for (auto variantEntry : zv::ArrRef(variants.raw())) {
			zv::Val parameters = pt_type_call(Z_OBJ_P(variantEntry.value().deref().raw()), PT_LC("getparameters"), 0, NULL);
			if (UNEXPECTED(parameters.isUndef())) return false;
			for (auto parameterEntry : zv::ArrRef(parameters.raw())) {
				zv::Val type = pt_type_call(Z_OBJ_P(parameterEntry.value().deref().raw()), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(type.isUndef())) return false;
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(type.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, closureType.raw());
				if (UNEXPECTED(isSuperType.isUndef())) return false;
				if (pt_type_result_trinary(isSuperType.raw()) != PT_TRI_NO) return true;
			}
		}
		out = false;
		return true;
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::StaticVariableInference;

/* {{{ direct entries (support.h): the native bodies for the native service,
 * the methods otherwise */

namespace {

inline bool isNative(zval *inference)
{
	return EXPECTED(Z_OBJCE_P(inference) == pt_ce_static_variable_inference);
}

} // namespace

zv::Val pt_static_variable_inference_get_sites(zval *inference, zval *functionLike, zval *stmts)
{
	if (isNative(inference)) return StaticVariableInference(Z_OBJ_P(inference)).getSites(functionLike, stmts);
	zv::Args argv{functionLike, stmts};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getsites"), 2, argv);
}

bool pt_static_variable_inference_has_function_like_from(zval *inference, zval *functionLike, zval *stmts, zend_long index, bool &out)
{
	if (isNative(inference)) return StaticVariableInference(Z_OBJ_P(inference)).hasFunctionLikeFrom(functionLike, stmts, index, out);
	zval indexZv;
	ZVAL_LONG(&indexZv, index);
	zv::Args argv{functionLike, stmts, &indexZv};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("hasfunctionlikefrom"), 3, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_static_variable_inference_is_inferred(zval *inference, zval *scope, zval *var, bool &out)
{
	if (isNative(inference)) return StaticVariableInference(Z_OBJ_P(inference)).isInferred(scope, var, out);
	zv::Args argv{scope, var};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("isinferred"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_static_variable_inference_get_resolved_types(zval *inference, zval *scope, zval *var)
{
	if (isNative(inference)) return StaticVariableInference::getResolvedTypes(scope, var);
	zv::Args argv{scope, var};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getresolvedtypes"), 2, argv);
}

zv::Val pt_static_variable_inference_get_runs(zval *inference, zval *functionLike, zval *stmts)
{
	if (isNative(inference)) return StaticVariableInference(Z_OBJ_P(inference)).getRuns(functionLike, stmts);
	zv::Args argv{functionLike, stmts};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getruns"), 2, argv);
}

zv::Val pt_static_variable_inference_get_resolved_conditional_expressions(zval *inference, zval *scope, zval *stmt)
{
	if (isNative(inference)) return StaticVariableInference::getResolvedConditionalExpressions(scope, stmt);
	zv::Args argv{scope, stmt};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getresolvedconditionalexpressions"), 2, argv);
}

bool pt_static_variable_inference_can_run_user_code(zval *inference, zval *node, zval *scope, bool &out)
{
	if (isNative(inference)) return StaticVariableInference(Z_OBJ_P(inference)).canRunUserCode(node, scope, out);
	zv::Args argv{node, scope};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("canrunusercode"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_static_variable_inference)
{
	pt_svi_sites_attribute = zend_string_init_interned(PT_LC("staticVariableInferenceSites"), 1);
	pt_svi_last_function_like_attribute = zend_string_init_interned(PT_LC("staticVariableInferenceLastFunctionLike"), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\StaticVariableInference");
	ptdecl::StaticVariableInference::declareClass(cls);
	ptdecl::StaticVariableInference::declareProperties(cls);
	cls.privateClassConstantString("SITES_ATTRIBUTE", "staticVariableInferenceSites");
	cls.privateClassConstantString("LAST_FUNCTION_LIKE_ATTRIBUTE", "staticVariableInferenceLastFunctionLike");

	/* the real parameter types: the DI container autowires the service by
	 * reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		bool enabled;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_BOOL(enabled)
		ZEND_PARSE_PARAMETERS_END();
		StaticVariableInference(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider, enabled);
	});

	cls.method(sigs::getRuns, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StaticVariableInference(Z_OBJ_P(ZEND_THIS)).getRuns(functionLike, stmts));
	});

	cls.method(sigs::getResolvedConditionalExpressions, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *stmt;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, stmt)) RETURN_THROWS();
		PT_RETURN_VAL(StaticVariableInference::getResolvedConditionalExpressions(scope, stmt));
	});

	cls.method(sigs::canRunUserCode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, node, scope)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!StaticVariableInference(Z_OBJ_P(ZEND_THIS)).canRunUserCode(node, scope, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::getSites, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StaticVariableInference(Z_OBJ_P(ZEND_THIS)).getSites(functionLike, stmts));
	});

	cls.method(sigs::hasFunctionLikeFrom, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		zend_long index;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
			Z_PARAM_LONG(index)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!StaticVariableInference(Z_OBJ_P(ZEND_THIS)).hasFunctionLikeFrom(functionLike, stmts, index, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::isInferred, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *var;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, var)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!StaticVariableInference(Z_OBJ_P(ZEND_THIS)).isInferred(scope, var, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::getResolvedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *var;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, var)) RETURN_THROWS();
		PT_RETURN_VAL(StaticVariableInference::getResolvedTypes(scope, var));
	});

	cls.shadow(&pt_ce_static_variable_inference);
}

/* }}} */
