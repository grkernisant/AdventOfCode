package com.webnologies.grkernisant.aoc.aoc2024.day21

enum class Button (
    val c: Char,
) {
    BUTTON_A('A'),
    BUTTON_0('0'),
    BUTTON_1('1'),
    BUTTON_2('2'),
    BUTTON_3('3'),
    BUTTON_4('4'),
    BUTTON_5('5'),
    BUTTON_6('6'),
    BUTTON_7('7'),
    BUTTON_8('8'),
    BUTTON_9('9'),
    BUTTON_UP('^'),
    BUTTON_LEFT('<'),
    BUTTON_DOWN('v'),
    BUTTON_RIGHT('>');

    companion object {
        fun of(c: Char) =
            Button.entries.find { it.c == c} ?: throw IllegalArgumentException("Cannot convert $c to Button")
    }
}
