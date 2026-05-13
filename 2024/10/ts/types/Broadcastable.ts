export type BroadcastEventType = 'TRAIL_PATH_END_REACHED';
export interface BroadcastEvent {
  type: BroadcastEventType;
  message?: string;
  metadata?: string;
};
export interface Broadcastable {
  broadcast(event: BroadcastEvent);
  enrichMessage(event: BroadcastEvent): BroadcastEvent;
  enrichMetadata(event: BroadcastEvent): BroadcastEvent;
}