export type Direction = '<' | '^' | '>' | 'v';

export const DIRECTION_OFFSET = new Map<Direction, number[]>([
  ['<', [-1, 0]],
  ['^', [0, -1]],
  ['>', [1, 0]],
  ['v', [0, 1]],
]);

export const DIRECTION_REGEX = /^(<|\^|>|v)+$/;