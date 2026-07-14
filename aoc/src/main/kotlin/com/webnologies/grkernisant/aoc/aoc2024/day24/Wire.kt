package com.webnologies.grkernisant.aoc.aoc2024.day24

data class Wire(
    val name: String,
    var value: Int? = null,
    var affectedBy: MutableSet<String> = mutableSetOf()
) : EquationInterface {
    override fun toEquationOutput(displayValues: Boolean): String {
        return if (displayValues && value != null) "$name: ($value)" else name
    }

    companion object {
        const val WIRE_REGEX = "[a-z][a-z0-9]{2}"
    }
}