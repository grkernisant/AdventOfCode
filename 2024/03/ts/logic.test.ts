import { describe, it, expect } from 'vitest';
import { parseMuls, parseMulsWithDoDonts, resolveMul, sumMuls } from './logic.ts';
import { MUL_REGEX } from './types.ts';
import type { Mul } from './types.ts';

describe('Advent of Code 2024 Day 3', () => {
  const mockInput = `xmul(2,4)%&mul[3,7]!@^do_not_mul(5,5)+mul(32,64]then(mul(11,8)mul(8,5))`;
  const mockDoDontInput = `xmul(2,4)&mul[3,7]!^don't()_mul(5,5)+mul(32,64](mul(11,8)undo()?mul(8,5))`;

  it('Corrupted Muls are ignored', () => {
    const badInstructions = [
        'mul(4*',
        'mul(6,9!',
        '?(12,24)',
        'mul ( 2, 4 )',
        'mul(100,1234)',
    ];

    badInstructions.map((instr) => {
        const matches = instr.match(MUL_REGEX);
        expect(matches).toBe(null);
    });
  });

  it('parses Muls correctly', () => {
    const goodInstructions = [
        'mul(44,46)',
        'mul(123,4)'
    ];
    const expectedResults = [2024, 492];
    goodInstructions.map((instr, i) => {
        const aParsedMul = parseMuls(instr);

        expect(aParsedMul).not.toBe(null);
        expect(typeof aParsedMul).toBe("object");
        expect(typeof aParsedMul[0]).toBe("object");
        expect(resolveMul(aParsedMul[0])).toBe(expectedResults[i]);
    });

    const allParsedMuls = parseMuls(mockInput);
    expect(allParsedMuls.length).toBe(4);
    expect(sumMuls(allParsedMuls)).toBe(161);
  });

  it('parses Do Donts correctly', () => {
    const allDontDosParsedMul = parseMulsWithDoDonts(mockDoDontInput);
    expect(allDontDosParsedMul.length).toBe(2);
    expect(sumMuls(allDontDosParsedMul)).toBe(48);
  });
});