import { TRAIL_PATH_STOP, TopoMap } from './index.ts';
import type {
  Altitude,
  BroadcastEvent,
  BroadcastEventType,
  Broadcastable,
  PathInfo,
  Position
} from './index.ts';

export class HikingTrailPath implements Position, Broadcastable {
  x: number;
  y: number;
  h: Altitude;
  map: TopoMap;
  parent: TrailHead | HikingTrailPath;
  children: HikingTrailPath[];

  constructor ({map, parent, x, y, h = 0 }: { map: TopoMap, parent: HikingTrailPath | TrailHead, x: number, y: number, h?: Altitude }) {
    this.x = x;
    this.y = y;
    this.h = h;
    this.map = map;
    this.parent = parent;
    this.children = [];
  }

  broadcast(event: BroadcastEvent) {
    event = this.enrichMessage(event);
    event = this.enrichMetadata(event);
    this.parent.broadcast(event);
  }

  enrichMessage(event: BroadcastEvent): BroadcastEvent { return event; }

  enrichMetadata(event: BroadcastEvent): BroadcastEvent {
    if (event.type === 'TRAIL_PATH_END_REACHED' && event.metadata !== undefined) {
      const metadata = JSON.parse(event.metadata);
      metadata.history.unshift(this.getPathInfo());
      event.metadata = JSON.stringify(metadata, null);
    }

    return event;
  }

  explore() {
    // console.log(`${this.toString()}\n`);
    if (this.h < TRAIL_PATH_STOP) {
      const nextH = this.h + 1;
      this.map
        .findNextPaths(this.x, this.y, this.h)
        .map((np) => {
          this.children.push(new HikingTrailPath({map: this.map, parent: this, x: np[0], y: np[1], h: nextH }));
        });

      // explore recursively via children
      this.children.map(c => c.explore());
    } else {
      const history = [];
      this.broadcast({
        type: 'TRAIL_PATH_END_REACHED',
        metadata: JSON.stringify({ history }, null)
      });
    }
  }

  getPathInfo(): PathInfo { return { x: this.x, y: this.y, h: this.h }; }

  toString(indent?: number): string {
    const nbIndent = indent ?? 0;
    const spaces = ' '.repeat(nbIndent);
    const children = this.children.reduce((acc, curr) => {
      acc+= `${curr.toString(nbIndent + 2)}\n`;
      return acc;
    }, '');
    const out = `${spaces}HikingTrailPath(x: ${this.x}, y: ${this.y}, h: ${this.h})`
      + (children ? `\n  ${spaces}- children:\n${children}` : '');

    return out;
  }
}