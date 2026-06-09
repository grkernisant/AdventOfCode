package com.webnologies.grkernisant.aoc.aoc2024.day20

data class Position(override val x: Int, override val y: Int) : PositionInterface {
    fun toPositionKey(start: String = "", end: String = ""): String =
        "${start}$x,$y${end}"
}
