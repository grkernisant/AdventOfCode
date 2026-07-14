package com.webnologies.grkernisant.aoc.aoc2024.day25

data class Key(
    override val pinCode: List<Int>,
    override val height: Int = 5,
    override val type: KeyType = KeyType.KEY,
) : KeyInterface {
    companion object {
        val REGEX = KeyInterface.EMPTY
    }
}
