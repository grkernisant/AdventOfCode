import { describe, it, expect } from 'vitest';
import { TopoMap } from './types/index.ts';

describe('Advent of Code 2024 Day 10', () => {
  const mockInput = `89010123\n`
    + `78121874\n`
    + `87430965\n`
    + `96549874\n`
    + `45678903\n`
    + `32019012\n`
    + `01329801\n`
    + `10456732`;

    const mockMini = `0123\n`
      + `1234\n`
      + `8765\n`
      + `9876`;

    const mockY = `...0...\n`
      + `...1...\n`
      + `...2...\n`
      + `6543456\n`
      + `7.....7\n`
      + `8.....8\n`
      + `9.....9`;

    const mock4 = `..90..9\n`
      + `...1.98\n`
      + `...2..7\n`
      + `6543456\n`
      + `765.987\n`
      + `876....\n`
      + `987....`;

    const mock5 = `10..9..\n`
      + `2...8..\n`
      + `3...7..\n`
      + `4567654\n`
      + `...8..3\n`
      + `...9..2\n`
      + `.....01`;

    const mockRating3 = `.....0.\n`
      + `..4321.\n`
      + `..5..2.\n`
      + `..6543.\n`
      + `..7..4.\n`
      + `..8765.\n`
      + `..9....`;

    const mockRating227 = `012345\n`
      + `123456\n`
      + `234567\n`
      + `345678\n`
      + `4.6789\n`
      + `56789.`;

    it('Parses a TopoMap to finds its TrailHeads', () => {
      const tm1 = new TopoMap(mockMini);
      expect(tm1.theads.length).toBe(1);

      const tm2 = new TopoMap(mockY);
      expect(tm1.theads.length).toBe(1);

      const tm3 = new TopoMap(mockInput);
      expect(tm3.theads.length).toBe(9);

      const tm4 = new TopoMap(mock4);
      expect(tm4.theads.length).toBe(1);

      const tm5 = new TopoMap(mock5);
      expect(tm5.theads.length).toBe(2);
    });

    it('Calculate a TopoMap scores correctly', () => {
      const tm1 = new TopoMap(mockMini);
      expect(tm1.getScore().total).toBe(1);

      const tm2 = new TopoMap(mockY);
      expect(tm2.getScore().total).toBe(2);

      const tm3 = new TopoMap(mockInput);
      expect(tm3.getScore().total).toBe(36);

      const tm4 = new TopoMap(mock4);
      expect(tm4.getScore().total).toBe(4);

      const tm5 = new TopoMap(mock5);
      expect(tm5.getScore().total).toBe(3);
    });

    it ('Calculates a Topomap ratings correctly', () => {
      const tmRating3 = new TopoMap(mockRating3);
      expect(tmRating3.getRatings().total).toBe(3);

      const tmRating227 = new TopoMap(mockRating227);
      expect(tmRating227.getRatings().total).toBe(227);
      const expectedRatings227 = [106, 121].join(',');
      const detailledResult227 = Array.from(tmRating227.getRatings().details.values())[0].toSorted((a, b) => a - b).join(',');
      expect(detailledResult227).toBe(expectedRatings227);

      const tm3 = new TopoMap(mockInput);
      expect(tm3.getRatings().total).toBe(81);
      const expectedRatings3 = [20, 24, 10, 4, 1, 4, 5, 8, 5].toSorted((a, b) => a - b).join(',');
      const subResults3 = Array.from(tm3.getRatings().subtotals.values()).toSorted((a, b) => a - b).join(',');
      expect(subResults3).toBe(expectedRatings3);
    });
});