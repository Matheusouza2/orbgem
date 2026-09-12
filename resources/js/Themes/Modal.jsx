const Modal = {
    "root": {
        "base": "fixed inset-x-0 top-0 z-50 h-screen overflow-y-auto overflow-x-hidden md:inset-0 md:h-full",
        "show": {
            "on": "flex bg-chicago-900/80 dark:bg-chicago-900/80",
            "off": "hidden"
        },
        "sizes": {
            "sm": "max-w-sm",
            "md": "max-w-md",
            "lg": "max-w-lg",
            "xl": "max-w-xl",
            "2xl": "max-w-2xl",
            "3xl": "max-w-3xl",
            "4xl": "max-w-4xl",
            "5xl": "max-w-5xl",
            "6xl": "max-w-6xl",
            "7xl": "max-w-7xl"
        },
        "positions": {
            "top-left": "items-start justify-start",
            "top-center": "items-start justify-center",
            "top-right": "items-start justify-end",
            "center-left": "items-center justify-start",
            "center": "items-center justify-center",
            "center-right": "items-center justify-end",
            "bottom-right": "items-end justify-end",
            "bottom-center": "items-end justify-center",
            "bottom-left": "items-end justify-start"
        }
    },
    "content": {
        "base": "relative h-full w-full p-4 md:h-auto",
        "inner": "relative flex max-h-[90dvh] flex-col rounded-lg bg-chicago-50 shadow dark:bg-chicago-50"
    },
    "body": {
        "base": "flex-1 overflow-auto p-6",
        "popup": "pt-0"
    },
    "header": {
        "base": "flex items-start justify-between rounded-t border-b p-5 border-sunglow-300 bg-sunglow-300 dark:border-sunglow-300 dark:bg-sunglow-300",
        "popup": "border-b-0 p-2",
        "title": "text-xl font-bold text-chicago-800 dark:text-chicago-800",
        "close": {
            "base": "ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-red-500 hover:bg-chicago-600 hover:text-red-900 dark:hover:bg-chicago-600 dark:hover:text-red-900",
            "icon": "h-5 w-5"
        }
    },
    "footer": {
        "base": "flex justify-end items-center space-x-2 rounded-b border-chicago-200 p-6 dark:border-chicago-200",
        "popup": "border-t"
    }
}

export default Modal;
