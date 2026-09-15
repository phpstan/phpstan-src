/*
 * PHPStanTurbo\VariableLivenessResolver — native implementation of
 * PHPStan\Analyser\VariableLivenessResolver.
 *
 * The twin's only public entry point is the static resolve(): it creates a
 * private instance, walks the flow tree twice (collect(), then the
 * backwards liveBefore() over the compiled access keys) and hands the
 * result to a VariableWritesNode. The native class keeps that instance's
 * state on the C stack — PHP arrays in the twin's exact shapes, so every
 * set union, key order and insertion order is the one the twin produces —
 * and never instantiates the PHP class; the private methods are C++ members
 * of the same names.
 *
 * The flow objects are the PHP flow classes (their readonly properties read
 * from the slots, per class entry), the writes are VariableWrite instances
 * (slots when exactly that class, the getters otherwise), the Type queries
 * of the catch clauses go through the Type ops.
 */

#include "support.h"
#include "generated/VariableLivenessResolver.h"

namespace sigs = ptdecl::VariableLivenessResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

#include <cstring>
#include <utility>
#include <vector>

static zend_class_entry *pt_ce_variable_liveness_resolver;

namespace {

/* the twin's VariableWrite::KIND_PARAMETER / KIND_CLOSURE_USE */
const zend_long PT_VLR_KIND_PARAMETER = 12;
const zend_long PT_VLR_KIND_CLOSURE_USE = 13;

/* VariableFlow::* as an enum, PT_VLR_OTHER for any other string */
enum FlowKind
{
	PT_VLR_SEQUENCE,
	PT_VLR_CHOICE,
	PT_VLR_LOOP,
	PT_VLR_TRY_CATCH,
	PT_VLR_SWITCH,
	PT_VLR_READ,
	PT_VLR_WRITE,
	PT_VLR_DEFINE,
	PT_VLR_DISCARD,
	PT_VLR_ESCAPE,
	PT_VLR_MENTION,
	PT_VLR_READ_ALL,
	PT_VLR_MENTION_ALL,
	PT_VLR_OPAQUE,
	PT_VLR_DEAD,
	PT_VLR_RETURN,
	PT_VLR_BREAK,
	PT_VLR_CONTINUE,
	PT_VLR_THROW,
	PT_VLR_STOP,
	PT_VLR_ARROW,
	PT_VLR_LOOP_STATEMENT,
	PT_VLR_OTHER,
};

const struct { const char *value; size_t len; FlowKind kind; } pt_vlr_kinds[] = {
	{ PT_LC("sequence"), PT_VLR_SEQUENCE },
	{ PT_LC("choice"), PT_VLR_CHOICE },
	{ PT_LC("loop"), PT_VLR_LOOP },
	{ PT_LC("try"), PT_VLR_TRY_CATCH },
	{ PT_LC("switch"), PT_VLR_SWITCH },
	{ PT_LC("read"), PT_VLR_READ },
	{ PT_LC("write"), PT_VLR_WRITE },
	{ PT_LC("define"), PT_VLR_DEFINE },
	{ PT_LC("discard"), PT_VLR_DISCARD },
	{ PT_LC("escape"), PT_VLR_ESCAPE },
	{ PT_LC("mention"), PT_VLR_MENTION },
	{ PT_LC("readAll"), PT_VLR_READ_ALL },
	{ PT_LC("mentionAll"), PT_VLR_MENTION_ALL },
	{ PT_LC("opaque"), PT_VLR_OPAQUE },
	{ PT_LC("dead"), PT_VLR_DEAD },
	{ PT_LC("return"), PT_VLR_RETURN },
	{ PT_LC("break"), PT_VLR_BREAK },
	{ PT_LC("continue"), PT_VLR_CONTINUE },
	{ PT_LC("throw"), PT_VLR_THROW },
	{ PT_LC("stop"), PT_VLR_STOP },
	{ PT_LC("arrow"), PT_VLR_ARROW },
	{ PT_LC("loopStatement"), PT_VLR_LOOP_STATEMENT },
};

FlowKind kindOf(zend_string *kind)
{
	for (size_t i = 0; i < sizeof(pt_vlr_kinds) / sizeof(pt_vlr_kinds[0]); i++) {
		if (ZSTR_LEN(kind) == pt_vlr_kinds[i].len && memcmp(ZSTR_VAL(kind), pt_vlr_kinds[i].value, ZSTR_LEN(kind)) == 0) return pt_vlr_kinds[i].kind;
	}
	return PT_VLR_OTHER;
}

/* the property slots of the four final PHP flow classes, resolved once per
 * resolve() (their class entries come from the class map) */
struct FlowSlots
{
	zend_class_entry *accessCe;
	zend_class_entry *sequenceCe;
	zend_class_entry *controlCe;
	zend_class_entry *inputCe;
	/* VariableFlow::$kind */
	uint32_t accessKind, sequenceKind, controlKind, inputKind;
	/* VariableAccessFlow */
	uint32_t accessName, accessWrite, accessType, accessTargetId, accessContainer, accessOffset;
	/* VariableSequenceFlow */
	uint32_t sequenceChildren;
	/* VariableControlFlow */
	uint32_t controlChildren, controlName, controlType, controlLevel, controlAtLeastOnce, controlCanExit, controlCatches, controlArrow, controlCases, controlCanRepeat, controlCanContainAnyThrowable, controlStmt, controlBindings, controlOwnWrites;
	/* VariableInputFlow */
	uint32_t inputWriteId, inputTargetId;

	static bool offsetOf(zend_class_entry *ce, const char *name, uint32_t &out)
	{
		int32_t offset = pt_instance_prop_offset(ce, name, strlen(name));
		if (UNEXPECTED(offset < 0)) {
			zend_throw_error(NULL, "phpstan_turbo: %s has no property $%s", ZSTR_VAL(ce->name), name);
			return false;
		}
		out = (uint32_t) offset;
		return true;
	}

	/* false = pending exception */
	[[nodiscard]] bool resolve()
	{
		accessCe = pt_class(PT_CLASS_VARIABLE_ACCESS_FLOW);
		sequenceCe = pt_class(PT_CLASS_VARIABLE_SEQUENCE_FLOW);
		controlCe = pt_class(PT_CLASS_VARIABLE_CONTROL_FLOW);
		inputCe = pt_class(PT_CLASS_VARIABLE_INPUT_FLOW);
		if (UNEXPECTED(accessCe == NULL || sequenceCe == NULL || controlCe == NULL || inputCe == NULL)) return false;
		return offsetOf(accessCe, "kind", accessKind) && offsetOf(sequenceCe, "kind", sequenceKind)
			&& offsetOf(controlCe, "kind", controlKind) && offsetOf(inputCe, "kind", inputKind)
			&& offsetOf(accessCe, "name", accessName) && offsetOf(accessCe, "write", accessWrite)
			&& offsetOf(accessCe, "type", accessType) && offsetOf(accessCe, "targetId", accessTargetId)
			&& offsetOf(accessCe, "container", accessContainer) && offsetOf(accessCe, "offset", accessOffset)
			&& offsetOf(sequenceCe, "children", sequenceChildren)
			&& offsetOf(controlCe, "children", controlChildren) && offsetOf(controlCe, "name", controlName)
			&& offsetOf(controlCe, "type", controlType) && offsetOf(controlCe, "level", controlLevel)
			&& offsetOf(controlCe, "atLeastOnce", controlAtLeastOnce) && offsetOf(controlCe, "canExit", controlCanExit)
			&& offsetOf(controlCe, "catches", controlCatches) && offsetOf(controlCe, "arrow", controlArrow)
			&& offsetOf(controlCe, "cases", controlCases) && offsetOf(controlCe, "canRepeat", controlCanRepeat)
			&& offsetOf(controlCe, "canContainAnyThrowable", controlCanContainAnyThrowable)
			&& offsetOf(controlCe, "stmt", controlStmt) && offsetOf(controlCe, "bindings", controlBindings)
			&& offsetOf(controlCe, "ownWrites", controlOwnWrites)
			&& offsetOf(inputCe, "writeId", inputWriteId) && offsetOf(inputCe, "targetId", inputTargetId);
	}
};

/* a readonly property slot, dereferenced; NULL with the engine's Error
 * pending when it was never initialized */
zval *slotOf(zend_object *object, uint32_t offset, const char *name)
{
	zval *slot = OBJ_PROP(object, offset);
	ZVAL_DEREF(slot);
	if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(object->ce->name), name);
		return NULL;
	}
	return slot;
}

/* the getters of a VariableWrite as values: the slots for an instance of
 * exactly that class, the getters otherwise */
struct WriteView
{
	zv::Val name;          /* string */
	zend_string *nameStr;  /* borrowed from name */
	zend_long id;
	zv::Val offset;        /* int|string|null */
	bool offsetIsNull;
	bool offsetWrite;
	bool parentIdIsNull;
	zend_long parentId;
	bool replacesOffset;

	/* false = pending exception */
	[[nodiscard]] bool load(zval *write)
	{
		bool error;
		const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
		zend_object *object = Z_OBJ_P(write);
		zv::Val parentIdValue;
		if (slots != NULL) {
			name = zv::Val::copyOf(zv::Ref(OBJ_PROP(object, slots->variableName)));
			id = Z_LVAL_P(OBJ_PROP(object, slots->id));
			offset = zv::Val::copyOf(zv::Ref(OBJ_PROP(object, slots->offset)).deref());
			offsetWrite = zend_is_true(OBJ_PROP(object, slots->offsetWrite));
			parentIdValue = zv::Val::copyOf(zv::Ref(OBJ_PROP(object, slots->parentId)).deref());
			replacesOffset = zend_is_true(OBJ_PROP(object, slots->replacesOffset));
		} else {
			if (UNEXPECTED(error)) return false;
			name = pt_type_call(object, PT_LC("getvariablename"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return false;
			zv::Val idValue = pt_type_call(object, PT_LC("getid"), 0, NULL);
			if (UNEXPECTED(idValue.isUndef())) return false;
			id = zval_get_long(idValue.raw());
			offset = pt_type_call(object, PT_LC("getoffset"), 0, NULL);
			if (UNEXPECTED(offset.isUndef())) return false;
			zv::Val offsetWriteValue = pt_type_call(object, PT_LC("isoffsetwrite"), 0, NULL);
			if (UNEXPECTED(offsetWriteValue.isUndef())) return false;
			offsetWrite = zend_is_true(offsetWriteValue.raw());
			parentIdValue = pt_type_call(object, PT_LC("getparentid"), 0, NULL);
			if (UNEXPECTED(parentIdValue.isUndef())) return false;
			zv::Val replacesOffsetValue = pt_type_call(object, PT_LC("replacesoffset"), 0, NULL);
			if (UNEXPECTED(replacesOffsetValue.isUndef())) return false;
			replacesOffset = zend_is_true(replacesOffsetValue.raw());
		}
		if (UNEXPECTED(Z_TYPE_P(name.raw()) != IS_STRING)) {
			zend_type_error("phpstan_turbo: VariableWrite::getVariableName() did not return a string");
			return false;
		}
		nameStr = Z_STR_P(name.raw());
		offsetIsNull = Z_TYPE_P(offset.raw()) == IS_NULL;
		parentIdIsNull = Z_TYPE_P(parentIdValue.raw()) == IS_NULL;
		parentId = parentIdIsNull ? 0 : zval_get_long(parentIdValue.raw());
		return true;
	}
};

/* $write->getId() alone (the loop bindings and own writes) */
bool writeId(zval *write, zend_long &out)
{
	bool error;
	const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (slots != NULL) {
		out = Z_LVAL_P(OBJ_PROP(Z_OBJ_P(write), slots->id));
		return true;
	}
	if (UNEXPECTED(error)) return false;
	zv::Val idValue = pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
	if (UNEXPECTED(idValue.isUndef())) return false;
	out = zval_get_long(idValue.raw());
	return true;
}

/* $write->getVariableName() alone (resolveDependencies) */
zv::Val writeName(zval *write)
{
	bool error;
	const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (slots != NULL) return zv::Val::copyOf(zv::Ref(OBJ_PROP(Z_OBJ_P(write), slots->variableName)));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), PT_LC("getvariablename"), 0, NULL);
}

/* {{{ the twin's array<string, true> / array<int, ...> tables as owned PHP
 * arrays: every operation is the PHP array operation of the same name, so
 * key order and copy-on-write behaviour are the twin's */

/* the empty array literal */
zv::Val emptyArray()
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	return zv::Val::adopt(empty);
}

HashTable *tableOf(const zv::Val &array)
{
	return Z_ARRVAL_P(const_cast<zv::Val &>(array).raw());
}

uint32_t countOf(const zv::Val &array)
{
	return zend_hash_num_elements(tableOf(array));
}

/* a shared (addref) copy */
zv::Val shareArray(const zv::Val &array)
{
	return zv::Val::copyOf(zv::Ref(const_cast<zv::Val &>(array).raw()));
}

/* $array[$key] = true / $array[$index] = true (separating a shared array) */
void setTrue(zv::Val &array, zend_string *key)
{
	SEPARATE_ARRAY(array.raw());
	zval trueValue;
	ZVAL_TRUE(&trueValue);
	zend_hash_update(Z_ARRVAL_P(array.raw()), key, &trueValue);
}

void setTrueIndex(zv::Val &array, zend_long index)
{
	SEPARATE_ARRAY(array.raw());
	zval trueValue;
	ZVAL_TRUE(&trueValue);
	zend_hash_index_update(Z_ARRVAL_P(array.raw()), (zend_ulong) index, &trueValue);
}

/* $array[$index] = $value (borrowed, addref'd) */
void setIndex(zv::Val &array, zend_long index, zval *value)
{
	SEPARATE_ARRAY(array.raw());
	Z_TRY_ADDREF_P(value);
	zend_hash_index_update(Z_ARRVAL_P(array.raw()), (zend_ulong) index, value);
}

/* unset($array[$key]) */
void unsetKey(zv::Val &array, zend_string *key)
{
	SEPARATE_ARRAY(array.raw());
	zend_hash_del(Z_ARRVAL_P(array.raw()), key);
}

/* $array[$index] ?? NULL — the inner table (borrowed), NULL when absent */
HashTable *innerTable(const zv::Val &array, zend_long index)
{
	zval *slot = zend_hash_index_find(tableOf(array), (zend_ulong) index);
	if (slot == NULL) return NULL;
	ZVAL_DEREF(slot);
	return Z_TYPE_P(slot) == IS_ARRAY ? Z_ARRVAL_P(slot) : NULL;
}

HashTable *innerTableByKey(const zv::Val &array, zend_string *key)
{
	zval *slot = zend_hash_find(tableOf(array), key);
	if (slot == NULL) return NULL;
	ZVAL_DEREF(slot);
	return Z_TYPE_P(slot) == IS_ARRAY ? Z_ARRVAL_P(slot) : NULL;
}

/* &$array[$index] as an array, created empty when absent (the twin's
 * `$this->x[$i][$j] = ...` autovivification); the returned slot is writable */
zval *innerSlot(zv::Val &array, zend_long index)
{
	SEPARATE_ARRAY(array.raw());
	zval *slot = zend_hash_index_find(Z_ARRVAL_P(array.raw()), (zend_ulong) index);
	if (slot == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		slot = zend_hash_index_add_new(Z_ARRVAL_P(array.raw()), (zend_ulong) index, &empty);
	}
	SEPARATE_ARRAY(slot);
	return slot;
}

zval *innerSlotByKey(zv::Val &array, zend_string *key)
{
	SEPARATE_ARRAY(array.raw());
	zval *slot = zend_hash_find(Z_ARRVAL_P(array.raw()), key);
	if (slot == NULL) {
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		slot = zend_hash_add_new(Z_ARRVAL_P(array.raw()), key, &empty);
	}
	SEPARATE_ARRAY(slot);
	return slot;
}

/* $a + $b: a copy of $a with $b's entries under keys $a lacks */
zv::Val unionOf(const zv::Val &a, HashTable *b)
{
	if (b == NULL || zend_hash_num_elements(b) == 0) return shareArray(a);
	zval result;
	ZVAL_ARR(&result, zend_array_dup(tableOf(a)));
	zend_hash_merge(Z_ARRVAL(result), b, zval_add_ref, 0);
	return zv::Val::adopt(result);
}

zv::Val unionOf(const zv::Val &a, const zv::Val &b)
{
	return unionOf(a, tableOf(b));
}

bool isTrueAt(const zv::Val &array, zend_string *key)
{
	return zend_hash_exists(tableOf(array), key);
}

bool isTrueAtIndex(const zv::Val &array, zend_long index)
{
	return zend_hash_index_exists(tableOf(array), (zend_ulong) index);
}

/* }}} */

/* the live variables at the surrounding control-flow destinations (the
 * twin's VariableFlowContext, a value object that never leaves resolve()) */
struct Context
{
	zv::Val return_;
	std::vector<zv::Val> breaks;
	std::vector<zv::Val> continues;
	std::vector<std::pair<zval *, zv::Val>> catches; /* [Type (borrowed from the flow), destination] */
	zv::Val uncaught;

	Context() : return_(emptyArray()), uncaught(emptyArray()) {}
	Context(Context &&) = default;
	Context &operator=(Context &&) = default;

	static std::vector<zv::Val> shareAll(const std::vector<zv::Val> &arrays)
	{
		std::vector<zv::Val> copies;
		copies.reserve(arrays.size());
		for (const zv::Val &array : arrays) {
			copies.push_back(shareArray(array));
		}
		return copies;
	}

	static std::vector<std::pair<zval *, zv::Val>> shareCatches(const std::vector<std::pair<zval *, zv::Val>> &catches)
	{
		std::vector<std::pair<zval *, zv::Val>> copies;
		copies.reserve(catches.size());
		for (const auto &entry : catches) {
			copies.emplace_back(entry.first, shareArray(entry.second));
		}
		return copies;
	}

	/* [$first, ...$rest] */
	static std::vector<zv::Val> prepend(const zv::Val &first, const std::vector<zv::Val> &rest)
	{
		std::vector<zv::Val> result;
		result.reserve(rest.size() + 1);
		result.push_back(shareArray(first));
		for (const zv::Val &array : rest) {
			result.push_back(shareArray(array));
		}
		return result;
	}

private:
	Context(const Context &) = delete;
};

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableLivenessResolver; one instance per
 * resolve() call, on the C stack. A method returning bool reports a pending
 * exception with false, one returning zv::Val with UNDEF. */
class VariableLivenessResolver
{
public:
	/* Mirrors resolve(); $flow NULL for null. */
	static zv::Val resolve(zval *function, zval *flow)
	{
		VariableLivenessResolver self;
		if (UNEXPECTED(!self.slots.resolve())) return zv::Val();
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		zend_class_entry *closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
		if (UNEXPECTED(variableCe == NULL || closureCe == NULL)) return zv::Val();

		zv::Val returnsByRef = pt_type_call(Z_OBJ_P(function), PT_LC("returnsbyref"), 0, NULL);
		if (UNEXPECTED(returnsByRef.isUndef())) return zv::Val();
		self.returnsByReference = zend_is_true(returnsByRef.raw());
		zv::Arr imports = zv::Arr::empty();
		zv::Val params = pt_type_call(Z_OBJ_P(function), PT_LC("getparams"), 0, NULL);
		if (UNEXPECTED(params.isUndef())) return zv::Val();
		if (Z_TYPE_P(params.raw()) == IS_ARRAY) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(params.raw()))) {
				zv::Ref param = entry.value().deref();
				if (!param.isObject()) continue;
				zv::Ref var = zv::ObjRef(param.asObject()).prop(PT_LC("var"));
				if (var.raw() == NULL) continue;
				var = var.deref();
				zend_string *name = var.instanceOf(variableCe) ? variableName(var.asObject()) : NULL;
				if (name == NULL) continue;
				zv::Ref byRef = zv::ObjRef(param.asObject()).prop(PT_LC("byRef"));
				zv::Ref flags = zv::ObjRef(param.asObject()).prop(PT_LC("flags"));
				if ((byRef.raw() != NULL && zend_is_true(byRef.raw())) || (flags.raw() != NULL && zval_get_long(flags.deref().raw()) != 0)) {
					setTrue(self.escapedNames, name);
					continue;
				}
				if (UNEXPECTED(!self.import(imports, name, var.raw(), PT_VLR_KIND_PARAMETER))) return zv::Val();
			}
		}
		if (instanceof_function(Z_OBJCE_P(function), closureCe)) {
			zv::Ref uses = zv::ObjRef(Z_OBJ_P(function)).prop(PT_LC("uses"));
			if (uses.raw() != NULL && uses.deref().isArray()) {
				for (auto entry : zv::TableRef(uses.deref().asArrayTable())) {
					zv::Ref use = entry.value().deref();
					if (!use.isObject()) continue;
					zv::Ref var = zv::ObjRef(use.asObject()).prop(PT_LC("var"));
					if (var.raw() == NULL) continue;
					var = var.deref();
					zend_string *name = var.isObject() ? variableName(var.asObject()) : NULL;
					if (name == NULL) continue;
					zv::Ref byRef = zv::ObjRef(use.asObject()).prop(PT_LC("byRef"));
					if (byRef.raw() != NULL && zend_is_true(byRef.raw())) {
						setTrue(self.escapedNames, name);
						continue;
					}
					if (UNEXPECTED(!self.import(imports, name, var.raw(), PT_VLR_KIND_CLOSURE_USE))) return zv::Val();
				}
			}
		}
		/* VariableFlow::sequence(...[...$imports, $flow]) */
		zv::Arr sequenceArgs = zv::Arr::create(zend_hash_num_elements(imports.table()) + 1);
		for (auto entry : zv::TableRef(imports.table())) {
			sequenceArgs.push(entry.value());
		}
		if (flow != NULL) {
			sequenceArgs.push(zv::Ref(flow));
		} else {
			sequenceArgs.push(zv::Val::null());
		}
		zv::Val body = pt_variable_flow_sequence_list(sequenceArgs.table());
		if (UNEXPECTED(body.isUndef())) return zv::Val();
		zval *bodyFlow = Z_TYPE_P(body.raw()) == IS_OBJECT ? body.raw() : NULL;
		if (UNEXPECTED(!self.collect(bodyFlow, false))) return zv::Val();
		if (countOf(self.writes) != 0 && !self.opaque) {
			if (UNEXPECTED(!self.compileAccesses())) return zv::Val();
			Context context;
			zv::Val live = self.liveBefore(bodyFlow, emptyArray(), context);
			if (UNEXPECTED(live.isUndef())) return zv::Val();
			if (UNEXPECTED(!self.resolveDependencies())) return zv::Val();
			self.resolveCoverage();
		}

		/* new VariableWritesNode(...) */
		zv::Arr writeList = zv::Arr::create(countOf(self.writes));
		for (auto entry : zv::TableRef(tableOf(self.writes))) {
			writeList.push(entry.value());
		}
		zv::Val readWriteIds = unionOf(self.observedIds, self.readIds);
		zval argv[12];
		ZVAL_COPY_VALUE(&argv[0], function);
		ZVAL_COPY_VALUE(&argv[1], writeList.raw());
		ZVAL_COPY_VALUE(&argv[2], readWriteIds.raw());
		ZVAL_COPY_VALUE(&argv[3], self.readIds.raw());
		ZVAL_COPY_VALUE(&argv[4], self.coveredIds.raw());
		ZVAL_COPY_VALUE(&argv[5], self.readNames.raw());
		ZVAL_COPY_VALUE(&argv[6], self.redundantTypes.raw());
		ZVAL_COPY_VALUE(&argv[7], self.mentionedNames.raw());
		ZVAL_COPY_VALUE(&argv[8], self.escapedNames.raw());
		ZVAL_COPY_VALUE(&argv[9], self.variableOverwritingLoops.raw());
		ZVAL_BOOL(&argv[10], self.opaque);
		ZVAL_BOOL(&argv[11], self.allNamesMentioned);
		return pt_type_new(PT_CLASS_VARIABLE_WRITES_NODE, 12, argv);
	}

private:
	FlowSlots slots;
	zv::Val writes = emptyArray();
	zv::Val readIds = emptyArray();
	zv::Val observedIds = emptyArray();
	zv::Val readNames = emptyArray();
	zv::Val mentionedNames = emptyArray();
	zv::Val escapedNames = emptyArray();
	zv::Val redundantTypes = emptyArray();
	zv::Val accesses = emptyArray();
	zv::Val readKeys = emptyArray();
	zv::Val nameKeys = emptyArray();
	zv::Val observedKeys = emptyArray();
	zv::Val killedKeys = emptyArray();
	zv::Val dependencies = emptyArray();
	zv::Val inputCopies = emptyArray();
	zv::Val inputSinks = emptyArray();
	zv::Val literalItems = emptyArray();
	zv::Val coveredIds = emptyArray();
	zv::Val allReadKeys = emptyArray();
	zv::Val loopStatements = emptyArray();
	zv::Val ownWriteIds = emptyArray();
	zv::Val variableOverwritingLoops = emptyArray();
	bool opaque = false;
	bool readsAllVariables = false;
	bool allNamesMentioned = false;
	bool returnsByReference = false;

	VariableLivenessResolver() = default;

	/* a Variable node's string name (borrowed), NULL for a variable variable */
	static zend_string *variableName(zend_object *variable)
	{
		zv::Ref name = zv::ObjRef(variable).prop(PT_LC("name"));
		if (name.raw() == NULL) return NULL;
		name = name.deref();
		return name.isString() ? name.asString() : NULL;
	}

	/* $imports[] = VariableFlow::write(new VariableWrite($name, $var, spl_object_id($var), $kind)) */
	bool import(zv::Arr &imports, zend_string *name, zval *var, zend_long kind)
	{
		zv::Args argv{name, var, zend_long((zend_long) Z_OBJ_HANDLE_P(var)), zend_long(kind)};
		zv::Val write = pt_type_new(PT_CLASS_VARIABLE_WRITE, 4, argv);
		if (UNEXPECTED(write.isUndef())) return false;
		zv::Val flow = pt_variable_flow_write(write.raw(), NULL);
		if (UNEXPECTED(flow.isUndef())) return false;
		imports.push(std::move(flow));
		return true;
	}

	/* {{{ flow readers */

	zval *slot(zend_object *flow, uint32_t offset, const char *name)
	{
		return slotOf(flow, offset, name);
	}

	/* $flow->kind; PT_VLR_OTHER with an exception pending when unreadable */
	FlowKind kindOfFlow(zend_object *flow, uint32_t kindOffset)
	{
		zval *kind = slot(flow, kindOffset, "kind");
		if (UNEXPECTED(kind == NULL || Z_TYPE_P(kind) != IS_STRING)) {
			if (kind != NULL) {
				zend_throw_error(NULL, "phpstan_turbo: VariableFlow::$kind is not a string");
			}
			return PT_VLR_OTHER;
		}
		return kindOf(Z_STR_P(kind));
	}

	bool isAccess(zend_object *flow) const { return instanceof_function(flow->ce, slots.accessCe); }
	bool isSequence(zend_object *flow) const { return instanceof_function(flow->ce, slots.sequenceCe); }
	bool isControl(zend_object *flow) const { return instanceof_function(flow->ce, slots.controlCe); }
	bool isInput(zend_object *flow) const { return instanceof_function(flow->ce, slots.inputCe); }

	/* the kind slot of whichever flow class this is */
	uint32_t kindOffsetOf(zend_object *flow) const
	{
		if (isAccess(flow)) return slots.accessKind;
		if (isSequence(flow)) return slots.sequenceKind;
		if (isControl(flow)) return slots.controlKind;
		return slots.inputKind;
	}

	/* a flow-valued slot: NULL for null, the object otherwise */
	static zval *flowOf(zval *slotValue)
	{
		return slotValue != NULL && Z_TYPE_P(slotValue) == IS_OBJECT ? slotValue : NULL;
	}

	/* $children[$i] (a list of ?VariableFlow) */
	static zval *childAt(HashTable *children, zend_long index)
	{
		zval *child = zend_hash_index_find(children, (zend_ulong) index);
		if (child == NULL) return NULL;
		ZVAL_DEREF(child);
		return flowOf(child);
	}

	/* }}} */

	/* Mirrors collect(). */
	bool collect(zval *flowValue, bool dead)
	{
		if (flowValue == NULL) return true;
		zend_object *flow = Z_OBJ_P(flowValue);
		if (isInput(flow)) return true;
		bool access = isAccess(flow);
		FlowKind kind = kindOfFlow(flow, kindOffsetOf(flow));
		if (UNEXPECTED(EG(exception))) return false;
		if (access) {
			zval *name = slot(flow, slots.accessName, "name");
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) == IS_STRING && !zend_string_equals_literal(Z_STR_P(name), "this") && !pt_is_superglobal_name(Z_STR_P(name))) {
				setTrue(mentionedNames, Z_STR_P(name));
				zval *accessList = innerSlotByKey(accesses, Z_STR_P(name));
				Z_ADDREF_P(flowValue);
				zend_hash_next_index_insert(Z_ARRVAL_P(accessList), flowValue);
				if (kind == PT_VLR_READ) {
					setTrue(readNames, Z_STR_P(name));
				} else if (kind == PT_VLR_ESCAPE) {
					setTrue(escapedNames, Z_STR_P(name));
				}
				zval *write = slot(flow, slots.accessWrite, "write");
				if (UNEXPECTED(write == NULL)) return false;
				if (Z_TYPE_P(write) == IS_OBJECT && kind != PT_VLR_DISCARD) {
					WriteView view;
					if (UNEXPECTED(!view.load(write))) return false;
					setIndex(writes, view.id, write);
					if (!view.parentIdIsNull) {
						zval *items = innerSlot(literalItems, view.parentId);
						Z_ADDREF_P(write);
						zend_hash_index_update(Z_ARRVAL_P(items), (zend_ulong) view.id, write);
					}
					zval *type = slot(flow, slots.accessType, "type");
					if (UNEXPECTED(type == NULL)) return false;
					if (Z_TYPE_P(type) != IS_NULL) {
						setIndex(redundantTypes, view.id, type);
					}
					if (dead) {
						setTrueIndex(readIds, view.id);
					}
				}
			}
		}
		if (kind == PT_VLR_READ_ALL) {
			readsAllVariables = true;
		}
		if (kind == PT_VLR_OPAQUE) {
			opaque = true;
		}
		if (kind == PT_VLR_READ_ALL || kind == PT_VLR_MENTION_ALL) {
			allNamesMentioned = true;
		}
		if (access) return true;
		bool control = isControl(flow);
		if (!isSequence(flow) && !control) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *children = slot(flow, control ? slots.controlChildren : slots.sequenceChildren, "children");
		if (UNEXPECTED(children == NULL)) return false;
		if (Z_TYPE_P(children) == IS_ARRAY) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(children))) {
				if (UNEXPECTED(!collect(flowOf(entry.value().deref().raw()), dead || kind == PT_VLR_DEAD))) return false;
			}
		}
		if (!control) return true;
		if (kind == PT_VLR_LOOP_STATEMENT) {
			zval *stmt = slot(flow, slots.controlStmt, "stmt");
			if (UNEXPECTED(stmt == NULL)) return false;
			if (Z_TYPE_P(stmt) == IS_OBJECT) {
				zval *ownWrites = slot(flow, slots.controlOwnWrites, "ownWrites");
				zval *bindings = slot(flow, slots.controlBindings, "bindings");
				if (UNEXPECTED(ownWrites == NULL || bindings == NULL)) return false;
				zv::Val ownIds = emptyArray();
				if (Z_TYPE_P(ownWrites) == IS_ARRAY) {
					for (auto entry : zv::TableRef(Z_ARRVAL_P(ownWrites))) {
						zend_long id;
						if (UNEXPECTED(!writeId(entry.value().deref().raw(), id))) return false;
						setTrueIndex(ownIds, id);
					}
				}
				if (Z_TYPE_P(bindings) == IS_ARRAY) {
					for (auto entry : zv::TableRef(Z_ARRVAL_P(bindings))) {
						zend_long id;
						if (UNEXPECTED(!writeId(entry.value().deref().raw(), id))) return false;
						setIndex(loopStatements, id, stmt);
						setIndex(ownWriteIds, id, ownIds.raw());
					}
				}
			}
		}
		if (kind == PT_VLR_RETURN && returnsByReference) {
			zval *name = slot(flow, slots.controlName, "name");
			if (UNEXPECTED(name == NULL)) return false;
			if (Z_TYPE_P(name) == IS_STRING) {
				setTrue(escapedNames, Z_STR_P(name));
			}
		}
		zval *cases = slot(flow, slots.controlCases, "cases");
		if (UNEXPECTED(cases == NULL)) return false;
		if (Z_TYPE_P(cases) == IS_ARRAY) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(cases))) {
				zv::Ref caseEntry = entry.value().deref();
				if (!caseEntry.isArray()) continue;
				if (UNEXPECTED(!collect(childAt(caseEntry.asArrayTable(), 0), dead) || !collect(childAt(caseEntry.asArrayTable(), 1), dead))) return false;
			}
		}
		zval *catches = slot(flow, slots.controlCatches, "catches");
		if (UNEXPECTED(catches == NULL)) return false;
		if (Z_TYPE_P(catches) == IS_ARRAY) {
			for (auto entry : zv::TableRef(Z_ARRVAL_P(catches))) {
				zv::Ref catchEntry = entry.value().deref();
				if (!catchEntry.isArray()) continue;
				if (UNEXPECTED(!collect(childAt(catchEntry.asArrayTable(), 1), dead))) return false;
			}
		}
		return true;
	}

	/* Mirrors liveBefore(); $next is consumed, the result owned. */
	zv::Val liveBefore(zval *flowValue, zv::Val next, const Context &context)
	{
		if (flowValue == NULL) return next;
		zend_object *flow = Z_OBJ_P(flowValue);
		FlowKind kind = kindOfFlow(flow, kindOffsetOf(flow));
		if (UNEXPECTED(EG(exception))) return zv::Val();
		if (kind == PT_VLR_DEAD) return next;
		if (isInput(flow)) {
			zval *writeIdValue = slot(flow, slots.inputWriteId, "writeId");
			zval *targetId = slot(flow, slots.inputTargetId, "targetId");
			if (UNEXPECTED(writeIdValue == NULL || targetId == NULL)) return zv::Val();
			if (Z_TYPE_P(targetId) == IS_NULL) {
				setTrueIndex(inputSinks, zval_get_long(writeIdValue));
			} else {
				zval *copies = innerSlot(inputCopies, zval_get_long(targetId));
				zval trueValue;
				ZVAL_TRUE(&trueValue);
				zend_hash_index_update(Z_ARRVAL_P(copies), (zend_ulong) zval_get_long(writeIdValue), &trueValue);
			}
			return next;
		}
		if (isAccess(flow)) {
			zval *name = slot(flow, slots.accessName, "name");
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (kind == PT_VLR_READ || kind == PT_VLR_ESCAPE) {
				// a by-reference capture aliases the variable - the value it
				// holds at that point is observable through the alias
				if (kind == PT_VLR_ESCAPE && countOf(loopStatements) != 0) {
					next = passBindingProbes(std::move(next), Z_STR_P(name), NULL, false);
					if (UNEXPECTED(next.isUndef())) return zv::Val();
				}
				return unionOf(next, innerTable(readKeys, (zend_long) flow->handle));
			}
			zval *write = slot(flow, slots.accessWrite, "write");
			if (UNEXPECTED(write == NULL)) return zv::Val();
			if (Z_TYPE_P(write) != IS_OBJECT || kind == PT_VLR_DEFINE) return next;
			WriteView view;
			if (UNEXPECTED(!view.load(write))) return zv::Val();
			if (countOf(loopStatements) != 0) {
				next = passBindingProbes(std::move(next), Z_STR_P(name), &view, kind == PT_VLR_DISCARD);
				if (UNEXPECTED(next.isUndef())) return zv::Val();
			}
			zend_long id = view.id;
			if (kind != PT_VLR_DISCARD) {
				observeWrite(id, next);
				HashTable *items = innerTable(literalItems, id);
				if (items != NULL) {
					for (auto entry : zv::TableRef(items)) {
						zend_long itemId;
						if (UNEXPECTED(!writeId(entry.value().deref().raw(), itemId))) return zv::Val();
						observeWrite(itemId, next);
					}
				}
			}
			HashTable *killed = innerTable(killedKeys, id);
			if (killed != NULL) {
				for (auto entry : zv::TableRef(killed)) {
					zend_string *key = entry.stringKeyOrNull();
					if (key != NULL) {
						unsetKey(next, key);
					}
				}
			}
			return next;
		}
		if (isSequence(flow)) {
			zval *children = slot(flow, slots.sequenceChildren, "children");
			if (UNEXPECTED(children == NULL)) return zv::Val();
			HashTable *childTable = Z_TYPE_P(children) == IS_ARRAY ? Z_ARRVAL_P(children) : NULL;
			if (kind == PT_VLR_SEQUENCE) {
				if (childTable != NULL) {
					/* array_reverse($flow->children): the list walked backwards */
					for (zend_long i = (zend_long) zend_hash_num_elements(childTable) - 1; i >= 0; i--) {
						next = liveBefore(childAt(childTable, i), std::move(next), context);
						if (UNEXPECTED(next.isUndef())) return zv::Val();
					}
				}
				return next;
			}
			zv::Val names = emptyArray();
			if (childTable != NULL) {
				for (auto entry : zv::TableRef(childTable)) {
					zv::Val childNames = liveBefore(flowOf(entry.value().deref().raw()), shareArray(next), context);
					if (UNEXPECTED(childNames.isUndef())) return zv::Val();
					names = unionOf(names, childNames);
				}
			}
			return names;
		}
		if (!isControl(flow)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zval *children = slot(flow, slots.controlChildren, "children");
		if (UNEXPECTED(children == NULL)) return zv::Val();
		HashTable *childTable = Z_TYPE_P(children) == IS_ARRAY ? Z_ARRVAL_P(children) : NULL;
		if (kind == PT_VLR_LOOP_STATEMENT) {
			// a binding reusing a variable that is read after the loop: the
			// probe follows the variable backwards through the statement;
			// surviving to its entry, it is armed to catch the assignment
			// before the loop whose value the binding replaces
			zval *bindings = slot(flow, slots.controlBindings, "bindings");
			if (UNEXPECTED(bindings == NULL)) return zv::Val();
			HashTable *bindingTable = Z_TYPE_P(bindings) == IS_ARRAY ? Z_ARRVAL_P(bindings) : NULL;
			if (bindingTable != NULL) {
				for (auto entry : zv::TableRef(bindingTable)) {
					WriteView binding;
					if (UNEXPECTED(!binding.load(entry.value().deref().raw()))) return zv::Val();
					HashTable *keys = innerTableByKey(nameKeys, binding.nameStr);
					if (keys == NULL) continue;
					for (auto keyEntry : zv::TableRef(keys)) {
						zend_string *key = keyEntry.stringKeyOrNull();
						if (key == NULL || !isTrueAt(next, key)) continue;
						zv::Str probe = bindingProbe(binding, false);
						setTrue(next, probe.get());
						break;
					}
				}
			}
			zv::Val names = liveBefore(childTable != NULL ? childAt(childTable, 0) : NULL, std::move(next), context);
			if (UNEXPECTED(names.isUndef())) return zv::Val();
			if (bindingTable != NULL) {
				for (auto entry : zv::TableRef(bindingTable)) {
					WriteView binding;
					if (UNEXPECTED(!binding.load(entry.value().deref().raw()))) return zv::Val();
					zv::Str probe = bindingProbe(binding, false);
					if (!isTrueAt(names, probe.get())) continue;
					unsetKey(names, probe.get());
					zv::Str armed = bindingProbe(binding, true);
					setTrue(names, armed.get());
				}
			}
			return names;
		}
		if (kind == PT_VLR_ARROW) {
			zval *arrow = slot(flow, slots.controlArrow, "arrow");
			if (UNEXPECTED(arrow == NULL)) return zv::Val();
			if (Z_TYPE_P(arrow) == IS_OBJECT) {
				Context innerContext;
				zv::Val outputs = liveBefore(childTable != NULL ? childAt(childTable, 1) : NULL, emptyArray(), innerContext);
				if (UNEXPECTED(outputs.isUndef())) return zv::Val();
				Context bodyContext;
				bodyContext.return_ = shareArray(outputs);
				bodyContext.uncaught = shareArray(outputs);
				zv::Val names = liveBefore(childTable != NULL ? childAt(childTable, 0) : NULL, shareArray(outputs), bodyContext);
				if (UNEXPECTED(names.isUndef())) return zv::Val();
				zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
				if (UNEXPECTED(variableCe == NULL)) return zv::Val();
				zv::Ref params = zv::ObjRef(Z_OBJ_P(arrow)).prop(PT_LC("params"));
				if (params.raw() != NULL && params.deref().isArray()) {
					for (auto entry : zv::TableRef(params.deref().asArrayTable())) {
						zv::Ref param = entry.value().deref();
						if (!param.isObject()) continue;
						zv::Ref var = zv::ObjRef(param.asObject()).prop(PT_LC("var"));
						if (var.raw() == NULL) continue;
						var = var.deref();
						zend_string *paramName = var.instanceOf(variableCe) ? variableName(var.asObject()) : NULL;
						if (paramName == NULL) continue;
						HashTable *keys = innerTableByKey(nameKeys, paramName);
						if (keys == NULL) continue;
						/* array_keys() snapshots the keys; $names is a
						 * different table, so walking $keys directly is the
						 * same walk */
						for (auto keyEntry : zv::TableRef(keys)) {
							zend_string *key = keyEntry.stringKeyOrNull();
							if (key != NULL) {
								unsetKey(names, key);
							}
						}
					}
				}
				return unionOf(next, names);
			}
		}
		if (kind == PT_VLR_LOOP) {
			zval *canRepeat = slot(flow, slots.controlCanRepeat, "canRepeat");
			zval *canExit = slot(flow, slots.controlCanExit, "canExit");
			zval *atLeastOnce = slot(flow, slots.controlAtLeastOnce, "atLeastOnce");
			if (UNEXPECTED(canRepeat == NULL || canExit == NULL || atLeastOnce == NULL)) return zv::Val();
			zval *condition = childTable != NULL ? childAt(childTable, 0) : NULL;
			zval *bodyFlow = childTable != NULL ? childAt(childTable, 1) : NULL;
			zval *updateFlow = childTable != NULL ? childAt(childTable, 2) : NULL;
			zv::Val head = emptyArray();
			zv::Val body;
			uint32_t previousCount;
			do {
				previousCount = countOf(head);
				zv::Val update = liveBefore(updateFlow, shareArray(head), context);
				if (UNEXPECTED(update.isUndef())) return zv::Val();
				Context loopContext;
				loopContext.return_ = shareArray(context.return_);
				loopContext.breaks = Context::prepend(next, context.breaks);
				loopContext.continues = Context::prepend(update, context.continues);
				loopContext.catches = Context::shareCatches(context.catches);
				loopContext.uncaught = shareArray(context.uncaught);
				body = liveBefore(bodyFlow, shareArray(update), loopContext);
				if (UNEXPECTED(body.isUndef())) return zv::Val();
				zv::Val afterCondition = zend_is_true(canRepeat)
					? (zend_is_true(canExit) ? unionOf(body, next) : shareArray(body))
					: shareArray(next);
				head = liveBefore(condition, std::move(afterCondition), context);
				if (UNEXPECTED(head.isUndef())) return zv::Val();
			} while (countOf(head) != previousCount);

			return zend_is_true(atLeastOnce) ? liveBefore(condition, std::move(body), context) : std::move(head);
		}
		if (kind == PT_VLR_SWITCH) {
			zval *canExit = slot(flow, slots.controlCanExit, "canExit");
			zval *cases = slot(flow, slots.controlCases, "cases");
			if (UNEXPECTED(canExit == NULL || cases == NULL)) return zv::Val();
			HashTable *caseTable = Z_TYPE_P(cases) == IS_ARRAY ? Z_ARRVAL_P(cases) : NULL;
			zend_long caseCount = caseTable != NULL ? (zend_long) zend_hash_num_elements(caseTable) : 0;
			Context switchContext;
			switchContext.return_ = shareArray(context.return_);
			switchContext.breaks = Context::prepend(next, context.breaks);
			switchContext.continues = Context::prepend(next, context.continues);
			switchContext.catches = Context::shareCatches(context.catches);
			switchContext.uncaught = shareArray(context.uncaught);
			std::vector<zv::Val> entries((size_t) caseCount);
			zv::Val caseNext = shareArray(next);
			zv::Val unmatched = zend_is_true(canExit) ? shareArray(next) : emptyArray();
			for (zend_long i = caseCount - 1; i >= 0; i--) {
				zval *caseEntry = zend_hash_index_find(caseTable, (zend_ulong) i);
				HashTable *caseParts = caseEntry != NULL && Z_TYPE_P(caseEntry) == IS_ARRAY ? Z_ARRVAL_P(caseEntry) : NULL;
				zval *bodyFlow = caseParts != NULL ? childAt(caseParts, 1) : NULL;
				zval *isDefault = caseParts != NULL ? zend_hash_index_find(caseParts, 2) : NULL;
				caseNext = liveBefore(bodyFlow, std::move(caseNext), switchContext);
				if (UNEXPECTED(caseNext.isUndef())) return zv::Val();
				entries[(size_t) i] = shareArray(caseNext);
				if (isDefault == NULL || !zend_is_true(isDefault)) continue;

				unmatched = shareArray(caseNext);
			}
			for (zend_long i = caseCount - 1; i >= 0; i--) {
				zval *caseEntry = zend_hash_index_find(caseTable, (zend_ulong) i);
				HashTable *caseParts = caseEntry != NULL && Z_TYPE_P(caseEntry) == IS_ARRAY ? Z_ARRVAL_P(caseEntry) : NULL;
				zval *condition = caseParts != NULL ? childAt(caseParts, 0) : NULL;
				zval *isDefault = caseParts != NULL ? zend_hash_index_find(caseParts, 2) : NULL;
				if (isDefault != NULL && zend_is_true(isDefault)) continue;
				unmatched = liveBefore(condition, unionOf(entries[(size_t) i], unmatched), context);
				if (UNEXPECTED(unmatched.isUndef())) return zv::Val();
			}
			return liveBefore(childTable != NULL ? childAt(childTable, 0) : NULL, std::move(unmatched), context);
		}
		if (kind == PT_VLR_RETURN) return shareArray(context.return_);
		if (kind == PT_VLR_BREAK || kind == PT_VLR_CONTINUE) {
			zval *level = slot(flow, slots.controlLevel, "level");
			if (UNEXPECTED(level == NULL)) return zv::Val();
			const std::vector<zv::Val> &destinations = kind == PT_VLR_BREAK ? context.breaks : context.continues;
			zend_long index = zval_get_long(level) - 1;
			if (index < 0 || (size_t) index >= destinations.size()) return emptyArray();
			return shareArray(destinations[(size_t) index]);
		}
		if (kind == PT_VLR_STOP) return emptyArray();
		if (kind == PT_VLR_THROW) {
			zval *canExit = slot(flow, slots.controlCanExit, "canExit");
			zval *type = slot(flow, slots.controlType, "type");
			zval *canContainAnyThrowable = slot(flow, slots.controlCanContainAnyThrowable, "canContainAnyThrowable");
			if (UNEXPECTED(canExit == NULL || type == NULL || canContainAnyThrowable == NULL)) return zv::Val();
			zv::Val names = zend_is_true(canExit) ? std::move(next) : emptyArray();
			if (context.catches.empty()) return unionOf(names, context.uncaught);
			if (Z_TYPE_P(type) != IS_OBJECT) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			if (zend_is_true(canContainAnyThrowable)) {
				zv::Val throwable;
				{
					zval out;
					zend_string *throwableName = zend_string_init(PT_LC("Throwable"), 0);
					bool created = pt_object_type_new(&out, throwableName);
					zend_string_release(throwableName);
					if (UNEXPECTED(!created)) return zv::Val();
					throwable = zv::Val::adopt(out);
				}
				for (const auto &entry : context.catches) {
					zend_long accepts = isSuperTypeOf(entry.first, throwable.raw());
					if (UNEXPECTED(accepts < 0)) return zv::Val();
					if (accepts != PT_TRI_YES) continue;
					names = unionOf(names, entry.second);
					break;
				}
			}
			for (const auto &entry : context.catches) {
				zend_long accepts = isSuperTypeOf(entry.first, type);
				if (UNEXPECTED(accepts < 0)) return zv::Val();
				bool destinationReached = accepts != PT_TRI_NO;
				if (!destinationReached) {
					zend_long reverse = isSuperTypeOf(type, entry.first);
					if (UNEXPECTED(reverse < 0)) return zv::Val();
					destinationReached = reverse != PT_TRI_NO;
				}
				if (destinationReached) {
					names = unionOf(names, entry.second);
				}
				if (accepts == PT_TRI_YES) return names;
			}
			return unionOf(names, context.uncaught);
		}
		if (kind == PT_VLR_TRY_CATCH) {
			zval *finally = childTable != NULL ? childAt(childTable, 1) : NULL;
			zv::Val normal = liveBefore(finally, std::move(next), context);
			if (UNEXPECTED(normal.isUndef())) return zv::Val();
			std::vector<zv::Val> breaks;
			breaks.reserve(context.breaks.size());
			for (const zv::Val &destination : context.breaks) {
				zv::Val live = liveBefore(finally, shareArray(destination), context);
				if (UNEXPECTED(live.isUndef())) return zv::Val();
				breaks.push_back(std::move(live));
			}
			std::vector<zv::Val> continues;
			continues.reserve(context.continues.size());
			for (const zv::Val &destination : context.continues) {
				zv::Val live = liveBefore(finally, shareArray(destination), context);
				if (UNEXPECTED(live.isUndef())) return zv::Val();
				continues.push_back(std::move(live));
			}
			std::vector<std::pair<zval *, zv::Val>> outerCatches;
			outerCatches.reserve(context.catches.size());
			for (const auto &entry : context.catches) {
				zv::Val live = liveBefore(finally, shareArray(entry.second), context);
				if (UNEXPECTED(live.isUndef())) return zv::Val();
				outerCatches.emplace_back(entry.first, std::move(live));
			}
			Context catchContext;
			catchContext.return_ = liveBefore(finally, shareArray(context.return_), context);
			if (UNEXPECTED(catchContext.return_.isUndef())) return zv::Val();
			catchContext.breaks = Context::shareAll(breaks);
			catchContext.continues = Context::shareAll(continues);
			catchContext.catches = Context::shareCatches(outerCatches);
			catchContext.uncaught = liveBefore(finally, shareArray(context.uncaught), context);
			if (UNEXPECTED(catchContext.uncaught.isUndef())) return zv::Val();
			zval *catches = slot(flow, slots.controlCatches, "catches");
			if (UNEXPECTED(catches == NULL)) return zv::Val();
			std::vector<std::pair<zval *, zv::Val>> allCatches;
			if (Z_TYPE_P(catches) == IS_ARRAY) {
				for (auto entry : zv::TableRef(Z_ARRVAL_P(catches))) {
					zv::Ref catchEntry = entry.value().deref();
					if (!catchEntry.isArray()) continue;
					zval *catchType = zend_hash_index_find(catchEntry.asArrayTable(), 0);
					zval *catchFlow = childAt(catchEntry.asArrayTable(), 1);
					if (UNEXPECTED(catchType == NULL)) continue;
					ZVAL_DEREF(catchType);
					zv::Val live = liveBefore(catchFlow, shareArray(normal), catchContext);
					if (UNEXPECTED(live.isUndef())) return zv::Val();
					allCatches.emplace_back(catchType, std::move(live));
				}
			}
			for (auto &entry : outerCatches) {
				allCatches.emplace_back(entry.first, std::move(entry.second));
			}
			Context bodyContext;
			bodyContext.return_ = shareArray(catchContext.return_);
			bodyContext.breaks = std::move(breaks);
			bodyContext.continues = std::move(continues);
			bodyContext.catches = std::move(allCatches);
			bodyContext.uncaught = shareArray(catchContext.uncaught);
			return liveBefore(childTable != NULL ? childAt(childTable, 0) : NULL, std::move(normal), bodyContext);
		}
		if (kind == PT_VLR_READ_ALL) {
			readNames = unionOf(readNames, mentionedNames);
			return unionOf(next, allReadKeys);
		}
		return next;
	}

	/* $a->isSuperTypeOf($b)->result as a PT_TRI_* value; -1 = pending exception */
	[[nodiscard]] static zend_long isSuperTypeOf(zval *a, zval *b)
	{
		if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT || Z_TYPE_P(b) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected a Type in a catch clause");
			return -1;
		}
		zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
		if (UNEXPECTED(result.isUndef())) return -1;
		if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() did not return a result object");
			return -1;
		}
		return pt_result_value(Z_OBJ_P(result.raw()));
	}

	/*
	 * Mirrors bindingProbe(): sprintf("\0%s\0%d%s", name, id, armed ? "\0" : "")
	 * — a key no read can produce.
	 */
	static zv::Str bindingProbe(const WriteView &binding, bool armed)
	{
		zend_string *name = binding.nameStr;
		smart_str probe = { NULL, 0 };
		smart_str_appendc(&probe, '\0');
		smart_str_append(&probe, name);
		smart_str_appendc(&probe, '\0');
		smart_str_append_long(&probe, binding.id);
		if (armed) {
			smart_str_appendc(&probe, '\0');
		}
		smart_str_0(&probe);
		return zv::Str::adopt(probe.s);
	}

	/* Mirrors passBindingProbes(); $write NULL for null; UNDEF = pending exception. */
	zv::Val passBindingProbes(zv::Val next, zend_string *name, const WriteView *write, bool discard)
	{
		/* sprintf("\0%s\0", $name) */
		size_t prefixLen = ZSTR_LEN(name) + 2;
		zv::Str prefixStr = zv::Str::adopt(zend_string_alloc(prefixLen, 0));
		char *prefix = ZSTR_VAL(prefixStr.get());
		prefix[0] = '\0';
		memcpy(prefix + 1, ZSTR_VAL(name), ZSTR_LEN(name));
		prefix[prefixLen - 1] = '\0';
		prefix[prefixLen] = '\0';
		/* array_keys($next) snapshots the keys: the table walked is the one
		 * before any removal (a removal separates a shared table and marks a
		 * bucket of an unshared one — the walk sees every original key
		 * either way) */
		HashTable *keys = tableOf(next);
		for (auto entry : zv::TableRef(keys)) {
			zend_string *key = entry.stringKeyOrNull();
			if (key == NULL || ZSTR_LEN(key) < prefixLen || memcmp(ZSTR_VAL(key), prefix, prefixLen) != 0) continue;
			const char *id = ZSTR_VAL(key) + prefixLen;
			size_t idLen = ZSTR_LEN(key) - prefixLen;
			bool armed = idLen > 0 && id[idLen - 1] == '\0';
			zend_long bindingId = ZEND_STRTOL(id, NULL, 10);
			if (write != NULL) {
				HashTable *ownIds = innerTable(ownWriteIds, bindingId);
				if (ownIds != NULL && zend_hash_index_exists(ownIds, (zend_ulong) write->id)) continue;
			}
			if (armed && !discard) {
				zval *statement = zend_hash_index_find(tableOf(loopStatements), (zend_ulong) bindingId);
				if (statement != NULL) {
					setIndex(variableOverwritingLoops, bindingId, statement);
				} else {
					/* the twin reads an undefined offset here: a warning and null */
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, bindingId);
					zval nullValue;
					ZVAL_NULL(&nullValue);
					setIndex(variableOverwritingLoops, bindingId, &nullValue);
				}
			}
			if (write == NULL || write->offsetWrite) {
				// an alias or an offset write keeps the variable - the probe
				// carries on to the assignment that created it
				continue;
			}
			zv::Str held = zv::Str::copyOf(key);
			unsetKey(next, held.get());
		}

		return next;
	}

	/* Mirrors offsetKey(): (is_int($offset) ? 'i:' : 's:') . $offset */
	static zv::Str offsetKey(zval *offset)
	{
		smart_str key = { NULL, 0 };
		if (Z_TYPE_P(offset) == IS_LONG) {
			smart_str_appendl(&key, "i:", 2);
			smart_str_append_long(&key, Z_LVAL_P(offset));
		} else {
			smart_str_appendl(&key, "s:", 2);
			zend_string *str = zval_get_string(offset);
			smart_str_append(&key, str);
			zend_string_release(str);
		}
		smart_str_0(&key);
		return zv::Str::adopt(key.s);
	}

	/* $name . "\0" . $slot . "\0" . $targetId */
	static zv::Str accessKey(zend_string *name, zend_string *slotName, zend_long targetId)
	{
		smart_str key = { NULL, 0 };
		smart_str_append(&key, name);
		smart_str_appendc(&key, '\0');
		smart_str_append(&key, slotName);
		smart_str_appendc(&key, '\0');
		smart_str_append_long(&key, targetId);
		smart_str_0(&key);
		return zv::Str::adopt(key.s);
	}

	/* Mirrors compileAccesses(); false = pending exception. */
	[[nodiscard]] bool compileAccesses()
	{
		zend_string *containerSlot = zend_string_init(PT_LC("container"), 0);
		zend_string *unknownSlot = zend_string_init(PT_LC("unknown"), 0);
		bool ok = compileAccessesWith(containerSlot, unknownSlot);
		zend_string_release(containerSlot);
		zend_string_release(unknownSlot);
		return ok;
	}

	bool compileAccessesWith(zend_string *containerSlot, zend_string *unknownSlot)
	{
		for (auto nameEntry : zv::TableRef(tableOf(accesses))) {
			zend_string *name = nameEntry.stringKeyOrNull();
			zv::Ref accessList = nameEntry.value().deref();
			if (UNEXPECTED(name == NULL || !accessList.isArray())) continue;
			HashTable *accessTable = accessList.asArrayTable();
			/* $slots = ['container' => true, 'unknown' => true] + every offset */
			zv::Val slotSet = emptyArray();
			setTrue(slotSet, containerSlot);
			setTrue(slotSet, unknownSlot);
			for (auto entry : zv::TableRef(accessTable)) {
				zend_object *access = Z_OBJ_P(entry.value().deref().raw());
				zval *write = slot(access, slots.accessWrite, "write");
				if (UNEXPECTED(write == NULL)) return false;
				zv::Val offset;
				if (Z_TYPE_P(write) == IS_OBJECT) {
					WriteView view;
					if (UNEXPECTED(!view.load(write))) return false;
					offset = std::move(view.offset);
				} else {
					zval *accessOffset = slot(access, slots.accessOffset, "offset");
					if (UNEXPECTED(accessOffset == NULL)) return false;
					offset = zv::Val::copyOf(zv::Ref(accessOffset));
				}
				if (Z_TYPE_P(offset.raw()) == IS_NULL) continue;

				zv::Str key = offsetKey(offset.raw());
				setTrue(slotSet, key.get());
			}
			zv::Val keysBySlot = emptyArray();
			for (auto entry : zv::TableRef(accessTable)) {
				zend_object *access = Z_OBJ_P(entry.value().deref().raw());
				FlowKind kind = kindOfFlow(access, slots.accessKind);
				if (UNEXPECTED(EG(exception))) return false;
				if (kind != PT_VLR_READ && kind != PT_VLR_ESCAPE) continue;
				zval *container = slot(access, slots.accessContainer, "container");
				zval *accessOffset = slot(access, slots.accessOffset, "offset");
				zval *targetId = slot(access, slots.accessTargetId, "targetId");
				if (UNEXPECTED(container == NULL || accessOffset == NULL || targetId == NULL)) return false;
				zv::Val selected;
				if (zend_is_true(container)) {
					selected = emptyArray();
					setTrue(selected, containerSlot);
				} else if (Z_TYPE_P(accessOffset) != IS_NULL) {
					selected = emptyArray();
					setTrue(selected, containerSlot);
					zv::Str key = offsetKey(accessOffset);
					setTrue(selected, key.get());
				} else {
					selected = shareArray(slotSet);
				}
				zend_long targetIdValue = Z_TYPE_P(targetId) == IS_NULL ? 0 : zval_get_long(targetId);
				for (auto slotEntry : zv::TableRef(tableOf(selected))) {
					zend_string *slotName = slotEntry.stringKeyOrNull();
					if (slotName == NULL) continue;
					zv::Str key = accessKey(name, slotName, targetIdValue);
					zval *bySlot = innerSlotByKey(keysBySlot, slotName);
					Z_TRY_ADDREF_P(targetId);
					zend_hash_update(Z_ARRVAL_P(bySlot), key.get(), targetId);
					zval *byAccess = innerSlot(readKeys, (zend_long) access->handle);
					zval trueValue;
					ZVAL_TRUE(&trueValue);
					zend_hash_update(Z_ARRVAL_P(byAccess), key.get(), &trueValue);
					zval *byName = innerSlotByKey(nameKeys, name);
					zend_hash_update(Z_ARRVAL_P(byName), key.get(), &trueValue);
				}
			}
			// A dynamic observation sees every offset, including ones never named by a read.
			if (readsAllVariables) {
				for (auto slotEntry : zv::TableRef(tableOf(slotSet))) {
					zend_string *slotName = slotEntry.stringKeyOrNull();
					if (slotName == NULL) continue;
					zv::Str key = accessKey(name, slotName, 0);
					zval *bySlot = innerSlotByKey(keysBySlot, slotName);
					zval nullValue;
					ZVAL_NULL(&nullValue);
					zend_hash_update(Z_ARRVAL_P(bySlot), key.get(), &nullValue);
					setTrue(allReadKeys, key.get());
					zval *byName = innerSlotByKey(nameKeys, name);
					zval trueValue;
					ZVAL_TRUE(&trueValue);
					zend_hash_update(Z_ARRVAL_P(byName), key.get(), &trueValue);
				}
			}
			for (auto entry : zv::TableRef(accessTable)) {
				zend_object *access = Z_OBJ_P(entry.value().deref().raw());
				zval *write = slot(access, slots.accessWrite, "write");
				if (UNEXPECTED(write == NULL)) return false;
				if (Z_TYPE_P(write) != IS_OBJECT) continue;
				WriteView view;
				if (UNEXPECTED(!view.load(write))) return false;
				zend_long id = view.id;
				bool offsetIsNull = view.offsetIsNull;
				zv::Val selectedKeys;
				if (view.offsetWrite && !offsetIsNull) {
					zv::Str slotName = offsetKey(view.offset.raw());
					selectedKeys = emptyArray();
					HashTable *keys = innerTableByKey(keysBySlot, slotName.get());
					zval keysValue;
					if (keys != NULL) {
						ZVAL_ARR(&keysValue, keys);
						Z_ADDREF(keysValue);
					} else {
						ZVAL_EMPTY_ARRAY(&keysValue);
					}
					SEPARATE_ARRAY(selectedKeys.raw());
					zend_hash_update(Z_ARRVAL_P(selectedKeys.raw()), slotName.get(), &keysValue);
				} else {
					selectedKeys = shareArray(keysBySlot);
					if (view.offsetWrite) {
						unsetKey(selectedKeys, containerSlot);
					}
				}
				bool kills = !view.offsetWrite || (!offsetIsNull && view.replacesOffset);
				for (auto slotEntry : zv::TableRef(tableOf(selectedKeys))) {
					zv::Ref keys = slotEntry.value().deref();
					if (!keys.isArray()) continue;
					for (auto keyEntry : zv::TableRef(keys.asArrayTable())) {
						zend_string *key = keyEntry.stringKeyOrNull();
						if (key == NULL) continue;
						zval *observed = innerSlot(observedKeys, id);
						Z_TRY_ADDREF_P(keyEntry.value().raw());
						zend_hash_update(Z_ARRVAL_P(observed), key, keyEntry.value().raw());
						if (!kills) continue;

						zval *killed = innerSlot(killedKeys, id);
						zval trueValue;
						ZVAL_TRUE(&trueValue);
						zend_hash_update(Z_ARRVAL_P(killed), key, &trueValue);
					}
				}
			}
		}
		return true;
	}

	/* Mirrors observeWrite(). */
	void observeWrite(zend_long id, const zv::Val &next)
	{
		HashTable *observed = innerTable(observedKeys, id);
		if (observed == NULL) return;
		for (auto entry : zv::TableRef(observed)) {
			zend_string *key = entry.stringKeyOrNull();
			if (key == NULL || !isTrueAt(next, key)) continue;
			setTrueIndex(observedIds, id);
			zv::Ref targetId = entry.value().deref();
			if (targetId.isNull()) {
				setTrueIndex(readIds, id);
			} else {
				zval *dependents = innerSlot(dependencies, targetId.toLong());
				zval trueValue;
				ZVAL_TRUE(&trueValue);
				zend_hash_index_update(Z_ARRVAL_P(dependents), (zend_ulong) id, &trueValue);
			}
		}
	}

	/* Mirrors resolveDependencies(); false = pending exception. */
	[[nodiscard]] bool resolveDependencies()
	{
		std::vector<std::pair<zend_long, bool>> stack;
		for (auto entry : zv::TableRef(tableOf(readIds))) {
			stack.emplace_back((zend_long) entry.indexKey(), false);
		}
		for (auto entry : zv::TableRef(tableOf(inputSinks))) {
			stack.emplace_back((zend_long) entry.indexKey(), true);
		}
		for (auto entry : zv::TableRef(tableOf(writes))) {
			zv::Val name = writeName(entry.value().deref().raw());
			if (UNEXPECTED(name.isUndef())) return false;
			if (Z_TYPE_P(name.raw()) != IS_STRING || !isTrueAt(escapedNames, Z_STR_P(name.raw()))) continue;
			// a write to an aliased variable is observable through the alias,
			// so whatever flows into it is used; whether the write itself is
			// read stays with the flow-sensitive capture read
			stack.emplace_back((zend_long) entry.indexKey(), false);
			stack.emplace_back((zend_long) entry.indexKey(), true);
		}
		/* $visited["$id:inputs"] / ["$id:value"] as (id, inputs) pairs */
		zv::ScratchTable visited(16);
		while (!stack.empty()) {
			std::pair<zend_long, bool> top = stack.back();
			stack.pop_back();
			zend_long id = top.first;
			bool inputs = top.second;
			zend_ulong visitedKey = ((zend_ulong) id << 1) | (inputs ? 1 : 0);
			if (zend_hash_index_exists(visited.table(), visitedKey)) continue;
			zval marker;
			ZVAL_TRUE(&marker);
			zend_hash_index_add_new(visited.table(), visitedKey, &marker);
			HashTable *dependents = innerTable(dependencies, id);
			if (dependents != NULL) {
				for (auto entry : zv::TableRef(dependents)) {
					setTrueIndex(readIds, (zend_long) entry.indexKey());
					stack.emplace_back((zend_long) entry.indexKey(), false);
				}
			}
			HashTable *sources = innerTable(inputCopies, id);
			if (sources != NULL) {
				for (auto entry : zv::TableRef(sources)) {
					stack.emplace_back((zend_long) entry.indexKey(), true);
				}
			}
			if (!inputs) continue;
			HashTable *items = innerTable(literalItems, id);
			if (items != NULL) {
				for (auto entry : zv::TableRef(items)) {
					zend_long itemId;
					if (UNEXPECTED(!writeId(entry.value().deref().raw(), itemId))) return false;
					stack.emplace_back(itemId, true);
				}
			}
		}
		return true;
	}

	/* Mirrors resolveCoverage(). */
	void resolveCoverage()
	{
		std::vector<zend_long> stack;
		for (auto entry : zv::TableRef(tableOf(writes))) {
			zend_long id = (zend_long) entry.indexKey();
			if (isTrueAtIndex(observedIds, id) || isTrueAtIndex(readIds, id)) continue;

			stack.push_back(id);
		}
		zv::ScratchTable visited(16);
		while (!stack.empty()) {
			zend_long id = stack.back();
			stack.pop_back();
			if (zend_hash_index_exists(visited.table(), (zend_ulong) id)) continue;
			zval marker;
			ZVAL_TRUE(&marker);
			zend_hash_index_add_new(visited.table(), (zend_ulong) id, &marker);
			std::vector<zend_long> sources;
			HashTable *dependents = innerTable(dependencies, id);
			if (dependents != NULL) {
				for (auto entry : zv::TableRef(dependents)) {
					sources.push_back((zend_long) entry.indexKey());
				}
			}
			HashTable *copies = innerTable(inputCopies, id);
			if (copies != NULL) {
				for (auto entry : zv::TableRef(copies)) {
					// the inputs of $copied flow into $id as well
					HashTable *copiedDependents = innerTable(dependencies, (zend_long) entry.indexKey());
					if (copiedDependents == NULL) continue;
					for (auto dependent : zv::TableRef(copiedDependents)) {
						sources.push_back((zend_long) dependent.indexKey());
					}
				}
			}
			for (zend_long source : sources) {
				setTrueIndex(coveredIds, source);
				stack.push_back(source);
			}
		}
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableLivenessResolver;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_variable_liveness_resolver()
{
	reg::Class cls("PHPStan\\Analyser\\VariableLivenessResolver");
	ptdecl::VariableLivenessResolver::declareClass(cls);

	cls.method(sigs::resolve, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *function, *flow;
		if (!zp::parse<zp::Obj, zp::ObjOrNull>(execute_data, function, flow)) RETURN_THROWS();
		zv::Val result = VariableLivenessResolver::resolve(function, flow);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_variable_liveness_resolver);
}

/* }}} */
