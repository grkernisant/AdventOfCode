import type { Position } from './index.ts';

export const BUTTON_REGEX = /^Button (A|B): X\+(\d+), Y\+(\d+)$/;
export interface Button extends Position {}