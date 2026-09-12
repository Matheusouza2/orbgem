const Sidebar = {
    "root": {
        "base": "h-screen fixed top-14 left-0 z-0 w-56 shadow-lg transition-transform",
        "collapsed": {
            "on": "w-16",
            "off": "w-56"
        },
        "inner": "sidebar-scroll h-screen overflow-y-auto pb-20 rounded-none overflow-x-hidden bg-chicago-800 px-3 py-4 dark:bg-chicago-800"
    },
    "collapse": {
        "button": "group flex w-full items-center rounded-lg p-2 text-xs font-normal text-slate-300 transition duration-75 hover:bg-gray-700 dark:text-slate-300 dark:hover:bg-gray-700",
        "icon": {
            "base": "h-3 w-3 text-gray-400 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-slate-300",
            "open": {
                "off": "",
                "on": "text-gray-900"
            }
        },
        "label": {
            "base": "ml-3 flex-1 whitespace-nowrap text-left",
            "title": "sr-only",
            "icon": {
                "base": "h-4 w-4 transition delay-0 ease-in-out",
                "open": {
                    "on": "rotate-180",
                    "off": ""
                }
            }
        },
        "list": "space-y-2 py-2"
    },
    "cta": {
        "base": "mt-6 rounded-lg bg-gray-700 p-4 dark:bg-gray-700",
        "color": {
            "blue": "bg-cyan-50 dark:bg-cyan-900",
            "dark": "bg-dark-50 dark:bg-dark-900",
            "failure": "bg-red-50 dark:bg-red-900",
            "gray": "bg-gray-50 dark:bg-gray-900",
            "green": "bg-green-50 dark:bg-green-900",
            "light": "bg-light-50 dark:bg-light-900",
            "red": "bg-red-50 dark:bg-red-900",
            "purple": "bg-purple-50 dark:bg-purple-900",
            "success": "bg-green-50 dark:bg-green-900",
            "yellow": "bg-yellow-50 dark:bg-yellow-900",
            "warning": "bg-yellow-50 dark:bg-yellow-900"
        }
    },
    "item": {
        "base": "flex items-center justify-center rounded-lg p-2 text-xs font-normal text-slate-300 hover:bg-gray-700 dark:text-slate-300 dark:hover:bg-gray-700",
        "active": "bg-transparent dark:bg-transparent text-sunglow-300 dark:text-sunglow-300",
        "collapsed": {
            "insideCollapse": "group w-full pl-8 transition duration-75",
            "noIcon": "font-bold"
        },
        "content": {
            "base": "flex-1 whitespace-nowrap px-3"
        },
        "icon": {
            "base": "h-3 w-3 shrink-0 text-gray-400 transition duration-75 group-hover:text-slate-300 dark:text-gray-400 dark:group-hover:text-slate-300",
            "active": "text-sunglow-300 dark:text-sunglow-300"
        },
        "label": "",
        "listItem": ""
    },
    "items": {
        "base": ""
    },
    "itemGroup": {
        "base": "mt-4 space-y-0 border-t border-slate-400 pt-4 first:mt-0 first:border-t-0 first:pt-0 dark:border-slate-400"
    },
    "logo": {
        "base": "mb-5 flex items-center pl-2.5",
        "collapsed": {
            "on": "hidden",
            "off": "self-center whitespace-nowrap text-slate-300 text-xl font-semibold dark:text-slate-300"
        },
        "img": "mr-3 h-6 sm:h-7"
    }
}

export default Sidebar;
