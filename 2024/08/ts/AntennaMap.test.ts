import { describe, it, expect } from 'vitest';
import { AntennaMap } from './types/index.ts';

describe('Advent of Code 2024 Day 8', () => {
  const mockInput = `............\n`
    + `........0...\n`
    + `.....0......\n`
    + `.......0....\n`
    + `....0.......\n`
    + `......A.....\n`
    + `............\n`
    + `............\n`
    + `........A...\n`
    + `.........A..\n`
    + `............\n`
    + `............`;

  const mockOutput = `......#....#\n`
    + `...#....0...\n`
    + `....#0....#.\n`
    + `..#....0....\n`
    + `....0....#..\n`
    + `.#....A.....\n`
    + `...#........\n`
    + `#......#....\n`
    + `........A...\n`
    + `.........A..\n`
    + `..........#.\n`
    + `..........#.`;
 
  it('parses a map correctly', () => {
    const map = new AntennaMap(mockInput);

    console.log(map.toString());
    expect(map.toString().trim()).toBe(mockOutput);
  });
});