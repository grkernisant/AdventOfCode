package com.webnologies.grkernisant.aoc.aoc2024.day20

import kotlin.math.abs

data class DirectionVector(
    val end: Position,
    val start: Position,
    val direction: Direction,
    val distance: Int
) {
    fun toVectorKey(): String =
        "${start.toPositionKey("(", ")")}-${distance}->${end.toPositionKey("(", ")")}"

    companion object {
        fun of(src: PositionInterface, dst: PositionInterface): DirectionVector? {
            val dir = Direction.of(src, dst) ?: return null

            return DirectionVector(
                end = Position(dst.x, dst.y),
                start = Position(src.x, src.y),
                direction = dir,
                distance = src.distanceTo(dst)
            )
        }
    }
}
