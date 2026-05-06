export type SafetyType = "increasing" | "decreasing";
export interface LevelData {
	levels: number[],
	safetyType: SafetyType | null,
	dampedLevel: number | null
}
