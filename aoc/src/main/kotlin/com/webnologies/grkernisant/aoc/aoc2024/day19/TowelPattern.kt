package com.webnologies.grkernisant.aoc.aoc2024.day19

data class TowelPattern(
    override val pattern: String,
    override val blocks: MutableSet<String> = mutableSetOf(),
    override var nbCombo: Int = 1,
    override val sets: MutableSet<String> = mutableSetOf(),
    var isUnique: Boolean = false,
) : PatternInterface {
    companion object {
        val TOWEL_PATTERN_REGEX = Regex("^(?:[a-z]+(?:, )?)+$")
    }
}
