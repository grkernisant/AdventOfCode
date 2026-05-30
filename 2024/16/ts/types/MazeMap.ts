import {
  DIRECTION_OFFSET,
  END_TILE_REGEX,
  START_TILE_REGEX,
  Floor,
  EndTile,
  goLeft,
  goStraight,
  goRight,
  Pos2String,
  PosDir2String,
  PosScore2String,
  RotateLeft,
  RotateRight,
  StartTile,
  String2Pos,
  String2PosScore,
  Wall,
} from './index.ts';
import type {
  MazeMapInterface,
  MazeTile,
  Position,
  PositionWithDirection,
  PositionWithScore,
} from './index.ts';

// Scores with Position and Direction
export const BestScore: Map<String, number> = new Map<String, number>();
// Minimum score from Position
export const Distances: Map<String, number> = new Map<String, number>();
// Visited tiles with direction
export const Visited: Set<String> = new Set<String>();
// Tiles left to visit with score
export const Unvisited: Set<String> = new Set<String>();

interface VisitedFilterOption {
  score?: number,
  pos?: Position,
  dir?: Direction,
}

export class MazeMap implements MazeMapInterface {
  cols: number;
  end: Position;
  maze: MazeTile[][];
  rows: number;
  start: Position;

  constructor(option: MazeMapInterface) {
    this.cols = option.cols;
    this.end = option.end;
    this.maze = option.maze;
    this.rows = option.rows;
    this.start = option.start;

    this.init();
  }

  addVisit(p: PositionWithScore) {
    const bk = PosDir2String(p);
    const vk = PosScore2String(p);
    const best = BestScore.get(bk);
    // Skip if we already visited this (pos, dir) with a better or equal score
    if (best !== undefined && p.score >= best) return;
    BestScore.set(bk, p.score);

    const dk = Pos2String(p);
    const ds = Distances.get(dk);
    if (ds === undefined || p.score < ds) Distances.set(dk, p.score);

    if (!Visited.has(vk)) Visited.add(vk);
  }

  checkUnvisited() {
    const nextVisits: PositionWithScore[] = [];
    Unvisited.forEach(un => {
      const p = String2PosScore(un);

      this.addVisit(p);
      nextVisits.splice(nextVisits.length, 0, ...this.getUnvisitedNeighbors(p));
    });
    Unvisited.clear();

    nextVisits.map(nv => Unvisited.add(PosScore2String(nv)));
  }

  findBestPaths(filter: VisitedFilterOption): string[] {
    const result: string[] = this.findVisitedKeys(filter);
    if (result.length === 0) return [];

    return result.flatMap((r) => {
      const pws = String2PosScore(r);
      const currentPath = Pos2String(pws);
      const prev = (pws.prev !== '') ? String2Pos(pws.prev) : null;

      if (!prev || pws.score === 0) return [currentPath];

      const prevResultFromLeft = this.findBestPaths({
        pos: { x: prev.x, y: prev.y },
        score: pws.score - 1001,
        dir: RotateRight(pws.dir)
      });

      const prevResultFromStraight = this.findBestPaths({
        pos: { x: prev.x, y: prev.y },
        score: pws.score - 1,
        dir: pws.dir
      });

      const prevResultFromRight = this.findBestPaths({
        pos: { x: prev.x, y: prev.y },
        score: pws.score - 1001,
        dir: RotateLeft(pws.dir)
      });

      const allPreviousPaths = [
        ...prevResultFromLeft,
        ...prevResultFromStraight,
        ...prevResultFromRight
      ];

      return allPreviousPaths.map((prevPath) => `${prevPath};${currentPath}`);
    });
  }

  findVisitedKeys(filter: VisitedFilterOption): string[] {
    if (filter.pos && this.outOfBounds(filter.pos.x, filter.pos.y)) return [];

    return Array.from(Visited.values()).filter(v => {
      const p = String2PosScore(v);
      let cond: boolean = p !== null;
      if (!cond) return false;

      // score
      if (filter.score !== undefined) cond = p.score === filter.score;
      if (!cond) return false;

      // position
      if (filter.pos !== undefined) cond = p.x === filter.pos.x && p.y === filter.pos.y;
      if (!cond) return false;

      // direction
      if (filter.dir !== undefined) cond = p.dir === filter.dir;
      if (!cond) return false;

      return true;
    });
  }

  getDistanceEnd(): number | undefined {
    return Distances.get(Pos2String(this.end));
  }

  getDistanceKey(p: Position): string {
    return Pos2String(p);
  }

  getMazeTile(p: Position): MazeTile {
    if (this.outOfBounds(p.x, p.y)) throw new Error(`TILE_NOT_FOUND: ${Pos2String(p)}`);

    return this.maze[p.y][p.x];
  }

  getNbOnBestPaths(): number {
    const bestPaths = this.findBestPaths({
      score: this.getDistanceEnd(),
      pos: this.end
    }).filter(path => path.startsWith(Pos2String(this.start)));

    if (bestPaths.length === 0) return 0;

    const uniqueTiles = bestPaths.reduce((acc, curr) => {
      curr.trim().split(`;`).forEach(pos => acc.add(pos));
      return acc;
    }, new Set<String>());

    return Array.from(uniqueTiles).length;
  }

  getUnvisitedNeighbors(pws: PositionWithScore): PositionWithScore[] {
    const neighbors = [
      { ...goLeft(pws), score: pws.score + 1001, prev: Pos2String(pws) },
      { ...goRight(pws), score: pws.score + 1001, prev: Pos2String(pws) },
      { ...goStraight(pws), score: pws.score + 1, prev: Pos2String(pws) },
    ].filter(n => {
      if (this.outOfBounds(n.x, n.y) || this.isWall(n)) return false;
      if (this.getDistanceEnd() && n.score > this.getDistanceEnd()) return false;
      // Prune if we already found a better or equal path to this (pos, dir)
      const nk = PosDir2String(n);
      const best = BestScore.get(nk);
      if (best !== undefined && n.score >= best) return false;
      return true;
    });
    return neighbors;
  }

  getNeighbors(p: Position): Position[] {
    const neighbors = Array.from(DIRECTION_OFFSET.values())
      .map(offset => {
        return { x: p.x + offset[0], y : p.y + offset[1] }
      })
      .filter(n => !(this.outOfBounds(n.x, n.y) || this.isWall(n)));
    return neighbors;
  }

  getVisitedKey(pwd: PositionWithDirection): string {
    return PosDir2String(pwd);
  }

  isEnd(p: Position): boolean {
    return p.x === this.end.x && p.y === this.end.y;
  }

  isFloor(p: Position): boolean {
    const mt = this.getMazeTile(p);
    return (mt.tile === Floor || mt.tile === StartTile || mt.tile === EndTile);
  }

  isOnlyFloor(p: Position): boolean {
    const mt = this.getMazeTile(p);
    return mt.tile === Floor;
  }

  isWall(p: Position): boolean {
    const mt = this.getMazeTile(p);
    return (mt.tile === Wall);
  }

  init() {
    BestScore.clear();
    Distances.clear();
    Visited.clear();
    Unvisited.clear();

    const visit = {
      ...this.start,
      dir: '>',
      score: 0,
      prev: Pos2String(this.start)
    };
    this.addVisit(visit);

    this.getUnvisitedNeighbors(visit)
      .map(un => Unvisited.add(PosScore2String(un)));
  }

  outOfBounds(x: number, y: number): boolean {
    return x < 0 || x >= this.cols || y < 0 || y >= this.rows;
  }

  run() {
    while (Unvisited.size > 0 && !this.stopDiscovery()) this.checkUnvisited();
  }

  setBestPath(p: Position) {
    const mt = this.getMazeTile(p);
    if (mt !== undefined) mt.onBestPath = true;
  }

  stopDiscovery(): boolean {
    if (Unvisited.size === 0) return true;

    const distanceEnd = this.getDistanceEnd();
    if (distanceEnd !== undefined) {
      // all scores too high?
      const minUnvisitedScore = Array.from(Unvisited).reduce((acc, curr) => {
        const p = String2PosScore(curr);
        if (p.score < acc || acc === 0) acc = p.score;
        return acc;
      }, 0);
      if (minUnvisitedScore > distanceEnd) return true;
      // impossible to get lower score?
      const stillPossible = Array.from(Unvisited).filter(un => {
        const p = String2PosScore(un);
        const dy = Math.abs(this.end.y - p.y);
        const dx = Math.abs(this.end.x - p.x);
        const manhattan = dy + dx;
        // minimum turns: 0 if already aligned, 1 if one turn needed, 2 otherwise
        const alignedStraight = (p.dir === '>' && this.end.x > p.x && this.end.y === p.y) ||
          (p.dir === '<' && this.end.x < p.x && this.end.y === p.y) ||
          (p.dir === 'v' && this.end.y > p.y && this.end.x === p.x) ||
          (p.dir === '^' && this.end.y < p.y && this.end.x === p.x);
        const alignedOneTurn = (p.dir === '>' || p.dir === '<') && (dx === 0 || dy === 0) ||
          (p.dir === 'v' || p.dir === '^') && (dx === 0 || dy === 0);
        const turnCost = alignedStraight ? 0 : (alignedOneTurn ? 1000 : 2000);
        const bestPossibleScore = p.score + manhattan + turnCost;
        return bestPossibleScore <= distanceEnd;
      });
      if (stillPossible.length === 0) return true;
    }

    return false;
  }
}