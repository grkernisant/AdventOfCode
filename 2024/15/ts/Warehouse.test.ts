import { describe, it, expect } from 'vitest';
import {
  REGULAR_BOX,
  WIDE_BOX,
  Box,
  Num2String,
  Pos2String,
  Parser,
  Parser2x,
  Warehouse
} from './types/index.ts';
import type { BoxType } from './types/index.ts';

describe('Advent of Code 2024 Day 15', () => {
  const mockMini = `########\n`
  + `#..O.O.#\n`
  + `##@.O..#\n`
  + `#...O..#\n`
  + `#.#.O..#\n`
  + `#...O..#\n`
  + `#......#\n`
  + `########\n\n`
  + `<^^>>>vv<v>>v<<`;

  const mockInput = `##########\n`
  + `#..O..O.O#\n`
  + `#......O.#\n`
  + `#.OO..O.O#\n`
  + `#..O@..O.#\n`
  + `#O#..O...#\n`
  + `#O..O..O.#\n`
  + `#.OO.O.OO#\n`
  + `#....O...#\n`
  + `##########\n`

  + `<vv>^<v^>v>^vv^v>v<>v^v<v<^vv<<<^><<><>>v<vvv<>^v^>^<<<><<v<<<v^vv^v>^\n`
  + `vvv<<^>^v^^><<>>><>^<<><^vv^^<>vvv<>><^^v>^>vv<>v<<<<v<^v>^<^^>>>^<v<v\n`
  + `><>vv>v^v^<>><>>>><^^>vv>v<^^^>>v^v^<^^>v^^>v^<^v>v<>>v^v^<v>v^^<^^vv<\n`
  + `<<v<^>>^^^^>>>v^<>vvv^><v<<<>^^^vv^<vvv>^>v<^^^^v<>^>vvvv><>>v^<<^^^^^\n`
  + `^><^><>>><>^^<<^^v>>><^<v>^<vv>>v>>>^v><>^v><<<<v>>v<v<v>vvv>^<><<>^><\n`
  + `^>><>^v<><^vvv<^^<><v<<<<<><^v<<<><<<^^<v<^^^><^>>^<v^><<<^>>^v<v^v<v^\n`
  + `>^>>^v>vv>^<<^v<>><<><<v<<v><>v<^vv<<<>^^v^>^^>>><<^v>>v^v><^^>>^<>vv^\n`
  + `<><^^>^^^<><vvvvv^v<v<<>^v<v>v<<^><<><<><<<^^<<<^<<>><<><^^^>^^<>^>v<>\n`
  + `^^>vv<^v^v<vv>^<><v<^v>^^^>>>^^vvv^>vvv<>>>^<^>>>>>^<<^v>^vvv<>^<><<v>\n`
  + `v^^>>><<^^<>>^v^<v^vv<>v^<<>^<^v^v><^<<<><<^<v><v<>vv>>v><v^<vv<>v^<<^`;

  it('Parse a warehouse mini map correctly', () => {
    const p = new Parser(mockMini);
    expect(p.map.length).toBe(8);
    expect(p.map[0].length).toBe(8);
    expect(p.robotMoves.length).toBe(15);

    const wh = new Warehouse({
      map: p.getMap(),
      moves: p.getRobotMoves(),
      robot: p.getRobotPosition(),
      boxSpecs: REGULAR_BOX,
    });
    expect(wh.map.length).toBe(8);
    expect(wh.map[0].length).toBe(8);
    expect(wh.moves.length).toBe(15);
    expect(wh.robot.x).toBe(2);
    expect(wh.robot.y).toBe(2);
    expect(wh.boxes.length).toBe(6);
  });

  it('The robot does not move after 1 move on the mini map', () => {
    const p = new Parser(mockMini);
    const wh = new Warehouse({
      map: p.getMap(),
      moves: p.getRobotMoves(),
      robot: p.getRobotPosition(),
      boxSpecs: REGULAR_BOX,
    });
    const firstMove = wh.moveRobot();
    expect(firstMove).toBe(false);
    expect(wh.print()).toBe(mockMini.substring(0, mockMini.length-15).trim());
  });

  it('The robot moves to free spaces or pushes boxes on the mini map', () => {
    const p = new Parser(mockMini);
    const wh = new Warehouse({
      map: p.getMap(),
      moves: p.getRobotMoves(),
      robot: p.getRobotPosition(),
      boxSpecs: REGULAR_BOX,
    });
    const expectedRobotPositions = [
      ['<', [2, 2]],
      ['^', [2, 1]],
      ['^', [2, 1]],
      ['>', [3, 1]],
      ['>', [4, 1]],
      ['>', [4, 1]],
      ['v', [4, 2]],
      ['v', [4, 2]],
      ['<', [3, 2]],
      ['v', [3, 3]],
      ['>', [4, 3]],
      ['>', [5, 3]],
      ['v', [5, 4]],
      ['<', [4, 4]],
      ['<', [4, 4]],
    ];
    let i = 0;
    for (const [dir, pos] of expectedRobotPositions) {
      const m = wh.moveRobot();
      expect(Pos2String(wh.robot)).toBe(Num2String(pos));
    }
  });

  it('The GPS of boxes after the robot moves is 2028 on the mini map', () => {
    const p = new Parser(mockMini);
    const wh = new Warehouse({
      map: p.getMap(),
      moves: p.getRobotMoves(),
      robot: p.getRobotPosition(),
      boxSpecs: REGULAR_BOX,
    });
    wh.run();
    expect(wh.getBoxesGPS()).toBe(2028);
  });

  it('Can widen a warehouse map 2x', () => {
    const p = new Parser(mockInput);
    const p2x = Parser2x.factorize(p);
    const wh = new Warehouse({
      map: p2x.getMap(),
      moves: p2x.getRobotMoves(),
      robot: p2x.getRobotPosition(),
      boxSpecs: WIDE_BOX,
    });
    const expectedWideMap = `####################\n`
      + `##....[]....[]..[]##\n`
      + `##............[]..##\n`
      + `##..[][]....[]..[]##\n`
      + `##....[]@.....[]..##\n`
      + `##[]##....[]......##\n`
      + `##[]....[]....[]..##\n`
      + `##..[][]..[]..[][]##\n`
      + `##........[]......##\n`
      + `####################`;
    expect(wh.print()).toBe(expectedWideMap);
  });

  it('The robot moves to free spaces or pushes wide boxes on a custom map', () => {
    const mockCustomMap = `#######\n`
      + `#...#.#\n`
      + `#.....#\n`
      + `#..OO@#\n`
      + `#..O..#\n`
      + `#.....#\n`
      + `#######\n\n`
      + `<vv<<^^<<^^`;
    const expectedResult = `##############\n`
      + `##...[].##..##\n`
      + `##...@.[]...##\n`
      + `##....[]....##\n`
      + `##..........##\n`
      + `##..........##\n`
      + `##############`;

    const p = new Parser(mockCustomMap);
    const p2x = Parser2x.factorize(p);
    const wh = new Warehouse({
      map: p2x.getMap(),
      moves: p2x.getRobotMoves(),
      robot: p2x.getRobotPosition(),
      boxSpecs: WIDE_BOX,
    });
    wh.run();
    expect(wh.print()).toBe(expectedResult);
  });

  it('The robet moves to free spaces or pushes wide boxes (9021) on the example map', () => {
    const expectedResult = `####################\n`
      + `##[].......[].[][]##\n`
      + `##[]...........[].##\n`
      + `##[]........[][][]##\n`
      + `##[]......[]....[]##\n`
      + `##..##......[]....##\n`
      + `##..[]............##\n`
      + `##..@......[].[][]##\n`
      + `##......[][]..[]..##\n`
      + `####################`;

    const p = new Parser(mockInput);
    const p2x = Parser2x.factorize(p);
    const wh = new Warehouse({
      map: p2x.getMap(),
      moves: p2x.getRobotMoves(),
      robot: p2x.getRobotPosition(),
      boxSpecs: WIDE_BOX,
    });
    wh.run();
    expect(wh.print()).toBe(expectedResult);
    expect(wh.getBoxesGPS()).toBe(9021);
    console.log(wh.print());
  });
});