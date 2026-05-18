import { describe, it, expect } from 'vitest';
import { Garden, Pos2String } from './types/index.ts';

describe('Advent of Code 2024 Day 12', () => {
  const mockMini = `AAAA\n`
    + `BBCD\n`
    + `BBCC\n`
    + `EEEC`;

  const mockInner = `OOOOO\n`
    + `OXOXO\n`
    + `OOOOO\n`
    + `OXOXO\n`
    + `OOOOO`;

  const mockInput = `RRRRIICCFF\n`
    + `RRRRIICCCF\n`
    + `VVRRRCCFFF\n`
    + `VVRCCCJFFF\n`
    + `VVVVCJJCFE\n`
    + `VVIVCCJJEE\n`
    + `VVIIICJJEE\n`
    + `MIIIIIJJEE\n`
    + `MIIISIJEEE\n`
    + `MMMISSJEEE`;

  const mockEShaped = `EEEEE\n`
    + `EXXXX\n`
    + `EEEEE\n`
    + `EXXXX\n`
    + `EEEEE`;

  const mockAB = `AAAAAA\n`
    + `AAABBA\n`
    + `AAABBA\n`
    + `ABBAAA\n`
    + `ABBAAA\n`
    + `AAAAAA`;

  it('Parses a Mini Garden regions correctly', () => {
    const gMini = new Garden(mockMini);

    const expectedMiniRegionCountResults = new Map<string, number>([
      ['A', 1],
      ['B', 1],
      ['C', 1],
      ['D', 1],
      ['E', 1],
    ]);
    const expectedMiniPlantCountResults = new Map<string, number>([
      ['A', 4],
      ['B', 4],
      ['C', 4],
      ['D', 1],
      ['E', 3],
    ]);
    const expectedMiniPerimeterResults = new Map<string, number>([
      ['A', 10],
      ['B', 8],
      ['C', 10],
      ['D', 4],
      ['E', 8],
    ]);
    const expectedMiniPriceResults = new Map<string, number>([
      ['A', 40],
      ['B', 32],
      ['C', 40],
      ['D', 4],
      ['E', 24],
    ]);
    const regionCountResults = new Map<string, number>();
    const plantCountResults = new Map<string, number>();
    gMini.regions.forEach((r, pt) => {
      regionCountResults.set(pt, r.length);
      const nbPlants = r.reduce((acc, curr) => acc + curr.plants.length, 0);
      plantCountResults.set(pt, nbPlants);
    });

    expectedMiniRegionCountResults.forEach((expectedCount, key) => {
      expect(regionCountResults.get(key)).toBe(expectedCount);
    });

    expectedMiniPlantCountResults.forEach((expectedCount, key) => {
      expect(plantCountResults.get(key)).toBe(expectedCount);
      expect(gMini.regions.get(key)[0].area).toBe(expectedCount);
    });

    expectedMiniPerimeterResults.forEach((expectedCount, key) => {
      expect(gMini.regions.get(key)[0].perimeter).toBe(expectedCount);
    });

    expectedMiniPriceResults.forEach((expectedCount, key) => {
      expect(gMini.regions.get(key)[0].fencePrice).toBe(expectedCount);
    });
  });

  it ('Parses a Inner Garden regions correctly', () => {
    const gInner = new Garden(mockInner);

    const expectedInnerRegionCountResults = new Map<string, number>([
      ['O', 1],
      ['X', 4]
    ]);
    const expectedInnerPlantCountResults = new Map<string, number>([
      ['O', 21],
      ['X', 4]
    ]);
    const expectedInnerAreaCountResults = new Map<string, number>([
      ['O', 21],
      ['X', 1]
    ]);
    const expectedInnerPerimeterResults = new Map<string, number>([
      ['O', 36],
      ['X', 4]
    ]);
    const expectedInnerPriceResults = new Map<string, number>([
      ['O', 756],
      ['X', 4]
    ]);
    const expectedInnerBulkPriceResults = new Map<string, number>([
      ['O', 420],
      ['X', 4]
    ]);

    const regionInnerCountResults = new Map<string, number>();
    const plantInnerCountResults = new Map<string, number>();
    gInner.regions.forEach((r, pt) => {
      regionInnerCountResults.set(pt, r.length);
      const nbPlants = r.reduce((acc, curr) => acc + curr.plants.length, 0);
      plantInnerCountResults.set(pt, nbPlants);
    });

    expectedInnerRegionCountResults.forEach((expectedCount, key) => {
      expect(regionInnerCountResults.get(key)).toBe(expectedCount);
    });

    expectedInnerPlantCountResults.forEach((expectedCount, key) => {
      expect(plantInnerCountResults.get(key)).toBe(expectedCount);
    });

    expectedInnerAreaCountResults.forEach((expectedCount, key) => {
      expect(gInner.regions.get(key)[0].area).toBe(expectedCount);
    });

    expectedInnerPerimeterResults.forEach((expectedCount, key) => {
      expect(gInner.regions.get(key)[0].perimeter).toBe(expectedCount);
    });

    expectedInnerPriceResults.forEach((expectedCount, key) => {
      expect(gInner.regions.get(key)[0].fencePrice).toBe(expectedCount);
    });

    expectedInnerBulkPriceResults.forEach((expectedCount, key) => {
      expect(gInner.regions.get(key)[0].bulkFencePrice).toBe(expectedCount);
    });

    expect(gInner.getBulkFencePrice()).toBe(436);
  });

  it('Finds the regions sides and bulk fence price', () => {
    const g = new Garden(mockMini);

    g.regions.get('A').map(r => {
      expect(r.getSides()).toBe(4);
      expect(r.bulkFencePrice).toBe(16);
    });
    g.regions.get('B').map(r => {
      expect(r.getSides()).toBe(4);
      expect(r.bulkFencePrice).toBe(16);
    });
    g.regions.get('C').map(r => {
      expect(r.getSides()).toBe(8);
      expect(r.bulkFencePrice).toBe(32);
    });
    g.regions.get('D').map(r => {
      expect(r.getSides()).toBe(4);
      expect(r.bulkFencePrice).toBe(4);
    });
    g.regions.get('E').map(r => {
      expect(r.getSides()).toBe(4);
      expect(r.bulkFencePrice).toBe(12);
    });
    expect(g.getBulkFencePrice()).toBe(80);

    const ge = new Garden(mockEShaped);
    ge.regions.get('E').map(r => {
      expect(r.area).toBe(17);
      expect(r.getSides()).toBe(12);
      expect(r.bulkFencePrice).toBe(204);
    });
    expect(ge.getBulkFencePrice()).toBe(236);
  });

  it ('Parses AB Garden regions correctly', () => {
    const gAB = new Garden(mockAB);
    expect(gAB.getBulkFencePrice()).toBe(368);
  });

  it ('Parses E-shaped Garden regions correctly', () => {
    const gE = new Garden(mockEShaped);
    expect(gE.regions.get('E')[0].area).toBe(17);
    expect(gE.regions.get('E')[0].outerSides).toBe(12);
    expect(gE.getBulkFencePrice()).toBe(236);
  });

  it('Parses the example fence price and bulk fence price correctly', () => {
    const gInput = new Garden(mockInput);
    expect(gInput.getFencePrice()).toBe(1930);
    expect(gInput.getBulkFencePrice()).toBe(1206);
  });
});