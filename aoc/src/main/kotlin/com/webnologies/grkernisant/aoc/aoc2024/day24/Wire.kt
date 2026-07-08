package com.webnologies.grkernisant.aoc.aoc2024.day24

data class Wire(val name: String, var value: Int? = null) {
    override fun toString(): String =
        "$name: ($value)"

    companion object {
        const val WIRE_REGEX = "[a-z][a-z0-9]{2}"
    }
}