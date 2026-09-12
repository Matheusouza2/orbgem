const Tabs = {
    "base": "flex flex-col gap-0",
    "tablist": {
        "base": "flex text-center",
        "variant": {
            "default": "flex-wrap border-b border-chicago-100 dark:border-chicago-100",
            "underline": "grid w-full grid-flow-col border-0 border-chicago-100 dark:border-chicago-200",
            "pills": "flex-wrap space-x-2 text-sm font-medium text-gray-500 dark:text-gray-400",
            "fullWidth": "grid w-full grid-flow-col divide-x divide-gray-200 rounded-none text-sm font-medium shadow dark:divide-transparent dark:text-gray-400"
        },
        "tabitem": {
            "base": "flex items-center justify-center rounded-t-lg p-4 text-sm font-medium first:ml-0 focus:outline-none disabled:cursor-not-allowed disabled:text-gray-400 disabled:dark:text-gray-500",
            "variant": {
                "default": {
                    "base": "rounded-t-lg",
                    "active": {
                        "on": "bg-white w-br-10 border-t border-l border-r border-chicago-200 text-primary-600 dark:bg-white dark:text-primary-500",
                        "off": "text-gray-500 hover:bg-gray-50 hover:text-gray-600 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-300"
                    }
                },
                "underline": {
                    "base": "rounded-t-lg",
                    "active": {
                        "on": "rounded-t-lg border-b-2 border-sunglow-600 text-sunglow-600 dark:border-sunglow-500 dark:text-sunglow-500",
                        "off": "text-gray-500 hover:border-chicago-400 hover:text-gray-600 dark:text-chicago-600 dark:hover:text-chicago-400"
                    }
                },
                "pills": {
                    "base": "",
                    "active": {
                        "on": "rounded-lg bg-primary-600 text-white",
                        "off": "rounded-lg hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                    }
                },
                "fullWidth": {
                    "base": "ml-0 flex w-full rounded-none first:ml-0",
                    "active": {
                        "on": "dark:bg-transparent border-b rounded-t-lg border-b-2 border-sunglow-600 text-sunglow-600 dark:border-sunglow-500 dark:text-sunglow-500",
                        "off": "dark:bg-transparent text-gray-500 hover:border-chicago-400 hover:text-gray-600 dark:text-chicago-600 dark:hover:text-chicago-400 dark:hover:bg-transparent"
                    }
                }
            },
            "icon": "mr-2 h-5 w-5"
        }
    },
    "tabitemcontainer": {
        "base": "",
        "variant": {
            "default": "",
            "underline": "",
            "pills": "",
            "fullWidth": ""
        }
    },
    "tabpanel": "py-0"
}

export default Tabs;
