/*
 * Direct C++ dispatch between native classes — pt_type_op().
 *
 * A native body calling `$type->equals($other)` on another native object
 * used to go through the engine: pt_type_call() looks the method up by
 * name, zend_call_function() pushes a frame, copies and addrefs the
 * arguments, the handler runs its ZEND_PARSE_PARAMETERS glue, and the
 * frame is torn down again — 34M such calls per self-analysis, all of them
 * landing in a one-line lambda that delegates to a handle class. Here every
 * hot operation (pt_type_op_id) gets, per registered class, a C++ entry
 * point (`pt_type_op_fn`) that delegates to the same handle-class member the
 * registered handler delegates to; pt_type_op() calls it directly when the
 * receiver is EXACTLY a registered native class and that class registered
 * the op, and falls back to pt_type_call() by name otherwise — so a PHP
 * subclass overriding the method, an object of any other class, or a class
 * that did not register the op behaves exactly as before.
 *
 * Finding the ops of a class entry: the engine leaves no field of a
 * runtime-linked user zend_class_entry free for us — info.user.* is read by
 * reflection and inheritance, iterator_funcs_ptr and the enum fields are
 * conditionally inherited, and stashing a pointer in any of them would be
 * a bet on engine internals. The ops are therefore found in a small
 * direct-mapped open-addressing table keyed by the zend_class_entry pointer
 * (pt_type_ops_of()): one multiply-hash, one indexed load, one pointer
 * compare on the common hit and miss. The table is filled at activation
 * (Shadow.cpp), where the linked class entry becomes known.
 *
 * What the direct path skips, and why that is safe:
 * - the call frame: no zend_execute_data is pushed, so observers (xdebug,
 *   the profilers) do not see the call and an exception thrown inside has
 *   no frame for the callee in its trace. The operations are pure Type
 *   queries that never throw in a correct run.
 * - the handler's ZEND_PARSE_PARAMETERS: replaced by the op's argument
 *   contract (pt_type_op_info: count and zval kind per argument — object,
 *   bool, string) checked before dispatch; a mismatch, a reference, or an
 *   int where a bool is expected takes the engine path, which raises the
 *   very TypeError or coerces the value the way zpp would. The class of
 *   an object argument is not checked by zpp either (Z_PARAM_OBJECT), so
 *   the direct entry sees exactly what the handler would see.
 * - argument and receiver addrefs: zend_call_function() copies the
 *   arguments into the frame and addrefs $this; the direct entry borrows
 *   them from the caller, who holds them for the duration of the call.
 *   Safe because the dispatched bodies run on immutable Type / result
 *   objects and never release their receiver or arguments.
 * - the return-type check: the engine never verifies an internal
 *   function's declared return type at run time in a release build; the
 *   direct entry returns the value the handler would have returned.
 *
 * Inheritance: a native subclass that does not declare a method itself
 * (own or via a trait registrar) inherits the parent plan's entry at
 * activation, together with the parent's scope — the inherited internal
 * method keeps its declaring scope in the engine too, so `self` inside a
 * trait body (the scope parameter) resolves the same way on both paths.
 */

#ifndef PHPSTANTURBO_TYPEOPS_H
#define PHPSTANTURBO_TYPEOPS_H

#include "support.h"
#include "zv.h"

/* the hot operations, picked from the native -> native call census; the
 * names and argument contracts are in pt_type_op_infos (TypeOps.cpp), in
 * this order */
enum pt_type_op_id : uint8_t
{
	PT_OP_EQUALS,
	PT_OP_TRAVERSE,
	PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE,
	PT_OP_IS_SUPER_TYPE_OF,
	PT_OP_IS_SUB_TYPE_OF,
	PT_OP_ACCEPTS,
	PT_OP_DESCRIBE,
	PT_OP_GET_OBJECT_CLASS_NAMES,
	PT_OP_GET_ITERABLE_VALUE_TYPE,
	PT_OP_GET_ITERABLE_KEY_TYPE,
	PT_OP_IS_ITERABLE_AT_LEAST_ONCE,
	PT_OP_IS_STRING,
	PT_OP_IS_ARRAY,
	PT_OP_IS_CONSTANT_ARRAY,
	PT_OP_IS_CONSTANT_SCALAR_VALUE,
	PT_OP_IS_CALLABLE,
	PT_OP_IS_LIST,
	PT_OP_IS_INTEGER,
	PT_OP_IS_BOOLEAN,
	PT_OP_IS_FLOAT,
	PT_OP_IS_NULL,
	PT_OP_IS_VOID,
	PT_OP_TO_ARRAY_KEY,
	PT_OP_GET_CONSTANT_SCALAR_VALUES,
	PT_OP_GET_CONSTANT_ARRAYS,
	PT_OP_GET_REFERENCED_TEMPLATE_TYPES,
	PT_OP_AND, /* IsSuperTypeOfResult / AcceptsResult ::and($other) */
	/* the second round: the getters, member queries and result operations
	 * left on the engine path */
	PT_OP_GET_ENUM_CASE_OBJECT,
	PT_OP_IS_COMPLETE,
	PT_OP_IS_UNSEALED,
	PT_OP_GET_KEY_TYPES,
	PT_OP_GET_VALUE_TYPES,
	PT_OP_GET_OPTIONAL_KEYS,
	PT_OP_GET_VALUE,
	PT_OP_GET_CLASS_REFLECTION,
	PT_OP_GET_TYPES,
	PT_OP_GET_SUBTRACTED_TYPE,
	PT_OP_GET_ITEM_TYPE,
	PT_OP_GET_TYPE_WITHOUT_SUBTRACTED_TYPE,
	PT_OP_GET_OBJECT_CLASS_REFLECTIONS,
	PT_OP_GET_REFERENCED_CLASSES,
	PT_OP_IS_TYPE_ONLY,
	PT_OP_GET_ANCESTOR_WITH_CLASS_NAME,
	PT_OP_HAS_METHOD,
	PT_OP_HAS_INSTANCE_PROPERTY,
	PT_OP_CHANGE_BASE_CLASS,
	PT_OP_HAS_OFFSET_VALUE_TYPE,
	PT_OP_GET_OFFSET_VALUE_TYPE,
	PT_OP_OR,
	PT_OP_GET_UNRESOLVED_INSTANCE_PROPERTY_PROTOTYPE,
	PT_OP_GET_UNRESOLVED_METHOD_PROTOTYPE,
	PT_OP_COUNT,
};

/* a direct entry: the receiver, the scope of the declaring class (`self`
 * in trait bodies), and the arguments as the handler's zpp would have
 * delivered them (borrowed); UNDEF = pending exception */
typedef zv::Val (*pt_type_op_fn)(zend_object *self, zend_class_entry *scope, uint32_t argc, zval *argv);

/* the zval kinds an op's arguments must have for the direct path */
enum pt_type_op_arg : uint8_t
{
	PT_OPARG_ANY = 0,
	PT_OPARG_OBJECT = 1,
	PT_OPARG_BOOL = 2,
	PT_OPARG_STRING = 3,
};

struct pt_type_op_info
{
	const char *lcname;
	uint8_t len;
	uint8_t argc;
	uint8_t argKinds; /* two bits per argument, argument 0 in the low bits */
};

extern const pt_type_op_info pt_type_op_infos[PT_OP_COUNT];

constexpr uint8_t pt_type_op_kinds(pt_type_op_arg a0 = PT_OPARG_ANY, pt_type_op_arg a1 = PT_OPARG_ANY)
{
	return (uint8_t) (a0 | (a1 << 2));
}

struct pt_type_op_entry
{
	pt_type_op_fn fn;
	zend_class_entry *scope;
};

/* the entries of one class, indexed by pt_type_op_id; fn NULL = not registered */
struct pt_type_ops
{
	pt_type_op_entry entries[PT_OP_COUNT];
};

/* the class-entry -> ops table: 2^PT_TYPE_OPS_TABLE_BITS slots, at most a
 * quarter full for the ~150 native classes, linear probing */
#define PT_TYPE_OPS_TABLE_BITS 10
#define PT_TYPE_OPS_TABLE_SIZE (1u << PT_TYPE_OPS_TABLE_BITS)

struct pt_type_ops_slot
{
	zend_class_entry *ce;
	const pt_type_ops *ops;
};

extern pt_type_ops_slot pt_type_ops_table[PT_TYPE_OPS_TABLE_SIZE];

static zend_always_inline size_t pt_type_ops_hash(const zend_class_entry *ce)
{
	/* Fibonacci hashing of the pointer (its low bits are alignment) */
	uintptr_t h = ((uintptr_t) ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	return (size_t) (h >> (sizeof(uintptr_t) * 8 - PT_TYPE_OPS_TABLE_BITS));
}

/* the ops of a registered native class, NULL for any other class */
static zend_always_inline const pt_type_ops *pt_type_ops_of(const zend_class_entry *ce)
{
	size_t i = pt_type_ops_hash(ce);
	for (;;) {
		const pt_type_ops_slot &slot = pt_type_ops_table[i];
		if (slot.ce == ce) return slot.ops;
		if (slot.ce == NULL) return NULL;
		i = (i + 1) & (PT_TYPE_OPS_TABLE_SIZE - 1);
	}
}

/* Shadow.cpp: records a class's ops once its class entry is linked (fatal
 * on a full table — cannot happen for the registered class count) */
void pt_type_ops_attach(zend_class_entry *ce, const pt_type_ops *ops);

/* the op behind a lowercase method name, PT_OP_COUNT for any other name —
 * for the by-name call helpers (pt_type_call() and the per-file wrappers
 * over it), so a native receiver takes the direct path there too; a
 * length-bucketed memcmp scan, run only when the receiver has an ops
 * table */
pt_type_op_id pt_type_op_of_name(const char *lcname, size_t len);

/* whether the arguments satisfy the op's contract */
static zend_always_inline bool pt_type_op_args_ok(const pt_type_op_info &info, uint32_t argc, const zval *argv)
{
	if (argc != info.argc) return false;
	uint8_t kinds = info.argKinds;
	for (uint32_t i = 0; i < argc; i++, kinds >>= 2) {
		switch (kinds & 3) {
			case PT_OPARG_OBJECT:
				if (Z_TYPE(argv[i]) != IS_OBJECT) return false;
				break;
			case PT_OPARG_BOOL:
				if (Z_TYPE(argv[i]) != IS_TRUE && Z_TYPE(argv[i]) != IS_FALSE) return false;
				break;
			case PT_OPARG_STRING:
				if (Z_TYPE(argv[i]) != IS_STRING) return false;
				break;
			default:
				break;
		}
	}
	return true;
}

/* $object->method(...$args) through the object's own class entry
 * (TypeTraits.cpp) — pt_type_call() takes the direct entry when the name is
 * an op the receiver's class registered, pt_type_call_engine() never does;
 * UNDEF = pending exception */
zv::Val pt_type_call(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);
zv::Val pt_type_call_engine(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);

static zend_always_inline zv::Val pt_type_op(zend_object *object, pt_type_op_id op, uint32_t argc, zval *argv)
{
	const pt_type_op_info &info = pt_type_op_infos[op];
	const pt_type_ops *ops = pt_type_ops_of(object->ce);
	if (EXPECTED(ops != NULL)) {
		const pt_type_op_entry &entry = ops->entries[op];
		if (EXPECTED(entry.fn != NULL) && EXPECTED(pt_type_op_args_ok(info, argc, argv))) return entry.fn(object, entry.scope, argc, argv);
	}
	return pt_type_call_engine(object, info.lcname, info.len, argc, argv);
}

/* the same for a method returning TrinaryLogic: the PT_TRI_* value, -1 =
 * pending exception */
zend_long pt_type_trinary_value(zval *trinary);

static zend_always_inline zend_long pt_type_op_trinary(zend_object *object, pt_type_op_id op, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_op(object, op, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

/* {{{ helpers for writing the entries — each entry is a one-line lambda
 * next to the class's cls.method(...) line, delegating to the handle class
 * the way the handler does */

/* the lambda header: self, scope, argc, argv */
#define PT_OP_LAMBDA [](zend_object *self, zend_class_entry *scope, uint32_t argc, zval *argv) -> zv::Val

/* RETURN_COPY(pt_trinary_singleton(value)), throwing for -1 */
static zend_always_inline zv::Val pt_op_trinary(zend_long value)
{
	if (UNEXPECTED(value < 0)) return zv::Val();
	return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value)));
}

/* RETURN_BOOL(out) after a `bool method(zval *, bool &out)` body */
static zend_always_inline zv::Val pt_op_bool(bool ok, bool out)
{
	return ok ? zv::Val::boolean(out) : zv::Val();
}

/* RETURN_OBJ_COPY($this) */
static zend_always_inline zv::Val pt_op_this(zend_object *self)
{
	zval z;
	ZVAL_OBJ_COPY(&z, self);
	return zv::Val::adopt(z);
}

/* RETURN_EMPTY_ARRAY() */
static zend_always_inline zv::Val pt_op_empty_array()
{
	return zv::Val(zv::Arr::empty());
}

/* RETURN_STRING(literal) */
static zend_always_inline zv::Val pt_op_string(const char *s)
{
	return zv::Val::string(s, strlen(s));
}

/* the Z_PARAM_FUNC parse of a traverse() callback: false = not callable —
 * the caller then takes the engine path, whose zpp raises the TypeError */
static zend_always_inline bool pt_op_parse_callable(zval *cb, zend_fcall_info &fci, zend_fcall_info_cache &fcc)
{
	if (UNEXPECTED(zend_fcall_info_init(cb, 0, &fci, &fcc, NULL, NULL) != SUCCESS)) return false;
	/* as Z_PARAM_FUNC does: a trampoline is refetched by the call */
	zend_release_fcall_info_cache(&fcc);
	return true;
}

/* the traverse(callable $cb) entry of a handle class H with a
 * `zv::Val traverse(zend_fcall_info *, zend_fcall_info_cache *) const`
 * member; a non-callable argument goes through the engine */
template <class H>
static zend_always_inline zv::Val pt_op_traverse_with(zend_object *self, zval *cb)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	if (UNEXPECTED(!pt_op_parse_callable(cb, fci, fcc))) return pt_type_call_engine(self, "traverse", sizeof("traverse") - 1, 1, cb);
	return H(self).traverse(&fci, &fcc);
}

/* the identity traverse (JustNullableTypeTrait's, and every class
 * registering pt_type_identity_traverse_handler()): the handler only
 * counts its argument */
static zend_always_inline zv::Val pt_op_traverse_identity(zend_object *self)
{
	return pt_op_this(self);
}

/* }}} */

#endif
