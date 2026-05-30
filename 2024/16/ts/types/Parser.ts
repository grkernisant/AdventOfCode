import { END_TILE_REGEX, START_TILE_REGEX } from './index.ts';
import type { MazeMapInterface } from './index.ts';

export class Parser implements MazeMapInterface {
  cols: number;
  end: Position;
  maze: MazeTile[][];
  rows: number;
  start: Position;

  constructor(input: string) {
    this.maze = input.trim()
      .split('\n')
      .map((row, y) => {
        const matchesE = row.trim().match(END_TILE_REGEX);
        if (matchesE !== null) {
          this.end = { x: matchesE.index, y };
        }

        const matchesS = row.trim().match(START_TILE_REGEX);
        if (matchesS !== null) {
          this.start = { x: matchesS.index, y };
        }

        return row.trim().split('')
          .map(t => {
            return { tile: t };
          });
      });
    this.rows = this.maze.length;
    this.cols = this.maze[0].length;
  }
}