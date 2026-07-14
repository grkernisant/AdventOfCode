package com.webnologies.grkernisant.aoc.aoc2024.day25

import kotlin.collections.joinToString

interface KeyInterface {
    val pinCode: List<Int>
    val height: Int
    val type: KeyType

    fun fits(ki: KeyInterface): Boolean {
        if (type == ki.type) return false
        if (pinCode.size != ki.pinCode.size) return false

        var i = 0
        var overlap = false
        val pinCodeSize = pinCode.size
        while (i < pinCodeSize && !overlap) {
            overlap = (pinCode[i] + ki.pinCode[i]) > height
            i++
        }

        return !overlap
    }

    fun toPinCodeString(l: List<Int>? = null): String {
        val pc = l ?: pinCode
        return pc.joinToString(",") { it.toString() }
    }

    companion object {
        val FULL = "^#+$"
        val EMPTY = "^\\.+$"
    }
}