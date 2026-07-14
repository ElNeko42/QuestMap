import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { ApiError, questApi } from '../api/client';
import type { Completion } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/Toast';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';

type Props = NativeStackScreenProps<RootStackParamList, 'Profile'>;

const statusLabel: Record<string, { txt: string; color: string }> = {
  approved: { txt: 'Aprobada', color: colors.ok },
  pending: { txt: 'Pendiente', color: colors.gold },
  manual_review: { txt: 'En revisión', color: colors.blue },
  rejected: { txt: 'Rechazada', color: colors.danger },
};

export default function ProfileScreen({ navigation }: Props) {
  const { user, logout } = useAuth();
  const toast = useToast();
  const [items, setItems] = useState<Completion[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    try {
      const data = await questApi.myCompletions();
      setItems(data);
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setLoading(false);
    }
  }, [toast]);

  useEffect(() => {
    load();
  }, [load]);

  // XP progress to next level: level = floor(sqrt(xp/100)) + 1
  const level = user?.level ?? 1;
  const xp = user?.xp ?? 0;
  const curFloor = (level - 1) * (level - 1) * 100;
  const nextFloor = level * level * 100;
  const pct = Math.min(
    100,
    Math.round(((xp - curFloor) / (nextFloor - curFloor)) * 100)
  );

  return (
    <View style={styles.flex}>
      <View style={styles.header}>
        <View style={styles.avatar}>
          <Text style={styles.avatarTxt}>
            {(user?.name ?? '?').slice(0, 1).toUpperCase()}
          </Text>
        </View>
        <Text style={styles.name}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>

        <View style={styles.levelRow}>
          <Text style={styles.levelBadge}>Nivel {level}</Text>
          <Text style={styles.xpTxt}>{xp} XP</Text>
        </View>
        <View style={styles.bar}>
          <View style={[styles.barFill, { width: `${pct}%` }]} />
        </View>
        <Text style={styles.nextTxt}>
          {nextFloor - xp} XP para el nivel {level + 1}
        </Text>
      </View>

      <View style={styles.navRow}>
        <TouchableOpacity style={styles.navBtn} onPress={() => navigation.navigate('EditProfile')}>
          <Text style={styles.navBtnTxt}>✏️  Editar perfil</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navBtn} onPress={() => navigation.navigate('Friends')}>
          <Text style={styles.navBtnTxt}>👥  Amigos</Text>
        </TouchableOpacity>
      </View>

      <Text style={styles.section}>Mis misiones</Text>
      {loading ? (
        <ActivityIndicator style={{ marginTop: 24 }} color={colors.brand} />
      ) : items.length === 0 ? (
        <Text style={styles.empty}>
          Todavía no has completado ninguna misión. ¡Ve al mapa!
        </Text>
      ) : (
        <FlatList
          data={items}
          keyExtractor={(c) => String(c.id)}
          contentContainerStyle={{ paddingHorizontal: 16 }}
          renderItem={({ item }) => {
            const s = statusLabel[item.status] ?? {
              txt: item.status,
              color: colors.muted,
            };
            return (
              <View style={styles.cRow}>
                <View style={styles.flex}>
                  <Text style={styles.cTitle} numberOfLines={1}>
                    {item.quest?.title ?? `Misión #${item.quest_id}`}
                  </Text>
                  <Text style={[styles.cStatus, { color: s.color }]}>{s.txt}</Text>
                </View>
                {item.xp_awarded > 0 && (
                  <Text style={styles.cXp}>+{item.xp_awarded} XP</Text>
                )}
              </View>
            );
          }}
        />
      )}

      <TouchableOpacity
        style={styles.logout}
        onPress={async () => {
          await logout();
          // AuthProvider flips to LoginScreen automatically
        }}
      >
        <Text style={styles.logoutTxt}>Cerrar sesión</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },
  header: { alignItems: 'center', paddingTop: 20, paddingHorizontal: 24 },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarTxt: { color: colors.brandInk, fontSize: 30, fontWeight: '800' },
  name: { color: colors.ink, fontSize: 22, fontWeight: '800', marginTop: 12 },
  email: { color: colors.muted, fontSize: 13, marginTop: 2 },
  levelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 16,
  },
  levelBadge: {
    color: colors.brand,
    fontWeight: '800',
    fontSize: 15,
  },
  xpTxt: { color: colors.gold, fontWeight: '800', fontSize: 15 },
  bar: {
    width: '100%',
    height: 10,
    borderRadius: 5,
    backgroundColor: colors.panel2,
    marginTop: 10,
    overflow: 'hidden',
  },
  barFill: { height: '100%', backgroundColor: colors.brand },
  nextTxt: { color: colors.muted, fontSize: 12, marginTop: 6 },
  navRow: { flexDirection: 'row', gap: 10, paddingHorizontal: 16, marginTop: 20 },
  navBtn: {
    flex: 1,
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.md,
    paddingVertical: 14,
    alignItems: 'center',
  },
  navBtnTxt: { color: colors.ink, fontWeight: '700', fontSize: 14 },
  section: {
    color: colors.ink,
    fontSize: 16,
    fontWeight: '800',
    paddingHorizontal: 20,
    marginTop: 24,
    marginBottom: 8,
  },
  empty: {
    color: colors.muted,
    textAlign: 'center',
    marginTop: 20,
    paddingHorizontal: 30,
    lineHeight: 20,
  },
  cRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    borderBottomColor: colors.line,
    borderBottomWidth: 1,
  },
  cTitle: { color: colors.ink, fontWeight: '700', fontSize: 15 },
  cStatus: { fontSize: 12, marginTop: 3, fontWeight: '600' },
  cXp: { color: colors.gold, fontWeight: '800' },
  logout: {
    margin: 16,
    paddingVertical: 14,
    borderRadius: radius.md,
    backgroundColor: colors.panel2,
    borderColor: colors.line,
    borderWidth: 1,
    alignItems: 'center',
  },
  logoutTxt: { color: colors.danger, fontWeight: '700', fontSize: 15 },
});
