import { DIRECTION_OFFSET, Pos2String, String2Pos } from './index.ts';
import type { Position, Robot, Tile, Box } from './index.ts';

interface WarehouseOption {
  map: Tile[][],
  moves: Direction[],
  robot: Robot,
  boxSpecs: Box,
}

export class Warehouse {
  boxes: Position[];
  boxSpecs: Box;
  cols: number;
  map: Tile[][];
  robot: Robot;
  rows: number;
  walls: Position[];

  constructor(options: WarehouseOption) {
    this.map = options.map;
    this.moves = options.moves;
    this.robot = options.robot;
    this.boxSpecs = options.boxSpecs;

    this.rows = this.map.length;
    this.cols = this.map[0].length;
    this.boxes = [];
    this.walls = [];
    for(let y = 0; y < this.rows; y++) {
      for(let x = 0; x < this.cols; x++) {
        if (this.map[y][x] === '#') {
          this.walls.push({ x, y });
        }

        if (this.map[y][x] === this.boxSpecs.schema[0][0]) {
          this.boxes.push({ x, y });
        }
      }
    }
  }

  canBoxesMove(bp: Position[], d: Direction): boolean {
    let nbCanMove: number;
    let canBoxMove: boolean;
    let pos: Position;
    let x: number;
    let y: number;
    switch (d) {
      case '<':
        // check left side
        nbCanMove = bp.reduce((acc, curr) => {
          y = 0;
          canBoxMove = true;
          while(y < this.boxSpecs.dimensions[1] && canBoxMove) {
            pos = { x: curr.x, y: curr.y + y };
            canBoxMove = this.canMove(pos, d);
            y++;
          }

          if (canBoxMove) acc++;

          return acc;
        }, 0);
        break;

      case '^':
        // check top side
        nbCanMove = bp.reduce((acc, curr) => {
          x = 0;
          canBoxMove = true;
          while(x < this.boxSpecs.dimensions[0] && canBoxMove) {
            pos = { x: curr.x + x, y: curr.y };
            canBoxMove = this.canMove(pos, d);
            x++;
          }

          if (canBoxMove) acc++;

          return acc;
        }, 0);
        break;

      case '>':
        // check right side
        nbCanMove = bp.reduce((acc, curr) => {
          y = 0;
          canBoxMove = true;
          while(y < this.boxSpecs.dimensions[1] && canBoxMove) {
            pos = { x: curr.x + this.boxSpecs.dimensions[0] - 1, y: curr.y + y };
            canBoxMove = this.canMove(pos, d);
            y++;
          }

          if (canBoxMove) acc++;

          return acc;
        }, 0);
        break;

      case 'v':
        // check bottom side
        nbCanMove = bp.reduce((acc, curr) => {
          x = 0;
          canBoxMove = true;
          while(x < this.boxSpecs.dimensions[0] && canBoxMove) {
            pos = { x: curr.x + x, y: curr.y + this.boxSpecs.dimensions[1] - 1 };
            canBoxMove = this.canMove(pos, d);
            x++;
          }

          if (canBoxMove) acc++;

          return acc;
        }, 0);
        break;
    }

    return nbCanMove === bp.length;
  }

  canMove(p:Position, d: Direction): boolean {
    switch (d) {
      case '<':
      case '^':
      case '>':
      case 'v':
        const posAhead = this.getPositionsAheadFrom(p, d);
        const nextFreeSpace = posAhead.findIndex(r => r === '.');
        if (nextFreeSpace === -1) return false;

        const nextWall = posAhead.findIndex(r => r === '#');
        return nextFreeSpace < nextWall;
        break;

      default: return false;
    }
  }

  getBoxGPS(p: Position): number {
    return 100 * p.y + p.x;
  }

  getBoxesGPS(): number {
    return this.boxes.reduce((acc, curr) => {
      acc+= this.getBoxGPS(curr);
      return acc;
    }, 0);
  }

  getBoxArea(p: Position): Position[] {
    const pos = [];
    for(let y = p.y; y < p.y + this.boxSpecs.dimensions[1]; y++) {
      for(let x = p.x; x < p.x + this.boxSpecs.dimensions[0]; x++) {
        pos.push({ x, y });
      }
    }

    return pos;
  }

  getBoxIndexAt(p: Position): number {
    return this.boxes.findIndex(b => b.x === p.x && b.y === p.y);
  }

  getCol(x: number): Tile[] | undefined {
    if (this.outOfBounds(x, 0)) return undefined;

    const col = this.map.reduce((acc, curr) => {
      acc.push(curr[x]);
      return acc;
    }, []);
    return col;
  }

  getColAsString(x: number): string | undefined {
    const col = this.getCol(x);
    if (col === undefined) return undefined;

    return col.reduce((acc, curr) => acc + curr, '');
  }

  getMoveIntentAffectedGroups(p: Position, d:Direction): Set<string> {
    const debug = '^';
    const affectedGroup = new Set<string>();
    const offset = DIRECTION_OFFSET.get(d);
    const pos = { x: p.x + offset[0], y: p.y + offset[1] };
    const posStr = Pos2String(pos);
    let x: number;
    let y: number;

    // find all boxes at pos
    const group = this.boxes.filter(bp => {
      const area = this.getBoxArea(bp).map(a => Pos2String(a));
      return area.includes(posStr);
    });
    group.map(g => {
      affectedGroup.add(Pos2String(g));
      const checkPositions: Position[] = [];
      let boxSidePos: Position;
      switch (d) {
        case '<':
          // check left side
          y = 0;
          while(y < this.boxSpecs.dimensions[1]) {
            boxSidePos = { x: g.x, y: g.y + y };
            checkPositions.push(boxSidePos);
            y++;
          }
          break;

        case '^':
          // check top side
          x = 0;
          while(x < this.boxSpecs.dimensions[0]) {
            boxSidePos = { x: g.x + x, y: g.y };
            checkPositions.push(boxSidePos);
            x++;
          }
          break;

        case '>':
          // check right side
          y = 0;
          while(y < this.boxSpecs.dimensions[1]) {
            boxSidePos = {
              x: g.x + this.boxSpecs.dimensions[0] - 1,
              y: g.y + y
            };
            checkPositions.push(boxSidePos);
            y++;
          }
          break;

        case 'v':
          // check bottom side
          x = 0;
          while(x < this.boxSpecs.dimensions[0]) {
            boxSidePos = {
              x: g.x + x,
              y: g.y + this.boxSpecs.dimensions[1] - 1
            };
            checkPositions.push(boxSidePos);
            x++;
          }
          break;
      }

      while (checkPositions.length > 0) {
        const cp: Position = checkPositions.shift();
        const ag:Set<string> = this.getMoveIntentAffectedGroups(cp, d);
        if (ag.size > 0) {
          const agArr: string[] = Array.from(ag);
          agArr.map(v => affectedGroup.add(v));
        }
      }
    });
    return affectedGroup;
  }

  getPositionsAheadFrom(p:Position, d: Direction): Tile[] {
    switch(d) {
      case '<':
        return [...this.getRow(p.y).slice(0, p.x)].reverse();
        break;

      case '^':
        return [...this.getCol(p.x).slice(0, p.y)].reverse();
        break;

      case '>':
        return [...this.getRow(p.y).slice(p.x + 1)];
        break;

      case 'v':
        return [...this.getCol(p.x).slice(p.y + 1)];
        break;

      default:
        return [];
    }
  }

  getRow(y: number): Tile[] | undefined {
    if (this.outOfBounds(0, y)) return undefined;

    return this.map[y];
  }

  getRowAsString(y: number): string | undefined {
    const row = this.getRow(y);
    if (row === undefined) return undefined;

    return row.reduce((acc, curr) => acc + curr, '');
  }

  moveRobot(): Direction | boolean {
    if (this.moves.length === 0) return false;

    const dir = this.moves.shift();
    const group = Array.from(this.getMoveIntentAffectedGroups(this.robot, dir)).map(g => String2Pos(g));
    const groupCanMove = group.length !== 0
      ? this.canBoxesMove(group, dir)
      : true;
    const robotCanMove = this.canMove(this.robot, dir);
    if (!(robotCanMove && groupCanMove)) return false;

    this.shiftRobotAndBoxes(group, dir);
    return dir;
  }

  print(): string {
    const outsideWall = `${'#'.repeat(this.cols)}\n`;
    const innerWall = `#${'.'.repeat(this.cols - 2)}#\n`;
    const map = `${outsideWall}${innerWall.repeat(this.rows - 2)}${outsideWall}`.trim()
      .split(`\n`)
      .map(l => l.trim().split(''));

    const remainingWalls = this.walls.filter(w => {
      return w.x > 0 && w.x < this.cols && w.y > 0 && w.y < this.rows;
    });
    const boxRows = this.boxSpecs.schema.length;
    const boxCols = this.boxSpecs.schema[0].length;
    const mapWithBoxesAndRobot = this.boxes.reduce((acc, curr) => {
      for (let y = 0; y < boxRows; y++) {
        for (let x = 0; x < boxCols; x++) {
          acc[curr.y + y][curr.x + x] = this.boxSpecs.schema[y][x];
        }
      }

      return acc;
    }, remainingWalls.reduce((acc, curr) => {
      acc[curr.y][curr.x] = '#';
      return acc;
    }, map));
    mapWithBoxesAndRobot[this.robot.y][this.robot.x] = '@';
    return mapWithBoxesAndRobot.map(row => row.join('')).join(`\n`);
  }

  outOfBounds(x: number, y: number): boolean {
    return (x < 0 || x >= this.cols || y < 0 || y >= this.rows);
  }

  run() {
    let i = 0;
    let m;
    while(this.moves.length > 0) {
      m = this.moveRobot();
    }
  }

  shiftRobotAndBoxes(boxes: Position[], d: Direction) {
    const offset = DIRECTION_OFFSET.get(d);
    let dx: number;
    let dy: number;
    // clear map than re-render boxes
    const boxesIndex = boxes.map(bp => {
      const bi = this.boxes.findIndex(pos => pos.x === bp.x && pos.y === bp.y);
      if (bi === -1) throw new Error(`BOX_NOT_FOUND: (${bp.x}, ${bp.y})`);
      for (let y = 0; y < this.boxSpecs.dimensions[1]; y++) {
        for (let x = 0; x < this.boxSpecs.dimensions[0]; x++) {
          this.map[bp.y + y][bp.x + x] = '.';
        }
      }

      return bi;
    });
    boxesIndex.map(bi => {
      dx = this.boxes[bi].x + offset[0];
      dy = this.boxes[bi].y + offset[1];
      for (let y = 0; y < this.boxSpecs.dimensions[1]; y++) {
        for (let x = 0; x < this.boxSpecs.dimensions[0]; x++) {
          this.map[dy + y][dx + x] = this.boxSpecs.schema[y][x];
        }
      }

      this.boxes[bi] = { x: dx, y: dy };
    });
    // move robot
    dx = this.robot.x + offset[0];
    dy = this.robot.y + offset[1];
    this.map[this.robot.y][this.robot.x] = '.';
    this.map[dy][dx] = '@';
    this.robot = { x: dx, y: dy };
  }
}