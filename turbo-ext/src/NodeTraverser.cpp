/*
 * PHPStanTurbo\NodeTraverser — native reimplementation of
 * PhpParser\NodeTraverser, declared under that name at activation (not
 * final and implementing NodeTraverserInterface, like the original).
 *
 * The logic lives in the NodeTraverser handle class below, structured to
 * mirror vendor/nikic/php-parser/lib/PhpParser/NodeTraverser.php method for
 * method — traverse()/traverseNode()/traverseArray() and the visitor
 * return-value protocol; the PHP_METHOD functions at the bottom are only the
 * engine ABI glue (parameter parsing + delegation). Additionally, visitors
 * that inherit enterNode()/leaveNode()/beforeTraverse()/afterTraverse()
 * unchanged from NodeVisitorAbstract are not called for that hook (the
 * inherited hook returns null, so skipping it is unobservable), and the
 * natively ported PHPStan\Parser\*Visitor classes are dispatched through
 * their pt_native_visitor entry, with no engine frame at all — visitor hooks
 * were 46.5% of the run's native->PHP crossings.
 */

#include "support.h"
#include "generated/NodeTraverser.h"
#include "Engine.h"

namespace slots = ptdecl::NodeTraverser::slot;
namespace sigs = ptdecl::NodeTraverser::sig;
#include "zv.h"

zend_class_entry *pt_ce_node_traverser = nullptr;

/* {{{ pt_* traversal substrate */

/* Per-visitor call plan built once per traverse(): the visitor object and
 * its cached hook functions. NULL before_fn/after_fn and false
 * call_enter/call_leave mean "inherited no-op from NodeVisitorAbstract,
 * skip the call". */
typedef struct {
	zend_object *visitor;
	zend_class_entry *ce;
	zend_function *enter_fn;
	zend_function *leave_fn;
	zend_function *before_fn;
	zend_function *after_fn;
	bool call_enter;
	bool call_leave;
	/* the shadowed PHPStan\Parser\*Visitor ports, dispatched in C++ instead
	 * of through the engine (support.h); NULL for every other visitor, and a
	 * NULL hook inside it falls back to the engine call */
	const pt_native_visitor *native;
} pt_visitor_plan;

/* The splices recorded by one traverseArray() pass ($doNodes in the twin):
 * the element's position, and its key — what array_splice() is handed */
typedef struct {
	zend_ulong pos;
	zend_string *key; /* owned, NULL for an integer key */
	zend_ulong index;
	zval replacement; /* IS_ARRAY (owned) or IS_FALSE for remove */
} pt_do_node;

typedef struct {
	pt_do_node *items;
	uint32_t count;
	uint32_t capacity;
} pt_do_nodes;

static void pt_do_nodes_push(pt_do_nodes *dn, zend_ulong pos, zend_string *key, zend_ulong index, zval *replacement)
{
	if (dn->count == dn->capacity) {
		dn->capacity = dn->capacity == 0 ? 4 : dn->capacity * 2;
		dn->items = (pt_do_node *) erealloc(dn->items, dn->capacity * sizeof(pt_do_node));
	}
	dn->items[dn->count].pos = pos;
	dn->items[dn->count].key = key != NULL ? zend_string_copy(key) : NULL;
	dn->items[dn->count].index = index;
	if (replacement != NULL) {
		ZVAL_COPY(&dn->items[dn->count].replacement, replacement);
	} else {
		ZVAL_FALSE(&dn->items[dn->count].replacement);
	}
	dn->count++;
}

static void pt_do_nodes_free(pt_do_nodes *dn)
{
	uint32_t i;
	for (i = 0; i < dn->count; i++) {
		if (dn->items[i].key != NULL) {
			zend_string_release(dn->items[i].key);
		}
		zval_ptr_dtor(&dn->items[i].replacement);
	}
	if (dn->items != NULL) {
		efree(dn->items);
	}
}

static void pt_trav_throw_logic(const char *message)
{
	zend_string *name = zend_string_init("LogicException", sizeof("LogicException") - 1, 0);
	zend_class_entry *ce = zend_lookup_class(name);
	zend_string_release(name);
	if (ce == NULL) {
		zend_throw_error(NULL, "%s", message);
		return;
	}
	zend_throw_exception(ce, message, 0);
}

/* "... returned invalid value of type " . gettype($return) */
static void pt_trav_throw_invalid_return(const char *hook, zval *value)
{
	zend_string *message = zend_strpprintf(0, "%s() returned invalid value of type %s", hook, ZSTR_VAL(zend_zval_get_legacy_type(value)));
	pt_trav_throw_logic(ZSTR_VAL(message));
	zend_string_release(message);
}

/* Node subnode info incl. names, resolved lazily per class. */
typedef struct {
	uint32_t *offsets;
	zend_string **names;
	uint32_t count;
	bool resolved;
} pt_trav_class_info;

static HashTable pt_trav_class_cache;
static bool pt_trav_class_cache_inited;

static void pt_trav_class_info_free(zval *zv)
{
	pt_trav_class_info *info = (pt_trav_class_info *) Z_PTR_P(zv);
	uint32_t i;
	for (i = 0; i < info->count; i++) {
		zend_string_release(info->names[i]);
	}
	if (info->offsets != NULL) {
		efree(info->offsets);
	}
	if (info->names != NULL) {
		efree(info->names);
	}
	efree(info);
}

void pt_node_traverser_rinit()
{
	pt_trav_class_cache_inited = false;
}

void pt_node_traverser_rshutdown()
{
	if (pt_trav_class_cache_inited) {
		zend_hash_destroy(&pt_trav_class_cache);
		pt_trav_class_cache_inited = false;
	}
}

static pt_trav_class_info *pt_trav_class_info_for(zend_object *obj)
{
	zend_class_entry *ce = obj->ce;
	pt_trav_class_info *info;
	zend_function *fn;
	zval names;

	if (!pt_trav_class_cache_inited) {
		zend_hash_init(&pt_trav_class_cache, 64, NULL, pt_trav_class_info_free, 0);
		pt_trav_class_cache_inited = true;
	}

	info = (pt_trav_class_info *) zend_hash_find_ptr(&pt_trav_class_cache, ce->name);
	if (EXPECTED(info != NULL)) return info;

	info = (pt_trav_class_info *) ecalloc(1, sizeof(pt_trav_class_info));

	fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, "getsubnodenames", sizeof("getsubnodenames") - 1);
	if (fn != NULL && (fn->common.fn_flags & ZEND_ACC_ABSTRACT) == 0) {
		zend_call_known_function(fn, obj, ce, &names, 0, NULL, NULL);
		if (!EG(exception) && Z_TYPE(names) == IS_ARRAY) {
			uint32_t capacity = zend_hash_num_elements(Z_ARRVAL(names));
			zval *name_zv;
			info->offsets = (uint32_t *) emalloc(sizeof(uint32_t) * (capacity > 0 ? capacity : 1));
			info->names = (zend_string **) emalloc(sizeof(zend_string *) * (capacity > 0 ? capacity : 1));
			ZEND_HASH_FOREACH_VAL(Z_ARRVAL(names), name_zv) {
				zend_property_info *prop;
				if (Z_TYPE_P(name_zv) != IS_STRING) continue;
				prop = (zend_property_info *) zend_hash_find_ptr(&ce->properties_info, Z_STR_P(name_zv));
				if (prop == NULL || (prop->flags & ZEND_ACC_STATIC) != 0) continue;
				info->offsets[info->count] = (uint32_t) prop->offset;
				info->names[info->count] = zend_string_copy(Z_STR_P(name_zv));
				info->count++;
			} ZEND_HASH_FOREACH_END();
		}
		zval_ptr_dtor(&names);
	}

	zend_hash_add_ptr(&pt_trav_class_cache, ce->name, info);
	return info;
}

/* }}} */

namespace phpstanturbo {

/*
 * Mirrors PhpParser\NodeTraverser. The visitor list and the stopTraversal
 * flag live in the PHP object's properties; this handle carries the state of
 * one traverse() call (the visitor plan and the stop/failed flags).
 */
class NodeTraverser
{
public:
	/* NodeVisitor protocol constants (frozen public API of php-parser) */
	static constexpr zend_long DONT_TRAVERSE_CHILDREN = 1;
	static constexpr zend_long STOP_TRAVERSAL = 2;
	static constexpr zend_long REMOVE_NODE = 3;
	static constexpr zend_long DONT_TRAVERSE_CURRENT_AND_CHILDREN = 4;
	static constexpr zend_long REPLACE_WITH_NULL = 5;

	explicit NodeTraverser(zend_object *self) : self(self) {}

	NodeTraverser(const NodeTraverser &) = delete;
	NodeTraverser &operator=(const NodeTraverser &) = delete;

	~NodeTraverser()
	{
		if (plan != NULL) {
			/* the plan owns its visitor references: removeVisitor() (or any
			 * userland write to $this->visitors) during traversal must not be
			 * able to free a visitor the plan still dispatches to */
			for (uint32_t i = 0; i < nplanned; i++) {
				OBJ_RELEASE(plan[i].visitor);
			}
			efree(plan);
		}
	}

	/* __construct(NodeVisitor ...$visitors); false means an argument error
	 * was thrown */
	bool construct(zval *visitors, uint32_t count)
	{
		zv::Arr list = zv::Arr::create(count);
		for (uint32_t i = 0; i < count; i++) {
			zv::Ref visitor = zv::Ref(&visitors[i]).deref();
			if (UNEXPECTED(!visitor.isObject())) {
				zend_argument_type_error(i + 1, "must be of type PhpParser\\NodeVisitor");
				return false;
			}
			list.push(visitor);
		}
		zv::ObjRef(self).propAtWrite(slots::visitors, std::move(list));
		return true;
	}

	/* $this->visitors[] = $visitor */
	void addVisitor(zv::Ref visitor)
	{
		zv::Ref prop = visitorsProp();
		if (!prop.isArray()) {
			prop.assign(zv::Arr::create(0));
		}
		zv::ArrRef(prop.raw()).push(visitor);
	}

	/* false means it threw */
	bool removeVisitor(zv::Ref visitor)
	{
		zv::Ref prop = visitorsProp();
		if (UNEXPECTED(!prop.isArray())) return throwVisitorsUninitialized();

		/* array_search() with loose comparison, like the PHP implementation */
		bool found = false;
		zend_ulong foundPos = 0;
		zend_ulong pos = 0;
		for (auto entry : zv::ArrRef(prop.raw())) {
			zv::Ref candidate = entry.value().deref();
			if (candidate.isObject() && zend_compare(candidate.raw(), visitor.raw()) == 0) {
				found = true;
				foundPos = pos;
				break;
			}
			pos++;
		}
		if (!found) return true;

		/* array_splice($visitors, $index, 1, []) — reindexes */
		zv::ArrRef old(prop.raw());
		zv::Arr rebuilt = zv::Arr::create(old.size());
		pos = 0;
		for (auto entry : old) {
			if (pos++ != foundPos) {
				rebuilt.push(entry.value());
			}
		}
		prop.assign(std::move(rebuilt));
		return true;
	}

	/* traverse(); UNDEF result means a pending exception */
	zv::Val traverse(HashTable *nodesTable)
	{
		/* $this->stopTraversal = false */
		writeStopTraversal(false);

		if (UNEXPECTED(!buildVisitorPlan())) return zv::Val();

		/* the by-value $nodes parameter */
		zv::Arr nodes = zv::Arr::copyOfTable(nodesTable);

		/* beforeTraverse */
		for (uint32_t vi = 0; vi < nvisitors; vi++) {
			const pt_visitor_plan *p = &plan[vi];
			if (p->before_fn == NULL) continue;
			if (p->native != NULL && p->native->before != NULL) {
				p->native->before(p->visitor);
				continue;
			}
			zv::Val ret = callVisitorHook(p, p->before_fn, nodes.ref());
			if (UNEXPECTED(ret.isUndef())) return zv::Val();
			if (ret.ref().isArray()) {
				nodes = zv::Arr::adoptVal(std::move(ret));
			}
		}

		zv::Val traversed = traverseArray(nodes.ref());
		if (UNEXPECTED(failed)) return zv::Val();
		nodes = zv::Arr::adoptVal(std::move(traversed));

		/* afterTraverse, in reverse */
		for (int64_t vi = (int64_t) nvisitors - 1; vi >= 0; vi--) {
			const pt_visitor_plan *p = &plan[vi];
			if (p->after_fn == NULL) continue;
			zv::Val ret = callVisitorHook(p, p->after_fn, nodes.ref());
			if (UNEXPECTED(ret.isUndef())) return zv::Val();
			if (ret.ref().isArray()) {
				nodes = zv::Arr::adoptVal(std::move(ret));
			}
		}

		/* persist stopTraversal like the PHP implementation */
		writeStopTraversal(stop);

		return zv::Val(std::move(nodes));
	}

private:
	/* Mirrors traverseNode(): recursively traverse the subnodes of a node,
	 * read via the per-class property offset cache. */
	void traverseNode(zend_object *node)
	{
		pt_trav_class_info *info = pt_trav_class_info_for(node);
		zend_class_entry *nodeIface = pt_class(PT_CLASS_NODE);

		if (UNEXPECTED(EG(exception))) {
			failed = true;
			return;
		}
		if (UNEXPECTED(info == NULL || nodeIface == NULL)) {
			failed = true;
			return;
		}

		for (uint32_t i = 0; i < info->count; i++) {
			zv::Ref value = zv::Ref(OBJ_PROP(node, info->offsets[i])).deref();

			if (value.isArray()) {
				/* $node->$name = $this->traverseArray($subNode): the traversal
				 * works on its own reference to the table (the twin's
				 * $subNode) and writes into a copy made on the first write, so
				 * nothing - a visitor holding the parent's array, the property
				 * itself mid-traversal - sees the table change under it, and
				 * an untouched array (the shared [] included) is never copied */
				zv::Val result;
				pt_engine_with_stack([&]() { result = traverseArray(zv::Ref(value.raw())); });
				if (UNEXPECTED(failed)) return;
				/* the assignment is skipped only when it would store the table
				 * the property already holds; a visitor may have reassigned it */
				zv::Ref current = zv::Ref(OBJ_PROP(node, info->offsets[i])).deref();
				if (!current.isArray() || Z_ARRVAL_P(current.raw()) != Z_ARRVAL_P(result.raw())) {
					if (UNEXPECTED(!writeSubnode(node, info->names[i], result.ref()))) return;
				}
				if (stop) return;
				continue;
			}

			if (!value.instanceOf(nodeIface)) continue;
			/* Own a reference for the whole block: a visitor writing to the
			 * parent's property from a hook can otherwise drop the node's
			 * last reference while later hooks still run on it. The PHP twin
			 * survives that by construction ($subNode owns a reference). */
			zv::Val subNodeOwned = zv::Val::copyOf(zv::Ref(value.raw()));
			zend_object *subNode = value.asObject();

			bool traverseChildren = true;
			int64_t visitorIndex = -1;
			bool skipToNext = false;

			/* enterNode */
			for (uint32_t vi = 0; vi < nvisitors; vi++) {
				const pt_visitor_plan *p = &plan[vi];
				visitorIndex = vi;
				if (!p->call_enter) continue;
				if (p->native != NULL && p->native->enter != NULL) {
					/* a natively dispatched port: no engine frame, and it
					 * always returns null */
					if (UNEXPECTED(!p->native->enter(p->visitor, subNode))) {
						failed = true;
						return;
					}
					continue;
				}
				zv::Val ret = callVisitorHook(p, p->enter_fn, subNode);
				if (UNEXPECTED(ret.isUndef())) {
					failed = true;
					return;
				}
				zv::Ref retRef = ret.ref();
				if (retRef.isNull()) continue;
				if (retRef.instanceOf(nodeIface)) {
					if (UNEXPECTED(!ensureReplacementReasonable(subNode, retRef.asObject()))) {
						failed = true;
						return;
					}
					/* $node->$name = $subNode = $return */
					if (UNEXPECTED(!writeSubnode(node, info->names[i], retRef))) return;
					subNodeOwned = zv::Val::copyOf(retRef);
					subNode = retRef.asObject();
					continue;
				}
				if (retRef.isLong()) {
					zend_long code = retRef.asLong();
					if (code == DONT_TRAVERSE_CHILDREN) {
						traverseChildren = false;
						continue;
					}
					if (code == DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
						traverseChildren = false;
						break;
					}
					if (code == STOP_TRAVERSAL) {
						stop = true;
						return;
					}
					if (code == REPLACE_WITH_NULL) {
						if (UNEXPECTED(!writeSubnodeNull(node, info->names[i]))) return;
						skipToNext = true;
						break;
					}
				}
				pt_trav_throw_invalid_return("enterNode", retRef.raw());
				failed = true;
				return;
			}

			if (skipToNext) continue;

			if (traverseChildren) {
				pt_engine_with_stack([&]() { traverseNode(subNode); });
				if (UNEXPECTED(failed) || stop) return;
			}

			/* leaveNode, in reverse from the last visitor whose enterNode ran */
			for (int64_t vi = visitorIndex; vi >= 0; vi--) {
				const pt_visitor_plan *p = &plan[vi];
				if (!p->call_leave) continue;
				if (p->native != NULL && p->native->leave != NULL) {
					/* a natively dispatched port: no engine frame, and it
					 * always returns null */
					if (UNEXPECTED(!p->native->leave(p->visitor, subNode))) {
						failed = true;
						return;
					}
					continue;
				}
				zv::Val ret = callVisitorHook(p, p->leave_fn, subNode);
				if (UNEXPECTED(ret.isUndef())) {
					failed = true;
					return;
				}
				zv::Ref retRef = ret.ref();
				if (retRef.isNull()) continue;
				if (retRef.instanceOf(nodeIface)) {
					if (UNEXPECTED(!ensureReplacementReasonable(subNode, retRef.asObject()))) {
						failed = true;
						return;
					}
					if (UNEXPECTED(!writeSubnode(node, info->names[i], retRef))) return;
					subNodeOwned = zv::Val::copyOf(retRef);
					subNode = retRef.asObject();
					continue;
				}
				if (retRef.isLong()) {
					zend_long code = retRef.asLong();
					if (code == STOP_TRAVERSAL) {
						stop = true;
						return;
					}
					if (code == REPLACE_WITH_NULL) {
						if (UNEXPECTED(!writeSubnodeNull(node, info->names[i]))) return;
						break;
					}
				}
				if (retRef.isArray()) {
					pt_trav_throw_logic("leaveNode() may only return an array if the parent structure is an array");
					failed = true;
					return;
				}
				pt_trav_throw_invalid_return("leaveNode", retRef.raw());
				failed = true;
				return;
			}
		}
	}

	/* $nodes[$i] = $node on the traversal's copy of the array, made on the
	 * first write (the twin's local $nodes separating from its foreach) */
	static void writeElement(zv::Val &working, zv::Val &original, zend_string *key, zend_ulong index, zv::Ref value)
	{
		if (working.isUndef()) {
			zval copy;
			ZVAL_ARR(&copy, zend_array_dup(Z_ARRVAL_P(original.raw())));
			working = zv::Val::adopt(copy);
		}
		HashTable *table = Z_ARRVAL_P(working.raw());
		zval *slot = key != NULL ? zend_hash_find(table, key) : zend_hash_index_find(table, index);
		if (slot != NULL && Z_ISREF_P(slot)) {
			zv::Ref(Z_REFVAL_P(slot)).assign(zv::Val::copyOf(value));
			return;
		}
		Z_TRY_ADDREF_P(value.raw());
		if (key != NULL) {
			zend_hash_update(table, key, value.raw());
		} else {
			zend_hash_index_update(table, index, value.raw());
		}
	}

	/*
	 * Mirrors traverseArray(): iterates the array it is handed (holding its
	 * own reference, like the twin's by-value parameter and foreach), writes
	 * replacements into a copy made on the first one, applies the recorded
	 * splices (REMOVE_NODE / replacement arrays) and returns the resulting
	 * array — the input's own table when nothing changed. UNDEF means a
	 * pending exception.
	 */
	zv::Val traverseArray(zv::Ref nodesZv)
	{
		zv::Val original = zv::Val::copyOf(nodesZv);
		zv::Val working;
		pt_do_nodes doNodes = {};
		zend_class_entry *nodeIface = pt_class(PT_CLASS_NODE);
		if (UNEXPECTED(nodeIface == NULL)) {
			failed = true;
			return zv::Val();
		}

		zend_ulong pos = 0;
		for (auto nodesEntry : zv::ArrRef(original.raw())) {
			zend_ulong i = pos++;
			zend_string *key = nodesEntry.stringKeyOrNull();
			zend_ulong index = nodesEntry.indexKey();
			zv::Ref value = nodesEntry.value().deref();

			if (!value.instanceOf(nodeIface)) {
				if (UNEXPECTED(value.isArray())) {
					pt_trav_throw_logic("Invalid node structure: Contains nested arrays");
					failed = true;
					break;
				}
				continue;
			}
			zend_object *node = value.asObject();

			bool traverseChildren = true;
			int64_t visitorIndex = -1;
			bool skipToNext = false;

			/* enterNode */
			for (uint32_t vi = 0; vi < nvisitors; vi++) {
				const pt_visitor_plan *p = &plan[vi];
				visitorIndex = vi;
				if (!p->call_enter) continue;
				if (p->native != NULL && p->native->enter != NULL) {
					/* a natively dispatched port: no engine frame, and it
					 * always returns null */
					if (UNEXPECTED(!p->native->enter(p->visitor, node))) {
						failed = true;
						break;
					}
					continue;
				}
				zv::Val ret = callVisitorHook(p, p->enter_fn, node);
				if (UNEXPECTED(ret.isUndef())) {
					failed = true;
					break;
				}
				zv::Ref retRef = ret.ref();
				if (retRef.isNull()) continue;
				if (retRef.instanceOf(nodeIface)) {
					if (UNEXPECTED(!ensureReplacementReasonable(node, retRef.asObject()))) {
						failed = true;
						break;
					}
					/* $nodes[$i] = $node = $return */
					writeElement(working, original, key, index, retRef);
					node = retRef.asObject();
					continue;
				}
				if (retRef.isArray()) {
					pt_do_nodes_push(&doNodes, i, key, index, retRef.raw());
					skipToNext = true;
					break;
				}
				if (retRef.isLong()) {
					zend_long code = retRef.asLong();
					if (code == REMOVE_NODE) {
						pt_do_nodes_push(&doNodes, i, key, index, NULL);
						skipToNext = true;
						break;
					}
					if (code == DONT_TRAVERSE_CHILDREN) {
						traverseChildren = false;
						continue;
					}
					if (code == DONT_TRAVERSE_CURRENT_AND_CHILDREN) {
						traverseChildren = false;
						break;
					}
					if (code == STOP_TRAVERSAL) {
						stop = true;
						break;
					}
					if (code == REPLACE_WITH_NULL) {
						pt_trav_throw_logic("REPLACE_WITH_NULL can not be used if the parent structure is an array");
						failed = true;
						break;
					}
				}
				pt_trav_throw_invalid_return("enterNode", retRef.raw());
				failed = true;
				break;
			}

			if (UNEXPECTED(failed) || stop) break;
			if (skipToNext) continue;

			if (traverseChildren) {
				pt_engine_with_stack([&]() { traverseNode(node); });
				if (UNEXPECTED(failed) || stop) break;
			}

			/* leaveNode, in reverse from the last visitor whose enterNode ran */
			for (int64_t vi = visitorIndex; vi >= 0; vi--) {
				const pt_visitor_plan *p = &plan[vi];
				if (!p->call_leave) continue;
				if (p->native != NULL && p->native->leave != NULL) {
					/* a natively dispatched port: no engine frame, and it
					 * always returns null */
					if (UNEXPECTED(!p->native->leave(p->visitor, node))) {
						failed = true;
						break;
					}
					continue;
				}
				zv::Val ret = callVisitorHook(p, p->leave_fn, node);
				if (UNEXPECTED(ret.isUndef())) {
					failed = true;
					break;
				}
				zv::Ref retRef = ret.ref();
				if (retRef.isNull()) continue;
				if (retRef.instanceOf(nodeIface)) {
					if (UNEXPECTED(!ensureReplacementReasonable(node, retRef.asObject()))) {
						failed = true;
						break;
					}
					writeElement(working, original, key, index, retRef);
					node = retRef.asObject();
					continue;
				}
				if (retRef.isArray()) {
					pt_do_nodes_push(&doNodes, i, key, index, retRef.raw());
					break;
				}
				if (retRef.isLong()) {
					zend_long code = retRef.asLong();
					if (code == REMOVE_NODE) {
						pt_do_nodes_push(&doNodes, i, key, index, NULL);
						break;
					}
					if (code == STOP_TRAVERSAL) {
						stop = true;
						break;
					}
					if (code == REPLACE_WITH_NULL) {
						pt_trav_throw_logic("REPLACE_WITH_NULL can not be used if the parent structure is an array");
						failed = true;
						break;
					}
				}
				pt_trav_throw_invalid_return("leaveNode", retRef.raw());
				failed = true;
				break;
			}

			if (UNEXPECTED(failed) || stop) break;
		}

		if (UNEXPECTED(failed)) {
			pt_do_nodes_free(&doNodes);
			return zv::Val();
		}

		zv::Val result = working.isUndef() ? std::move(original) : std::move(working);
		if (doNodes.count > 0 && !zend_array_is_list(Z_ARRVAL_P(result.raw()))) {
			/* while (list($i, $replace) = array_pop($doNodes))
			 *     array_splice($nodes, $i, 1, $replace); */
			for (uint32_t k = doNodes.count; k-- > 0; ) {
				pt_do_node &splice = doNodes.items[k];
				if (splice.key != NULL) {
					zend_type_error("array_splice(): Argument #2 ($offset) must be of type int, string given");
					failed = true;
					pt_do_nodes_free(&doNodes);
					return zv::Val();
				}
				zval *replacement = &splice.replacement;
				result = arraySplice(Z_ARRVAL_P(result.raw()), (zend_long) splice.index, Z_TYPE_P(replacement) == IS_ARRAY ? Z_ARRVAL_P(replacement) : NULL);
			}
		} else if (doNodes.count > 0) {
			/* on a list every key is its position and each splice leaves
			 * the positions below it alone: one rebuild pass does them all */
			zv::ArrRef nodes(result.raw());
			zv::Arr rebuilt = zv::Arr::create(nodes.size());
			uint32_t cursor = 0;
			zend_ulong rebuildPos = 0;
			for (auto nodesEntry : nodes) {
				if (cursor < doNodes.count && doNodes.items[cursor].pos == rebuildPos) {
					zv::Ref replacement(&doNodes.items[cursor].replacement);
					if (replacement.isArray()) {
						for (auto replacementEntry : zv::ArrRef(replacement.raw())) {
							rebuilt.push(replacementEntry.value());
						}
					}
					/* IS_FALSE => removed, append nothing */
					cursor++;
				} else {
					rebuilt.push(nodesEntry.value());
				}
				rebuildPos++;
			}
			result = zv::Val(std::move(rebuilt));
		}

		pt_do_nodes_free(&doNodes);
		return result;
	}

	/* array_splice($nodes, $offset, 1, $replacement) into a new array: the
	 * offset clamped like array_splice() does, the integer keys renumbered,
	 * the string keys kept; NULL replacement = [] */
	static zv::Val arraySplice(HashTable *nodes, zend_long offset, HashTable *replacement)
	{
		zend_long count = (zend_long) zend_hash_num_elements(nodes);
		if (offset > count) {
			offset = count;
		} else if (offset < 0) {
			offset = count + offset < 0 ? 0 : count + offset;
		}
		zend_long length = offset + 1 > count ? count - offset : 1;

		zv::Arr spliced = zv::Arr::create((uint32_t) (count - length + (replacement != NULL ? zend_hash_num_elements(replacement) : 0)));
		zv::ArrRef out(spliced.raw());
		auto insertReplacement = [&]() {
			if (replacement == NULL) return;
			for (auto entry : zv::TableRef(replacement)) {
				out.push(entry.value());
			}
		};
		zend_long position = 0;
		for (auto entry : zv::TableRef(nodes)) {
			if (position == offset) {
				insertReplacement();
			}
			if (position < offset || position >= offset + length) {
				zend_string *key = entry.stringKeyOrNull();
				if (key != NULL) {
					Z_TRY_ADDREF_P(entry.value().raw());
					zend_hash_update(out.table(), key, entry.value().raw());
				} else {
					out.push(entry.value());
				}
			}
			position++;
		}
		if (offset == count) {
			insertReplacement();
		}
		return zv::Val(std::move(spliced));
	}

	/* $old instanceof Stmt && $new instanceof Expr (and vice versa)
	 * => LogicException; false means it threw */
	static bool ensureReplacementReasonable(zend_object *oldNode, zend_object *newNode)
	{
		zend_class_entry *stmtCe = pt_class(PT_CLASS_STMT);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(stmtCe == NULL || exprCe == NULL)) return false;

		zv::ObjRef oldRef(oldNode);
		zv::ObjRef newRef(newNode);
		bool statementWithExpression = oldRef.instanceOf(stmtCe) && newRef.instanceOf(exprCe);
		if (!statementWithExpression && !(oldRef.instanceOf(exprCe) && newRef.instanceOf(stmtCe))) return true;

		/* "... ({$old->getType()}) ... ({$new->getType()})" */
		zv::Val oldType = nodeType(oldNode);
		if (UNEXPECTED(oldType.isUndef())) return false;
		zv::Val newType = nodeType(newNode);
		if (UNEXPECTED(newType.isUndef())) return false;
		zend_string *message = statementWithExpression
			? zend_strpprintf(0, "Trying to replace statement (%s) with expression (%s). Are you missing a Stmt_Expression wrapper?", Z_STRVAL_P(oldType.raw()), Z_STRVAL_P(newType.raw()))
			: zend_strpprintf(0, "Trying to replace expression (%s) with statement (%s)", Z_STRVAL_P(oldType.raw()), Z_STRVAL_P(newType.raw()));
		pt_trav_throw_logic(ZSTR_VAL(message));
		zend_string_release(message);
		return false;
	}

	/* $node->getType() as a string; UNDEF means a pending exception */
	static zv::Val nodeType(zend_object *node)
	{
		zval type;
		ZVAL_UNDEF(&type);
		zend_call_method_with_0_params(node, node->ce, NULL, "gettype", &type);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&type);
			return zv::Val();
		}
		zv::Val owned = zv::Val::adopt(type);
		if (Z_TYPE_P(owned.raw()) != IS_STRING) {
			zend_string *string = zval_get_string(owned.raw());
			if (UNEXPECTED(EG(exception))) {
				zend_string_release(string);
				return zv::Val();
			}
			return zv::Val::adoptString(string);
		}
		return owned;
	}

	/* Builds the per-visitor hook plan from $this->visitors; false on error. */
	bool buildVisitorPlan()
	{
		zv::Ref visitors = visitorsProp();
		if (UNEXPECTED(!visitors.isArray())) return throwVisitorsUninitialized();

		zend_function *baseEnter = NULL;
		zend_function *baseLeave = NULL;
		zend_function *baseBefore = NULL;
		zend_function *baseAfter = NULL;
		zend_class_entry *abstractCe = pt_class(PT_CLASS_NODE_VISITOR_ABSTRACT);
		if (abstractCe != NULL) {
			baseEnter = findHook(abstractCe, "enternode", sizeof("enternode") - 1);
			baseLeave = findHook(abstractCe, "leavenode", sizeof("leavenode") - 1);
			baseBefore = findHook(abstractCe, "beforetraverse", sizeof("beforetraverse") - 1);
			baseAfter = findHook(abstractCe, "aftertraverse", sizeof("aftertraverse") - 1);
		} else if (EG(exception)) {
			zend_clear_exception();
		}

		zv::ArrRef visitorList(visitors.raw());
		nvisitors = visitorList.size();
		plan = nvisitors > 0 ? (pt_visitor_plan *) emalloc(nvisitors * sizeof(pt_visitor_plan)) : NULL;

		uint32_t i = 0;
		for (auto entry : visitorList) {
			zv::Ref visitor = entry.value().deref();
			if (UNEXPECTED(!visitor.isObject())) {
				zend_throw_error(NULL, "phpstan_turbo: NodeTraverser visitor is not an object");
				return false;
			}
			pt_visitor_plan *p = &plan[i];
			p->visitor = visitor.asObject();
			GC_ADDREF(p->visitor);
			nplanned = i + 1;
			p->ce = p->visitor->ce;
			p->enter_fn = findHook(p->ce, "enternode", sizeof("enternode") - 1);
			p->leave_fn = findHook(p->ce, "leavenode", sizeof("leavenode") - 1);
			if (UNEXPECTED(p->enter_fn == NULL || p->leave_fn == NULL)) {
				zend_throw_error(NULL, "phpstan_turbo: visitor %s lacks enterNode/leaveNode", ZSTR_VAL(p->ce->name));
				return false;
			}
			p->call_enter = p->enter_fn != baseEnter;
			p->call_leave = p->leave_fn != baseLeave;
			p->before_fn = findHook(p->ce, "beforetraverse", sizeof("beforetraverse") - 1);
			p->after_fn = findHook(p->ce, "aftertraverse", sizeof("aftertraverse") - 1);
			p->native = pt_native_visitor_for(p->ce);
			if (p->before_fn == baseBefore) {
				p->before_fn = NULL;
			}
			if (p->after_fn == baseAfter) {
				p->after_fn = NULL;
			}
			i++;
		}

		return true;
	}

	/* hook lookup by lowercased name; NULL when the class lacks the method */
	static zend_function *findHook(zend_class_entry *ce, const char *lcname, size_t len)
	{
		return (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	}

	/* calls one visitor hook through its cached zend_function;
	 * UNDEF result means a pending exception */
	zv::Val callVisitorHook(const pt_visitor_plan *p, zend_function *hook, zv::Ref arg) const
	{
		zval retval;
		zend_call_known_function(hook, p->visitor, p->ce, &retval, 1, arg.raw(), NULL);
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return zv::Val::adopt(retval);
	}

	zv::Val callVisitorHook(const pt_visitor_plan *p, zend_function *hook, zend_object *node) const
	{
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		return callVisitorHook(p, hook, zv::Ref(&nodeZv));
	}

	/* $node->$name = $value via the engine write path; false when it threw */
	bool writeSubnode(zend_object *node, zend_string *name, zv::Ref value)
	{
		zv::ObjRef(node).propWrite(name, value);
		if (UNEXPECTED(EG(exception))) {
			failed = true;
			return false;
		}
		return true;
	}

	bool writeSubnodeNull(zend_object *node, zend_string *name)
	{
		zv::Val nullValue = zv::Val::null();
		return writeSubnode(node, name, nullValue.ref());
	}

	zv::Ref visitorsProp() const
	{
		return zv::ObjRef(self).propAt(slots::visitors).deref();
	}

	/* the typed property is an array unless a subclass unset() it: reading
	 * it then throws the engine's "must not be accessed before
	 * initialization" Error, as the twin's read does; always false */
	bool throwVisitorsUninitialized() const
	{
		zval rv;
		zend_read_property(self->ce, self, "visitors", sizeof("visitors") - 1, false, &rv);
		if (!EG(exception)) {
			zend_throw_error(NULL, "phpstan_turbo: NodeTraverser visitors is not an array");
		}
		return false;
	}

	/* $this->stopTraversal = $value — a typed property the constructor
	 * leaves uninitialized, so the write also clears IS_PROP_UNINIT */
	void writeStopTraversal(bool value)
	{
		zv::ObjRef(self).propAtWrite(slots::stopTraversal, zv::Val::boolean(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::stopTraversal)) = 0;
	}

	zend_object *self;
	pt_visitor_plan *plan = NULL;
	uint32_t nvisitors = 0;
	/* how many plan entries own a visitor reference (build can fail mid-way) */
	uint32_t nplanned = 0;
	bool stop = false;
	bool failed = false;
};

} // namespace phpstanturbo

using phpstanturbo::NodeTraverser;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define NODE_VISITOR_CLASS "PhpParser\\NodeVisitor"

void pt_register_node_traverser()
{
	reg::Class cls("PhpParser\\NodeTraverser");
	ptdecl::NodeTraverser::declareClass(cls);
	/* `protected array $visitors = []` and `protected bool $stopTraversal`
	 * (typed, uninitialized until traverse()), exactly the twin's: a
	 * subclass may redeclare them */
	ptdecl::NodeTraverser::declareProperties(cls);

	cls.classConstantLong("DONT_TRAVERSE_CHILDREN", NodeTraverser::DONT_TRAVERSE_CHILDREN);
	cls.classConstantLong("STOP_TRAVERSAL", NodeTraverser::STOP_TRAVERSAL);
	cls.classConstantLong("REMOVE_NODE", NodeTraverser::REMOVE_NODE);
	cls.classConstantLong("DONT_TRAVERSE_CURRENT_AND_CHILDREN", NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *visitors = NULL;
		uint32_t count = 0;
		ZEND_PARSE_PARAMETERS_START(0, -1)
			Z_PARAM_VARIADIC('+', visitors, count)
		ZEND_PARSE_PARAMETERS_END();
		NodeTraverser self(Z_OBJ_P(ZEND_THIS));
		if (UNEXPECTED(!self.construct(visitors, count))) RETURN_THROWS();
	});

	cls.method(sigs::addVisitor, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *visitor;
		if (!zp::parse<zp::Obj>(execute_data, visitor)) RETURN_THROWS();
		NodeTraverser(Z_OBJ_P(ZEND_THIS)).addVisitor(zv::Ref(visitor));
	});

	cls.method(sigs::removeVisitor, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *visitor;
		if (!zp::parse<zp::Obj>(execute_data, visitor)) RETURN_THROWS();
		if (UNEXPECTED(!NodeTraverser(Z_OBJ_P(ZEND_THIS)).removeVisitor(zv::Ref(visitor)))) RETURN_THROWS();
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *nodes;
		if (!zp::parse<zp::Ht>(execute_data, nodes)) RETURN_THROWS();
		NodeTraverser self(Z_OBJ_P(ZEND_THIS));
		zv::Val result = self.traverse(nodes);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_node_traverser);
}

/* }}} */
