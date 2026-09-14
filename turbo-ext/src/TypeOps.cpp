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
