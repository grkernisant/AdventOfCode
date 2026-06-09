package com.webnologies.grkernisant.aoc.aoc2024.day20

import kotlin.math.abs

interface PositionInterface {
    val x: Int
    val y: Int

    fun distanceTo(dst: PositionInterface): Int {
        return abs(x - dst.x) + abs(y - dst.y)
    }
}