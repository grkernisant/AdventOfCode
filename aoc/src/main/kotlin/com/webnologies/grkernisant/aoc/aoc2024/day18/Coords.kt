package com.webnologies.grkernisant.aoc.aoc2024.day18

import java.util.IllegalFormatConversionException

data class Coords(val x: Int, val y: Int) {
    fun toCacheKey(t: Int? = null): String =
        if (t == null) "$x,$y" else "$x,$y@{$t}"

    companion object {
        val COORDS_REGEX = Regex("^(\\d+),(\\d+)")

        fun of(str: String): Coords {
            val match = COORDS_REGEX.find(str.trim())
            if (match != null) {
                return Coords(match.groupValues[1].toInt(), match.groupValues[2].toInt())
            }

            throw IllegalArgumentException("Cannot convert $str to a Coords")
        }
    }
}
