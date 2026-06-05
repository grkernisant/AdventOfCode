package com.webnologies.grkernisant.aoc.aoc2024.day18

enum class MemoryType (val c: Char){
    FREE('.'),
    CORRUPTED('#'),
    SAFE_PATH('O');

    companion object {
        fun of(c: Char): MemoryType =
            MemoryType.entries.find { c == it.c } ?: throw IllegalArgumentException("unknown memory type $c")
    }
}