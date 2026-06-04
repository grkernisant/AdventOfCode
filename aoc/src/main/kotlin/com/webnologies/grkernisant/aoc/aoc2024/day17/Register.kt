package com.webnologies.grkernisant.aoc.aoc2024.day17

typealias RegisterMap = Map<String, Operand>

val REGISTER_REGEX = Regex("^Register ([ABC]): (\\d+)$")