package com.webnologies.grkernisant.aoc.aoc2024.day18

import kotlin.properties.Delegates

class Parser : MemorySpaceInterface {
    override var bytes: List<Coords>
    override var cols by Delegates.notNull<Int>()
    override var grid: List<List<MemoryTile>>
    override var rows by Delegates.notNull<Int>()

    constructor(input: List<String>, env: Map<String, String>? = null) {
        this.cols = env?.get("COLS")?.toInt() ?: 7
        this.rows = env?.get("ROWS")?.toInt() ?: 7

        this.bytes = input.mapNotNull { line ->
            val matches = Coords.COORDS_REGEX.matchEntire(line.trim())
            if (matches != null) {
                return@mapNotNull Coords(
                    x = matches.groupValues[1].toInt(),
                    y = matches.groupValues[2].toInt()
                )
            }

            null
        }

        this.grid = List(this.rows) {
            List(this.cols) { MemoryTile(MemoryType.FREE) }
        }
    }
}
