const Tooltip = {
    "target": "w-fit",
    "animation": "transition-opacity",
    "arrow": {
        "base": "absolute z-10 h-2 w-2 rotate-45 border-l border-b border-chicago-500",
        "style": {
            "dark": "bg-sunglow-300 dark:bg-sunglow-300",
            "light": "bg-sunglow-300 dark:bg-sunglow-300",
            "auto": "bg-sunglow-300 dark:bg-sunglow-300"
        },
        "placement": "-4px"
    },
    "base": "absolute z-10 inline-block rounded-lg px-3 py-2 text-sm font-medium shadow-sm",
    "hidden": "invisible opacity-0",
    "style": {
        "dark": "border-2 bg-sunglow-300 text-chicago-800 font-bold border-chicago-500 dark:bg-sunglow-300 dark:border-chicago-500",
        "light": "border-2 bg-sunglow-300 text-chicago-800 font-bold border-chicago-500 dark:bg-sunglow-300 dark:border-chicago-500",
        "auto": "border-2 bg-sunglow-300 text-chicago-800 font-bold border-chicago-500 dark:bg-sunglow-300 dark:border-chicago-500"
    },
    "content": "relative z-20"
};

export default Tooltip;
