package com.webnologies.grkernisant.aoc.aoc2024.day20

enum class Direction(
    val c: Char,
    val offset: List<Int>,
    val orthogonal: Boolean,
) {
    UP('^', listOf(0, -1), true),
    RIGHT('>', listOf(1, 0), true),
    DOWN('v', listOf(0, 1), true),
    LEFT('<', listOf(-1, 0), true),

    UP_LEFT('9', listOf(-1, -1), false),
    UP_RIGHT('7', listOf(1, -1), false),
    DOWN_RIGHT('1', listOf(1, 1), false),
    DOWN_LEFT('3', listOf(-1, 1), false);

    companion object {
        fun of(src: PositionInterface, dst: PositionInterface): Direction? {
            if (src == dst) return null

            val diffX = dst.x - src.x
            val diffY = dst.y - src.y

            if (diffX == 0) {
                return if (src.y < dst.y) Direction.DOWN else Direction.UP
            }

            if (diffY == 0) {
                return if (src.x < dst.x) Direction.RIGHT else Direction.LEFT
            }

            return if (diffX < 0) {
                if (diffY < 0) Direction.UP_LEFT else Direction.DOWN_LEFT
            } else {
                if (diffY < 0) Direction.UP_RIGHT else Direction.DOWN_RIGHT
            }
        }
    }
}
