import { describe, it, expect } from 'vitest';
// import { Arrangement } from './types/Arrangement.ts';
import { Stone } from './types/Stone.ts';

describe('Advent of Code 2024 Day 11', () => {
  it ('A 0 on a Stone gives 1 after blinking', () => {
    expect(Stone.blinkResult('0')).toBe('1');
  });

  it ('A 1 on a Stone gives 2024 after blinking', () => {
    expect(Stone.blinkResult('1')).toBe('2024');
  });

  it ('A 10 on a Stone gives 1 0 after blinking', () => {
    expect(Stone.blinkResult('10')).toBe('1 0');
  });

  it ('A 99 on a Stone gives 9 9 after blinking', () => {
    expect(Stone.blinkResult('99')).toBe('9 9');
  });

  it ('A 999 on a Stone gives 2021976 after blinking', () => {
    expect(Stone.blinkResult('999')).toBe('2021976');
  });

  it ('Parse an Arrangement correclty', () => {
    expect(Stone.blinkResult('0 1 10 99 999')).toBe('1 2024 1 0 9 9 2021976');

    const expectedResults = [
      '253000 1 7',
      '253 0 2024 14168',
      '512072 1 20 24 28676032',
      '512 72 2024 2 0 2 4 2867 6032',
      '1036288 7 2 20 24 4048 1 4048 8096 28 67 60 32',
      '2097446912 14168 4048 2 0 2 4 40 48 2024 40 48 80 96 2 8 6 7 6 0 3 2'
    ];
    let i = 0;
    let result: string;
    let input: string = '125 17';
    while (i < expectedResults.length) {
      result = Stone.blinkResult(input);
      expect(result).toBe(expectedResults[i]);
      input = result;
      i++;
    }
  });

  it('Counts total stones in Arrangement after blinking N times', () => {
    expect(Stone.totalStones('125 17', 1)).toBe(3);
    expect(Stone.totalStones('125 17', 2)).toBe(4);
    expect(Stone.totalStones('125 17', 3)).toBe(5);
    expect(Stone.totalStones('125 17', 4)).toBe(9);
    expect(Stone.totalStones('125 17', 5)).toBe(13);
    expect(Stone.totalStones('125 17', 6)).toBe(22);
    expect(Stone.totalStones('125 17', 25)).toBe(55312);
  });
});