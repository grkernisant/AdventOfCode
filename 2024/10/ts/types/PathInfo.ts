import type { Altitude, Position } from './index.ts';

export interface PathInfo extends Position {
  h: Altitude
}