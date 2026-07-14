# Generar el APK instalable (Android) con EAS Build

Esto crea un **APK** que instalas directamente en tu móvil (Android 13 ✅), sin
depender de Expo Go. La compilación ocurre en la **nube de Expo** (este servidor
no tiene Android SDK), así que necesitas una cuenta de Expo (gratis).

Los comandos se ejecutan desde `mobile/`:

```bash
cd /var/www/QuestMap/mobile
```

---

## Paso 1 — Cuenta de Expo (gratis)

Crea una en https://expo.dev/signup si no tienes. Luego inicia sesión:

```bash
npx eas-cli login
```

## Paso 2 — API key de Google Maps (obligatorio para el mapa en Android)

En un APK propio, `react-native-maps` en Android **necesita** una API key de Google
(Expo Go traía una incluida; una build independiente no). Es gratis:

1. Ve a https://console.cloud.google.com/ → crea un proyecto (o usa uno).
2. **APIs y servicios → Biblioteca** → busca **"Maps SDK for Android"** → **Habilitar**.
3. **APIs y servicios → Credenciales → Crear credenciales → Clave de API**.
4. Copia la clave y pégala en `app.json`, sustituyendo el marcador:

   ```jsonc
   // app.json → expo.android.config.googleMaps.apiKey
   "apiKey": "AIza......tu-clave"
   ```

> Para empezar puedes dejar la clave **sin restricciones** (funciona al instante).
> Para restringirla luego a esta app: en la clave elige "Apps de Android" y añade el
> nombre de paquete `es.nekoserver.questmap` + la huella **SHA-1** de tu keystore de
> EAS (la ves con `npx eas-cli credentials`).

## Paso 3 — Vincular el proyecto a tu cuenta

```bash
npx eas-cli init
```

Esto crea el proyecto en tu cuenta y añade `extra.eas.projectId` a `app.json`.

## Paso 4 — Compilar el APK

```bash
npx eas-cli build --platform android --profile preview
```

- El perfil `preview` (ya configurado en `eas.json`) produce un **APK** de
  distribución interna.
- La primera vez, EAS te ofrece **generar un keystore automáticamente**: acepta
  (responde *Yes*). No necesitas nada más.
- La build entra en cola y tarda ~10–20 min. Al terminar te da una **URL** con el
  APK (también aparece en https://expo.dev → tu proyecto → Builds).

## Paso 5 — Instalar en el móvil

1. Abre esa URL en el móvil (o escanea el QR que muestra EAS) y descarga el `.apk`.
2. Ándroid te pedirá permitir **"Instalar apps de fuentes desconocidas"** para el
   navegador/archivos → acéptalo.
3. Instala y abre **QuestMap**.

Ya está: la app apunta al backend público `https://questmap.nekoserver.es/api`,
así que funciona con cualquier red. Inicia sesión con `alba@questmap.test` /
`password`, o usa el **modo demo 🧭** si no estás en A Coruña.

---

## Alternativa rápida (sin APK): Expo Go en SDK 56

Si solo quieres enseñarlo sin instalar nada, el proyecto ya está en **SDK 56**,
compatible con la Expo Go del Play Store:

```bash
npx expo start -c    # escanea el QR con Expo Go
```

## Notas

- **Actualizar la app luego**: repite el Paso 4 (sube `version`/`versionCode` con
  `autoIncrement` si usas el perfil `production`). Para el `preview` basta reinstalar
  el nuevo APK encima.
- **iOS**: `--platform ios` requiere cuenta de Apple Developer de pago para instalar
  en un dispositivo físico; en Android no hace falta nada de eso.
- **Build local** (sin nube) sería `eas build --local`, pero necesita Android SDK +
  Java instalados; en este VPS no están, por eso usamos la nube.
