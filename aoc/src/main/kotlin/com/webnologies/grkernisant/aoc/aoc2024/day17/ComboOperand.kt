package com.webnologies.grkernisant.aoc.aoc2024.day17

enum class ComboOperand(
    val literal: Int,
    val reserved: Boolean = false,
    val registerName: String? = null
) {
    COMBO_0(literal = 0),
    COMBO_1(literal = 1),
    COMBO_2(literal = 2),
    COMBO_3(literal = 3),
    COMBO_4(literal = 4, registerName = "A"),
    COMBO_5(literal = 5, registerName = "B"),
    COMBO_6(literal = 6, registerName = "C"),
    COMBO_7(literal = 7, reserved = true);

    companion object {
        fun of(literal: Int): ComboOperand =
            ComboOperand.entries.find { it.literal == literal } ?: throw IllegalArgumentException("Unknown Combo Operand $literal")
    }
}