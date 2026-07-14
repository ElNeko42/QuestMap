import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import React, { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
} from 'react-native';
import { ApiError, authApi } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/Toast';
import type { RootStackParamList } from '../navigation/types';
import { colors, radius } from '../theme';

type Props = NativeStackScreenProps<RootStackParamList, 'EditProfile'>;

export default function EditProfileScreen({ navigation }: Props) {
  const { user, setUser } = useAuth();
  const toast = useToast();

  const [name, setName] = useState(user?.name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [savingProfile, setSavingProfile] = useState(false);

  const [current, setCurrent] = useState('');
  const [next, setNext] = useState('');
  const [savingPass, setSavingPass] = useState(false);

  const saveProfile = async () => {
    setSavingProfile(true);
    try {
      const updated = await authApi.updateProfile({ name: name.trim(), email: email.trim() });
      setUser(updated);
      toast.show('Perfil actualizado', 'ok');
      navigation.goBack();
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setSavingProfile(false);
    }
  };

  const savePassword = async () => {
    if (next.length < 8) {
      toast.show('La nueva contraseña debe tener al menos 8 caracteres.', 'err');
      return;
    }
    setSavingPass(true);
    try {
      await authApi.updatePassword(current, next);
      setCurrent('');
      setNext('');
      toast.show('Contraseña actualizada', 'ok');
    } catch (e) {
      toast.show(e instanceof ApiError ? e.message : 'Error', 'err');
    } finally {
      setSavingPass(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={{ padding: 18 }} keyboardShouldPersistTaps="handled">
        <Text style={styles.section}>Datos</Text>
        <Text style={styles.label}>Nombre</Text>
        <TextInput style={styles.input} value={name} onChangeText={setName} placeholderTextColor={colors.muted} />
        <Text style={styles.label}>Email</Text>
        <TextInput
          style={styles.input}
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          keyboardType="email-address"
          placeholderTextColor={colors.muted}
        />
        <TouchableOpacity
          style={[styles.btn, savingProfile && styles.disabled]}
          onPress={saveProfile}
          disabled={savingProfile}
        >
          {savingProfile ? (
            <ActivityIndicator color={colors.brandInk} />
          ) : (
            <Text style={styles.btnTxt}>Guardar cambios</Text>
          )}
        </TouchableOpacity>

        <Text style={[styles.section, { marginTop: 30 }]}>Cambiar contraseña</Text>
        <Text style={styles.label}>Contraseña actual</Text>
        <TextInput
          style={styles.input}
          value={current}
          onChangeText={setCurrent}
          secureTextEntry
          placeholder="········"
          placeholderTextColor={colors.muted}
        />
        <Text style={styles.label}>Nueva contraseña (mín. 8)</Text>
        <TextInput
          style={styles.input}
          value={next}
          onChangeText={setNext}
          secureTextEntry
          placeholder="········"
          placeholderTextColor={colors.muted}
        />
        <TouchableOpacity
          style={[styles.btn, styles.ghost, savingPass && styles.disabled]}
          onPress={savePassword}
          disabled={savingPass}
        >
          {savingPass ? (
            <ActivityIndicator color={colors.ink} />
          ) : (
            <Text style={[styles.btnTxt, { color: colors.ink }]}>Actualizar contraseña</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },
  section: { color: colors.ink, fontSize: 17, fontWeight: '800', marginBottom: 6 },
  label: { color: colors.muted, fontSize: 13, fontWeight: '600', marginTop: 12, marginBottom: 6 },
  input: {
    backgroundColor: colors.panel2,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.md,
    paddingVertical: 13,
    paddingHorizontal: 14,
    color: colors.ink,
    fontSize: 15,
  },
  btn: {
    backgroundColor: colors.brand,
    borderRadius: radius.md,
    paddingVertical: 15,
    alignItems: 'center',
    marginTop: 20,
  },
  ghost: { backgroundColor: colors.panel2, borderColor: colors.line, borderWidth: 1 },
  disabled: { opacity: 0.6 },
  btnTxt: { color: colors.brandInk, fontWeight: '800', fontSize: 16 },
});
