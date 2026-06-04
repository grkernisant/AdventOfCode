package com.webnologies.grkernisant.aoc.aoc2024.day17

interface ComputerInterface {
    val program: List<Instruction>
    val programStr: String
    val registers: RegisterMap
}
