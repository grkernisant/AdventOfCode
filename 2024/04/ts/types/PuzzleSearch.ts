import type { CharacterTile } from './CharacterTile.ts';
import type { Direction } from './Direction.ts';
import type { Solution } from './Solution.ts';

const allDirections: Direction[] = ["E", "NE", "N", "NW", "W", "SW", "S", "SE"];
const diagonalDirections: Direction[] = ["NE", "NW", "SW", "SE"];
const diagonalNwSeDirections: Direction[] = ["NW", "SE"];
const diagonalSwNeDirections: Direction[] = ["NE", "SW"];
const directionOffsets = new Map<Direction, int[]>([
  ["E", [0, 1]],
  ["NE", [-1, 1]],
  ["N", [-1, 0]],
  ["NW", [-1, -1]],
  ["W",  [0, -1]],
  ["SW", [1, -1]],
  ["S",  [1, 0]],
  ["SE", [1, 1]]
]);

export class PuzzleSearch {
  cols: number;
  puzzle: string[][];
  rows: number;
  word: string;
  solutions: Solution[];

  constructor(input: string, search: string) {
    this.puzzle = input.split("\n").map((str) => str.trim().split(''));
    this.rows = this.puzzle.length;
    this.cols = this.puzzle[0].length;
    this.word = search;
  }

  explore(ct: CharacterTile): Solution[] {
    const solutions = allDirections.reduce((acc, dir) => {
      const found = this.findWordFrom(this.word, ct, dir);
      if (found) acc.push({...ct, dir: dir});

      return acc;
    }, []);

    return solutions;
  }

  exploreX(ct: CharacterTile): Solution[] {
    const wordLength = this.word.length;
    const firstChar = this.word.substring(0, 1);
    const wordStartOffset = (this.word.length - 1) / 2;
    // match 1st diagonal NW - SE
    const solutions = diagonalNwSeDirections.reduce((acc1, dir1) => {
      let n = 0;
      let firstCharTileNwSe = ct;
      while (n < wordStartOffset && firstCharTileNwSe !== null) {
        firstCharTileNwSe = this.getPrev(firstCharTileNwSe, dir1);
        n++;
      }

      if (firstCharTileNwSe === null || firstCharTileNwSe.c !== firstChar) return acc1;

      // match 2nd diagonal SW - NE
      if (this.findWordFrom(this.word, firstCharTileNwSe, dir1)) {
        diagonalSwNeDirections.reduce((acc2, dir2) => {
          let m = 0;
          let firstCharTileSwNe = ct;
          while (m < wordStartOffset && firstCharTileSwNe !== null) {
            firstCharTileSwNe = this.getPrev(firstCharTileSwNe, dir2);
            m++;
          }

          if (firstCharTileSwNe === null || firstCharTileSwNe.c !== firstChar) return acc2;

          // found an X solution
          if (this.findWordFrom(this.word, firstCharTileSwNe, dir2))
            acc1.push({...ct, dir1: dir1});
        }, []);
      }

      return acc1;
    }, []);

    return solutions;
  }

  findWordFrom(find: string, ct: CharacterTile, dir: Direction): bool {
    const wordLength = find.length;
    let word = '';
    let next: CharacterTile | null = {...ct};
    let found = false;
    let n = 0;
    while (n < wordLength && !found && next !== null) {
      word+= next.c;
      found = word === find;
      next = !found ? this.getNext(next, dir) : null;
      n++;
    }

    return found;
  }

  getNext(ct: CharacterTile, dir: Direction): CharacterTile | null {
    const offset = directionOffsets.get(dir);
    const nextY = ct.y + offset[0];
    const nextX = ct.x + offset[1];

    if (nextY < 0 || nextY >= this.rows) return null;
    if (nextX < 0 || nextX >= this.cols) return null;

    return {
      c: this.puzzle[nextY][nextX],
      y: nextY,
      x: nextX
    } as CharacterTile;
  }

  getPrev(ct: CharacterTile, dir: Direction): CharacterTile | null {
    const offset = directionOffsets.get(dir);
    const prevY = ct.y - offset[0];
    const prevX = ct.x - offset[1];

    if (prevY < 0 || prevY >= this.rows) return null;
    if (prevX < 0 || prevX >= this.cols) return null;

    return {
      c: this.puzzle[prevY][prevX],
      y: prevY,
      x: prevX
    } as CharacterTile;
  }

  solve(): number {
    const q = this.word.substring(0, 1);
    this.solutions = this.puzzle
      .map((row, y) => {
        return row
          .map((col, x) => {
            return {c: col, y, x};
          })
          .filter((w) => w.c === q);
      })
      .filter((ws) => ws.length !== 0)
      .flat()
      .reduce((acc, curr) => {
        const sol = this.explore(curr);
        if (sol.length > 0) acc = [...acc, ...sol];

        return acc;
      }, []);

    return this.solutions.length;
  }

  solveX(): number {
    if (this.word.length % 2 === 0)
      throw new Error(`${this.word} should have an even number of characters`);

    const indexMid = Math.floor(this.word.length / 2);
    const q = this.word.substring(indexMid, indexMid + 1);

    this.solutions = this.puzzle
      .map((row, y) => {
        return row
          .map((col, x) => {
            return {c: col, y, x};
          })
          .filter((w) => w.c === q);
      })
      .filter((ws) => ws.length !== 0)
      .flat()
      .reduce((acc, curr) => {
        const sol = this.exploreX(curr);
        if (sol.length > 0) acc = [...acc, ...sol];

        return acc;
      }, []);

    return this.solutions.length;
  }
}