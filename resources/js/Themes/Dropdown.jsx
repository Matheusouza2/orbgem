const Dropdown = {
    "arrowIcon": "ml-2 h-4 w-4",
    "content": "py-1 focus:outline-none",
    "floating": {
        "animation": "transition-opacity",
        "arrow": {
            "base": "absolute z-10 h-2 w-2 rotate-45",
            "style": {
                "dark": "bg-gray-700 dark:bg-gray-700",
                "light": "bg-white",
                "auto": "bg-gray-700 dark:bg-gray-700"
            },
            "placement": "-4px"
        },
        "base": "z-10 w-64 divide-y divide-gray-100 rounded-lg shadow focus:outline-none mt-4",
        "content": "py-1 text-sm text-gray-200 dark:text-gray-200",
        "divider": "my-1 mx-3 h-px bg-chicago-400 dark:bg-chicago-400",
        "header": "block px-4 py-2 text-sm text-chicago-100 dark:text-chicago-100",
        "hidden": "invisible opacity-0",
        "item": {
            "container": "",
            "base": "flex w-full cursor-pointer items-center justify-start px-4 py-2 text-sm text-chicago-100 hover:bg-gray-600 hover:text-white focus:bg-gray-600 focus:text-white focus:outline-none dark:text-chicago-100 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:bg-gray-600 dark:focus:text-white",
            "icon": "mr-2 h-4 w-4"
        },
        "style": {
            "dark": "bg-gray-900 text-white dark:bg-gray-700",
            "light": "border border-gray-200 bg-white text-gray-900",
            "auto": "border border-chicago-200 bg-chicago-700 text-chicago-100 dark:border-none dark:bg-chicago-700 dark:text-chicago-100"
        },
        "target": "w-fit"
    },
    "inlineWrapper": "flex items-center"
}

export default Dropdown;
