package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlinx.serialization.Serializable

@Serializable
data class Position(override val x: Int, override val y: Int) : PositionInterface {
    fun toKey(): String {
        return "(${x},${y})"
    }

    companion object {
        val POSITION_KEY_REGEX = Regex("^\\((\\d+),(\\d+)\\)$")
        fun of (str: String): Position {
            val matches = POSITION_KEY_REGEX.matchEntire(str)
            if (matches != null) {
                return Position(
                    x = matches.groupValues[1].toInt(),
                    y = matches.groupValues[2].toInt(),
                )
            }

            throw IllegalArgumentException("Cannot convert invalid key: $str")
        }
    }
}
