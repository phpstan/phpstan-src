/*
 * PHPStanTurbo\TypeTraverser — native implementation of PHPStan\Type\TypeTraverser.
 *
 * When the extension is active, PHPStan\Type\TypeTraverser is this class,
 * declared under that name at activation (final, like the twin). map()
 * builds one instance per traversal; the callback receives the instance's
 * [$this, 'traverseInternal'] array as its $traverse and Type::traverse()
 * receives [$this, 'mapInternal'] — the same PHP callables the twin hands
 * out, so a callback inspecting them sees no difference.
 */

#include "support.h"
#include "generated/TypeTraverser.h"

namespace slots = ptdecl::TypeTraverser::slot;
namespace sigs = ptdecl::TypeTraverser::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_type_traverser = NULL;

/* the method names of the bound callables, interned once at module
 * startup (the arrays hold them without a refcount) */
static zend_string *pt_tt_str_map_internal = NULL;
static zend_string *pt_tt_str_traverse_internal = NULL;

/* The traversals in progress, innermost last, each with its two bound
 * callables — [$this, 'mapInternal'] and [$this, 'traverseInternal'] —
 * built on first use instead of once per traversed type, and released when
 * its map() returns (each holds the instance). A traversal nested deeper
 * than the stack, or a callable called after its map() returned, builds
 * fresh ones as before. */
#define PT_TT_ACTIVE_LIMIT 64

struct ActiveTraversal
{
	zend_object *traverser;
	zval mapCallable;
	zval traverseCallable;
};

static ActiveTraversal pt_tt_active[PT_TT_ACTIVE_LIMIT];
static uint32_t pt_tt_active_depth = 0;

/* the cache slots of a traversal in progress, NULL when there is none */
static ActiveTraversal *ttActive(zend_object *traverser)
{
	for (uint32_t i = pt_tt_active_depth; i > 0 && i <= PT_TT_ACTIVE_LIMIT; i--) {
		if (pt_tt_active[i - 1].traverser == traverser) return &pt_tt_active[i - 1];
	}
	return NULL;
}

/* marks the traversal of one map() call as in progress for its lifetime */
class ActiveTraversalScope
{
public:
	explicit ActiveTraversalScope(zend_object *traverser)
	{
		if (pt_tt_active_depth < PT_TT_ACTIVE_LIMIT) {
			ActiveTraversal &entry = pt_tt_active[pt_tt_active_depth];
			entry.traverser = traverser;
			ZVAL_UNDEF(&entry.mapCallable);
			ZVAL_UNDEF(&entry.traverseCallable);
		}
		pt_tt_active_depth++;
	}

	~ActiveTraversalScope()
	{
		pt_tt_active_depth--;
		if (pt_tt_active_depth < PT_TT_ACTIVE_LIMIT) {
			ActiveTraversal &entry = pt_tt_active[pt_tt_active_depth];
			entry.traverser = NULL;
			zval_ptr_dtor(&entry.mapCallable);
			zval_ptr_dtor(&entry.traverseCallable);
			ZVAL_UNDEF(&entry.mapCallable);
			ZVAL_UNDEF(&entry.traverseCallable);
		}
	}

	ActiveTraversalScope(const ActiveTraversalScope &) = delete;
	ActiveTraversalScope &operator=(const ActiveTraversalScope &) = delete;
};

namespace phpstanturbo {

/* Mirrors PHPStan\Type\TypeTraverser. State lives in the PHP object's $cb. */
class TypeTraverser
{
public:
	explicit TypeTraverser(zend_object *self) : self(self) {}

	/* map(): `$self = new self($cb); return $self->mapInternal($type);` —
	 * the instance lives as long as the callables handed out hold it;
	 * UNDEF = pending exception */
	static zv::Val map(zval *type, zval *cb)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_type_traverser) != SUCCESS)) return zv::Val();
		zv::Val holder = zv::Val::adopt(object);
		TypeTraverser traverser(Z_OBJ(object));
		traverser.construct(cb);
		ActiveTraversalScope active(Z_OBJ(object));
		return traverser.mapInternal(type);
	}

	/* mapMemoized(): map() with `$self->memo = [];` */
	static zv::Val mapMemoized(zval *type, zval *cb)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_type_traverser) != SUCCESS)) return zv::Val();
		zv::Val holder = zv::Val::adopt(object);
		TypeTraverser traverser(Z_OBJ(object));
		traverser.construct(cb);
		zval *memo = OBJ_PROP_NUM(Z_OBJ(object), slots::memo);
		zval_ptr_dtor(memo);
		ZVAL_EMPTY_ARRAY(memo);
		ActiveTraversalScope active(Z_OBJ(object));
		return traverser.mapInternal(type);
	}

	/* __construct(): the callback as given. A TypeTraverserCallable is kept
	 * as the object itself and its traverse() called from mapInternal() —
	 * the twin wraps it in `static fn (Type $type, callable $traverse): Type
	 * => $cb->traverse($type, $traverse)`, and the object receives exactly
	 * the (Type, callable) pair either way. */
	void construct(zval *cb)
	{
		zv::ObjRef(self).propAtWrite(slots::cb, zv::Val::copyOf(zv::Ref(cb)));
	}

	/* the memoized result of $type when $this->memo is an array, else
	 * ($this->cb)($type, [$this, 'traverseInternal']); UNDEF = pending
	 * exception */
	zv::Val mapInternal(zval *type) const
	{
		/* spl_object_id($type) is the object handle */
		zend_ulong id = (zend_ulong) Z_OBJ_HANDLE_P(type);
		zval *memo = OBJ_PROP_NUM(self, slots::memo);
		if (Z_TYPE_P(memo) != IS_ARRAY) return callCallback(type);

		zval *hit = zend_hash_index_find(Z_ARRVAL_P(memo), id);
		if (hit != NULL) {
			zval *memoized = zend_hash_index_find(Z_ARRVAL_P(hit), 1);
			ZEND_ASSERT(memoized != NULL);
			return zv::Val::copyOf(zv::Ref(memoized));
		}

		zv::Val result = callCallback(type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		/* $this->memo[$id] = [$type, $result] — re-read, the callback's
		 * nested mapInternal() calls have written to it meanwhile */
		memo = OBJ_PROP_NUM(self, slots::memo);
		ZEND_ASSERT(Z_TYPE_P(memo) == IS_ARRAY);
		SEPARATE_ARRAY(memo);
		zval pair;
		array_init_size(&pair, 2);
		Z_ADDREF_P(type);
		add_next_index_zval(&pair, type);
		Z_TRY_ADDREF_P(result.raw());
		add_next_index_zval(&pair, result.raw());
		zend_hash_index_update(Z_ARRVAL_P(memo), id, &pair);

		return result;
	}

	/* ($this->cb)($type, [$this, 'traverseInternal']); UNDEF = pending
	 * exception */
	zv::Val callCallback(zval *type) const
	{
		zval *cb = OBJ_PROP_NUM(self, slots::cb);
		zv::Val traverse = boundCallable(false, pt_tt_str_traverse_internal);
		zv::Args args{type, traverse.raw()};
		bool isTraverserCallable;
		if (UNEXPECTED(!pt_type_instanceof(cb, PT_CLASS_TYPE_TRAVERSER_CALLABLE, isTraverserCallable))) return zv::Val();
		zv::Val result = isTraverserCallable
			? pt_type_op(Z_OBJ_P(cb), PT_OP_TRAVERSE, 2, args)
			: pt_type_call_callable(cb, 2, args);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		/* the twin's `: Type` return type */
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(result.raw(), PT_CLASS_TYPE, isType))) return zv::Val();
		if (UNEXPECTED(!isType)) {
			zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
			zend_type_error("%s::mapInternal(): Return value must be of type %s, %s returned", ZSTR_VAL(pt_ce_type_traverser->name), typeCe != NULL ? ZSTR_VAL(typeCe->name) : "PHPStan\\Type\\Type", zend_zval_value_name(result.raw()));
			return zv::Val();
		}
		return result;
	}

	/* $type->traverse([$this, 'mapInternal']); UNDEF = pending exception */
	zv::Val traverseInternal(zval *type) const
	{
		zv::Val map = boundCallable(true, pt_tt_str_map_internal);
		return pt_type_op(Z_OBJ_P(type), PT_OP_TRAVERSE, 1, map.raw());
	}

private:
	zend_object *self;

	/* [$this, '<method>'], kept for the traversal in progress */
	zv::Val boundCallable(bool map, zend_string *method) const
	{
		ActiveTraversal *active = ttActive(self);
		zval *cache = active == NULL ? NULL : (map ? &active->mapCallable : &active->traverseCallable);
		if (cache != NULL && Z_TYPE_P(cache) != IS_UNDEF) return zv::Val::copyOf(zv::Ref(cache));
		zv::Arr callable = zv::Arr::create(2);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		callable.push(zv::Ref(&selfZv));
		zval methodZv;
		ZVAL_INTERNED_STR(&methodZv, method);
		callable.push(zv::Ref(&methodZv));
		zv::Val built(std::move(callable));
		if (cache != NULL) ZVAL_COPY(cache, built.raw());
		return built;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeTraverser;

/* {{{ exported helpers */

void pt_type_traverser_rshutdown()
{
	/* a bailout skipped the scopes of the traversals it interrupted */
	uint32_t depth = pt_tt_active_depth < PT_TT_ACTIVE_LIMIT ? pt_tt_active_depth : PT_TT_ACTIVE_LIMIT;
	for (uint32_t i = 0; i < depth; i++) {
		pt_tt_active[i].traverser = NULL;
		zval_ptr_dtor(&pt_tt_active[i].mapCallable);
		zval_ptr_dtor(&pt_tt_active[i].traverseCallable);
		ZVAL_UNDEF(&pt_tt_active[i].mapCallable);
		ZVAL_UNDEF(&pt_tt_active[i].traverseCallable);
	}
	pt_tt_active_depth = 0;
}

bool pt_type_traverser_map(zval *out, zval *type, zval *cb)
{
	zv::Val result = TypeTraverser::map(type, cb);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_type_traverser_map_memoized(zval *out, zval *type, zval *cb)
{
	zv::Val result = TypeTraverser::mapMemoized(type, cb);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_type_traverser_traverse(zval *out, zval *traverse, zval *type)
{
	/* the [$this, 'traverseInternal'] array is entered directly by
	 * pt_type_call_callable() */
	zv::Val result = pt_type_call_callable(traverse, 1, type);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

zv::Val pt_type_traverser_map_internal(zend_object *traverser, zval *type)
{
	return TypeTraverser(traverser).mapInternal(type);
}

zv::Val pt_type_traverser_traverse_internal(zend_object *traverser, zval *type)
{
	return TypeTraverser(traverser).traverseInternal(type);
}

/* the handlers of mapInternal() / traverseInternal(), for pt_direct_invoke() */
static void ZEND_FASTCALL ttMapInternal(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(TypeTraverser(Z_OBJ_P(ZEND_THIS)).mapInternal(type));
}

static void ZEND_FASTCALL ttTraverseInternal(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL(TypeTraverser(Z_OBJ_P(ZEND_THIS)).traverseInternal(type));
}

bool pt_type_traverser_bound_fcc(zval *callable, zend_fcall_info &fci, zend_fcall_info_cache &fcc)
{
	HashTable *ht = Z_ARRVAL_P(callable);
	if (!HT_IS_PACKED(ht) || zend_hash_num_elements(ht) != 2 || ht->nNumUsed != 2) return false;
	zval *object = zend_hash_index_find(ht, 0);
	zval *method = zend_hash_index_find(ht, 1);
	if (Z_TYPE_P(object) != IS_OBJECT || Z_OBJCE_P(object) != pt_ce_type_traverser || Z_TYPE_P(method) != IS_STRING) return false;
	/* only the arrays boundCallable() builds: the interned names by identity */
	static zend_class_entry *cachedCe = NULL;
	static zend_function *mapInternal = NULL;
	static zend_function *traverseInternal = NULL;
	if (cachedCe != pt_ce_type_traverser) {
		mapInternal = (zend_function *) zend_hash_str_find_ptr(&PT_CE(pt_ce_type_traverser, function_table), "mapinternal", sizeof("mapinternal") - 1);
		traverseInternal = (zend_function *) zend_hash_str_find_ptr(&PT_CE(pt_ce_type_traverser, function_table), "traverseinternal", sizeof("traverseinternal") - 1);
		cachedCe = pt_ce_type_traverser;
	}
	zend_function *fn = Z_STR_P(method) == pt_tt_str_map_internal ? mapInternal
		: (Z_STR_P(method) == pt_tt_str_traverse_internal ? traverseInternal : NULL);
	if (fn == NULL) return false;
	/* what zend_fcall_info_init() fills for the resolved [$object, 'method'] */
	fci.size = sizeof(fci);
	ZVAL_COPY_VALUE(&fci.function_name, callable);
	fci.object = Z_OBJ_P(object);
	fci.retval = NULL;
	fci.params = NULL;
	fci.param_count = 0;
	fci.named_params = NULL;
	fcc.function_handler = fn;
	fcc.calling_scope = pt_ce_type_traverser;
	fcc.called_scope = pt_ce_type_traverser;
	fcc.object = Z_OBJ_P(object);
	fcc.closure = NULL;
	return true;
}

zif_handler pt_type_traverser_map_internal_handler()
{
	return ttMapInternal;
}

zif_handler pt_type_traverser_traverse_internal_handler()
{
	return ttTraverseInternal;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* the twin's `TypeTraverserCallable|callable $cb` parameter check; false
 * with a TypeError pending */
static bool pt_tt_check_cb(zval *cb, uint32_t argNum)
{
	bool isTraverserCallable;
	if (UNEXPECTED(!pt_type_instanceof(cb, PT_CLASS_TYPE_TRAVERSER_CALLABLE, isTraverserCallable))) return false;
	if (isTraverserCallable || zend_is_callable(cb, 0, NULL)) return true;
	zend_class_entry *iface = pt_class(PT_CLASS_TYPE_TRAVERSER_CALLABLE);
	zend_argument_type_error(argNum, "must be of type %s|callable, %s given", iface != NULL ? ZSTR_VAL(iface->name) : "PHPStan\\Type\\TypeTraverserCallable", zend_zval_value_name(cb));
	return false;
}

PT_MINIT_REGISTRATION(pt_register_type_traverser)
{
	pt_tt_str_map_internal = zend_string_init_interned("mapInternal", sizeof("mapInternal") - 1, 1);
	pt_tt_str_traverse_internal = zend_string_init_interned("traverseInternal", sizeof("traverseInternal") - 1, 1);

	/* `TypeTraverserCallable|callable $cb` — a class name next to the
	 * callable bit, as the compiler encodes that union */

	reg::Class cls("PHPStan\\Type\\TypeTraverser");
	ptdecl::TypeTraverser::declareClass(cls);
	/* "cb" must stay the first declared property (OBJ_PROP_NUM slot 0) */
	ptdecl::TypeTraverser::declareProperties(cls);

	cls.method(sigs::map, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *cb;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, cb)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tt_check_cb(cb, 2))) RETURN_THROWS();
		PT_RETURN_VAL(TypeTraverser::map(type, cb));
	});

	cls.method(sigs::mapMemoized, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *cb;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, cb)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tt_check_cb(cb, 2))) RETURN_THROWS();
		PT_RETURN_VAL(TypeTraverser::mapMemoized(type, cb));
	});

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cb;
		if (!zp::parse<zp::Zval>(execute_data, cb)) RETURN_THROWS();
		if (UNEXPECTED(!pt_tt_check_cb(cb, 1))) RETURN_THROWS();
		TypeTraverser(Z_OBJ_P(ZEND_THIS)).construct(cb);
	});

	cls.method(sigs::mapInternal, ttMapInternal);

	cls.method(sigs::traverseInternal, ttTraverseInternal);

	cls.shadow(&pt_ce_type_traverser);
}

/* }}} */
