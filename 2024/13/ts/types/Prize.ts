import type { Position } from './index.ts';

export const PRIZE_REGEX = /^Prize: X=(\d+), Y=(\d+)$/;
export interface Prize extends Position {};