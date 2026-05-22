import { TILE_REGEX, DIRECTION_REGEX } from './index.ts';
import type { Direction, Position, Tile } from './index.ts';

export class Parser {
  map: Tile[][];
  robotPosition: Position;
  robotMoves: Direction[];

  constructor(input?: string) {
    this.map = [];
    this.robotMoves = [];

    if (input) {
      this.parse(input);
    }
  }

  getMap() { return this.map; }
  getRobotMoves() { return this.robotMoves; }
  getRobotPosition() { return this.robotPosition; }

  parse(input: string) {
    input.trim()
      .split(`\n`)
      .map((l, y) => {
        let line = l.trim();
        const matchesTile = line.match(TILE_REGEX);
        if (matchesTile !== null) {
          const robotIndex = line.indexOf('@');
          if (robotIndex !== -1) {
            this.robotPosition = { x: robotIndex, y };
            line = line.replace('@', '.');
          }

          this.map.push(line.split(''));
        }

        const matchesDirection = line.match(DIRECTION_REGEX);
        if (matchesDirection !== null) {
          this.robotMoves.splice(this.robotMoves.length, 0, ...line.trim().split(''));
        }
      });
  }
}