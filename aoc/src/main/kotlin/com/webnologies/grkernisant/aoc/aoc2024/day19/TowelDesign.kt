package com.webnologies.grkernisant.aoc.aoc2024.day19

data class TowelDesign(
    override val pattern: String,
    override val blocks: MutableSet<String> = mutableSetOf(),
    override var nbCombo: Int = 1,
    override val sets: MutableSet<String> = mutableSetOf(),
    var canDesign: Boolean = false,
    val subpatterns: MutableSet<String> = mutableSetOf(),
) : PatternInterface {
    companion object {
        val TOWEL_DESIGN_REGEX = Regex("^[a-z]+$")
    }
}
