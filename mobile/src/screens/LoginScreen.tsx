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
  View,
} from 'react-native';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { useToast } from '../components/Toast';
import { colors, radius } from '../theme';

export default function LoginScreen() {
  const { login, register } = useAuth();
  const toast = useToast();

  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [busy, setBusy] = useState(false);

  const [email, setEmail] = useState('alba@questmap.test');
  const [password, setPassword] = useState('password');
  const [name, setName] = useState('');

  const submit = async () => {
    setBusy(true);
    try {
      if (mode === 'login') {
        await login(email.trim(), password);
      } else {
        await register(name.trim(), email.trim(), password);
      }
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : 'Algo salió mal.';
      toast.show(msg, 'err');
    } finally {
      setBusy(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.flex}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView
        contentContainerStyle={styles.scroll}
        keyboardShouldPersistTaps="handled"
      >
        <View style={styles.card}>
          <Text style={styles.title}>
            Quest<Text style={{ color: colors.brand }}>Map</Text>
          </Text>
          <Text style={styles.sub}>Aventuras urbanas por A Coruña ✨</Text>

          {mode === 'register' && (
            <>
              <Text style={styles.label}>Nombre</Text>
              <TextInput
                style={styles.input}
                value={name}
                onChangeText={setName}
                placeholder="Tu nombre"
                placeholderTextColor={colors.muted}
              />
            </>
          )}

          <Text style={styles.label}>Email</Text>
          <TextInput
            style={styles.input}
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
            placeholder="tu@email.com"
            placeholderTextColor={colors.muted}
          />

          <Text style={styles.label}>
            Contraseña{mode === 'register' ? ' (mín. 8)' : ''}
          </Text>
          <TextInput
            style={styles.input}
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            placeholder="········"
            placeholderTextColor={colors.muted}
          />

          <TouchableOpacity
            style={[styles.btn, busy && styles.btnDisabled]}
            onPress={submit}
            disabled={busy}
          >
            {busy ? (
              <ActivityIndicator color={colors.brandInk} />
            ) : (
              <Text style={styles.btnText}>
                {mode === 'login' ? 'Entrar' : 'Crear cuenta'}
              </Text>
            )}
          </TouchableOpacity>

          <TouchableOpacity
            onPress={() => setMode(mode === 'login' ? 'register' : 'login')}
          >
            <Text style={styles.switch}>
              {mode === 'login'
                ? '¿Crear una cuenta nueva?'
                : '← Ya tengo cuenta'}
            </Text>
          </TouchableOpacity>

          {mode === 'login' && (
            <Text style={styles.seed}>
              Usuarios demo: alba, brais, carmela, diego, uxia @questmap.test
              {'\n'}contraseña: password
            </Text>
          )}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.bg },
  scroll: { flexGrow: 1, justifyContent: 'center', padding: 20 },
  card: {
    backgroundColor: colors.panel,
    borderColor: colors.line,
    borderWidth: 1,
    borderRadius: radius.lg,
    padding: 26,
  },
  title: { fontSize: 30, fontWeight: '800', color: colors.ink },
  sub: { color: colors.muted, marginTop: 4, marginBottom: 18, fontSize: 14 },
  label: {
    color: colors.muted,
    fontSize: 13,
    fontWeight: '600',
    marginTop: 12,
    marginBottom: 6,
  },
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
  btnDisabled: { opacity: 0.6 },
  btnText: { color: colors.brandInk, fontWeight: '800', fontSize: 16 },
  switch: {
    color: colors.brand,
    textAlign: 'center',
    marginTop: 16,
    fontWeight: '600',
  },
  seed: {
    color: colors.muted,
    fontSize: 12,
    textAlign: 'center',
    marginTop: 16,
    lineHeight: 18,
  },
});
