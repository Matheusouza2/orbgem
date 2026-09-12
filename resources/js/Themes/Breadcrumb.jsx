const Breadcrumb = {
    "root": {
        "base": "",
        "list": "flex items-center"
    },
    "item": {
        "base": "group flex items-center",
        "chevron": "mx-1 h-4 w-4 text-chicago-400 group-first:hidden md:mx-2",
        "href": {
            "off": "flex items-center text-sm font-medium text-chicago-800 dark:text-chicago-800",
            "on": "flex items-center text-sm font-medium text-chicago-400 hover:text-chicago-200 dark:text-chicago-400 dark:hover:text-chicago-200"
        },
        "icon": "mr-2 h-4 w-4"
    }
}

export default Breadcrumb;
