import React, {
  createContext,
  useCallback,
  useContext,
  useRef,
  useState,
} from 'react';
import { Animated, StyleSheet, Text } from 'react-native';
import { colors } from '../theme';

type Kind = 'info' | 'ok' | 'err';

interface ToastState {
  show: (message: string, kind?: Kind) => void;
}

const ToastContext = createContext<ToastState | undefined>(undefined);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [msg, setMsg] = useState('');
  const [kind, setKind] = useState<Kind>('info');
  const opacity = useRef(new Animated.Value(0)).current;
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const show = useCallback(
    (message: string, k: Kind = 'info') => {
      setMsg(message);
      setKind(k);
      if (timer.current) clearTimeout(timer.current);
      Animated.timing(opacity, {
        toValue: 1,
        duration: 180,
        useNativeDriver: true,
      }).start();
      timer.current = setTimeout(() => {
        Animated.timing(opacity, {
          toValue: 0,
          duration: 250,
          useNativeDriver: true,
        }).start();
      }, 2800);
    },
    [opacity]
  );

  const borderColor =
    kind === 'ok' ? colors.ok : kind === 'err' ? colors.danger : colors.line;

  return (
    <ToastContext.Provider value={{ show }}>
      {children}
      <Animated.View
        pointerEvents="none"
        style={[styles.toast, { opacity, borderColor }]}
      >
        <Text style={styles.text}>{msg}</Text>
      </Animated.View>
    </ToastContext.Provider>
  );
}

export function useToast(): ToastState {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return ctx;
}

const styles = StyleSheet.create({
  toast: {
    position: 'absolute',
    bottom: 120,
    alignSelf: 'center',
    maxWidth: '90%',
    backgroundColor: colors.panel2,
    borderWidth: 1,
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 18,
  },
  text: {
    color: colors.ink,
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
});
