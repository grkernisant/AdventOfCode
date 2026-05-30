import type { Position, PositionWithDirection } from './index.ts';

export type Direction = '<' | '^' | '>' | 'v';

export const DIRECTION_OFFSET = new Map<Direction, number[]>([
  ['<', [-1, 0]],
  ['^', [0, -1]],
  ['>', [1, 0]],
  ['v', [0, 1]],
]);
const directionOffsetKeys = Array.from(DIRECTION_OFFSET.keys());

export const findOffset = (d: Direction): number => {
  return directionOffsetKeys.findIndex((e) => e === d);
}

export const getOffsetLeft = (d: Direction): number => {
  return (directionOffsetKeys.length + findOffset(d) - 1) % directionOffsetKeys.length;
}

export const getOffsetRight = (d: Direction): number => {
  return (directionOffsetKeys.length + findOffset(d) + 1) % directionOffsetKeys.length;
}

export const goLeft = (p: PositionWithDirection): PositionWithDirection => {
  const k = directionOffsetKeys[getOffsetLeft(p.dir)];
  return {
    x: p.x + DIRECTION_OFFSET.get(k)[0],
    y: p.y + DIRECTION_OFFSET.get(k)[1],
    dir: k,
  }
}

export const goStraight = (p: PositionWithDirection): PositionWithDirection => {
  return {
    x: p.x + DIRECTION_OFFSET.get(p.dir)[0],
    y: p.y + DIRECTION_OFFSET.get(p.dir)[1],
    dir: p.dir,
  }
}

export const goRight = (p: PositionWithDirection): PositionWithDirection => {
  const k = directionOffsetKeys[getOffsetRight(p.dir)];
  return {
    x: p.x + DIRECTION_OFFSET.get(k)[0],
    y: p.y + DIRECTION_OFFSET.get(k)[1],
    dir: k,
  }
}

export const RotateLeft = (d: Direction): Direction => {
  return directionOffsetKeys[getOffsetLeft(d)];
}

export const RotateRight = (d: Direction): Direction => {
  return directionOffsetKeys[getOffsetRight(d)];
}