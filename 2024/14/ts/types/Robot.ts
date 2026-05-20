import type { Position, Vector } from './index.ts';

export const ROBOT_REGEX = /^p=(\d+),(\d+) v=(-?\d+),(-?\d+)$/;

export interface Robot {
  pos: Position,
  acc: Vector,
}