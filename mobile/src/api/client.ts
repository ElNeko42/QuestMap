import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import type {
  Completion,
  Friend,
  FriendRequestItem,
  GeoPoint,
  LeaderboardRow,
  Quest,
  Route,
  User,
} from './types';

const BASE_URL: string =
  (Constants.expoConfig?.extra as any)?.apiBaseUrl ??
  'https://questmap.nekoserver.es/api';

const TOKEN_KEY = 'qm_token';

let inMemoryToken: string | null = null;

export async function loadToken(): Promise<string | null> {
  if (inMemoryToken) return inMemoryToken;
  inMemoryToken = await AsyncStorage.getItem(TOKEN_KEY);
  return inMemoryToken;
}

export async function setToken(token: string | null): Promise<void> {
  inMemoryToken = token;
  if (token) await AsyncStorage.setItem(TOKEN_KEY, token);
  else await AsyncStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
  status: number;
  data: any;
  constructor(message: string, status: number, data: any) {
    super(message);
    this.status = status;
    this.data = data;
  }
}

type ReqOpts = {
  method?: string;
  body?: any;
  form?: FormData;
  auth?: boolean;
};

async function request<T>(path: string, opts: ReqOpts = {}): Promise<T> {
  const { method = 'GET', body, form, auth = true } = opts;
  const headers: Record<string, string> = { Accept: 'application/json' };

  if (auth) {
    const token = await loadToken();
    if (token) headers.Authorization = `Bearer ${token}`;
  }

  let payload: BodyInit | undefined;
  if (form) {
    payload = form as any; // RN sets the multipart boundary automatically
  } else if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let res: Response;
  try {
    res = await fetch(`${BASE_URL}${path}`, { method, headers, body: payload });
  } catch (e: any) {
    throw new ApiError('No hay conexión con el servidor.', 0, null);
  }

  let data: any = null;
  const text = await res.text();
  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = text;
    }
  }

  if (!res.ok) {
    const message =
      (data && (data.message || data.error)) || `Error ${res.status}`;
    throw new ApiError(message, res.status, data);
  }

  return data as T;
}

// ---- Auth ----
export const authApi = {
  register: (name: string, email: string, password: string) =>
    request<{ user: User; token: string }>('/auth/register', {
      method: 'POST',
      auth: false,
      body: { name, email, password, password_confirmation: password },
    }),
  login: (email: string, password: string) =>
    request<{ user: User; token: string }>('/auth/login', {
      method: 'POST',
      auth: false,
      body: { email, password },
    }),
  logout: () => request<{ message: string }>('/auth/logout', { method: 'POST' }),
  me: () => request<{ data: User }>('/me').then((r) => r.data),
  // Nested resources (inside an array) are NOT wrapped in `data`.
  updateLocation: (p: GeoPoint) =>
    request<{ user: User; implied_speed_kmh: number | null }>('/me/location', {
      method: 'POST',
      body: p,
    }),
  updateProfile: (fields: { name?: string; email?: string }) =>
    request<{ data: User }>('/me', { method: 'PATCH', body: fields }).then(
      (r) => r.data
    ),
  updatePassword: (current: string, next: string) =>
    request<{ message: string }>('/me/password', {
      method: 'PUT',
      body: {
        current_password: current,
        password: next,
        password_confirmation: next,
      },
    }),
  setTarget: (questId: number) =>
    request<{ data: User }>('/me/target', {
      method: 'POST',
      body: { quest_id: questId },
    }).then((r) => r.data),
  clearTarget: () =>
    request<{ data: User }>('/me/target', { method: 'DELETE' }).then(
      (r) => r.data
    ),
};

// ---- Friends ----
export const friendApi = {
  list: () => request<{ data: Friend[] }>('/friends').then((r) => r.data),
  requests: () =>
    request<{ data: FriendRequestItem[] }>('/friends/requests').then(
      (r) => r.data
    ),
  add: (email: string) =>
    request<{ message: string; status: string }>('/friends/request', {
      method: 'POST',
      body: { email },
    }),
  accept: (userId: number) =>
    request<{ message: string }>(`/friends/${userId}/accept`, {
      method: 'POST',
    }),
  remove: (userId: number) =>
    request<{ message: string }>(`/friends/${userId}`, { method: 'DELETE' }),
};

// ---- Quests ----
export const questApi = {
  nearby: (p: GeoPoint, radius = 20000, category?: string) => {
    const q = new URLSearchParams({
      lat: String(p.lat),
      lng: String(p.lng),
      radius: String(radius),
    });
    if (category) q.append('category', category);
    return request<{ data: Quest[] }>(`/quests/nearby?${q.toString()}`).then(
      (r) => r.data
    );
  },
  show: (id: number) =>
    request<{ data: Quest }>(`/quests/${id}`).then((r) => r.data),
  checkin: (id: number, p: GeoPoint) =>
    request<{ message: string; completion: Completion }>(
      `/quests/${id}/checkin`,
      { method: 'POST', body: p }
    ),
  submit: (id: number, p: GeoPoint, photo: { uri: string; name: string; type: string }) => {
    const form = new FormData();
    form.append('lat', String(p.lat));
    form.append('lng', String(p.lng));
    form.append('photo', {
      uri: photo.uri,
      name: photo.name,
      type: photo.type,
    } as any);
    return request<{ message: string; completion: Completion }>(
      `/quests/${id}/submit`,
      { method: 'POST', form }
    );
  },
  myCompletions: () =>
    request<{ data: Completion[] }>('/me/completions').then((r) => r.data),
};

// ---- Leaderboard & routes ----
export const otherApi = {
  leaderboard: (period: 'week' | 'month' | 'all') =>
    request<{ period: string; data: LeaderboardRow[] }>(
      `/leaderboard?period=${period}`
    ),
  routes: (theme?: string) =>
    request<{ data: Route[] }>(`/routes${theme ? `?theme=${theme}` : ''}`).then(
      (r) => r.data
    ),
  route: (id: number) =>
    request<{ data: Route }>(`/routes/${id}`).then((r) => r.data),
};
