<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\GeoPoint;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Cast for PostGIS geography(Point, 4326) columns.
 *
 * - get(): parses whatever PostgreSQL returned for the column into a GeoPoint.
 *   A plain `SELECT *` returns the value as EWKB hex (e.g. "0101000020E6100000…");
 *   raw selects in the app may instead return EWKT ("SRID=4326;POINT(lng lat)")
 *   or GeoJSON. All three shapes are handled.
 * - set(): accepts a GeoPoint or ['lat'=>..,'lng'=>..] and returns a raw
 *   `ST_GeogFromText('SRID=4326;POINT(lng lat)')` expression so PostGIS stores a
 *   proper geography. Coordinates come from floats only — never raw user strings —
 *   so inlining them in SQL is safe.
 *
 * @implements CastsAttributes<GeoPoint|null, GeoPoint|array{lat: float, lng: float}|null>
 */
final class AsGeographyPoint implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?GeoPoint
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof GeoPoint) {
            return $value;
        }

        if ($value instanceof Expression) {
            // Value was just set() this request and not yet round-tripped to DB.
            return null;
        }

        if (is_array($value)) {
            return GeoPoint::fromArray($value);
        }

        $value = (string) $value;

        // GeoJSON: {"type":"Point","coordinates":[lng,lat]}
        if (str_starts_with(ltrim($value), '{')) {
            $json = json_decode($value, true);
            if (isset($json['coordinates'][0], $json['coordinates'][1])) {
                return new GeoPoint((float) $json['coordinates'][1], (float) $json['coordinates'][0]);
            }
        }

        // EWKT / WKT: "SRID=4326;POINT(lng lat)" or "POINT(lng lat)"
        if (preg_match('/POINT\s*\(\s*([\-0-9.eE]+)\s+([\-0-9.eE]+)\s*\)/i', $value, $m) === 1) {
            return new GeoPoint((float) $m[2], (float) $m[1]);
        }

        // EWKB hex (PostGIS default text form of geography in SELECT *)
        if (preg_match('/^[0-9A-Fa-f]+$/', $value) === 1) {
            $point = self::parseEwkbHex($value);
            if ($point !== null) {
                return $point;
            }
        }

        return null;
    }

    /**
     * @return array<string, Expression>|array<string, null>
     */
    public function set($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        $point = $value instanceof GeoPoint ? $value : GeoPoint::fromArray((array) $value);

        return [
            $key => DB::raw(sprintf("ST_GeogFromText('SRID=4326;%s')", $point->toWkt())),
        ];
    }

    /**
     * Parse a 2D point EWKB hex string into a GeoPoint. Handles byte order and
     * the optional SRID flag; ignores Z/M since our columns are 2D.
     */
    private static function parseEwkbHex(string $hex): ?GeoPoint
    {
        $bytes = @hex2bin($hex);
        if ($bytes === false || strlen($bytes) < 21) {
            return null;
        }

        $order = ord($bytes[0]);            // 0 = big endian, 1 = little endian
        $little = $order === 1;
        $u32 = fn (string $s) => unpack($little ? 'V' : 'N', $s)[1];
        $double = fn (string $s) => unpack($little ? 'e' : 'G', $s)[1];

        $type = $u32(substr($bytes, 1, 4));
        $offset = 5;

        // High bit 0x20000000 signals an embedded SRID (skip its 4 bytes).
        if (($type & 0x20000000) !== 0) {
            $offset += 4;
        }

        if (strlen($bytes) < $offset + 16) {
            return null;
        }

        $lng = $double(substr($bytes, $offset, 8));
        $lat = $double(substr($bytes, $offset + 8, 8));

        return new GeoPoint($lat, $lng);
    }
}
