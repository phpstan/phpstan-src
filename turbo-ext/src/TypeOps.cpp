/*
 * The op contracts and the class-entry -> ops table behind pt_type_op()
 * (TypeOps.h).
 */

#include "TypeOps.h"

#include <cstring>

/* in pt_type_op_id order; lcname is what pt_type_call() looks up on the
 * engine path, argc / argKinds the handler's zpp contract */
const pt_type_op_info pt_type_op_infos[PT_OP_COUNT] = {
	/* PT_OP_EQUALS */ { "equals", sizeof("equals") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_TRAVERSE */ { "traverse", sizeof("traverse") - 1, 1, pt_type_op_kinds(PT_OPARG_ANY) },
	/* PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE */ { "hastemplateorlateresolvabletype", sizeof("hastemplateorlateresolvabletype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_SUPER_TYPE_OF */ { "issupertypeof", sizeof("issupertypeof") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_IS_SUB_TYPE_OF */ { "issubtypeof", sizeof("issubtypeof") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_ACCEPTS */ { "accepts", sizeof("accepts") - 1, 2, pt_type_op_kinds(PT_OPARG_OBJECT, PT_OPARG_BOOL) },
	/* PT_OP_DESCRIBE */ { "describe", sizeof("describe") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_GET_OBJECT_CLASS_NAMES */ { "getobjectclassnames", sizeof("getobjectclassnames") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_ITERABLE_VALUE_TYPE */ { "getiterablevaluetype", sizeof("getiterablevaluetype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_ITERABLE_KEY_TYPE */ { "getiterablekeytype", sizeof("getiterablekeytype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_ITERABLE_AT_LEAST_ONCE */ { "isiterableatleastonce", sizeof("isiterableatleastonce") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_STRING */ { "isstring", sizeof("isstring") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_ARRAY */ { "isarray", sizeof("isarray") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_CONSTANT_ARRAY */ { "isconstantarray", sizeof("isconstantarray") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_CONSTANT_SCALAR_VALUE */ { "isconstantscalarvalue", sizeof("isconstantscalarvalue") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_CALLABLE */ { "iscallable", sizeof("iscallable") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_LIST */ { "islist", sizeof("islist") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_INTEGER */ { "isinteger", sizeof("isinteger") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_BOOLEAN */ { "isboolean", sizeof("isboolean") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_FLOAT */ { "isfloat", sizeof("isfloat") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_NULL */ { "isnull", sizeof("isnull") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_VOID */ { "isvoid", sizeof("isvoid") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_TO_ARRAY_KEY */ { "toarraykey", sizeof("toarraykey") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_CONSTANT_SCALAR_VALUES */ { "getconstantscalarvalues", sizeof("getconstantscalarvalues") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_CONSTANT_ARRAYS */ { "getconstantarrays", sizeof("getconstantarrays") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_REFERENCED_TEMPLATE_TYPES */ { "getreferencedtemplatetypes", sizeof("getreferencedtemplatetypes") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_AND */ { "and", sizeof("and") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_GET_ENUM_CASE_OBJECT */ { "getenumcaseobject", sizeof("getenumcaseobject") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_COMPLETE */ { "iscomplete", sizeof("iscomplete") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_UNSEALED */ { "isunsealed", sizeof("isunsealed") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_KEY_TYPES */ { "getkeytypes", sizeof("getkeytypes") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_VALUE_TYPES */ { "getvaluetypes", sizeof("getvaluetypes") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_OPTIONAL_KEYS */ { "getoptionalkeys", sizeof("getoptionalkeys") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_VALUE */ { "getvalue", sizeof("getvalue") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_CLASS_REFLECTION */ { "getclassreflection", sizeof("getclassreflection") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_TYPES */ { "gettypes", sizeof("gettypes") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_SUBTRACTED_TYPE */ { "getsubtractedtype", sizeof("getsubtractedtype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_ITEM_TYPE */ { "getitemtype", sizeof("getitemtype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_TYPE_WITHOUT_SUBTRACTED_TYPE */ { "gettypewithoutsubtractedtype", sizeof("gettypewithoutsubtractedtype") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_OBJECT_CLASS_REFLECTIONS */ { "getobjectclassreflections", sizeof("getobjectclassreflections") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_REFERENCED_CLASSES */ { "getreferencedclasses", sizeof("getreferencedclasses") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_TYPE_ONLY */ { "istypeonly", sizeof("istypeonly") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_ANCESTOR_WITH_CLASS_NAME */ { "getancestorwithclassname", sizeof("getancestorwithclassname") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_HAS_METHOD */ { "hasmethod", sizeof("hasmethod") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_HAS_INSTANCE_PROPERTY */ { "hasinstanceproperty", sizeof("hasinstanceproperty") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_CHANGE_BASE_CLASS */ { "changebaseclass", sizeof("changebaseclass") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_HAS_OFFSET_VALUE_TYPE */ { "hasoffsetvaluetype", sizeof("hasoffsetvaluetype") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_GET_OFFSET_VALUE_TYPE */ { "getoffsetvaluetype", sizeof("getoffsetvaluetype") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_OR */ { "or", sizeof("or") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
	/* PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE */ { "getunresolvedinstancepropertyprototype", sizeof("getunresolvedinstancepropertyprototype") - 1, 2, pt_type_op_kinds(PT_OPARG_STRING, PT_OPARG_OBJECT) },
	/* PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE */ { "getunresolvedmethodprototype", sizeof("getunresolvedmethodprototype") - 1, 2, pt_type_op_kinds(PT_OPARG_STRING, PT_OPARG_OBJECT) },
	/* PT_OP_GET_METHOD */ { "getmethod", sizeof("getmethod") - 1, 2, pt_type_op_kinds(PT_OPARG_STRING, PT_OPARG_OBJECT) },
	/* PT_OP_GET_INSTANCE_PROPERTY */ { "getinstanceproperty", sizeof("getinstanceproperty") - 1, 2, pt_type_op_kinds(PT_OPARG_STRING, PT_OPARG_OBJECT) },
	/* PT_OP_GET_TRANSFORMED_METHOD */ { "gettransformedmethod", sizeof("gettransformedmethod") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_TRANSFORMED_PROPERTY */ { "gettransformedproperty", sizeof("gettransformedproperty") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_NAKED_METHOD */ { "getnakedmethod", sizeof("getnakedmethod") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_NAKED_PROPERTY */ { "getnakedproperty", sizeof("getnakedproperty") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_ACTIVE_TEMPLATE_TYPE_MAP */ { "getactivetemplatetypemap", sizeof("getactivetemplatetypemap") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_GET_CALL_SITE_VARIANCE_MAP */ { "getcallsitevariancemap", sizeof("getcallsitevariancemap") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_HAS_NATIVE_METHOD */ { "hasnativemethod", sizeof("hasnativemethod") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_GET_NATIVE_METHOD */ { "getnativemethod", sizeof("getnativemethod") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_HAS_NATIVE_PROPERTY */ { "hasnativeproperty", sizeof("hasnativeproperty") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_GET_NATIVE_PROPERTY */ { "getnativeproperty", sizeof("getnativeproperty") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_IS_FINAL_BY_KEYWORD */ { "isfinalbykeyword", sizeof("isfinalbykeyword") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_FINAL */ { "isfinal", sizeof("isfinal") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_INTERFACE */ { "isinterface", sizeof("isinterface") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_TRAIT */ { "istrait", sizeof("istrait") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS_BUILTIN */ { "isbuiltin", sizeof("isbuiltin") - 1, 0, pt_type_op_kinds() },
	/* PT_OP_IS */ { "is", sizeof("is") - 1, 1, pt_type_op_kinds(PT_OPARG_STRING) },
	/* PT_OP_IS_SUBCLASS_OF_CLASS */ { "issubclassofclass", sizeof("issubclassofclass") - 1, 1, pt_type_op_kinds(PT_OPARG_OBJECT) },
};

pt_type_ops_slot pt_type_ops_table[PT_TYPE_OPS_TABLE_SIZE];

void pt_type_ops_attach(zend_class_entry *ce, const pt_type_ops *ops)
{
	size_t i = pt_type_ops_hash(ce);
	for (size_t probes = 0; probes < PT_TYPE_OPS_TABLE_SIZE; probes++) {
		pt_type_ops_slot &slot = pt_type_ops_table[i];
		if (slot.ce == NULL || slot.ce == ce) {
			slot.ce = ce;
			slot.ops = ops;
			return;
		}
		i = (i + 1) & (PT_TYPE_OPS_TABLE_SIZE - 1);
	}
	zend_error_noreturn(E_CORE_ERROR, "phpstan_turbo: the native ops table is full");
}

/* the name index: the ops chained by name length, built on first use */
static int8_t pt_type_op_head_by_len[64];
static int8_t pt_type_op_next[PT_OP_COUNT];
static bool pt_type_op_names_built = false;

static void pt_type_op_names_build()
{
	memset(pt_type_op_head_by_len, -1, sizeof(pt_type_op_head_by_len));
	for (int op = 0; op < PT_OP_COUNT; op++) {
		uint8_t len = pt_type_op_infos[op].len;
		ZEND_ASSERT(len < sizeof(pt_type_op_head_by_len));
		pt_type_op_next[op] = pt_type_op_head_by_len[len];
		pt_type_op_head_by_len[len] = (int8_t) op;
	}
	pt_type_op_names_built = true;
}

pt_type_op_id pt_type_op_of_name(const char *lcname, size_t len)
{
	if (UNEXPECTED(!pt_type_op_names_built)) {
		pt_type_op_names_build();
	}
	if (len >= sizeof(pt_type_op_head_by_len)) return PT_OP_COUNT;
	for (int8_t op = pt_type_op_head_by_len[len]; op >= 0; op = pt_type_op_next[op]) {
		if (memcmp(lcname, pt_type_op_infos[op].lcname, len) == 0) return (pt_type_op_id) op;
	}
	return PT_OP_COUNT;
}
