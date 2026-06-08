package com.webnologies.grkernisant.aoc.aoc2024.day19

interface PatternInterface {
    val pattern: String
    val blocks: MutableSet<String>
    var nbCombo: Long
    val sets: MutableSet<String>

    fun buildSets(blocks: List<String>, pattern: String, currentSets: List<MutableSet<String>>) {
        if (pattern.isEmpty()) {
            currentSets.forEach { cs ->
                this.sets += cs.joinToString(",")
            }
            // patterns count as 1 set
            // where as towels do not
            val offset  = if(this.javaClass.simpleName == "TowelPattern") 1 else 0
            nbCombo = (offset + this.sets.size).toLong()
            return
        }

        blocks.forEach { block ->
            val regex = Regex("^($block)")
            val matches = regex.find(pattern)
            if (matches != null) {
                val newPattern = pattern.substring(matches.groupValues[1].length)
                val nextSets = currentSets.fold(currentSets) { acc, curr ->
                    val newAcc = acc.map{ a ->
                        (a + matches.groupValues[1]).toMutableSet()
                    }
                    newAcc
                }
                buildSets(blocks, newPattern, nextSets)
            }
        }
    }

    fun countSets(towelPatterns: List<TowelPattern>, pattern: String, currentSets: Int) {
        val n = pattern.length
        val matches: MutableList<Long> = MutableList(n + 1) { 0L }
        matches[0] = currentSets.toLong()

        for (i in 0 until n) {
            val ways = matches[i]
            if (ways == 0L) continue

            for (towelP in towelPatterns) {
                val len = towelP.pattern.length
                if (i + len <= n && pattern.startsWith(towelP.pattern, i)) {
                    matches[i + len] += ways
                }
            }
        }

        nbCombo = matches[n]
    }

    fun setBlocks(b: List<String>) {
        this.blocks.addAll(b)
        this.buildSets(b, pattern, listOf(mutableSetOf()))
    }

    fun getPatternRegex(p: List<TowelPattern>): String {
        return "^(${p.joinToString("|") { it.pattern }})+$"
    }
}