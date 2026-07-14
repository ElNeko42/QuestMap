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
import { ApiError, otherApi } from '../api/client';
import type { LeaderboardRow } from '../api/types';
import { useToast } from '../components/Toast';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';

type Props = NativeStackScreenProps<RootStackParamList, 'Leaderboard'>;
type Period = 'week' | 'month' | 'all';

const PERIODS: { key: Period; label: string }[] = [
  { key: 'week', label: 'Semana' },
  { key: 'month', label: 'Mes' },
  { key: 'all', label: 'Global' },
];

export default function LeaderboardScreen(_props: Props) {
  const toast = useToast();
  const [period, setPeriod] = useState<Period>('week');
  const [rows, setRows] = useState<LeaderboardRow[]>([]);
  const [loading, setLoading] = useState(true);

  const load = useCallback(
    async (p: Period) => {
      setLoading(true);
      try {
        const res = await otherApi.leaderboard(p);
        setRows(res.data ?? []);
      } catch (e) {
        toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
      } finally {
        setLoading(false);
      }
    },
    [toast]
  );

  useEffect(() => {
    load(period);
  }, [period, load]);

  return (
    <View style={styles.flex}>
      <View style={styles.tabs}>
        {PERIODS.map((p) => (
          <TouchableOpacity
            key={p.key}
            style={[styles.tab, period === p.key && styles.tabActive]}
            onPress={() => setPeriod(p.key)}
          >
            <Text style={[styles.tabTxt, period === p.key && styles.tabTxtActive]}>
              {p.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {loading ? (
        <ActivityIndicator style={{ marginTop: 40 }} color={colors.brand} />
      ) : rows.length === 0 ? (
        <Text style={styles.empty}>
          Aún no hay puntuaciones en este periodo. ¡Completa misiones!
        </Text>
      ) : (
        <FlatList
          data={rows}
          keyExtractor={(r) => String(r.user_id)}
          contentContainerStyle={{ padding: 16 }}
          renderItem={({ item }) => (
            <View style={styles.row}>
              <Text style={[styles.rank, item.rank <= 3 && styles.rankTop]}>
                {item.rank}
              </Text>
              <View style={styles.flex}>
                <Text style={styles.name}>{item.name}</Text>
                <Text style={styles.lvl}>Nivel {item.level}</Text>
              </View>
              <Text style={styles.xp}>{item.period_xp} XP</Text>
            </View>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },
  tabs: { flexDirection: 'row', gap: 8, padding: 16 },
  tab: {
    flex: 1,
    paddingVertical: 9,
    borderRadius: radius.sm,
    backgroundColor: colors.panel2,
    borderColor: colors.line,
    borderWidth: 1,
    alignItems: 'center',
  },
  tabActive: { borderColor: colors.brand },
  tabTxt: { color: colors.muted, fontWeight: '600', fontSize: 13 },
  tabTxtActive: { color: colors.brand },
  empty: {
    color: colors.muted,
    textAlign: 'center',
    marginTop: 40,
    paddingHorizontal: 30,
    lineHeight: 20,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    borderBottomColor: colors.line,
    borderBottomWidth: 1,
  },
  rank: { width: 30, textAlign: 'center', fontWeight: '800', color: colors.muted, fontSize: 16 },
  rankTop: { color: colors.gold },
  name: { color: colors.ink, fontWeight: '700', fontSize: 15 },
  lvl: { color: colors.muted, fontSize: 12, marginTop: 2 },
  xp: { color: colors.gold, fontWeight: '800', fontSize: 15 },
});
