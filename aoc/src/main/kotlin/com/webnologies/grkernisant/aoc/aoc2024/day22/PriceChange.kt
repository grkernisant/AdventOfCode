package com.webnologies.grkernisant.aoc.aoc2024.day22

data class PriceChange(
    val deltas: List<Int>,
    val nbBananas: Int,
    val time: Int
) {
    fun toKey(): String = deltas.joinToString(",")
}
