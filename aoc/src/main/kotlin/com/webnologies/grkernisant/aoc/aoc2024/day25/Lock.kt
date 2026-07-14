package com.webnologies.grkernisant.aoc.aoc2024.day25

data class Lock(
    override val pinCode: List<Int>,
    override val height: Int = 5,
    override val type: KeyType = KeyType.LOCK,
) : KeyInterface {
    companion object {
        val REGEX = KeyInterface.FULL
    }
}
