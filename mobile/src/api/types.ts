export interface GeoPoint {
  lat: number;
  lng: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  tenant_id: number | null;
  xp: number;
  level: number;
  last_location: GeoPoint | null;
  last_location_at: string | null;
  target_quest_id: number | null;
  target_quest?: Quest | null;
  created_at: string | null;
}

export interface Friend {
  id: number;
  name: string;
  xp: number;
  level: number;
}

export interface FriendRequestItem {
  id: number;
  from: Friend;
  created_at: string | null;
}

export type ValidationType = 'photo_ai' | 'checkin' | 'data_input';
export type QuestStatus = 'active' | 'paused' | 'expired' | 'exhausted';

export interface Quest {
  id: number;
  tenant_id: number;
  creator_type: string;
  title: string;
  description: string;
  category: string;
  location: GeoPoint | null;
  geofence_radius_m: number;
  validation_type: ValidationType;
  xp_reward: number;
  status: QuestStatus;
  starts_at: string | null;
  expires_at: string | null;
  max_completions: number | null;
  completions_count: number;
  dist_m?: number;
}

export type CompletionStatus = 'pending' | 'approved' | 'rejected' | 'manual_review';

export interface Completion {
  id: number;
  quest_id: number;
  user_id: number;
  status: CompletionStatus;
  submitted_at: string | null;
  location_at_submit: GeoPoint | null;
  ai_confidence: number | null;
  xp_awarded: number;
  quest?: Quest;
}

export interface LeaderboardRow {
  rank: number;
  user_id: number;
  name: string;
  level: number;
  period_xp: number;
}

export interface Route {
  id: number;
  tenant_id: number;
  title: string;
  theme: string;
  description: string | null;
  quest_ids: number[];
  estimated_minutes: number;
  quests?: Quest[];
}
