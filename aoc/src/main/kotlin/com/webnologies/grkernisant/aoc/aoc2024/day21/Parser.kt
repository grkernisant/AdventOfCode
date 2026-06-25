package com.webnologies.grkernisant.aoc.aoc2024.day21

import kotlin.collections.joinToString

class Parser : KeyCodesInterface {
    override var codes: List<KeyCode>

    constructor(input: List<String>) {
        codes = input.mapNotNull { inp ->
            try {
                KeyCode.of(inp)
            } catch (_: IllegalArgumentException) {
                return@mapNotNull null
            }
        }
    }

    fun toKeyCodeString(): List<String> {
        return codes.map { itCodes ->
            itCodes.code.joinToString("") { itCode ->
                itCode.c.toString()
            }
        }
    }
}