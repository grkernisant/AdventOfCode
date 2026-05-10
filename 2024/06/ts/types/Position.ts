import type { Direction } from './Direction.ts';

export interface Position {
  x: number,
  y: number
};

export interface PositionWithDirection extends Position {
  dir: Direction;
}
