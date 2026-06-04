package com.webnologies.grkernisant.aoc.aoc2024.day17

data class Binary(val value: String) {
    fun xor(other: Binary): Binary {
        val l = maxOf(value.length, other.value.length)
        val a = toLength(l).split("").filter { it.isNotBlank() }
        val b = other.toLength(l).split("").filter { it.isNotBlank() }
        val result: MutableList<String> = mutableListOf()
        for (i in 0..<l) {
            result.add(if (a[i] != b[i]) "1" else "0")
        }

        return Binary(result.joinToString(""))
    }

    fun toULong(): ULong {
        if (value.trim().isBlank()) return 0UL

        return value.toULong(2)
    }

    fun toLength(l : Int) : String {
        if (l > 0 && value.length < l) {
            val padding = "0".repeat(l - value.length)
            return "$padding$value"
        }

        return value
    }

    companion object {
        fun of(number: ULong, length: Int? = null): Binary {
            val b = Binary(number.toString(2))
            if (length != null && b.value.length < length) {
                val padding = "0".repeat(length - b.value.length)
                return b.copy(value = "$padding${b.value}")
            }

            return b
        }
    }
}
