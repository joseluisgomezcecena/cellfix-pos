<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Foto de perfil del cliente autenticado (app Celfix Socios).
 *
 * Endpoints:
 *   POST   /api/v1/me/photo   — sube nueva foto (multipart, field 'photo')
 *   DELETE /api/v1/me/photo   — borra la foto actual
 *
 * Storage:
 *   Archivo físico: storage/app/public/customer_photos/<contact_id>_<hash>.jpg
 *   URL pública:    /storage/customer_photos/<contact_id>_<hash>.jpg
 *   Guardado en:    contacts.photo_path (VARCHAR — path relativo al disk 'public')
 *
 * Restricciones:
 *   - Max 5 MB
 *   - Formatos: jpg, jpeg, png, webp
 *   - Resize server-side a max 800x800 (proporcional) con GD nativo
 *   - Se convierte siempre a JPEG (uniformidad, menor tamaño)
 *   - Al subir nueva se borra la anterior automáticamente
 */
class PhotoController extends Controller
{
    private const MAX_BYTES = 5 * 1024 * 1024;  // 5 MB
    private const MAX_DIMENSION = 800;
    private const JPEG_QUALITY = 85;
    private const STORAGE_DIR = 'customer_photos';
    private const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

    /**
     * POST /api/v1/me/photo  (protected)
     * Body: multipart/form-data con field 'photo'
     */
    public function upload(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');

        if (!$request->hasFile('photo')) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar una imagen en el campo "photo".',
            ], 422);
        }

        $file = $request->file('photo');

        // Validación básica: existencia y upload OK
        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'La imagen no llegó completa. Intenta de nuevo.',
            ], 422);
        }

        // Tamaño
        if ($file->getSize() > self::MAX_BYTES) {
            return response()->json([
                'success' => false,
                'message' => 'La imagen es muy grande (máximo 5 MB).',
            ], 422);
        }

        // MIME type
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Formato no soportado. Usa JPG, PNG o WEBP.',
            ], 422);
        }

        // Procesar con GD: resize + convert to JPEG
        try {
            $jpeg_data = $this->processImage($file->getRealPath(), $mime);
        } catch (\Throwable $e) {
            \Log::warning('[app:photo] procesamiento falló para contact_id=' . $contact->id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No pudimos procesar la imagen. Intenta con otra.',
            ], 500);
        }

        // Nombre único (evita cache antiguo y colisiones)
        $filename = self::STORAGE_DIR . '/' . $contact->id . '_' . Str::random(10) . '.jpg';

        // Guardar (Storage::put en el disk 'public' crea la carpeta si no existe)
        $ok = Storage::disk('public')->put($filename, $jpeg_data);
        if (!$ok) {
            \Log::error('[app:photo] Storage::put falló para contact_id=' . $contact->id);
            return response()->json([
                'success' => false,
                'message' => 'No pudimos guardar la imagen. Intenta más tarde.',
            ], 500);
        }

        // Borrar la anterior (si existía) — DESPUÉS de guardar la nueva
        // para no dejar al cliente sin foto si algo falla en medio.
        $old_path = $contact->photo_path;
        $contact->photo_path = $filename;
        $contact->saveQuietly();

        if ($old_path && Storage::disk('public')->exists($old_path)) {
            try {
                Storage::disk('public')->delete($old_path);
            } catch (\Throwable $e) {
                // No es crítico. Solo log.
                \Log::info('[app:photo] no se pudo borrar foto anterior ' . $old_path . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Foto de perfil actualizada.',
            'photo_url' => self::urlFor($filename),
        ]);
    }

    /**
     * DELETE /api/v1/me/photo  (protected)
     * Borra la foto actual del cliente (archivo + campo en BD).
     */
    public function destroy(Request $request): JsonResponse
    {
        $contact = $request->attributes->get('api_customer');

        if (empty($contact->photo_path)) {
            return response()->json([
                'success' => true,
                'message' => 'No hay foto de perfil que borrar.',
                'photo_url' => null,
            ]);
        }

        $path = $contact->photo_path;
        $contact->photo_path = null;
        $contact->saveQuietly();

        if (Storage::disk('public')->exists($path)) {
            try {
                Storage::disk('public')->delete($path);
            } catch (\Throwable $e) {
                \Log::warning('[app:photo] no se pudo borrar ' . $path . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Foto de perfil eliminada.',
            'photo_url' => null,
        ]);
    }

    /**
     * Procesa la imagen: la abre con GD según su MIME, la resize proporcional
     * a MAX_DIMENSION, y devuelve los bytes JPEG resultantes.
     * Lanza \RuntimeException si algo falla.
     */
    private function processImage(string $path, string $mime): string
    {
        // Abrir según formato
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $img = @imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $img = @imagecreatefrompng($path);
                break;
            case 'image/webp':
                $img = @imagecreatefromwebp($path);
                break;
            default:
                throw new \RuntimeException("MIME no soportado: {$mime}");
        }

        if (!$img) {
            throw new \RuntimeException('imagecreatefrom* devolvió false');
        }

        // Corregir orientación por EXIF (fotos de móvil vienen rotadas)
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $img = $this->fixOrientation($img, $path);
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Resize proporcional si excede
        if ($w > self::MAX_DIMENSION || $h > self::MAX_DIMENSION) {
            if ($w >= $h) {
                $new_w = self::MAX_DIMENSION;
                $new_h = (int) round($h * (self::MAX_DIMENSION / $w));
            } else {
                $new_h = self::MAX_DIMENSION;
                $new_w = (int) round($w * (self::MAX_DIMENSION / $h));
            }
            $resized = imagecreatetruecolor($new_w, $new_h);
            // Fondo blanco (por si el png tiene transparencia)
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }

        // Exportar a JPEG a un buffer en memoria
        ob_start();
        imagejpeg($img, null, self::JPEG_QUALITY);
        $jpeg_data = ob_get_clean();
        imagedestroy($img);

        if ($jpeg_data === false || strlen($jpeg_data) === 0) {
            throw new \RuntimeException('imagejpeg produjo buffer vacío');
        }

        return $jpeg_data;
    }

    /**
     * Rota la imagen según el EXIF Orientation (fotos de celular vienen
     * "acostadas" y hay que enderezarlas antes de resize).
     */
    private function fixOrientation($img, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }
        try {
            $exif = @exif_read_data($path);
            if (!$exif || !isset($exif['Orientation'])) return $img;
            switch ($exif['Orientation']) {
                case 3: return imagerotate($img, 180, 0);
                case 6: return imagerotate($img, -90, 0);
                case 8: return imagerotate($img,  90, 0);
                default: return $img;
            }
        } catch (\Throwable $e) {
            return $img;
        }
    }

    /**
     * Construye la URL pública para una foto guardada. Usa asset() para
     * respetar el APP_URL de configuración (importante en prod detrás de
     * proxy/HTTPS).
     */
    public static function urlFor(?string $path): ?string
    {
        if (empty($path)) return null;
        return asset('storage/' . $path);
    }
}
