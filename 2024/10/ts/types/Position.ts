export interface Position {
	x: number,
	y: number,
}

export const Pos2String = (p: Position): string => {
	return `(${p.x},${p.y})`;
}