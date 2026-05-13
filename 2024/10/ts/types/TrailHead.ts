import { HikingTrailPath, Pos2String, TopoMap } from './index.ts';
import type { HikingTrail, BroadcastEvent, BroadcastEventType, Broadcastable, PathInfo, Position } from './index.ts';

export class TrailHead implements Position, Broadcastable {
  hikingTrails: HikingTrail[];
  map: TopoMap;
  ratings: Map<string, Set<string>>;
  score: number;
  scoreCard: Map<string, PathInfo[]>;
  x: number;
  y: number;

  constructor({map, x, y, score = 0 }: { map: TopoMap, x: number, y: number, score?: number }) {
    this.x = x;
    this.y = y;
    this.map = map;
    this.hikingTrails = [];
    this.score = score;
    this.scoreCard = new Map<string, PathInfo[]>();
    this.ratings = new Map<string, Set<string>>();
  }

  broadcast(event: BroadcastEvent) {
    event = this.enrichMetadata(this.enrichMessage(event));
    if (event.type === 'TRAIL_PATH_END_REACHED' && event.metadata !== undefined) {
      const metadata = JSON.parse(event.metadata);
      const trailStart = metadata.history[0];
      const trailEnd = metadata.history[metadata.history.length-1];
      const scoreKey = Pos2String({x: trailEnd.x, y: trailEnd.y});
      const ratingKey =  `${Pos2String({x: trailStart.x, y: trailStart.y})}-${scoreKey}`;
      if (!this.scoreCard.has(scoreKey)) {
        this.scoreCard.set(scoreKey, metadata.history);
        this.score++;
      }
      if (!this.ratings.has(ratingKey)) this.ratings.set(ratingKey, new Set<string>);
      const trail = metadata.history
        .reduce((acc, curr) => {
          acc.push(Pos2String(curr));
          return acc;
        }, [])
        .join('->');
      this.ratings.get(ratingKey)?.add(trail);
    }
  }

  enrichMessage(event: BroadcastEvent): BroadcastEvent { return event; }
  enrichMetadata(event: BroadcastEvent): BroadcastEvent { return event; }

  init() {
    this.hikingTrails.push(new HikingTrailPath({map: this.map, parent: this, x: this.x, y: this.y, h: 0}));
    this.hikingTrails[this.hikingTrails.length - 1].explore();
  }

  outOfBounds(x: number, y: number): boolean {
    return this.map.outOfBounds(x, y);
  }
}