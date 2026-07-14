# QuestMap — App móvil (Expo / React Native)

Frontend nativo de QuestMap. Consume la API pública del backend Laravel en
**`https://questmap.nekoserver.es/api`** (configurable en `app.json` →
`expo.extra.apiBaseUrl`).

## Requisitos

- Node 20+ (probado con Node 22).
- La app **Expo Go** en tu móvil:
  - iOS: App Store → "Expo Go".
  - Android: Play Store → "Expo Go".

## Ejecutar (para probar / enseñar)

```bash
cd mobile
npm install          # solo la primera vez
npx expo start       # abre el bundler + muestra un QR
```

Escanea el QR:
- **iOS**: con la app **Cámara** → abre en Expo Go.
- **Android**: desde la propia app **Expo Go** → "Scan QR code".

Como la app apunta al **dominio público HTTPS**, tu móvil no necesita estar en la
misma red que el servidor: funciona con datos móviles o cualquier WiFi.

### Iniciar sesión

Usuarios de prueba (contraseña `password`):
`alba@questmap.test`, `brais@…`, `carmela@…`, `diego@…`, `uxia@…`.
También puedes registrarte con una cuenta nueva desde la propia app.

### Probar sin estar en A Coruña (modo demo 🧭)

Las misiones están en A Coruña. Si no estás físicamente allí:

1. Pulsa el botón **🧭** (esquina inferior derecha) para activar el **modo demo**.
2. **Toca el mapa** para "teletransportarte" por la ciudad — se recargan las
   misiones cercanas.
3. Abre un pin y haz **check-in** o **foto**. En modo demo, la app envía las
   coordenadas de la propia misión para pasar el geofence del servidor
   (simulando que has caminado hasta el punto).

Con el botón **📍** usas tu **ubicación real** (GPS) — el flujo de producción.

## Pantallas

- **Login / Registro** — auth con token (Sanctum), guardado en AsyncStorage.
- **Mapa** — `react-native-maps` con los pines de misiones; check-in y foto
  (cámara con `expo-image-picker`); geolocalización con `expo-location`.
  - **Navegación a tu próximo punto**: en el detalle de una misión pulsa
    "🎯 Ir aquí (fijar destino)". Aparece una línea al destino en el mapa y un
    **banner con brújula** que muestra distancia + una **flecha que apunta hacia
    dónde ir** (usa el sensor de rumbo del móvil, como Google Maps).
- **🏆 Clasificación** — ranking por semana / mes / global.
- **👤 Perfil** — nivel, barra de progreso de XP, historial de "mis misiones".
  - **✏️ Editar perfil** — cambiar nombre, email y **contraseña**.
  - **👥 Amigos** — añadir por email, aceptar solicitudes y **ranking de amigos**
    (compara tus puntos con los suyos, tú incluido).

## Estructura

```
mobile/
  App.tsx                  # providers + navegación + gate de sesión
  app.json                 # config Expo, permisos, apiBaseUrl
  src/
    api/client.ts          # cliente fetch + token (AsyncStorage)
    api/types.ts           # tipos de la API
    auth/AuthContext.tsx   # estado de sesión
    components/Toast.tsx    # feedback (éxito/error)
    navigation/types.ts
    screens/                # Login, Map, Leaderboard, Profile
    theme/                  # colores y constantes
```

## Notas

- **Mapas en Android**: en Expo Go funcionan directamente. Para una build
  independiente (EAS) necesitarás una API key de Google Maps en `app.json`.
- **Cambiar de servidor**: edita `expo.extra.apiBaseUrl` en `app.json` (p. ej.
  `http://TU_IP:8090/api` para apuntar al backend local en tu red).
- **Expo SDK 54** (React Native 0.81). Se fijó a 54 para coincidir con la versión
  de la app Expo Go instalada (v54.x); un SDK más nuevo daría "incompatible".
- Verificado: `expo-doctor` 21/21, `npx tsc --noEmit` sin errores y `npx expo export`
  bundlea correctamente.
