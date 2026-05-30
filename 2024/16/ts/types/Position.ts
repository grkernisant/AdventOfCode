import type { Direction } from './index.ts';

export interface Position {
  x: number,
  y: number
}

export interface PositionWithDirection extends Position {
  dir: Direction
}

export interface PositionWithScore extends PositionDirection {
  score: number
  prev: String
}

export const String2Pos = (str: string): Position | undefined => {
  const matches = str.match(/^\((\d+),(\d+)\)$/);
  if (matches !== null) {
    return {
      x: Number(matches[1]),
      y: Number(matches[2]),
    };
  }

  return null;
}

export const String2PosScore = (str: string): PositionWithScore | undefined => {
  const matches = str.match(/^(\(\d+,\d+\))(<|v|>|\^)\((\d+),(\d+),(\d+)\)$/);
  if (matches !== null) {
    return {
      prev: matches[1],
      dir: matches[2],
      x: Number(matches[3]),
      y: Number(matches[4]),
      score: Number(matches[5]),
    };
  }

  return null;
}

export const Pos2String = (p: Position): string => {
  return `(${p.x},${p.y})`;
}

export const PosDir2String = (pwd: PositionWithDirection): string => {
  return `(${pwd.x},${pwd.y})${pwd.dir}`;
}

export const PosScore2String = (pws: PositionWithScore): string => {
  const prev = pws.prev ?? '';
  return `${prev}${pws.dir}(${pws.x},${pws.y},${pws.score})`;
}
