package com.webnologies.grkernisant.aoc.aoc2024.day17

data class Operand(var value: ULong? = null) {
    fun getSafeValue(): ULong {
        return (value ?: 0) as ULong
    }
}
