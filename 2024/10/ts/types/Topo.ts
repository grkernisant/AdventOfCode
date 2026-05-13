import type { Altitude, Position } from './index.ts';

export type Ground = '.';
export type TopoType = 'trail' | 'ground';
export interface Topo extends Position {
  type: TopoType;
  h?: Altitude | Ground;
}