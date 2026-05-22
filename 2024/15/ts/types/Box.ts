import type { Position } from './index.ts';

export type BoxType = 'O' | '[' | ']';

export interface Box extends Position {
  dimensions: number[],
  schema: BoxType[][],
}

export const REGULAR_BOX = {
  dimensions: [1, 1],
  schema: [['O']]
};
export const WIDE_BOX = {
  dimensions: [2, 1],
  schema: [['[', ']']]
};