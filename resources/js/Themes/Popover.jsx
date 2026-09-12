const Popover = {
    "base": "absolute z-20 inline-block w-max max-w-[100vw] rounded-lg border border-gray-200 bg-white shadow-sm outline-none dark:border-chicago-200 dark:bg-chicago-100",
    "inner": "relative",
    "content": "z-10 overflow-hidden rounded-[7px] p-2",
    "arrow": {
        "base": "absolute z-0 h-2 w-2 rotate-45 border border-gray-200 bg-white mix-blend-lighten dark:border-gray-600 dark:bg-gray-800 dark:mix-blend-color",
        "placement": "-4px"
    }
}

export default Popover;