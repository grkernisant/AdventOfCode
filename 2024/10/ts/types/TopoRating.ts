import type { TopoScore } from './index.ts';

export interface TopoRating extends TopoScore {
  details: Map<string, number[]> | undefined,
};