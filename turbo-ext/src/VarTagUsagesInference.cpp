/*
 * PHPStanTurbo\VarTagUsagesInference — native implementation of
 * PHPStan\Analyser\Generics\VarTagUsagesInference.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getDeclarations() scans the top-level
 * statements of a function-like body once (cached in a node attribute) for
 * the `@var` declarations over values that do not decide the variable's type,
 * getWrites() the body once (cached too) for the writes to those variables;
 * isSuppressed() answers AssignHandler from the current template argument
 * frame. The direct entries pt_var_tag_usages_inference_*() serve the native
 * AssignHandler and StatementsHandler.
 */

#include "support.h"
#include "generated/VarTagUsagesInference.h"

namespace slots = ptdecl::VarTagUsagesInference::slot;
namespace sigs = ptdecl::VarTagUsagesInference::sig;
#include "zv.h"
#include "Engine.h"
#include "ClosureSupport.h"

zend_class_entry *pt_ce_var_tag_usages_inference = nullptr;

namespace {

/* the twin's DECLARATIONS_ATTRIBUTE, a permanent interned string (module
 * startup) */
zend_string *pt_vtui_declarations_attribute = nullptr;

/* the twin's WRITES_ATTRIBUTE, a permanent interned string (module startup) */
zend_string *pt_vtui_writes_attribute = nullptr;

pt_method_site pt_vtui_get_sub_node_names_site;
pt_method_site pt_vtui_get_start_file_pos_site;
pt_method_site pt_vtui_get_end_file_pos_site;
pt_property_site pt_vtui_closure_uses_site;
pt_property_site pt_vtui_closure_stmts_site;
pt_property_site pt_vtui_closure_use_by_ref_site;
pt_property_site pt_vtui_closure_use_var_site;
pt_property_site pt_vtui_write_var_site;
pt_property_site pt_vtui_dim_fetch_var_site;
pt_property_site pt_vtui_list_items_site;
pt_property_site pt_vtui_item_value_site;

pt_property_site pt_vtui_stmt_expr_site;
pt_property_site pt_vtui_assign_var_site;
pt_property_site pt_vtui_assign_expr_site;
pt_property_site pt_vtui_variable_name_site;
pt_property_site pt_vtui_array_items_site;
pt_property_site pt_vtui_static_vars_site;
pt_property_site pt_vtui_static_var_var_site;

/* $value instanceof <class-map class>; false = pending exception */
[[nodiscard]] bool isA(zval *value, int classIdx, bool &out)
{
	int is = ptclosure::instanceOf(value, classIdx);
	if (UNEXPECTED(is < 0)) return false;
	out = is == 1;
	return true;
}

/* Mirrors the private static isUndecidingValue(); false = pending exception */
[[nodiscard]] bool isUndecidingValue(zval *expr, bool &out)
{
	if (UNEXPECTED(!isA(expr, PT_CLASS_CONST_FETCH, out))) return false;
	if (!out && UNEXPECTED(!isA(expr, PT_CLASS_SCALAR, out))) return false;
	if (!out && UNEXPECTED(!isA(expr, PT_CLASS_NEW, out))) return false;
	if (out) return true;
	bool isArray;
	if (UNEXPECTED(!isA(expr, PT_CLASS_ARRAY_EXPR, isArray))) return false;
	if (!isArray) return true;
	zval *items = ptclosure::prop(pt_vtui_array_items_site, expr, PT_LC("items"));
	if (UNEXPECTED(items == NULL)) return false;
	out = Z_TYPE_P(items) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(items)) == 0;
	return true;
}

[[nodiscard]] bool declaredVariableName(zval *stmt, zval *&name);

/* one statement of getDeclarations(): its variable's name when it is a
 * declaration (NULL otherwise, borrowed from the statement); false = pending
 * exception */
[[nodiscard]] bool declarationName(zval *stmt, zval *&name)
{
	name = NULL;
	zval *declared;
	if (UNEXPECTED(!declaredVariableName(stmt, declared))) return false;
	if (declared == NULL) return true;
	zv::Val text = pt_node_doc_comment_text(stmt);
	if (UNEXPECTED(text.isUndef())) return false;
	if (!text.ref().isString() || !pt_doc_comment_declares_var(Z_STR_P(text.raw()))) return true;
	name = declared;
	return true;
}

/* Mirrors the private static getDeclaredVariableName(): borrowed from the
 * statement, NULL otherwise; false = pending exception */
[[nodiscard]] bool declaredVariableName(zval *stmt, zval *&name)
{
	name = NULL;
	bool is;
	if (UNEXPECTED(!isA(stmt, PT_CLASS_STATIC_STMT, is))) return false;
	if (is) {
		// what the previous calls left decides the type, not the default
		zval *vars = ptclosure::prop(pt_vtui_static_vars_site, stmt, PT_LC("vars"));
		if (UNEXPECTED(vars == NULL)) return false;
		if (Z_TYPE_P(vars) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(vars)) != 1) return true;
		zval *staticVar = zend_hash_index_find(Z_ARRVAL_P(vars), 0);
		if (staticVar == NULL) return true;
		ZVAL_DEREF(staticVar);
		zval *var = ptclosure::prop(pt_vtui_static_var_var_site, staticVar, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zval *varName = ptclosure::prop(pt_vtui_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (Z_TYPE_P(varName) == IS_STRING) name = varName;
		return true;
	}
	if (UNEXPECTED(!isA(stmt, PT_CLASS_EXPRESSION_STMT, is))) return false;
	if (!is) return true;
	zval *assign = ptclosure::prop(pt_vtui_stmt_expr_site, stmt, PT_LC("expr"));
	if (UNEXPECTED(assign == NULL)) return false;
	if (UNEXPECTED(!isA(assign, PT_CLASS_ASSIGN_EXPR, is))) return false;
	if (!is) return true;
	zval *var = ptclosure::prop(pt_vtui_assign_var_site, assign, PT_LC("var"));
	if (UNEXPECTED(var == NULL)) return false;
	if (UNEXPECTED(!isA(var, PT_CLASS_VARIABLE, is))) return false;
	if (!is) return true;
	zval *varName = ptclosure::prop(pt_vtui_variable_name_site, var, PT_LC("name"));
	if (UNEXPECTED(varName == NULL)) return false;
	if (Z_TYPE_P(varName) != IS_STRING) return true;
	zval *value = ptclosure::prop(pt_vtui_assign_expr_site, assign, PT_LC("expr"));
	if (UNEXPECTED(value == NULL)) return false;
	if (UNEXPECTED(!isUndecidingValue(value, is))) return false;
	if (!is) return true;
	name = varName;
	return true;
}


/* the twin's `foreach ($node->getSubNodeNames() as $subNodeName) { ... }`
 * pushing every Node found (directly or in an array) onto $stack with
 * $visibleNames onto $namesStack; false = pending exception */
[[nodiscard]] bool pushSubNodes(zval *node, zval *visibleNames, zv::Arr &stack, zv::Arr &namesStack)
{
	zv::Val names = pt_call_method_cached(pt_vtui_get_sub_node_names_site, Z_OBJ_P(node), PT_LC("getsubnodenames"), 0, NULL);
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
			namesStack.push(zv::Ref(visibleNames));
		} else if (held.ref().isArray()) {
			for (auto item : zv::ArrRef(held.raw())) {
				zval *value = item.value().deref().raw();
				if (Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), nodeCe)) continue;
				stack.push(zv::Val::copyOf(zv::Ref(value)));
				namesStack.push(zv::Ref(visibleNames));
			}
		}
	}
	return true;
}

/* array_pop($stack), or UNDEF for an empty stack */
zv::Val popValue(zv::Arr &stack)
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

/* $node->getStartFilePos() / getEndFilePos(); false = pending exception */
[[nodiscard]] bool filePos(pt_method_site &site, zval *node, const char *lcName, size_t len, zend_long &out)
{
	zv::Val position = pt_call_method_cached(site, Z_OBJ_P(node), lcName, len, 0, NULL);
	if (UNEXPECTED(position.isUndef())) return false;
	out = zval_get_long(position.raw());
	return true;
}

/* Mirrors the private static getWrittenNames(): the names pushed onto
 * $names; false = pending exception */
[[nodiscard]] bool writtenNames(zval *targetArg, zv::Arr &names)
{
	zv::Val target = zv::Val::copyOf(zv::Ref(targetArg));
	for (;;) {
		bool fetch;
		if (UNEXPECTED(!isA(target.raw(), PT_CLASS_ARRAY_DIM_FETCH, fetch))) return false;
		if (!fetch) break;
		zval *var = ptclosure::prop(pt_vtui_dim_fetch_var_site, target.raw(), PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		target = zv::Val::copyOf(zv::Ref(var));
	}
	bool is;
	if (UNEXPECTED(!isA(target.raw(), PT_CLASS_VARIABLE, is))) return false;
	if (is) {
		zval *name = ptclosure::prop(pt_vtui_variable_name_site, target.raw(), PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) == IS_STRING) names.push(zv::Ref(name));
		return true;
	}
	if (UNEXPECTED(!isA(target.raw(), PT_CLASS_LIST_EXPR, is))) return false;
	if (!is && UNEXPECTED(!isA(target.raw(), PT_CLASS_ARRAY_EXPR, is))) return false;
	if (!is) return true;

	zval *items = ptclosure::prop(pt_vtui_list_items_site, target.raw(), PT_LC("items"));
	if (UNEXPECTED(items == NULL)) return false;
	if (Z_TYPE_P(items) != IS_ARRAY) return true;
	zv::Val itemsHold = zv::Val::copyOf(zv::Ref(items));
	for (auto entry : zv::ArrRef(itemsHold.raw())) {
		zval *item = entry.value().deref().raw();
		if (Z_TYPE_P(item) == IS_NULL) continue;
		zval *value = ptclosure::prop(pt_vtui_item_value_site, item, PT_LC("value"));
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val valueHold = zv::Val::copyOf(zv::Ref(value));
		bool ok = true;
		pt_engine_with_stack([&]() { ok = writtenNames(valueHold.raw(), names); });
		if (UNEXPECTED(!ok)) return false;
	}
	return true;
}

/* the closure branch of getWrites(): the names it uses by reference among
 * $visibleNames (empty when none) */
[[nodiscard]] bool byRefUseNames(zval *closure, zval *visibleNames, zv::Arr &byRefNames)
{
	zval *uses = ptclosure::prop(pt_vtui_closure_uses_site, closure, PT_LC("uses"));
	if (UNEXPECTED(uses == NULL)) return false;
	if (Z_TYPE_P(uses) != IS_ARRAY) return true;
	zv::Val usesHold = zv::Val::copyOf(zv::Ref(uses));
	for (auto entry : zv::ArrRef(usesHold.raw())) {
		zval *use = entry.value().deref().raw();
		zval *byRef = ptclosure::prop(pt_vtui_closure_use_by_ref_site, use, PT_LC("byRef"));
		if (UNEXPECTED(byRef == NULL)) return false;
		if (!zend_is_true(byRef)) continue;
		zval *var = ptclosure::prop(pt_vtui_closure_use_var_site, use, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zval *name = ptclosure::prop(pt_vtui_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) != IS_STRING || !zend_hash_exists(Z_ARRVAL_P(visibleNames), Z_STR_P(name))) continue;
		byRefNames.set(Z_STR_P(name), zv::Val::boolean(true));
	}
	return true;
}

/* one node of getWrites()'s walk: its writes onto $writes; $descend = false
 * for a function-like or class-like it does not enter, with a closure's
 * by-reference body pushed already; false = pending exception */
[[nodiscard]] bool scanWriteNode(zval *node, zval *visibleNames, zval *declarationsByName, zval *declaringAssigns, zv::Arr &stack, zv::Arr &namesStack, zv::Arr &writes, bool &descend)
{
	descend = false;
	bool is;
	if (UNEXPECTED(!isA(node, PT_CLASS_CLOSURE_EXPR, is))) return false;
	if (is) {
		// its own variables, except those it uses by reference
		zv::Arr byRefNames = zv::Arr::create(0);
		if (UNEXPECTED(!byRefUseNames(node, visibleNames, byRefNames))) return false;
		if (zend_hash_num_elements(byRefNames.table()) == 0) return true;
		zval *closureStmts = ptclosure::prop(pt_vtui_closure_stmts_site, node, PT_LC("stmts"));
		if (UNEXPECTED(closureStmts == NULL)) return false;
		if (Z_TYPE_P(closureStmts) != IS_ARRAY) return true;
		zv::Val byRefNamesValue(std::move(byRefNames));
		zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(closureStmts));
		for (auto entry : zv::ArrRef(stmtsHold.raw())) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
			namesStack.push(zv::Ref(byRefNamesValue.raw()));
		}
		return true;
	}
	if (UNEXPECTED(!isA(node, PT_CLASS_FUNCTION_LIKE, is))) return false;
	if (!is && UNEXPECTED(!isA(node, PT_CLASS_CLASS_LIKE_STMT, is))) return false;
	if (is) return true;
	descend = true;

	static const int writeClasses[] = {PT_CLASS_ASSIGN_EXPR, PT_CLASS_ASSIGN_OP_EXPR, PT_CLASS_PRE_INC, PT_CLASS_PRE_DEC, PT_CLASS_POST_INC, PT_CLASS_POST_DEC};
	bool isWrite = false;
	for (int writeClass : writeClasses) {
		if (UNEXPECTED(!isA(node, writeClass, isWrite))) return false;
		if (isWrite) break;
	}
	if (!isWrite || zend_hash_index_exists(Z_ARRVAL_P(declaringAssigns), Z_OBJ_HANDLE_P(node))) return true;
	zval *target = ptclosure::prop(pt_vtui_write_var_site, node, PT_LC("var"));
	if (UNEXPECTED(target == NULL)) return false;
	zv::Val targetHold = zv::Val::copyOf(zv::Ref(target));
	zv::Arr names = zv::Arr::create(0);
	if (UNEXPECTED(!writtenNames(targetHold.raw(), names))) return false;
	for (auto entry : zv::ArrRef(names.raw())) {
		zval *name = entry.value().raw();
		if (!zend_hash_exists(Z_ARRVAL_P(visibleNames), Z_STR_P(name))) continue;
		zval *nameDeclarations = zend_hash_find(Z_ARRVAL_P(declarationsByName), Z_STR_P(name));
		if (nameDeclarations == NULL) continue;
		zend_long startPosition;
		if (UNEXPECTED(!filePos(pt_vtui_get_start_file_pos_site, node, PT_LC("getstartfilepos"), startPosition))) return false;
		zval *declarationKey = NULL;
		for (auto declarationEntry : zv::ArrRef(nameDeclarations)) {
			HashTable *declaration = Z_ARRVAL_P(declarationEntry.value().raw());
			if (zval_get_long(zend_hash_index_find(declaration, 0)) >= startPosition) continue;
			declarationKey = zend_hash_index_find(declaration, 1);
		}
		if (declarationKey == NULL) continue;
		zv::Arr write = zv::Arr::create(3);
		write.push(zv::Ref(node));
		write.push(zv::Ref(declarationKey));
		write.push(zv::Ref(name));
		writes.push(std::move(write));
	}
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\Generics\VarTagUsagesInference; UNDEF / false =
 * pending exception. */
class VarTagUsagesInference
{
public:
	explicit VarTagUsagesInference(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(bool enabled)
	{
		zv::ObjRef(self).propAtWrite(slots::enabled, zv::Val::boolean(enabled));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::enabled)) = 0;
	}

	/* Mirrors getDeclarations() */
	zv::Val getDeclarations(zval *functionLike, zval *stmts) const
	{
		if (Z_TYPE_P(OBJ_PROP_NUM(self, slots::enabled)) != IS_TRUE) return zv::Val(zv::Arr::empty());

		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_vtui_declarations_attribute), ZSTR_LEN(pt_vtui_declarations_attribute));
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		if (!cached.isNull()) return cached;

		zv::Arr declarations = zv::Arr::empty();
		for (auto entry : zv::ArrRef(stmts)) {
			zval *stmt = entry.value().deref().raw();
			zval *name;
			if (UNEXPECTED(!declarationName(stmt, name))) return zv::Val();
			if (name == NULL) continue;
			zv::Arr declaration = zv::Arr::create(3);
			declaration.push(zv::Ref(stmt));
			// statement lists are lists
			declaration.push(zv::Val::integer(entry.stringKeyOrNull() == NULL ? (zend_long) entry.indexKey() : 0));
			declaration.push(zv::Ref(name));
			declarations.push(std::move(declaration));
		}
		zv::Val result(std::move(declarations));
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_vtui_declarations_attribute), ZSTR_LEN(pt_vtui_declarations_attribute), result.raw()))) return zv::Val();
		return result;
	}

	/* Mirrors getWrites() */
	zv::Val getWrites(zval *functionLike, zval *stmts) const
	{
		zv::Val declarations = getDeclarations(functionLike, stmts);
		if (UNEXPECTED(declarations.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(declarations.raw())) == 0) return zv::Val(zv::Arr::empty());

		zv::Val cached = pt_engine_node_get_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_vtui_writes_attribute), ZSTR_LEN(pt_vtui_writes_attribute));
		if (UNEXPECTED(cached.isUndef())) return zv::Val();
		if (!cached.isNull()) return cached;

		zv::Arr declarationsByName = zv::Arr::create(0);
		zv::Arr declaringAssigns = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(declarations.raw())) {
			HashTable *declaration = Z_ARRVAL_P(entry.value().deref().raw());
			zval *stmt = zend_hash_index_find(declaration, 0);
			zval *name = zend_hash_index_find(declaration, 2);
			zend_long endPosition;
			if (UNEXPECTED(!filePos(pt_vtui_get_end_file_pos_site, stmt, PT_LC("getendfilepos"), endPosition))) return zv::Val();
			zv::Arr position = zv::Arr::create(2);
			position.push(zv::Val::integer(endPosition));
			position.push(zv::Val::integer((zend_long) entry.indexKey()));
			declarationsByName.separate();
			zval *nameDeclarations = zend_hash_find(declarationsByName.table(), Z_STR_P(name));
			if (nameDeclarations == NULL) {
				zval empty;
				array_init(&empty);
				nameDeclarations = zend_hash_add_new(declarationsByName.table(), Z_STR_P(name), &empty);
			}
			SEPARATE_ARRAY(nameDeclarations);
			zval positionZv = zv::Val(std::move(position)).take();
			zend_hash_next_index_insert(Z_ARRVAL_P(nameDeclarations), &positionZv);
			bool isExpression;
			if (UNEXPECTED(!isA(stmt, PT_CLASS_EXPRESSION_STMT, isExpression))) return zv::Val();
			if (!isExpression) continue;
			zval *expr = ptclosure::prop(pt_vtui_stmt_expr_site, stmt, PT_LC("expr"));
			if (UNEXPECTED(expr == NULL)) return zv::Val();
			declaringAssigns.separate();
			zval marked;
			ZVAL_TRUE(&marked);
			zend_hash_index_update(declaringAssigns.table(), Z_OBJ_HANDLE_P(expr), &marked);
		}
		zv::Arr names = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(declarationsByName.raw())) {
			names.set(entry.stringKeyOrNull(), zv::Val::boolean(true));
		}
		zv::Val namesValue(std::move(names));

		zv::Arr writes = zv::Arr::empty();
		zv::Arr stack = zv::Arr::create(0);
		zv::Arr namesStack = zv::Arr::create(0);
		for (auto entry : zv::ArrRef(stmts)) {
			stack.push(zv::Val::copyOf(entry.value().deref()));
			namesStack.push(zv::Ref(namesValue.raw()));
		}
		for (;;) {
			zv::Val node = popValue(stack);
			if (node.isUndef()) break;
			zv::Val visibleNames = popValue(namesStack);
			bool descend;
			bool ok = true;
			pt_engine_with_stack([&]() { ok = scanWriteNode(node.raw(), visibleNames.raw(), declarationsByName.raw(), declaringAssigns.raw(), stack, namesStack, writes, descend); });
			if (UNEXPECTED(!ok)) return zv::Val();
			if (!descend) continue;
			if (UNEXPECTED(!pushSubNodes(node.raw(), visibleNames.raw(), stack, namesStack))) return zv::Val();
		}
		zv::Val result(std::move(writes));
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(functionLike), ZSTR_VAL(pt_vtui_writes_attribute), ZSTR_LEN(pt_vtui_writes_attribute), result.raw()))) return zv::Val();
		return result;
	}

	/* Mirrors isSuppressed(); false = pending exception */
	[[nodiscard]] static bool isSuppressed(zval *scope, zval *stmt, bool &out)
	{
		out = false;
		zv::Val frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
		if (UNEXPECTED(frame.isUndef())) return false;
		if (frame.isNull()) return true;
		return pt_template_argument_frame_is_var_tag_suppressed(frame.raw(), stmt, out);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::VarTagUsagesInference;

/* {{{ direct entries (support.h): the native bodies for the native service,
 * the methods otherwise */

namespace {

inline bool isNative(zval *inference)
{
	return EXPECTED(Z_OBJCE_P(inference) == pt_ce_var_tag_usages_inference);
}

} // namespace

zv::Val pt_var_tag_usages_inference_get_declarations(zval *inference, zval *functionLike, zval *stmts)
{
	if (isNative(inference)) return VarTagUsagesInference(Z_OBJ_P(inference)).getDeclarations(functionLike, stmts);
	zv::Args argv{functionLike, stmts};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getdeclarations"), 2, argv);
}

zv::Val pt_var_tag_usages_inference_get_writes(zval *inference, zval *functionLike, zval *stmts)
{
	if (isNative(inference)) return VarTagUsagesInference(Z_OBJ_P(inference)).getWrites(functionLike, stmts);
	zv::Args argv{functionLike, stmts};
	return pt_type_call(Z_OBJ_P(inference), PT_LC("getwrites"), 2, argv);
}

bool pt_var_tag_usages_inference_is_suppressed(zval *inference, zval *scope, zval *stmt, bool &out)
{
	if (isNative(inference)) return VarTagUsagesInference::isSuppressed(scope, stmt, out);
	zv::Args argv{scope, stmt};
	zv::Val result = pt_type_call(Z_OBJ_P(inference), PT_LC("issuppressed"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_var_tag_usages_inference)
{
	pt_vtui_declarations_attribute = zend_string_init_interned(PT_LC("varTagUsagesDeclarations"), 1);
	pt_vtui_writes_attribute = zend_string_init_interned(PT_LC("varTagUsagesWrites"), 1);

	reg::Class cls("PHPStan\\Analyser\\Generics\\VarTagUsagesInference");
	ptdecl::VarTagUsagesInference::declareClass(cls);
	ptdecl::VarTagUsagesInference::declareProperties(cls);
	cls.privateClassConstantString("DECLARATIONS_ATTRIBUTE", "varTagUsagesDeclarations");
	cls.privateClassConstantString("WRITES_ATTRIBUTE", "varTagUsagesWrites");

	/* the real parameter types: the DI container autowires the service by
	 * reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool enabled;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_BOOL(enabled)
		ZEND_PARSE_PARAMETERS_END();
		VarTagUsagesInference(Z_OBJ_P(ZEND_THIS)).construct(enabled);
	});

	cls.method(sigs::getDeclarations, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(VarTagUsagesInference(Z_OBJ_P(ZEND_THIS)).getDeclarations(functionLike, stmts));
	});

	cls.method(sigs::getWrites, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *functionLike, *stmts;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(functionLike)
			Z_PARAM_ARRAY(stmts)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(VarTagUsagesInference(Z_OBJ_P(ZEND_THIS)).getWrites(functionLike, stmts));
	});

	cls.method(sigs::isSuppressed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *stmt;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!VarTagUsagesInference::isSuppressed(scope, stmt, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.shadow(&pt_ce_var_tag_usages_inference);
}

/* }}} */
