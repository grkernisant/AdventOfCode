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
 
  const mockOutputWithResonance = `##....#....#\n`
    + `.#.#....0...\n`
    + `..#.#0....#.\n`
    + `..##...0....\n`
    + `....0....#..\n`
    + `.#...#A....#\n`
    + `...#..#.....\n`
    + `#....#.#....\n`
    + `..#.....A...\n`
    + `....#....A..\n`
    + `.#........#.\n`
    + `...#......##`;

  it('parses a map correctly without resonance', () => {
    const map = new AntennaMap(mockInput, false);

    console.log(map.toString());
    expect(map.antinodes.size).toBe(14);
    expect(map.toString().trim()).toBe(mockOutput);
  });

  it('parses a map correctly with resonance', () => {
    const map = new AntennaMap(mockInput, true);

    console.log(map.toString());
    expect(map.antinodes.size).toBe(34);
    expect(map.toString().trim()).toBe(mockOutputWithResonance);
  });
});