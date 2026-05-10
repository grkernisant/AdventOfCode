import { Map } from './Map.ts';
import { DIRECTION_OFFSET } from './Direction.ts';
import type { Direction } from './Direction.ts';
import type { GuardTrail, GuardTrailType } from './GuardRoute.ts';
import type { Position, PositionWithDirection } from './Position.ts';

export class Guard implements Position {
  x: number;
  y: number;
  dir: Direction;
  initialPosition: PositionWithDirection;
  iteration: number;
  trail: GuardTrail[];
  uniqueVisits: Set<string>;
  visited: Set<string>;
  trackingComplete: boolean;
  looping: boolean;
  map: Map;

  constructor(x: number, y: number, dir: Direction, map: Map) {
    this.map = map;
    this.x = x;
    this.y = y;
    this.dir = dir;
    this.initialPosition = { x, y, dir };
    this.iteration = 0;
    this.trail = [{ dir: dir, step: 0, x, y }];
    this.uniqueVisits = new Set([this.getVisitedKey(x, y)]);
    this.visited = new Set([this.getVisitedKey(x, y, dir)]);
    this.trackingComplete = false;
    this.looping = false;
  }

  canSee(x?: number, y?: number, dir?: Direction): boolean {
    const check_x: number = x ?? this.x;
    const check_y: number = y ?? this.y;
    const check_d: Direction = dir ?? this.dir;

    const key = this.getVisitedKey(check_x, check_y, check_d);
    return this.map.guardCanSee(key);
  }

  getLastTrailType(): GuardTrailType | undefined {
    const tl = this.trail.length;
    return tl > 0
      ? this.trail[tl - 1].type
      : undefined;
  }

  getVisitedKey(x: number, y: number, dir?: Direction): string {
    return dir !== undefined
      ? `(${x},${y})${dir}`
      : `(${x},${y})`;
  }

  goto(p: Position | PositionWithDirection) {
    this.moveTo(p.x, p.y);
    if (p.dir !== undefined && p.dir !== this.dir) {
      this.rotateTowards(p.dir);
    }
  }

  hasLeft(): boolean { return this.outOfBounds(); }

  loopDetected(): boolean { return this.looping; }

  moveTo(x: number, y: number) {
    if (this.x === x && this.y === y) throw new ERROR(`KEEP_IT_MOVING`);

    this.iteration++;
    this.x = x;
    this.y = y;
    if (this.outOfBounds()) return;

    if (!this.trackingComplete) {
      this.trackVisits('move', this.x, this.y, this.dir);
    }
  }

  outOfBounds(x?: number, y?: number): boolean {
    const check_x: number = x ?? this.x;
    const check_y: number = y ?? this.y;

    return this.map.outOfBounds(check_x, check_y);
  }

  patrol() {
    const [dx, dy] = DIRECTION_OFFSET.get(this.dir) as number[];
    const nextX = this.x + dx;
    const nextY = this.y + dy;

    if (this.outOfBounds(nextX, nextY)) {
      this.moveTo(nextX, nextY);
      return;
    }

    const mapTileChar = this.map.getTilesChar(nextX, nextY);
    if (mapTileChar === '.') {
      this.moveTo(nextX, nextY);
    }

    if (mapTileChar === '#' || mapTileChar === 'O') {
      this.rotateTowards(this.turnRight());
    }
  }

  rotateTowards(d: Direction) {
    if (this.dir === d) return;

    this.dir = d;

    if (!this.trackingComplete) {
      this.trackVisits('rotate', this.x, this.y, this.dir);
    }
  }

  run() {
    while (!this.hasLeft() && !this.loopDetected()) {
      this.patrol();
    }

    if (!this.trackingComplete) {
      this.setTrackingComplete(true);
    }
  }

  setTrackingComplete(b: boolean) {
    this.trackingComplete = b;
  }

  trackVisits(type: GuardTrailType, x: number, y: number, dir: Direction) {
    // trail
    const tl = this.trail.length;
    const step = type === 'move'
      ? tl > 0 ? (this.trail[tl - 1].step + 1) : 0
      : tl > 0 ? this.trail[tl - 1].step : 0;

    this.trail.push({ type, x, y, dir, step });
    // unique tile tracking
    this.uniqueVisits.add(this.getVisitedKey(x, y));
    // visited tracking with direction
    const vk = this.getVisitedKey(x, y, this.dir);
    if (!this.visited.has(vk)) {
      this.visited.add(vk);
    } else {
      this.looping = true;
    }
  }

  toString(): string {
    const oob = this.outOfBounds() ? '<#OOB!>' : '';
    const loop = this.loopDetected() ? '<#LOOP!>' : '';
    const status = `${this.dir} ${oob} ${loop}`.trim();
    return `Guard (${this.x}, ${this.y})${status}`;
  }

  turnRight(dir?: Direction): Direction {
    const currentDir = dir ?? this.dir;
    const directions: Direction[] = ['<', '^', '>', 'v'];
    const currentIndex = directions.indexOf(currentDir);
    return directions[(currentIndex + 1) % directions.length];
  }
}