import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { ApiError, friendApi } from '../api/client';
import type { Friend, FriendRequestItem } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/Toast';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';

type Props = NativeStackScreenProps<RootStackParamList, 'Friends'>;

export default function FriendsScreen(_props: Props) {
  const { user } = useAuth();
  const toast = useToast();

  const [friends, setFriends] = useState<Friend[]>([]);
  const [requests, setRequests] = useState<FriendRequestItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [email, setEmail] = useState('');
  const [adding, setAdding] = useState(false);

  const load = useCallback(async () => {
    try {
      const [f, r] = await Promise.all([friendApi.list(), friendApi.requests()]);
      setFriends(f);
      setRequests(r);
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setLoading(false);
    }
  }, [toast]);

  useEffect(() => {
    load();
  }, [load]);

  const add = async () => {
    if (!email.trim()) return;
    setAdding(true);
    try {
      const res = await friendApi.add(email.trim());
      toast.show(res.message, 'ok');
      setEmail('');
      await load();
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setAdding(false);
    }
  };

  const accept = async (userId: number) => {
    try {
      await friendApi.accept(userId);
      toast.show('¡Ahora sois amigos!', 'ok');
      await load();
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    }
  };

  const remove = async (userId: number) => {
    try {
      await friendApi.remove(userId);
      await load();
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    }
  };

  // Build a comparison list that includes the current user, ranked by XP.
  const ranked: Friend[] = user
    ? [...friends, { id: user.id, name: user.name + ' (tú)', xp: user.xp, level: user.level }]
        .sort((a, b) => b.xp - a.xp)
    : friends;

  return (
    <View style={styles.flex}>
      {/* Add by email */}
      <View style={styles.addRow}>
        <TextInput
          style={styles.input}
          value={email}
          onChangeText={setEmail}
          placeholder="email del amigo"
          placeholderTextColor={colors.muted}
          autoCapitalize="none"
          keyboardType="email-address"
        />
        <TouchableOpacity style={[styles.addBtn, adding && styles.disabled]} onPress={add} disabled={adding}>
          {adding ? <ActivityIndicator color={colors.brandInk} /> : <Text style={styles.addTxt}>Añadir</Text>}
        </TouchableOpacity>
      </View>

      {loading ? (
        <ActivityIndicator style={{ marginTop: 30 }} color={colors.brand} />
      ) : (
        <FlatList
          data={ranked}
          keyExtractor={(f) => String(f.id)}
          contentContainerStyle={{ padding: 16 }}
          ListHeaderComponent={
            requests.length > 0 ? (
              <View style={{ marginBottom: 10 }}>
                <Text style={styles.section}>Solicitudes</Text>
                {requests.map((r) => (
                  <View key={r.id} style={styles.reqRow}>
                    <Text style={styles.reqName}>{r.from.name}</Text>
                    <View style={styles.reqBtns}>
                      <TouchableOpacity style={styles.accept} onPress={() => accept(r.from.id)}>
                        <Text style={styles.acceptTxt}>Aceptar</Text>
                      </TouchableOpacity>
                      <TouchableOpacity style={styles.decline} onPress={() => remove(r.from.id)}>
                        <Text style={styles.declineTxt}>✕</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ))}
                <Text style={[styles.section, { marginTop: 18 }]}>Ranking de amigos</Text>
              </View>
            ) : (
              <Text style={styles.section}>Ranking de amigos</Text>
            )
          }
          ListEmptyComponent={
            <Text style={styles.empty}>
              Aún no tienes amigos. Añade a alguien por su email para comparar puntos.
            </Text>
          }
          renderItem={({ item, index }) => {
            const isMe = item.id === user?.id;
            return (
              <View style={[styles.row, isMe && styles.rowMe]}>
                <Text style={[styles.rank, index < 3 && styles.rankTop]}>{index + 1}</Text>
                <View style={styles.flex}>
                  <Text style={styles.name}>{item.name}</Text>
                  <Text style={styles.lvl}>Nivel {item.level}</Text>
                </View>
                <Text style={styles.xp}>{item.xp} XP</Text>
                {!isMe && (
                  <TouchableOpacity onPress={() => remove(item.id)} hitSlop={8} style={{ marginLeft: 10 }}>
                    <Text style={styles.removeTxt}>✕</Text>
                  </TouchableOpacity>
                )}
              </View>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },
  addRow: { flexDirection: 'row', gap: 8, padding: 16, paddingBottom: 4 },
  input: {
    flex: 1,
    backgroundColor: colors.panel2,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.md,
    paddingVertical: 12,
    paddingHorizontal: 14,
    color: colors.ink,
    fontSize: 15,
  },
  addBtn: {
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    paddingHorizontal: 18,
    justifyContent: 'center',
  },
  addTxt: { color: colors.brandInk, fontWeight: '800' },
  disabled: { opacity: 0.6 },
  section: { color: colors.ink, fontSize: 15, fontWeight: '800', marginBottom: 8 },
  empty: { color: colors.muted, textAlign: 'center', marginTop: 24, paddingHorizontal: 24, lineHeight: 20 },
  reqRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.md,
    padding: 12,
    marginBottom: 8,
  },
  reqName: { flex: 1, color: colors.ink, fontWeight: '700' },
  reqBtns: { flexDirection: 'row', gap: 8, alignItems: 'center' },
  accept: { backgroundColor: colors.brand, borderRadius: radius.sm, paddingVertical: 7, paddingHorizontal: 14 },
  acceptTxt: { color: colors.brandInk, fontWeight: '800', fontSize: 13 },
  decline: { paddingHorizontal: 6 },
  declineTxt: { color: colors.muted, fontSize: 18 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    paddingHorizontal: 12,
    borderBottomColor: colors.line,
    borderBottomWidth: 1,
    borderRadius: radius.sm,
  },
  rowMe: { backgroundColor: colors.panel },
  rank: { width: 26, textAlign: 'center', fontWeight: '800', color: colors.muted, fontSize: 15 },
  rankTop: { color: colors.gold },
  name: { color: colors.ink, fontWeight: '700', fontSize: 15 },
  lvl: { color: colors.muted, fontSize: 12, marginTop: 2 },
  xp: { color: colors.gold, fontWeight: '800', fontSize: 15 },
  removeTxt: { color: colors.muted, fontSize: 16 },
});
