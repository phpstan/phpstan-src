/*
 * Allocation census: with -DPT_ALLOC_COUNT, support.h routes the extension's
 * own object_init_ex(), zend_new_array() and zend_array_dup() calls here, and
 * the counts by class and by call site are written at exit to the file named
 * by $PT_ALLOC_COUNT_OUT (stderr otherwise). An ordinary build compiles this
 * file to nothing.
 *
 * Escape census: each object created through object_init_ex() gets a copy of
 * its handler table whose member, method, cast and compare handlers note
 * when they run in a userland frame — the object reached PHP code. When the
 * object is freed (or at exit, while still alive), its class is counted under
 * "X created/escaped/live" with the handler that first saw it from PHP. An
 * object PHP only passes along without touching is not seen.
 */

#ifdef PT_ALLOC_COUNT

#define PT_ALLOC_COUNT_IMPL
#include "support.h"

#include <algorithm>
#include <cstdio>
#include <cstdlib>
#include <dlfcn.h>
#include <map>
#include <string>
#include <unordered_map>
#include <vector>

namespace {

std::map<std::string, unsigned long> *counts;

void dump()
{
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &entry : *counts) rows.emplace_back(entry.second, entry.first);
	std::sort(rows.rbegin(), rows.rend());
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	for (const auto &row : rows) fprintf(out, "%10lu %s\n", row.first, row.second.c_str());
	if (out != stderr) fclose(out);
}

void hit(const std::string &key)
{
	if (counts == nullptr) {
		counts = new std::map<std::string, unsigned long>();
		atexit(dump);
	}
	(*counts)[key]++;
}

std::string site(const char *file, int line)
{
	const char *slash = strrchr(file, '/');
	return std::string(slash != nullptr ? slash + 1 : file) + ":" + std::to_string(line);
}

/* the calling function, as an offset into the shared object for atos/addr2line */
std::string caller(void *address)
{
	static char *base = nullptr;
	if (base == nullptr) {
		Dl_info info;
		if (dladdr((void *) &caller, &info) == 0) return "?";
		base = (char *) info.dli_fbase;
	}
	char buffer[32];
	snprintf(buffer, sizeof(buffer), "@%lx", (unsigned long) ((char *) address - base));
	return buffer;
}

struct Census
{
	const zend_object_handlers *original;
	const char *escapedVia;
	// the class name, copied: objects still alive at exit outlive the engine's memory
	const std::string *className;
};

std::unordered_map<zend_class_entry *, std::string> *classNames;

std::unordered_map<zend_object *, Census> *tracked;
std::map<const zend_object_handlers *, zend_object_handlers *> *clones;
// a clone made by the original clone_obj copies the wrapped table: map it back
std::unordered_map<const zend_object_handlers *, const zend_object_handlers *> *originals;

struct ClassCounts
{
	unsigned long created = 0;
	unsigned long escaped = 0;
	unsigned long live = 0;
	std::map<std::string, unsigned long> via;
};

std::map<std::string, ClassCounts> *classes;

void tally(const Census &census, bool live)
{
	ClassCounts &counts = (*classes)[*census.className];
	counts.created++;
	if (live) counts.live++;
	if (census.escapedVia != nullptr) {
		counts.escaped++;
		counts.via[census.escapedVia]++;
	}
}

void dumpEscapes()
{
	for (auto &entry : *tracked) tally(entry.second, true);
	std::vector<std::pair<unsigned long, std::string>> rows;
	for (auto &entry : *classes) {
		std::string via;
		for (auto &channel : entry.second.via) via += " " + channel.first + "=" + std::to_string(channel.second);
		rows.emplace_back(entry.second.created, "X " + entry.first + " escaped=" + std::to_string(entry.second.escaped) + " live=" + std::to_string(entry.second.live) + via);
	}
	std::sort(rows.rbegin(), rows.rend());
	const char *path = getenv("PT_ALLOC_COUNT_OUT");
	FILE *out = path != nullptr ? fopen(path, "a") : stderr;
	for (const auto &row : rows) fprintf(out, "%10lu %s\n", row.first, row.second.c_str());
	if (out != stderr) fclose(out);
}

bool fromUserland()
{
	zend_execute_data *frame = EG(current_execute_data);
	return frame != nullptr && frame->func != nullptr && ZEND_USER_CODE(frame->func->type);
}

const zend_object_handlers *originalOf(zend_object *obj)
{
	auto found = tracked->find(obj);
	if (found != tracked->end()) return found->second.original;
	auto original = originals->find(obj->handlers);
	return original != originals->end() ? original->second : obj->handlers;
}

void noteEscape(zend_object *obj, const char *via)
{
	if (!fromUserland()) return;
	auto found = tracked->find(obj);
	if (found != tracked->end() && found->second.escapedVia == nullptr) found->second.escapedVia = via;
}

void censusFree(zend_object *obj)
{
	auto found = tracked->find(obj);
	if (found == tracked->end()) {
		const zend_object_handlers *original = originalOf(obj);
		obj->handlers = original;
		original->free_obj(obj);
		return;
	}
	Census census = found->second;
	tracked->erase(found);
	tally(census, false);
	obj->handlers = census.original;
	census.original->free_obj(obj);
}

zval *censusReadProperty(zend_object *obj, zend_string *name, int type, void **cache, zval *rv)
{
	noteEscape(obj, "read_property");
	return originalOf(obj)->read_property(obj, name, type, cache, rv);
}

zval *censusWriteProperty(zend_object *obj, zend_string *name, zval *value, void **cache)
{
	noteEscape(obj, "write_property");
	return originalOf(obj)->write_property(obj, name, value, cache);
}

int censusHasProperty(zend_object *obj, zend_string *name, int type, void **cache)
{
	noteEscape(obj, "has_property");
	return originalOf(obj)->has_property(obj, name, type, cache);
}

zval *censusGetPropertyPtrPtr(zend_object *obj, zend_string *name, int type, void **cache)
{
	noteEscape(obj, "get_property_ptr_ptr");
	return originalOf(obj)->get_property_ptr_ptr(obj, name, type, cache);
}

HashTable *censusGetProperties(zend_object *obj)
{
	noteEscape(obj, "get_properties");
	return originalOf(obj)->get_properties(obj);
}

zend_function *censusGetMethod(zend_object **obj, zend_string *method, const zval *key)
{
	zend_object *object = *obj;
	noteEscape(object, "get_method");
	return originalOf(object)->get_method(obj, method, key);
}

zend_result censusCastObject(zend_object *obj, zval *result, int type)
{
	noteEscape(obj, "cast_object");
	return originalOf(obj)->cast_object(obj, result, type);
}

int censusCompare(zval *a, zval *b)
{
	zend_object *obj = Z_TYPE_P(a) == IS_OBJECT ? Z_OBJ_P(a) : Z_OBJ_P(b);
	noteEscape(obj, "compare");
	if (Z_TYPE_P(a) == IS_OBJECT) noteEscape(Z_OBJ_P(a), "compare");
	if (Z_TYPE_P(b) == IS_OBJECT) noteEscape(Z_OBJ_P(b), "compare");
	return originalOf(obj)->compare(a, b);
}

void track(zend_object *obj);

zend_object *censusCloneObj(zend_object *obj)
{
	noteEscape(obj, "clone_obj");
	zend_object *clone = originalOf(obj)->clone_obj(obj);
	if (clone != nullptr) {
		auto original = originals->find(clone->handlers);
		if (original != originals->end()) clone->handlers = original->second;
		track(clone);
	}
	return clone;
}

void track(zend_object *obj)
{
	if (tracked == nullptr) {
		tracked = new std::unordered_map<zend_object *, Census>();
		clones = new std::map<const zend_object_handlers *, zend_object_handlers *>();
		classes = new std::map<std::string, ClassCounts>();
		originals = new std::unordered_map<const zend_object_handlers *, const zend_object_handlers *>();
		classNames = new std::unordered_map<zend_class_entry *, std::string>();
		atexit(dumpEscapes);
	}
	auto back = originals->find(obj->handlers);
	if (back != originals->end()) obj->handlers = back->second;
	const zend_object_handlers *original = obj->handlers;
	zend_object_handlers *&clone = (*clones)[original];
	if (clone == nullptr) {
		clone = new zend_object_handlers(*original);
		clone->free_obj = censusFree;
		clone->read_property = censusReadProperty;
		clone->write_property = censusWriteProperty;
		clone->has_property = censusHasProperty;
		clone->get_property_ptr_ptr = censusGetPropertyPtrPtr;
		clone->get_properties = censusGetProperties;
		clone->get_method = censusGetMethod;
		clone->cast_object = censusCastObject;
		clone->compare = censusCompare;
		if (original->clone_obj != nullptr) clone->clone_obj = censusCloneObj;
		(*originals)[clone] = original;
	}
	auto name = classNames->find(obj->ce);
	if (name == classNames->end()) name = classNames->emplace(obj->ce, ZSTR_VAL(obj->ce->name)).first;
	(*tracked)[obj] = Census{original, nullptr, &name->second};
	obj->handlers = clone;
}

} // namespace

const zend_object_handlers *pt_census_original_handlers(zend_object *obj)
{
	return tracked != nullptr ? originalOf(obj) : obj->handlers;
}

zend_result pt_count_object_init_ex(zval *arg, zend_class_entry *ce, const char *file, int line)
{
	hit(std::string("O ") + ZSTR_VAL(ce->name));
	hit(std::string("o ") + site(file, line) + " " + ZSTR_VAL(ce->name));
	hit(std::string("c ") + site(file, line) + " " + ZSTR_VAL(ce->name) + " " + caller(__builtin_return_address(0)));
	zend_result result = object_init_ex(arg, ce);
	if (result == SUCCESS) track(Z_OBJ_P(arg));
	return result;
}

HashTable *pt_count_new_array(uint32_t size, const char *file, int line)
{
	hit(std::string("A ") + site(file, line));
	hit(std::string("a ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_new_array(size);
}

HashTable *pt_count_array_dup(const HashTable *source, const char *file, int line)
{
	const char *kind = zend_hash_num_elements(source) == 0 ? "E " : "D ";
	hit(kind + site(file, line));
	hit(std::string(kind[0] == 'E' ? "e " : "d ") + site(file, line) + " " + caller(__builtin_return_address(0)));
	return zend_array_dup(source);
}

#endif
