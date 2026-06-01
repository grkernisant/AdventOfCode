package com.webnologies.grkernisant.aoc.aoc2024.day16

data class Position(override val x: Int, override val y: Int) : PositionInterface {
    fun toKey(): String {
        return "(${x},${y})"
    }
}
