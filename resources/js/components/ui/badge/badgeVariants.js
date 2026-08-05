import { cva } from 'class-variance-authority'

export const badgeVariants = cva(
    'inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] transition-[color,box-shadow] overflow-hidden',
    {
        variants: {
            variant: {
                default: 'border-transparent bg-primary text-primary-foreground hover:bg-primary-hover',
                secondary: 'border-transparent bg-secondary text-secondary-foreground',
                destructive: 'border-transparent bg-destructive text-destructive-foreground',
                outline: 'text-foreground',
                success: 'border-transparent bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                warning: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                inactive: 'border-transparent bg-muted text-muted-foreground',
                purna: 'border-transparent bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
)
