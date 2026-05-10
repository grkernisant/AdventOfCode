import { Guard } from './Guard.ts';
import { DIRECTION_OFFSET } from './Direction.ts';
import type { Direction } from './Direction.ts';
import type { GuardTrailType } from './GuardRoute.ts';
import type { MapTile } from './MapTile.ts';
import type { PositionWithDirection } from './Position.ts';

export class Map {
  cols: number;
  guard: Guard;
  guardCanSee: boolean;
  rows: number;
  tiles: MapTile[][];
  unsafeChecks: Set<string>;
  loopBlocks: Set<string>;

  constructor(input: string) {
    let guardX = 0;
    let guardY = 0;
    let guardDir: Direction = '^';
    const lines = input
      .split('\n')
      .filter(Boolean)
      .map((line, y) => {
        const guardIndex = line.search(/[<>^v]/);
        if (guardIndex !== -1) {
          guardX = guardIndex;
          guardY = y;
          guardDir = line[guardIndex] as Direction;
        }

        const row = line.trim().split('').map((char, x) => {
          return { x, y, char } as MapTile;
        });
        return row;
      });

    // clear map from initial position
    lines[guardY][guardX].char = '.';

    this.cols = lines[0].length;
    this.guard = new Guard(guardX, guardY, guardDir, this);
    this.guardCanSee = true;
    this.loopBlocks = new Set<string>();
    this.rows = lines.length;
    this.tiles = lines as unknown as MapTile[][];
    this.unsafeChecks = new Set<string>();

    this.init();
  }

  addLoopBlock(x: number, y: number) {
    this.loopBlocks.add(this.guard.getVisitedKey(x, y));
  }

  checkGuardLoopAtPosition(x: number, y: number) {
    // run > 1 ?
    if (!this.guard.trackingComplete) return;
    if (this.getTilesChar(x, y) !== '.') return;

    const currPosKey = this.guard.getVisitedKey(this.guard.x, this.guard.y, this.guard.dir);
    if (this.guard.trackingComplete && !this.unsafeChecks.has(currPosKey)) {
      // put an obstacle at the next position
      this.tiles[y][x].char = 'O';
      // place a guard at the current position
      const loopExplorerGuard = new Guard(
        this.guard.initialPosition.x,
        this.guard.initialPosition.y,
        this.guard.initialPosition.dir,
        this
      );
      // check if he exits or get stuck in loop
      loopExplorerGuard.run();
      if (loopExplorerGuard.loopDetected()) {
        this.addLoopBlock(x, y);
      }

      // clear map
      this.tiles[y][x].char = '.';
    }

    // const nextPosKey = this.guard.getVisitedKey(x, y);

    // prevent guard from noticing
    /*if (this.guardCanSee && !this.guard.canSee()) {
      this.guardCanSee = false;
    }

    if (this.guardCanSee && !this.unsafeChecks.has(currPosKey)) {
      this.unsafeChecks.add(currPosKey);
    }*/

    // as soon as we detect a loop
    // we can start checking for loop blocks
    // in subsequent runs
    /*if (
      !this.guardCanSee &&
      !this.guard.trackingComplete &&
      !this.unsafeChecks.has(currPosKey) &&
      this.guard.visited.has(nextPosKey) &&
      this.guard.getLastTrailType() === "move" &&
      this.minIteration === -1
      ) {
      this.minIteration = this.guard.iteration + 1;
    }*/

    // run > 1
    /*if (this.guard.trackingComplete && !this.unsafeChecks.has(currPosKey)) {
      // if blocked guard turns right,
      // check if that position and direction is in the trail already
      const loopDir = this.guard.turnRight(this.guard.dir);
      let n = 1;
      let loopNextX: number;
      let loopNextY: number;
      let continueDoWhile: boolean = true;
      const loopPositions: PositionWithDirection[] = [];

      do {
        loopNextX = this.guard.x + n * (DIRECTION_OFFSET.get(loopDir) as number[])[0];
        loopNextY = this.guard.y + n * (DIRECTION_OFFSET.get(loopDir) as number[])[1];
        continueDoWhile = !this.outOfBounds(loopNextX, loopNextY) && this.tiles[loopNextY][loopNextX].char === '.';
        if (continueDoWhile) {
          loopPositions.push({ x: loopNextX, y: loopNextY, dir: loopDir });
        }

        n++;
      } while (continueDoWhile);

      const onTrail = this.guard.trail.find((trail) => {
        // return loopPositions.find((lp) => trail.x === lp.x && trail.y === lp.y && trail.dir === lp.dir);
        return loopPositions.find((lp) => trail.x === lp.x && trail.y === lp.y);
      });
      if (onTrail) {
        this.guard.addLoopBlock(x, y);
      }
    }*/
  }

  getTilesChar(x: number, y:number): string | undefined {
    if (this.outOfBounds(x, y)) return undefined;
    return this.tiles[y][x].char;
  }

  guardCanSee(key: string): boolean {
    return this.unsafeChecks.has(key);
  }

  guardHasLeft(): boolean {
    return this.outOfBounds(this.guard.x, this.guard.y);
  }

  guardPatrol() {
    const [dx, dy] = DIRECTION_OFFSET.get(this.guard.dir) as number[];
    const nextX = this.guard.x + dx;
    const nextY = this.guard.y + dy;

    if (this.outOfBounds(nextX, nextY)) {
      this.guard.moveTo(nextX, nextY);
      return;
    }

    const mapTileChar = this.getTilesChar(nextX, nextY);
    if (mapTileChar === '.') {
      // Loop detected?
      this.checkGuardLoopAtPosition(nextX, nextY);
      this.guard.moveTo(nextX, nextY);
    }

    if (mapTileChar === '#' || mapTileChar === 'O') {
      this.guard.rotateTowards(this.guard.turnRight());
    }

  }

  init() {
    const [dx, dy] = DIRECTION_OFFSET.get(this.guard.dir) as number[];
    let unsafeX = this.guard.x;
    let unsafeY = this.guard.y;
    while(!this.outOfBounds(unsafeX, unsafeY) && this.getTilesChar(unsafeX, unsafeY) !== '#') {
      const unsafeKey = this.guard.getVisitedKey(unsafeX, unsafeY, this.guard.dir);
      this.unsafeChecks.add(unsafeKey);

      unsafeX+= dx;
      unsafeY+= dy;
    }
  }

  outOfBounds(x: number, y: number): boolean {
    return x < 0 || x >= this.cols || y < 0 || y >= this.rows;
  }

  run() {
    while (!this.guardHasLeft()) {
      this.guardPatrol();
    }

    if (!this.guard.trackingComplete) {
      this.guard.setTrackingComplete(true);
    }
  }

  toString() {
    const mapCopy = this.tiles.map((row) => row.map((tile) => tile.char));

    if (!this.outOfBounds(this.guard.x, this.guard.y)) {
      mapCopy[this.guard.y][this.guard.x] = this.guard.dir;
    }

    return mapCopy.map((row) => row.join('')).join('\n');
  }
}