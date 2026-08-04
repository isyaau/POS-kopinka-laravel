import { provide, inject } from 'vue'

export const SHEET_INJECTION_KEY = Symbol('sheet')

export function useSheetContext() {
    return inject(SHEET_INJECTION_KEY)
}

export function provideSheetContext(context) {
    provide(SHEET_INJECTION_KEY, context)
    return context
}
