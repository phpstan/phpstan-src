#include "support.h"

#include <cstring>

pt_globals_t pt_globals;

zend_class_entry *pt_ce_trinary = nullptr;
zend_class_entry *pt_ce_expr_type_holder = nullptr;
zend_class_entry *pt_ce_cond_expr_holder = nullptr;

/* {{{ class map */

typedef struct _pt_class_template {
	const char *key;
	const char *default_name;
} pt_class_template;

static const pt_class_template pt_class_templates[PT_CLASS_COUNT] = {
	/* PT_CLASS_TYPE_COMBINATOR */ {"typeCombinator", "PHPStan\\Type\\TypeCombinator"},
	/* PT_CLASS_BOOLEAN_TYPE */ {"booleanType", "PHPStan\\Type\\BooleanType"},
	/* PT_CLASS_CONSTANT_BOOLEAN_TYPE */ {"constantBooleanType", "PHPStan\\Type\\Constant\\ConstantBooleanType"},
	/* PT_CLASS_SHOULD_NOT_HAPPEN */ {"shouldNotHappenException", "PHPStan\\ShouldNotHappenException"},
	/* PT_CLASS_VERBOSITY_LEVEL */ {"verbosityLevel", "PHPStan\\Type\\VerbosityLevel"},
	/* PT_CLASS_VARIABLE */ {"variable", "PhpParser\\Node\\Expr\\Variable"},
	/* PT_CLASS_FUNC_CALL */ {"funcCall", "PhpParser\\Node\\Expr\\FuncCall"},
	/* PT_CLASS_VIRTUAL_NODE */ {"virtualNode", "PHPStan\\Node\\VirtualNode"},
	/* PT_CLASS_NODE */ {"node", "PhpParser\\Node"},
	/* PT_CLASS_NAME */ {"name", "PhpParser\\Node\\Name"},
	/* PT_CLASS_EXPR */ {"expr", "PhpParser\\Node\\Expr"},
	/* PT_CLASS_PROPERTY_FETCH */ {"propertyFetch", "PhpParser\\Node\\Expr\\PropertyFetch"},
	/* PT_CLASS_INTERTWINED_VAR */ {"intertwinedVariableByReferenceWithExpr", "PHPStan\\Node\\Expr\\IntertwinedVariableByReferenceWithExpr"},
	/* PT_CLASS_ARRAY_DIM_FETCH */ {"arrayDimFetch", "PhpParser\\Node\\Expr\\ArrayDimFetch"},
	/* PT_CLASS_METHOD_CALL */ {"methodCall", "PhpParser\\Node\\Expr\\MethodCall"},
	/* PT_CLASS_FUNCTION_LIKE */ {"functionLike", "PhpParser\\Node\\FunctionLike"},
	/* PT_CLASS_CALL_LIKE */ {"callLike", "PhpParser\\Node\\Expr\\CallLike"},
	/* PT_CLASS_STATIC_CALL */ {"staticCall", "PhpParser\\Node\\Expr\\StaticCall"},
	/* PT_CLASS_NEW */ {"newExpr", "PhpParser\\Node\\Expr\\New_"},
	/* PT_CLASS_CLASS_STMT */ {"classStmt", "PhpParser\\Node\\Stmt\\Class_"},
	/* PT_CLASS_VARIADIC_PLACEHOLDER */ {"variadicPlaceholder", "PhpParser\\Node\\VariadicPlaceholder"},
	/* PT_CLASS_ERROR_TYPE */ {"errorType", "PHPStan\\Type\\ErrorType"},
	/* PT_CLASS_SCALAR */ {"scalar", "PhpParser\\Node\\Scalar"},
	/* PT_CLASS_ARRAY_EXPR */ {"arrayExpr", "PhpParser\\Node\\Expr\\Array_"},
	/* PT_CLASS_UNARY_MINUS */ {"unaryMinus", "PhpParser\\Node\\Expr\\UnaryMinus"},
	/* PT_CLASS_YIELD */ {"yield", "PhpParser\\Node\\Expr\\Yield_"},
	/* PT_CLASS_YIELD_FROM */ {"yieldFrom", "PhpParser\\Node\\Expr\\YieldFrom"},
	/* PT_CLASS_STMT */ {"stmt", "PhpParser\\Node\\Stmt"},
	/* PT_CLASS_NODE_VISITOR_ABSTRACT */ {"nodeVisitorAbstract", "PhpParser\\NodeVisitorAbstract"},
	/* PT_CLASS_CLOSURE_EXPR */ {"closureExpr", "PhpParser\\Node\\Expr\\Closure"},
	/* PT_CLASS_ARROW_FUNCTION */ {"arrowFunction", "PhpParser\\Node\\Expr\\ArrowFunction"},
	/* PT_CLASS_TYPE */ {"type", "PHPStan\\Type\\Type"},
	/* PT_CLASS_RECURSION_GUARD */ {"recursionGuard", "PHPStan\\Type\\RecursionGuard"},
	/* PT_CLASS_TRINARY */ {"trinaryLogic", NULL},
	/* PT_CLASS_ETH */ {"expressionTypeHolder", NULL},
	/* PT_CLASS_CEH */ {"conditionalExpressionHolder", NULL},
};

zend_class_entry *pt_class(int idx)
{
	pt_class_ref *ref = &PT_G(class_refs)[idx];
	zend_class_entry *ce;
	zend_string *name;

	if (EXPECTED(ref->ce != NULL)) {
		return ref->ce;
	}

	if (ref->configured != NULL) {
		name = zend_string_copy(ref->configured);
	} else if (ref->default_name != NULL) {
		name = zend_string_init(ref->default_name, strlen(ref->default_name), 0);
	} else {
		zend_throw_error(NULL, "phpstan_turbo: class for '%s' was not configured", ref->key);
		return NULL;
	}
	ce = zend_lookup_class(name);
	if (ce == NULL) {
		zend_throw_error(NULL, "phpstan_turbo: class %s not found", ZSTR_VAL(name));
		zend_string_release(name);
		return NULL;
	}
	zend_string_release(name);
	ref->ce = ce;
	return ce;
}

zend_class_entry *pt_impl_class(int idx, zend_class_entry *native_fallback)
{
	pt_class_ref *ref = &PT_G(class_refs)[idx];
	if (EXPECTED(ref->ce != NULL)) {
		return ref->ce;
	}
	if (ref->configured == NULL) {
		ref->ce = native_fallback;
		return native_fallback;
	}
	return pt_class(idx);
}

void pt_class_map_configure(zend_string *key, zend_string *value)
{
	for (int idx = 0; idx < PT_CLASS_COUNT; idx++) {
		if (strcmp(ZSTR_VAL(key), pt_class_templates[idx].key) == 0) {
			pt_class_ref *ref = &PT_G(class_refs)[idx];
			if (ref->configured != NULL) {
				zend_string_release(ref->configured);
			}
			ref->configured = zend_string_copy(value);
			ref->ce = NULL;
			return;
		}
	}
}

void pt_class_refs_dump(zval *return_value)
{
	array_init_size(return_value, PT_CLASS_COUNT);
	for (int idx = 0; idx < PT_CLASS_COUNT; idx++) {
		zval v;
		if (pt_class_templates[idx].default_name != NULL) {
			ZVAL_STRING(&v, pt_class_templates[idx].default_name);
		} else {
			ZVAL_NULL(&v);
		}
		zend_hash_str_add_new(Z_ARRVAL_P(return_value), pt_class_templates[idx].key, strlen(pt_class_templates[idx].key), &v);
	}
}

/* }}} */

/* {{{ lifecycle */

zend_string *pt_str_cache_printer = nullptr;
zend_string *pt_str_contains_super_global = nullptr;
zend_string *pt_str_array_map_args = nullptr;
zend_string *pt_str_start_file_pos = nullptr;
zend_string *pt_str_keep_void = nullptr;
static bool pt_strs_inited = false;

static HashTable pt_node_class_cache;
static bool pt_node_class_cache_inited = false;

void pt_init_strs()
{
	if (pt_strs_inited) {
		return;
	}
	pt_str_cache_printer = zend_string_init("phpstan_cache_printer", sizeof("phpstan_cache_printer") - 1, 0);
	pt_str_contains_super_global = zend_string_init("containsSuperGlobal", sizeof("containsSuperGlobal") - 1, 0);
	pt_str_array_map_args = zend_string_init("arrayMapArgs", sizeof("arrayMapArgs") - 1, 0);
	pt_str_start_file_pos = zend_string_init("startFilePos", sizeof("startFilePos") - 1, 0);
	pt_str_keep_void = zend_string_init("keepVoid", sizeof("keepVoid") - 1, 0);
	pt_strs_inited = true;
}

void pt_support_rinit()
{
	PT_G(trinary_inited) = false;
	PT_G(verbosity_inited) = false;
	ZVAL_UNDEF(&PT_G(trinary_yes));
	ZVAL_UNDEF(&PT_G(trinary_maybe));
	ZVAL_UNDEF(&PT_G(trinary_no));
	ZVAL_UNDEF(&PT_G(verbosity_precise));

	for (int i = 0; i < PT_CLASS_COUNT; i++) {
		PT_G(class_refs)[i].key = pt_class_templates[i].key;
		PT_G(class_refs)[i].default_name = pt_class_templates[i].default_name;
		PT_G(class_refs)[i].configured = NULL;
		PT_G(class_refs)[i].ce = NULL;
	}

	pt_strs_inited = false;
	pt_node_class_cache_inited = false;
}

void pt_support_rshutdown()
{
	if (PT_G(trinary_inited)) {
		zval_ptr_dtor(&PT_G(trinary_yes));
		zval_ptr_dtor(&PT_G(trinary_maybe));
		zval_ptr_dtor(&PT_G(trinary_no));
		PT_G(trinary_inited) = false;
	}
	if (PT_G(verbosity_inited)) {
		zval_ptr_dtor(&PT_G(verbosity_precise));
		PT_G(verbosity_inited) = false;
	}
	for (int i = 0; i < PT_CLASS_COUNT; i++) {
		if (PT_G(class_refs)[i].configured != NULL) {
			zend_string_release(PT_G(class_refs)[i].configured);
			PT_G(class_refs)[i].configured = NULL;
		}
		PT_G(class_refs)[i].ce = NULL;
	}
	if (pt_strs_inited) {
		zend_string_release(pt_str_cache_printer);
		zend_string_release(pt_str_contains_super_global);
		zend_string_release(pt_str_array_map_args);
		zend_string_release(pt_str_start_file_pos);
		zend_string_release(pt_str_keep_void);
		pt_strs_inited = false;
	}
	if (pt_node_class_cache_inited) {
		zend_hash_destroy(&pt_node_class_cache);
		pt_node_class_cache_inited = false;
	}
}

/* }}} */

/* {{{ TrinaryLogic singletons */

zval *pt_trinary_singleton(zend_long value)
{
	if (UNEXPECTED(!PT_G(trinary_inited))) {
		static const zend_long values[3] = {PT_TRI_YES, PT_TRI_MAYBE, PT_TRI_NO};
		zend_class_entry *impl = pt_impl_class(PT_CLASS_TRINARY, pt_ce_trinary);
		zval *slots[3];
		slots[0] = &PT_G(trinary_yes);
		slots[1] = &PT_G(trinary_maybe);
		slots[2] = &PT_G(trinary_no);
		for (int i = 0; i < 3; i++) {
			object_init_ex(slots[i], impl);
			ZVAL_LONG(OBJ_PROP_NUM(Z_OBJ_P(slots[i]), PT_TRI_PROP_VALUE), values[i]);
		}
		PT_G(trinary_inited) = true;
	}

	if (value == PT_TRI_YES) {
		return &PT_G(trinary_yes);
	}
	if (value == PT_TRI_MAYBE) {
		return &PT_G(trinary_maybe);
	}
	return &PT_G(trinary_no);
}

/* }}} */

/* {{{ userland callback helpers */

zend_function *pt_find_method(zend_class_entry *ce, const char *lcname, size_t len)
{
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", ZSTR_VAL(ce->name), lcname);
	}
	return fn;
}

bool pt_call_type_equals(zval *type_a, zval *type_b)
{
	zend_class_entry *ce = Z_OBJCE_P(type_a);
	zend_function *fn = pt_find_method(ce, "equals", sizeof("equals") - 1);
	zval ret, args[1];
	bool result;

	if (UNEXPECTED(fn == NULL)) {
		return false;
	}
	ZVAL_COPY_VALUE(&args[0], type_b);
	zend_call_known_function(fn, Z_OBJ_P(type_a), ce, &ret, 1, args, NULL);
	if (UNEXPECTED(EG(exception))) {
		return false;
	}
	result = Z_TYPE(ret) == IS_TRUE;
	zval_ptr_dtor(&ret);
	return result;
}

bool pt_types_identical_or_equal(zval *type_a, zval *type_b)
{
	if (Z_OBJ_P(type_a) == Z_OBJ_P(type_b)) {
		return true;
	}
	return pt_call_type_equals(type_a, type_b);
}

bool pt_type_combinator_binary(const char *lcname, size_t len, zval *type_a, zval *type_b, zval *result)
{
	zend_class_entry *ce = pt_class(PT_CLASS_TYPE_COMBINATOR);
	zend_function *fn;
	zval args[2];

	if (UNEXPECTED(ce == NULL)) {
		return false;
	}
	fn = pt_find_method(ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) {
		return false;
	}
	ZVAL_COPY_VALUE(&args[0], type_a);
	ZVAL_COPY_VALUE(&args[1], type_b);
	zend_call_known_function(fn, NULL, ce, result, 2, args, NULL);
	return !EG(exception);
}

bool pt_type_describe_precise(zval *type, zval *result)
{
	zend_class_entry *ce;
	zend_function *fn;
	zval args[1];

	if (UNEXPECTED(!PT_G(verbosity_inited))) {
		zend_class_entry *vce = pt_class(PT_CLASS_VERBOSITY_LEVEL);
		if (UNEXPECTED(vce == NULL)) {
			return false;
		}
		fn = pt_find_method(vce, "precise", sizeof("precise") - 1);
		if (UNEXPECTED(fn == NULL)) {
			return false;
		}
		zend_call_known_function(fn, NULL, vce, &PT_G(verbosity_precise), 0, NULL, NULL);
		if (UNEXPECTED(EG(exception))) {
			return false;
		}
		PT_G(verbosity_inited) = true;
	}

	ce = Z_OBJCE_P(type);
	fn = pt_find_method(ce, "describe", sizeof("describe") - 1);
	if (UNEXPECTED(fn == NULL)) {
		return false;
	}
	ZVAL_COPY_VALUE(&args[0], &PT_G(verbosity_precise));
	zend_call_known_function(fn, Z_OBJ_P(type), ce, result, 1, args, NULL);
	return !EG(exception);
}

void pt_throw_should_not_happen()
{
	zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
	if (ce == NULL) {
		return; /* error already thrown */
	}
	zend_throw_exception(ce, "Internal error.", 0);
}

bool pt_call_scope_bool(zval *scope, const char *lcname, size_t len, uint32_t argc, zval *argv, bool *out)
{
	zend_class_entry *ce = Z_OBJCE_P(scope);
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	zval ret;

	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: method %s::%s not found", ZSTR_VAL(ce->name), lcname);
		return false;
	}
	zend_call_known_function(fn, Z_OBJ_P(scope), ce, &ret, argc, argv, NULL);
	if (UNEXPECTED(EG(exception))) {
		return false;
	}
	*out = zend_is_true(&ret);
	zval_ptr_dtor(&ret);
	return true;
}

/* }}} */

/* {{{ node class info + attributes */

static void pt_node_class_info_free(zval *zv)
{
	pt_node_class_info *info = (pt_node_class_info *) Z_PTR_P(zv);
	if (info->subnode_offsets != NULL) {
		efree(info->subnode_offsets);
	}
	efree(info);
}

int32_t pt_instance_prop_offset(zend_class_entry *ce, const char *name, size_t len)
{
	zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, name, len);
	if (info == NULL || (info->flags & ZEND_ACC_STATIC) != 0) {
		return -1;
	}
	return (int32_t) info->offset;
}

pt_node_class_info *pt_get_node_class_info(zend_class_entry *ce)
{
	pt_node_class_info *info;
	zend_class_entry *variable_ce;

	if (!pt_node_class_cache_inited) {
		zend_hash_init(&pt_node_class_cache, 64, NULL, pt_node_class_info_free, 0);
		pt_node_class_cache_inited = true;
	}

	info = (pt_node_class_info *) zend_hash_find_ptr(&pt_node_class_cache, ce->name);
	if (EXPECTED(info != NULL)) {
		return info;
	}

	info = (pt_node_class_info *) ecalloc(1, sizeof(pt_node_class_info));
	info->attributes_offset = pt_instance_prop_offset(ce, "attributes", sizeof("attributes") - 1);
	info->name_offset = pt_instance_prop_offset(ce, "name", sizeof("name") - 1);

	variable_ce = pt_class(PT_CLASS_VARIABLE);
	if (variable_ce == NULL) {
		efree(info);
		return NULL;
	}
	info->is_variable = instanceof_function(ce, variable_ce);

	zend_hash_add_ptr(&pt_node_class_cache, ce->name, info);
	return info;
}

pt_node_class_info *pt_node_class_info_for_object(zend_object *obj)
{
	zend_class_entry *ce = obj->ce;
	pt_node_class_info *info = pt_get_node_class_info(ce);
	zend_function *fn;
	zval retval;

	if (info == NULL) {
		return NULL;
	}
	if (info->subnode_offsets != NULL || info->subnode_count == UINT32_MAX) {
		return info;
	}

	fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, "getsubnodenames", sizeof("getsubnodenames") - 1);
	if (fn == NULL || (fn->common.fn_flags & ZEND_ACC_ABSTRACT) != 0) {
		info->subnode_count = UINT32_MAX;
		return info;
	}

	zend_call_known_function(fn, obj, ce, &retval, 0, NULL, NULL);
	if (EG(exception) || Z_TYPE(retval) != IS_ARRAY) {
		zval_ptr_dtor(&retval);
		info->subnode_count = UINT32_MAX;
		return info;
	}

	{
		HashTable *names = Z_ARRVAL(retval);
		uint32_t count = zend_hash_num_elements(names);
		uint32_t i = 0;
		zval *name_zv;

		info->subnode_offsets = (uint32_t *) emalloc(sizeof(uint32_t) * (count > 0 ? count : 1));
		ZEND_HASH_FOREACH_VAL(names, name_zv) {
			int32_t off;
			if (Z_TYPE_P(name_zv) != IS_STRING) {
				continue;
			}
			off = pt_instance_prop_offset(ce, Z_STRVAL_P(name_zv), Z_STRLEN_P(name_zv));
			if (off >= 0) {
				info->subnode_offsets[i++] = (uint32_t) off;
			}
		} ZEND_HASH_FOREACH_END();
		info->subnode_count = i;
	}
	zval_ptr_dtor(&retval);
	return info;
}

zval *pt_node_attribute(zend_object *node, zend_string *name)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *attrs;

	if (info == NULL || info->attributes_offset < 0) {
		return NULL;
	}
	attrs = OBJ_PROP(node, info->attributes_offset);
	ZVAL_DEREF(attrs);
	if (Z_TYPE_P(attrs) != IS_ARRAY) {
		return NULL;
	}
	return zend_hash_find(Z_ARRVAL_P(attrs), name);
}

bool pt_node_set_attribute(zend_object *node, zend_string *name, zval *value)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *attrs;

	if (info == NULL || info->attributes_offset < 0) {
		return false;
	}
	attrs = OBJ_PROP(node, info->attributes_offset);
	ZVAL_DEREF(attrs);
	if (Z_TYPE_P(attrs) != IS_ARRAY) {
		return false;
	}
	SEPARATE_ARRAY(attrs);
	Z_TRY_ADDREF_P(value);
	zend_hash_update(Z_ARRVAL_P(attrs), name, value);
	return true;
}

/* }}} */

/* {{{ node key */

static bool pt_call_print_expr(zval *expr_printer, zend_object *node, zval *result)
{
	zend_class_entry *ce = Z_OBJCE_P(expr_printer);
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, "printexpr", sizeof("printexpr") - 1);
	zval arg;

	if (UNEXPECTED(fn == NULL)) {
		zend_throw_error(NULL, "phpstan_turbo: printExpr not found");
		return false;
	}
	ZVAL_OBJ(&arg, node);
	zend_call_known_function(fn, Z_OBJ_P(expr_printer), ce, result, 1, &arg, NULL);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(result);
		return false;
	}
	if (UNEXPECTED(Z_TYPE_P(result) != IS_STRING)) {
		zval_ptr_dtor(result);
		zend_throw_error(NULL, "phpstan_turbo: printExpr did not return a string");
		return false;
	}
	return true;
}

/* The printed form of the expression, without pt_node_key's attribute-derived
 * suffixes — the equivalent of a plain $exprPrinter->printExpr($node) call
 * (which caches through the printer attribute). Owned string; NULL on
 * failure with an exception pending. */
static zend_string *pt_node_printed_expr(zend_object *node, zval *expr_printer)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);

	if (info == NULL) {
		return NULL;
	}

	/* fast path: '$' . $node->name for Variable with a string name */
	if (info->is_variable && info->name_offset >= 0) {
		zval *name = OBJ_PROP(node, info->name_offset);
		ZVAL_DEREF(name);
		if (Z_TYPE_P(name) == IS_STRING) {
			zend_string *name_str = Z_STR_P(name);
			zend_string *key = zend_string_alloc(ZSTR_LEN(name_str) + 1, 0);
			ZSTR_VAL(key)[0] = '$';
			memcpy(ZSTR_VAL(key) + 1, ZSTR_VAL(name_str), ZSTR_LEN(name_str));
			ZSTR_VAL(key)[ZSTR_LEN(key)] = '\0';
			return key;
		}
	}

	zval *attr = pt_node_attribute(node, pt_str_cache_printer);
	if (attr != NULL && Z_TYPE_P(attr) == IS_STRING) {
		return zend_string_copy(Z_STR_P(attr));
	}

	zval printed;
	if (!pt_call_print_expr(expr_printer, node, &printed)) {
		return NULL;
	}
	return Z_STR(printed); /* take ownership */
}

zend_string *pt_node_key(zend_object *node, zval *expr_printer)
{
	zend_string *key;

	pt_init_strs();

	/* the Variable fast path returns before any suffix handling below, same
	 * as the twin: a Variable node never carries the suffix attributes */
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	if (info == NULL) {
		return NULL;
	}
	if (info->is_variable && info->name_offset >= 0) {
		zval *name = OBJ_PROP(node, info->name_offset);
		ZVAL_DEREF(name);
		if (Z_TYPE_P(name) == IS_STRING) {
			return pt_node_printed_expr(node, expr_printer);
		}
	}

	key = pt_node_printed_expr(node, expr_printer);
	if (key == NULL) {
		return NULL;
	}

	/* FunctionLike with arrayMapArgs + startFilePos: append the array_map
	 * argument suffix exactly like MutatingScope::getNodeKey() */
	{
		zend_class_entry *fl_ce = pt_class(PT_CLASS_FUNCTION_LIKE);
		if (fl_ce == NULL) {
			zend_string_release(key);
			return NULL;
		}
		if (instanceof_function(node->ce, fl_ce)) {
			zval *map_args = pt_node_attribute(node, pt_str_array_map_args);
			zval *start_pos = pt_node_attribute(node, pt_str_start_file_pos);
			if (map_args != NULL && Z_TYPE_P(map_args) != IS_NULL
				&& start_pos != NULL && Z_TYPE_P(start_pos) != IS_NULL) {
				smart_str str = {};
				smart_str_append(&str, key);
				smart_str_appendl(&str, "/*", 2);
				if (Z_TYPE_P(start_pos) == IS_LONG) {
					smart_str_append_long(&str, Z_LVAL_P(start_pos));
				}
				if (Z_TYPE_P(map_args) == IS_ARRAY) {
					zval *arg;
					ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(map_args), arg) {
						zval *arg_deref = arg;
						zval *value_prop;
						ZVAL_DEREF(arg_deref);
						if (Z_TYPE_P(arg_deref) != IS_OBJECT) {
							continue;
						}
						{
							int32_t voff = pt_instance_prop_offset(Z_OBJCE_P(arg_deref), "value", sizeof("value") - 1);
							if (voff < 0) {
								continue;
							}
							value_prop = OBJ_PROP(Z_OBJ_P(arg_deref), voff);
							ZVAL_DEREF(value_prop);
						}
						if (Z_TYPE_P(value_prop) != IS_OBJECT) {
							continue;
						}
						smart_str_appendc(&str, ':');
						{
							/* plain printExpr like the twin — NOT the full node
							 * key: an argument carrying its own suffix
							 * attributes must not have them appended here */
							zend_string *arg_key = pt_node_printed_expr(Z_OBJ_P(value_prop), expr_printer);
							if (arg_key == NULL) {
								smart_str_free(&str);
								zend_string_release(key);
								return NULL;
							}
							smart_str_append(&str, arg_key);
							zend_string_release(arg_key);
						}
					} ZEND_HASH_FOREACH_END();
				}
				smart_str_appendl(&str, "*/", 2);
				zend_string_release(key);
				key = smart_str_extract(&str);
			}
		}
	}

	{
		zval *keep_void = pt_node_attribute(node, pt_str_keep_void);
		if (keep_void != NULL && Z_TYPE_P(keep_void) == IS_TRUE) {
			smart_str str = {};
			smart_str_append(&str, key);
			smart_str_appendl(&str, "/*keepVoid*/", sizeof("/*keepVoid*/") - 1);
			zend_string_release(key);
			key = smart_str_extract(&str);
		}
	}

	return key;
}

/* }}} */

/* {{{ findFirst walker + superglobal scan */

zend_object *pt_find_first_recursive(zend_object *node, pt_node_matcher matcher, void *ctx)
{
	pt_node_class_info *info;
	zend_class_entry *node_iface;
	uint32_t i;

	if (matcher(node, ctx)) {
		return node;
	}
	if (UNEXPECTED(((pt_find_ctx *) ctx)->failed)) {
		return NULL;
	}

	info = pt_node_class_info_for_object(node);
	if (info == NULL || !PT_HAS_SUBNODES(info)) {
		return NULL;
	}

	node_iface = pt_class(PT_CLASS_NODE);
	if (UNEXPECTED(node_iface == NULL)) {
		((pt_find_ctx *) ctx)->failed = true;
		return NULL;
	}

	for (i = 0; i < info->subnode_count; i++) {
		zval *val = OBJ_PROP(node, info->subnode_offsets[i]);
		ZVAL_DEREF(val);
		if (Z_TYPE_P(val) == IS_OBJECT) {
			if (instanceof_function(Z_OBJCE_P(val), node_iface)) {
				zend_object *found = pt_find_first_recursive(Z_OBJ_P(val), matcher, ctx);
				if (found != NULL || ((pt_find_ctx *) ctx)->failed) {
					return found;
				}
			}
		} else if (Z_TYPE_P(val) == IS_ARRAY) {
			zval *el;
			ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(val), el) {
				zval *el_deref = el;
				ZVAL_DEREF(el_deref);
				if (Z_TYPE_P(el_deref) == IS_OBJECT && instanceof_function(Z_OBJCE_P(el_deref), node_iface)) {
					zend_object *found = pt_find_first_recursive(Z_OBJ_P(el_deref), matcher, ctx);
					if (found != NULL || ((pt_find_ctx *) ctx)->failed) {
						return found;
					}
				}
			} ZEND_HASH_FOREACH_END();
		}
	}
	return NULL;
}

static const struct { const char *name; size_t len; } pt_superglobals[] = {
	{"GLOBALS", 7},
	{"_SERVER", 7},
	{"_GET", 4},
	{"_POST", 5},
	{"_FILES", 6},
	{"_COOKIE", 7},
	{"_SESSION", 8},
	{"_REQUEST", 8},
	{"_ENV", 4},
};

bool pt_is_superglobal_name(zend_string *name)
{
	for (size_t i = 0; i < sizeof(pt_superglobals) / sizeof(pt_superglobals[0]); i++) {
		if (ZSTR_LEN(name) == pt_superglobals[i].len
			&& memcmp(ZSTR_VAL(name), pt_superglobals[i].name, pt_superglobals[i].len) == 0) {
			return true;
		}
	}
	return false;
}

static bool pt_superglobal_matcher(zend_object *node, void *ctx)
{
	pt_node_class_info *info = pt_get_node_class_info(node->ce);
	zval *name;

	(void) ctx;
	if (info == NULL || !info->is_variable || info->name_offset < 0) {
		return false;
	}
	name = OBJ_PROP(node, info->name_offset);
	ZVAL_DEREF(name);
	if (Z_TYPE_P(name) != IS_STRING) {
		return false;
	}
	return pt_is_superglobal_name(Z_STR_P(name));
}

bool pt_expr_contains_superglobal(zend_object *expr)
{
	zval *attr;
	pt_find_ctx ctx;
	bool contains;
	zval attr_val;

	pt_init_strs();

	attr = pt_node_attribute(expr, pt_str_contains_super_global);
	if (attr != NULL && (Z_TYPE_P(attr) == IS_TRUE || Z_TYPE_P(attr) == IS_FALSE)) {
		return Z_TYPE_P(attr) == IS_TRUE;
	}

	memset(&ctx, 0, sizeof(ctx));
	contains = pt_find_first_recursive(expr, pt_superglobal_matcher, &ctx) != NULL;
	ZVAL_BOOL(&attr_val, contains);
	pt_node_set_attribute(expr, pt_str_contains_super_global, &attr_val);
	return contains;
}

/* }}} */

/* {{{ holder helpers */

bool pt_check_holder(zval *zv)
{
	if (UNEXPECTED(Z_TYPE_P(zv) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(zv), pt_ce_expr_type_holder))) {
		zend_type_error("phpstan_turbo: expected ExpressionTypeHolder, got %s", zend_zval_value_name(zv));
		return false;
	}
	return true;
}

void pt_holder_create(zval *result, zval *expr, zval *type, zend_long certainty)
{
	zend_class_entry *impl = pt_impl_class(PT_CLASS_ETH, pt_ce_expr_type_holder);
	zend_object *obj;
	object_init_ex(result, impl);
	obj = Z_OBJ_P(result);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_EXPR), expr);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_TYPE), type);
	ZVAL_COPY(OBJ_PROP_NUM(obj, PT_ETH_PROP_CERTAINTY), pt_trinary_singleton(certainty));
}

bool pt_holder_and(zval *a, zval *b, zval *result)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);
	zval *a_type = OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE);
	zval *b_type = OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE);
	zend_long ac = pt_holder_certainty_value(ao);
	zend_long bc = pt_holder_certainty_value(bo);

	if (pt_types_identical_or_equal(a_type, b_type)) {
		if ((ac & bc) == PT_TRI_YES || ac == PT_TRI_MAYBE) {
			ZVAL_COPY(result, a);
		} else {
			ZVAL_COPY(result, b);
		}
		return true;
	}
	if (UNEXPECTED(EG(exception))) {
		return false;
	}
	{
		zval union_type;
		if (UNEXPECTED(!pt_type_combinator_binary("union", sizeof("union") - 1, a_type, b_type, &union_type))) {
			return false;
		}
		pt_holder_create(result, OBJ_PROP_NUM(ao, PT_ETH_PROP_EXPR), &union_type, ac & bc);
		zval_ptr_dtor(&union_type);
	}
	return true;
}

bool pt_holder_equals(zval *a, zval *b, bool *out)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);

	if (ao == bo) {
		*out = true;
		return true;
	}
	if (pt_holder_certainty_value(ao) != pt_holder_certainty_value(bo)) {
		*out = false;
		return true;
	}
	*out = pt_types_identical_or_equal(OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE), OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE));
	return !EG(exception);
}

bool pt_holder_equal_types(zval *a, zval *b, bool *out)
{
	zend_object *ao = Z_OBJ_P(a);
	zend_object *bo = Z_OBJ_P(b);
	if (ao == bo) {
		*out = true;
		return true;
	}
	*out = pt_types_identical_or_equal(OBJ_PROP_NUM(ao, PT_ETH_PROP_TYPE), OBJ_PROP_NUM(bo, PT_ETH_PROP_TYPE));
	return !EG(exception);
}

zend_string *pt_ceh_key_build(HashTable *conds, zval *type_holder)
{
	smart_str str = {};
	zend_string *key_str;
	zend_ulong kidx;
	zval *entry;
	bool first = true;

	ZEND_HASH_FOREACH_KEY_VAL(conds, kidx, key_str, entry) {
		zval described;
		zval *entry_deref = entry;
		ZVAL_DEREF(entry_deref);
		if (!first) {
			smart_str_appendl(&str, " && ", 4);
		}
		first = false;
		if (key_str != NULL) {
			smart_str_append(&str, key_str);
		} else {
			smart_str_append_long(&str, (zend_long) kidx);
		}
		smart_str_appendc(&str, '=');
		if (UNEXPECTED(!pt_type_describe_precise(OBJ_PROP_NUM(Z_OBJ_P(entry_deref), PT_ETH_PROP_TYPE), &described))) {
			smart_str_free(&str);
			return NULL;
		}
		if (EXPECTED(Z_TYPE(described) == IS_STRING)) {
			smart_str_append(&str, Z_STR(described));
		}
		zval_ptr_dtor(&described);
	} ZEND_HASH_FOREACH_END();

	smart_str_appendl(&str, " => ", 4);
	{
		zval described;
		if (UNEXPECTED(!pt_type_describe_precise(OBJ_PROP_NUM(Z_OBJ_P(type_holder), PT_ETH_PROP_TYPE), &described))) {
			smart_str_free(&str);
			return NULL;
		}
		if (EXPECTED(Z_TYPE(described) == IS_STRING)) {
			smart_str_append(&str, Z_STR(described));
		}
		zval_ptr_dtor(&described);
	}
	smart_str_appendl(&str, " (", 2);
	{
		zend_long certainty = pt_holder_certainty_value(Z_OBJ_P(type_holder));
		if (certainty == PT_TRI_YES) {
			smart_str_appendl(&str, "Yes", 3);
		} else if (certainty == PT_TRI_MAYBE) {
			smart_str_appendl(&str, "Maybe", 5);
		} else {
			smart_str_appendl(&str, "No", 2);
		}
	}
	smart_str_appendc(&str, ')');

	return smart_str_extract(&str);
}

/* }}} */
