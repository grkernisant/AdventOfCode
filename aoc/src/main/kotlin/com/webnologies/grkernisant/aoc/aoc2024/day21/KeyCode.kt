package com.webnologies.grkernisant.aoc.aoc2024.day21

data class KeyCode(val code: List<Button>) {
    override fun toString() : String =
        code.joinToString("") { it.c.toString() }

    companion object {
        val KEYCODE_REGEX = Regex("^[0-9]{3}A$")
        fun of(code: String): KeyCode {
            if (!code.trim().matches(KEYCODE_REGEX)) {
                throw IllegalArgumentException("Invalid key code: $code")
            }

            val buttons = code.trim()
                .split("")
                .filter { it.isNotBlank() }
                .map { Button.of(it.toCharArray()[0]) }
            return KeyCode(buttons)
        }
    }
}
