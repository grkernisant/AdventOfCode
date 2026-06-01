package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlin.math.abs

data class PositionWithDirection(
    override val x: Int,
    override val y: Int,
    val d: Direction,
    val score: Int? = null,
    val prev: String = "",
) : PositionInterface {
    fun safeScore(): Int {
        return score ?: 0
    }

    fun toKey(): String {
        return "(${x},${y})"
    }

    fun toDirectionKey(): String {
        return "(${x},${y})$d"
    }

    fun toScoreKey(): String {
        return "$prev$d(${x},${y},${score})"
    }

    companion object {
        val DIRECTION_KEY_REGEX = Regex("^\\((\\d+),(\\d+)\\)(LEFT|UP|RIGHT|DOWN)$")
        val SCORE_KEY_REGEX = Regex("^(\\(\\d+,\\d+\\))(LEFT|UP|RIGHT|DOWN)\\((\\d+),(\\d+),(\\d+)\\)$")
        fun of (str: String): PositionWithDirection {
            val matchesScore = SCORE_KEY_REGEX.matchEntire(str)
            if (matchesScore != null) {
                return PositionWithDirection(
                    x = matchesScore.groupValues[3].toInt(),
                    y = matchesScore.groupValues[4].toInt(),
                    score = matchesScore.groupValues[5].toInt(),
                    d = Direction.valueOf(matchesScore.groupValues[2]),
                    prev = matchesScore.groupValues[1]
                )
            }

            val matchesDirection = DIRECTION_KEY_REGEX.matchEntire(str)
            if (matchesDirection != null) {
                return PositionWithDirection(
                    x = matchesDirection.groupValues[1].toInt(),
                    y = matchesDirection.groupValues[2].toInt(),
                    d = Direction.valueOf(matchesDirection.groupValues[3]),
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
        score = deltaScore + (p.score ?: 0),
        prev = p.toKey()
    )
}