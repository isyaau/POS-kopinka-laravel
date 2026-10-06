import { onBeforeUnmount, onMounted, ref, unref } from 'vue'

let uid = 0

export const hotkeyRegistry = ref([])

const EDITABLE_TAGS = new Set(['INPUT', 'TEXTAREA', 'SELECT'])

export const isMac =
    typeof navigator !== 'undefined' &&
    /mac|iphone|ipad|ipod/i.test(navigator.platform || navigator.userAgent || '')

const KEY_LABELS = {
    escape: 'Esc',
    enter: 'Enter',
    arrowup: '↑',
    arrowdown: '↓',
    arrowleft: '←',
    arrowright: '→',
    delete: 'Del',
    backspace: '⌫',
    tab: 'Tab',
    ' ': 'Space',
    ctrl: 'Ctrl',
    shift: 'Shift',
    alt: isMac ? '⌥' : 'Alt',
    meta: isMac ? '⌘' : 'Win',
    mod: isMac ? '⌘' : 'Ctrl',
}

function labelFor(token) {
    const key = String(token).toLowerCase()
    if (KEY_LABELS[key]) return KEY_LABELS[key]
    if (key.length === 1) return key.toUpperCase()
    return String(token)
}

export function displayKeys(binding) {
    const tokens = []
    if (binding.mod) tokens.push('mod')
    if (binding.ctrl) tokens.push('ctrl')
    if (binding.meta) tokens.push('meta')
    if (binding.alt) tokens.push('alt')
    if (binding.shift) tokens.push('shift')
    tokens.push(binding.key)
    return tokens.map(labelFor)
}

function isEditableTarget(target) {
    if (!target || typeof target !== 'object') return false
    if (target.isContentEditable) return true
    return EDITABLE_TAGS.has(target.tagName)
}

function normalize(key) {
    return String(key ?? '').toLowerCase()
}

function matches(binding, event) {
    const want = normalize(binding.key)
    if (!want) return false

    const key = normalize(event.key)
    const shifted = event.shiftKey
    const code = normalize(event.code).replace(/^key/, '').replace(/^digit/, '')
    const codeKey = code === 'slash' ? '/' : code

    if (key !== want && codeKey !== want) return false

    const hasCtrl = event.ctrlKey
    const hasMeta = event.metaKey

    if (binding.mod) {
        if (!hasCtrl && !hasMeta) return false
    } else {
        if (hasCtrl !== !!binding.ctrl) return false
        if (hasMeta !== !!binding.meta) return false
    }

    if (event.altKey !== !!binding.alt) return false

    if (binding.shift) {
        if (!shifted) return false
    } else if (/^[a-z0-9]$/.test(want) && shifted) {
        return false
    }

    return true
}

/**
 * Register keyboard shortcuts for the current component.
 *
 * useHotkeys([
 *   { key: '/', description: 'Fokus pencarian', handler: focusSearch },
 *   { key: 'i', ctrl: true, description: 'Import', handler: openImport },
 *   { key: 'Enter', when: () => activeRow.value >= 0, handler: openDetail },
 * ])
 *
 * Options:
 *   enabled: boolean | () => boolean   // disable all bindings (e.g. while a modal is open)
 */
export function useHotkeys(bindings, options = {}) {
    const entries = []

    const resolve = (value) => (typeof value === 'function' ? !!value() : !!unref(value))

    const enabled = () => (options.enabled === undefined ? true : resolve(options.enabled))

    const isSatisfied = (binding) => {
        if (!binding.when) return true
        return typeof binding.when === 'function' ? !!binding.when() : !!unref(binding.when)
    }

    const onKeyDown = (event) => {
        if (!enabled()) return

        const editable = isEditableTarget(event.target)

        for (const binding of bindings) {
            if (editable && !binding.allowInInput) continue
            if (!isSatisfied(binding)) continue
            if (!matches(binding, event)) continue

            if (binding.preventDefault !== false) event.preventDefault()
            binding.handler(event)
            return
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', onKeyDown, true)

        for (const binding of bindings) {
            if (!binding.description) continue
            entries.push({
                id: ++uid,
                keys: displayKeys(binding),
                description: binding.description,
                group: binding.group || 'Umum',
            })
        }
        if (entries.length) hotkeyRegistry.value.push(...entries)
    })

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', onKeyDown, true)
        if (entries.length) {
            const ids = new Set(entries.map((e) => e.id))
            hotkeyRegistry.value = hotkeyRegistry.value.filter((e) => !ids.has(e.id))
        }
    })
}
