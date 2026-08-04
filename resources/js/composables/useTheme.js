import { ref, watchEffect } from 'vue'

const theme = ref(localStorage.getItem('theme') || 'light')

watchEffect(() => {
    const root = document.documentElement
    root.classList.toggle('dark', theme.value === 'dark')
    localStorage.setItem('theme', theme.value)
})

export function useTheme() {
    const toggleTheme = () => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark'
    }
    const setTheme = (t) => {
        theme.value = t
    }
    return { theme, toggleTheme, setTheme }
}
