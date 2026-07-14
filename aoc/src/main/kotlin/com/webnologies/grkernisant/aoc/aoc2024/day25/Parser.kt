package com.webnologies.grkernisant.aoc.aoc2024.day25

class Parser {
    val locks: MutableList<Lock> = mutableListOf()
    val keys: MutableList<Key> = mutableListOf()

    constructor(input: List<String>) {
        var mode: KeyType? = null
        val regex = mapOf(
            KeyType.KEY to Regex(Key.REGEX),
            KeyType.LOCK to Regex(Lock.REGEX),
        )
        var pinCodePattern: MutableList<String> = mutableListOf()

        input.forEach { l ->
            val line = l.trim()
            when (mode) {
                null -> {
                    val isKeyType = regex[KeyType.KEY]!!.matches(line)
                    val isLockType = regex[KeyType.LOCK]!!.matches(line)
                    mode = if (isKeyType) {
                        KeyType.KEY
                    } else if (isLockType) {
                        KeyType.LOCK
                    } else {
                        null
                    }

                    if (pinCodePattern.isNotEmpty()) pinCodePattern.clear()
                }

                KeyType.KEY, KeyType.LOCK -> {
                    if (pinCodePattern.size < 5) {
                        pinCodePattern.add(line)
                        if (pinCodePattern.size == 5) {
                            addPattern(pinCodePattern, mode)
                        }
                    } else if (line.isEmpty()) {
                        // prepare for a new pattern
                        mode = null
                    }
                }
            }
        }
    }

    fun addPattern(pattern: List<String>, mode: KeyType) {
        val height = pattern.size
        if (height < 5) return

        val pinCode = getPatternPinCode(pattern.subList(0, 5))
        if (mode == KeyType.LOCK) {
            locks.add(Lock(pinCode))
        } else {
            keys.add(Key(pinCode))
        }
    }

    fun getKeyLockKey(k: Key, l: Lock): String =
        "${l.toPinCodeString()}-${k.toPinCodeString()}"

    fun getKeyLockFitCount(): Int {
        var nbFits = 0
        val combo = mutableMapOf<String, Boolean>()
        locks.forEach { lock ->
            keys.forEach { key ->
                val kl = getKeyLockKey(key, lock)
                if (!combo.containsKey(kl)) {
                    val fits = key.fits(lock)
                    combo[kl] = fits
                    nbFits += if (fits) 1 else 0
                }
            }
        }

        return nbFits
    }

    private fun getPatternPinCode(pattern: List<String>): List<Int> {
        val chars = pattern.map { line ->
            line.split("").filter { it.isNotBlank() }
        }
        val rows = chars.size
        val cols = chars[0].size
        val pinCode = mutableListOf<Int>()
        for (c in 0 until cols) {
            var pinAtCol = 0
            for (r in 0 until rows) pinAtCol += if (chars[r][c] == "#") 1 else 0
            pinCode.add(pinAtCol)
        }

        return pinCode
    }
}
