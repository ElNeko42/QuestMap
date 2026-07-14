import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import MapView, { Marker, type MapPressEvent, type Region } from 'react-native-maps';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiError } from '../api/client';
import { questApi, authApi } from '../api/client';
import type { GeoPoint, Quest } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/Toast';
import type { RootStackParamList } from '../navigation/types';
import { categoryEmoji, colors, CORUNA, radius, validationLabel } from '../theme';

type Props = NativeStackScreenProps<RootStackParamList, 'Map'>;

const INITIAL_REGION: Region = {
  latitude: CORUNA.lat,
  longitude: CORUNA.lng,
  latitudeDelta: 0.06,
  longitudeDelta: 0.06,
};

export default function MapScreen({ navigation }: Props) {
  const { user, refresh } = useAuth();
  const toast = useToast();
  const mapRef = useRef<MapView | null>(null);

  const [quests, setQuests] = useState<Quest[]>([]);
  const [myPos, setMyPos] = useState<GeoPoint>({ lat: CORUNA.lat, lng: CORUNA.lng });
  const [demoMode, setDemoMode] = useState(false);
  const [selected, setSelected] = useState<Quest | null>(null);
  const [done, setDone] = useState<Set<number>>(new Set());
  const [busy, setBusy] = useState(false);

  const loadNearby = useCallback(
    async (pos: GeoPoint) => {
      try {
        const list = await questApi.nearby(pos, 20000);
        setQuests(list);
        if (list.length === 0) {
          toast.show('No hay misiones en 20 km. Prueba el modo demo 🧭', 'err');
        }
      } catch (e) {
        toast.show(e instanceof ApiError ? e.message : 'Error al cargar', 'err');
      }
    },
    [toast]
  );

  useEffect(() => {
    loadNearby(myPos);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const recenter = (pos: GeoPoint) => {
    mapRef.current?.animateToRegion(
      {
        latitude: pos.lat,
        longitude: pos.lng,
        latitudeDelta: 0.03,
        longitudeDelta: 0.03,
      },
      450
    );
  };

  const useMyLocation = async () => {
    if (demoMode) setDemoMode(false);
    toast.show('Buscando tu ubicación…');
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') {
      toast.show('Sin permiso de ubicación. Usa el modo demo 🧭', 'err');
      return;
    }
    try {
      const loc = await Location.getCurrentPositionAsync({
        accuracy: Location.Accuracy.High,
      });
      const pos = { lat: loc.coords.latitude, lng: loc.coords.longitude };
      setMyPos(pos);
      recenter(pos);
      try {
        await authApi.updateLocation(pos);
      } catch {
        /* location update is best-effort */
      }
      await refresh();
      await loadNearby(pos);
    } catch {
      toast.show('No se pudo obtener el GPS.', 'err');
    }
  };

  const toggleDemo = () => {
    const next = !demoMode;
    setDemoMode(next);
    if (next) {
      const pos = { lat: CORUNA.lat, lng: CORUNA.lng };
      setMyPos(pos);
      recenter(pos);
      loadNearby(pos);
      toast.show('Modo demo: toca el mapa para moverte 🧭');
    }
  };

  const onMapPress = (e: MapPressEvent) => {
    if (!demoMode) return;
    const { latitude, longitude } = e.nativeEvent.coordinate;
    const pos = { lat: latitude, lng: longitude };
    setMyPos(pos);
    loadNearby(pos);
  };

  // In demo mode we submit the quest's own coordinates (simulating "I walked
  // there") so the server-side geofence passes; otherwise the real GPS fix.
  const submitCoords = (q: Quest): GeoPoint =>
    demoMode && q.location ? q.location : myPos;

  const doCheckin = async (q: Quest) => {
    setBusy(true);
    try {
      const res = await questApi.checkin(q.id, submitCoords(q));
      toast.show(`✅ +${res.completion.xp_awarded} XP · ${q.title}`, 'ok');
      setDone((prev) => new Set(prev).add(q.id));
      setSelected(null);
      await refresh();
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setBusy(false);
    }
  };

  const doPhoto = async (q: Quest) => {
    const perm = await ImagePicker.requestCameraPermissionsAsync();
    if (!perm.granted) {
      toast.show('Sin permiso de cámara.', 'err');
      return;
    }
    const result = await ImagePicker.launchCameraAsync({
      quality: 0.6,
      mediaTypes: ['images'],
    });
    if (result.canceled || !result.assets?.length) return;

    const asset = result.assets[0];
    setBusy(true);
    try {
      const name = asset.fileName || `quest-${q.id}.jpg`;
      const type = asset.mimeType || 'image/jpeg';
      await questApi.submit(q.id, submitCoords(q), { uri: asset.uri, name, type });
      toast.show('📤 Foto enviada. La IA la validará en segundos…', 'ok');
      setSelected(null);
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setBusy(false);
    }
  };

  return (
    <View style={styles.flex}>
      <MapView
        ref={mapRef}
        style={StyleSheet.absoluteFill}
        initialRegion={INITIAL_REGION}
        onPress={onMapPress}
        showsUserLocation={!demoMode}
      >
        {/* Simulated position marker in demo mode */}
        {demoMode && (
          <Marker coordinate={{ latitude: myPos.lat, longitude: myPos.lng }}>
            <View style={styles.meDot} />
          </Marker>
        )}

        {quests.map((q) =>
          q.location ? (
            <Marker
              key={q.id}
              coordinate={{ latitude: q.location.lat, longitude: q.location.lng }}
              onPress={() => setSelected(q)}
            >
              <View style={[styles.pin, done.has(q.id) && styles.pinDone]}>
                <Text style={styles.pinTxt}>{done.has(q.id) ? '✓' : '★'}</Text>
              </View>
            </Marker>
          ) : null
        )}
      </MapView>

      {/* Top bar */}
      <SafeAreaView edges={['top']} style={styles.topSafe} pointerEvents="box-none">
        <View style={styles.topbar} pointerEvents="box-none">
          <Text style={styles.logo}>
            Quest<Text style={{ color: colors.brand }}>Map</Text>
          </Text>
          <View style={styles.spacer} />
          <View style={styles.pill}>
            <Text style={styles.pillTxt}>
              Nv <Text style={{ color: colors.gold }}>{user?.level ?? 1}</Text> ·{' '}
              {user?.xp ?? 0} XP
            </Text>
          </View>
          <TouchableOpacity
            style={styles.iconBtn}
            onPress={() => navigation.navigate('Leaderboard')}
          >
            <Text style={styles.iconTxt}>🏆</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.iconBtn}
            onPress={() => navigation.navigate('Profile')}
          >
            <Text style={styles.iconTxt}>👤</Text>
          </TouchableOpacity>
        </View>
        {demoMode && (
          <View style={styles.demoBanner}>
            <Text style={styles.demoTxt}>🧭 Modo demo: toca el mapa para moverte</Text>
          </View>
        )}
      </SafeAreaView>

      {/* FABs */}
      <TouchableOpacity
        style={[styles.fab, styles.fabSecondary, demoMode && styles.fabActive]}
        onPress={toggleDemo}
      >
        <Text style={{ fontSize: 20 }}>🧭</Text>
      </TouchableOpacity>
      <TouchableOpacity style={styles.fab} onPress={useMyLocation}>
        <Text style={{ fontSize: 22 }}>📍</Text>
      </TouchableOpacity>

      {/* Quest detail modal */}
      <Modal
        visible={!!selected}
        transparent
        animationType="slide"
        onRequestClose={() => setSelected(null)}
      >
        <TouchableOpacity
          style={styles.backdrop}
          activeOpacity={1}
          onPress={() => setSelected(null)}
        />
        {selected && (
          <View style={styles.sheet}>
            <View style={styles.grabber} />
            <Text style={styles.cat}>
              {categoryEmoji[selected.category] ?? '📍'} {selected.category}
            </Text>
            <Text style={styles.qTitle}>{selected.title}</Text>
            <Text style={styles.qDesc}>{selected.description}</Text>

            <View style={styles.metaRow}>
              <View>
                <Text style={styles.metaLbl}>Recompensa</Text>
                <Text style={styles.metaVal}>
                  <Text style={{ color: colors.gold }}>{selected.xp_reward}</Text> XP
                </Text>
              </View>
              <View>
                <Text style={styles.metaLbl}>Distancia</Text>
                <Text style={styles.metaVal}>
                  {selected.dist_m != null ? `${Math.round(selected.dist_m)} m` : '—'}
                </Text>
              </View>
              <View>
                <Text style={styles.metaLbl}>Tipo</Text>
                <Text style={styles.metaVal}>
                  {validationLabel[selected.validation_type] ?? selected.validation_type}
                </Text>
              </View>
            </View>

            {selected.validation_type === 'checkin' && (
              <>
                <TouchableOpacity
                  style={[styles.actBtn, busy && styles.btnDisabled]}
                  disabled={busy}
                  onPress={() => doCheckin(selected)}
                >
                  {busy ? (
                    <ActivityIndicator color={colors.brandInk} />
                  ) : (
                    <Text style={styles.actTxt}>📍 Hacer check-in aquí</Text>
                  )}
                </TouchableOpacity>
                <Text style={styles.hint}>
                  Debes estar a menos de {selected.geofence_radius_m} m del punto.
                </Text>
              </>
            )}

            {selected.validation_type === 'photo_ai' && (
              <>
                <TouchableOpacity
                  style={[styles.actBtn, styles.actGold, busy && styles.btnDisabled]}
                  disabled={busy}
                  onPress={() => doPhoto(selected)}
                >
                  {busy ? (
                    <ActivityIndicator color="#3a2b00" />
                  ) : (
                    <Text style={[styles.actTxt, { color: '#3a2b00' }]}>
                      📷 Hacer foto
                    </Text>
                  )}
                </TouchableOpacity>
                <Text style={styles.hint}>
                  Una IA validará tu foto y te dará los XP.
                </Text>
              </>
            )}

            {selected.validation_type === 'data_input' && (
              <Text style={styles.hint}>
                Este tipo de misión llegará en una próxima versión.
              </Text>
            )}
          </View>
        )}
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },

  topSafe: { position: 'absolute', top: 0, left: 0, right: 0 },
  topbar: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 14,
    paddingTop: 8,
    paddingBottom: 8,
  },
  logo: { fontSize: 18, fontWeight: '800', color: colors.ink },
  spacer: { flex: 1 },
  pill: {
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.pill,
    paddingVertical: 6,
    paddingHorizontal: 12,
  },
  pillTxt: { color: colors.ink, fontSize: 13, fontWeight: '700' },
  iconBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  iconTxt: { fontSize: 18 },
  demoBanner: {
    alignSelf: 'center',
    marginTop: 4,
    backgroundColor: 'rgba(245,179,1,0.14)',
    borderColor: 'rgba(245,179,1,0.4)',
    borderWidth: 1,
    borderRadius: radius.pill,
    paddingVertical: 5,
    paddingHorizontal: 12,
  },
  demoTxt: { color: colors.gold, fontSize: 12, fontWeight: '600' },

  fab: {
    position: 'absolute',
    right: 16,
    bottom: 28,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: colors.brand,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 6,
    shadowColor: '#000',
    shadowOpacity: 0.4,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
  },
  fabSecondary: {
    bottom: 96,
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
  },
  fabActive: { backgroundColor: colors.gold },

  pin: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: colors.brand,
    borderColor: '#fff',
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pinDone: { backgroundColor: colors.gold },
  pinTxt: { color: '#fff', fontSize: 15, fontWeight: '800' },
  meDot: {
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: colors.blue,
    borderColor: '#fff',
    borderWidth: 3,
  },

  backdrop: { flex: 1, backgroundColor: 'rgba(4,8,16,0.6)' },
  sheet: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: colors.panel,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    borderTopColor: colors.line,
    borderTopWidth: 1,
    padding: 18,
    paddingBottom: 34,
  },
  grabber: {
    width: 44,
    height: 5,
    borderRadius: 3,
    backgroundColor: colors.line,
    alignSelf: 'center',
    marginBottom: 12,
  },
  cat: {
    color: colors.brand,
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  qTitle: { color: colors.ink, fontSize: 22, fontWeight: '800', marginTop: 6 },
  qDesc: { color: colors.muted, fontSize: 14, lineHeight: 21, marginTop: 6 },
  metaRow: { flexDirection: 'row', gap: 22, marginTop: 16, marginBottom: 16 },
  metaLbl: { color: colors.muted, fontSize: 12 },
  metaVal: { color: colors.ink, fontSize: 15, fontWeight: '700', marginTop: 2 },
  actBtn: {
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    paddingVertical: 15,
    alignItems: 'center',
  },
  actGold: { backgroundColor: colors.gold },
  actTxt: { color: colors.brandInk, fontWeight: '800', fontSize: 16 },
  btnDisabled: { opacity: 0.6 },
  hint: { color: colors.muted, fontSize: 13, textAlign: 'center', marginTop: 10 },
});
