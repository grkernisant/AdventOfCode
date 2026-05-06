import { describe, it, expect } from 'vitest';
import {
  MAX_SAFETY_DELTA,
  checkSafetyLevel,
  checkSafetyLevelWithDampener,
  getSafetyType,
  parseLevels
} from './logic.ts';
import type { SafetyType } from './types.ts';

describe('Advent of Code 2024 Day 2', () => {
  const mockInput = `7 6 4 2 1\n1 2 7 8 9\n9 7 6 2 1\n1 3 2 4 5\n8 6 4 4 1\n1 3 6 7 9`;
  const safeLevels = [0, 5];
  const safeLevelsWithDampener = [3, 4];

  it(`de/increasing levels by more than ${MAX_SAFETY_DELTA} throws`, () => {
    expect(() => getSafetyType(4)).toThrow('MAX_DIFF_EXCEEDED 4');
    expect(() => getSafetyType(-4)).toThrow('MAX_DIFF_EXCEEDED -4');
  });

  it('stagnant levels throws', () => {
    expect(() => getSafetyType(0)).toThrow('NO_DIFF');
  });

  it('parses levels correctly', () => {
    const mockLevelData = mockInput.split("\n").map((lvl) => parseLevels(lvl));

    expect(mockLevelData.length).toBe(6);
    mockLevelData.map((mld, i, arr) => {
      expect(mld.levels.length).toBe(5);

      const expected = safeLevels.includes(i);
      const result = checkSafetyLevel(mld);
      expect(result).toBe(expected);

      const tolerated = expected || safeLevelsWithDampener.includes(i);
      const resultWithDampener = checkSafetyLevelWithDampener(mld);
      eqxpect(resultWithDampener).toBe(tolerated);
    });
  });
});