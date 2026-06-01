package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlin.math.abs

data class PositionWithDirection(
    override val x: Int,
    override val y: Int,
    val d: Direction,
    val score: Int
) : PositionInterface {
    fun toKey(): String {
        return "(${x},${y})"
    }

    fun toDirectionKey(): String {
        return "(${x},${y})$d"
    }

    fun toScoreKey(): String {
        return "(${x},${y},${score})$d"
    }

    companion object {
        val SCORE_KEY_REGEX = Regex("^\\((\\d+),(\\d+),(\\d+)\\)(LEFT|UP|RIGHT|DOWN)$")
        fun of (str: String): PositionWithDirection {
            val matches = SCORE_KEY_REGEX.matchEntire(str)
            if (matches != null) {
                return PositionWithDirection(
                    x = matches.groupValues[1].toInt(),
                    y = matches.groupValues[2].toInt(),
                    score = matches.groupValues[3].toInt(),
                    d = Direction.valueOf(matches.groupValues[4])
                )
            }

            throw IllegalArgumentException("Cannot convert invalid key: $str")
        }
    }
}

fun Direction.getPositionWithDirection(p: PositionWithDirection, delta: Int = 0): PositionWithDirection {
    val nextDirection = this.getNextDirection(delta)
    val deltaScore = when(abs(delta) % 4) {
        0 -> 1
        2 -> 2001
        else -> 1001
    }

    return PositionWithDirection(
        x = p.x + nextDirection.offset.first,
        y = p.y + nextDirection.offset.second,
        d = nextDirection,
        score = p.score + deltaScore
    )
}