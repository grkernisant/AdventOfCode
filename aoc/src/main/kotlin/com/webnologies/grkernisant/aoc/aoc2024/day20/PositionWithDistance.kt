package com.webnologies.grkernisant.aoc.aoc2024.day20

data class PositionWithDistance (
    override val x: Int,
    override val y: Int,
    var distance: Int? = null
) : PositionInterface {
    fun toPositionKey(): String = "$x,$y"
    fun toDistanceKey(): String = "$x,$y,$distance"

    companion object {
        fun of(str: String): PositionWithDistance {
            val coords = str.trim()
                .split(",")
                .filter { it.isNotEmpty() }
                .map { it.trim().toInt() }
            return PositionWithDistance(coords[0], coords[1], coords[2])
        }
    }
}
