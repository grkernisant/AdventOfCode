import { describe, it, expect } from 'vitest';
import { MazeMap, Parser, Visited, Distances } from './types/index.ts';

describe('Advent of Code 2024 Day 16', () => {
  it('it parses a maze map and its score is 7036 and has 45 best tiles', () => {
    const mockInput = `###############\n`
    + `#.......#....E#\n`
    + `#.#.###.#.###.#\n`
    + `#.....#.#...#.#\n`
    + `#.###.#####.#.#\n`
    + `#.#.#.......#.#\n`
    + `#.#.#####.###.#\n`
    + `#...........#.#\n`
    + `###.#.#####.#.#\n`
    + `#...#.....#.#.#\n`
    + `#.#.#.###.#.#.#\n`
    + `#.....#...#.#.#\n`
    + `#.###.#.#.#.#.#\n`
    + `#S..#.....#...#\n`
    + `###############`;
    const mm = new MazeMap(new Parser(mockInput));
    mm.run();
    expect(mm.getDistanceEnd()).toBe(7036);
    expect(mm.getNbOnBestPaths()).toBe(45);
  });

  it('it parses a maze map and its score is 11048', () => {
    const mockInput = `#################\n`
    + `#...#...#...#..E#\n`
    + `#.#.#.#.#.#.#.#.#\n`
    + `#.#.#.#...#...#.#\n`
    + `#.#.#.#.###.#.#.#\n`
    + `#...#.#.#.....#.#\n`
    + `#.#.#.#.#.#####.#\n`
    + `#.#...#.#.#.....#\n`
    + `#.#.#####.#.###.#\n`
    + `#.#.#.......#...#\n`
    + `#.#.###.#####.###\n`
    + `#.#.#...#.....#.#\n`
    + `#.#.#.#####.###.#\n`
    + `#.#.#.........#.#\n`
    + `#.#.#.#########.#\n`
    + `#S#.............#\n`
    + `#################`;

    const mm = new MazeMap(new Parser(mockInput));
    mm.run();
    expect(mm.getDistanceEnd()).toBe(11048);
    expect(mm.getNbOnBestPaths()).toBe(64);
  });
});