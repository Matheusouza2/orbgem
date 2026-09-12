const Table = {
    "root": {
        "base": "w-full text-left text-sm text-chicago-600 dark:text-chicago-600",
        "shadow": "absolute left-0 top-0 -z-10 h-full w-full rounded-lg bg-black drop-shadow-md dark:bg-black",
        "wrapper": "relative"
    },
    "body": {
        "base": "group/body",
        "cell": {
            "base": "px-6 py-4 group-first/body:group-first/row:first:rounded-tl-lg group-first/body:group-first/row:last:rounded-tr-lg group-last/body:group-last/row:first:rounded-bl-lg group-last/body:group-last/row:last:rounded-br-lg"
        }
    },
    "head": {
        "base": "group/head text-xs uppercase text-chicago-200 dark:text-chicago-200",
        "cell": {
            "base": "bg-chicago-800 px-6 py-3 group-first/head:first:rounded-tl-lg group-first/head:last:rounded-tr-lg dark:bg-chicago-800"
        }
    },
    "row": {
        "base": "group/row border-b",
        "hovered": "hover:bg-chicago-600/10 dark:hover:bg-chicago-600/10",
        "striped": "odd:bg-chicago-900/30 even:bg-chicago-900/20 odd:dark:bg-chicago-900/30 even:dark:bg-chicago-900/20"
    }
}

export default Table;
