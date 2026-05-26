package com.webnologies.grkernisant.aoc.aoc2024.day15

data class Position(val x: Int, val y: Int) {
    fun offset(rm: RobotMove): Position {
        return of(this, rm)
    }

    companion object {
        fun of(p: Position, rm: RobotMove): Position =
            Position(x = p.x + rm.offset[0], y = p.y + rm.offset[1])

        fun sortPositionByX(a: Position, b: Position): Int {
            val diffX = a.x - b.x
            if (diffX != 0) return diffX

            return a.y - b.y
        }

        fun sortPositionByY(a: Position, b: Position): Int {
            val diffY = a.y - b.y
            if (diffY != 0) return diffY

            return a.x - b.x
        }
    }
}
