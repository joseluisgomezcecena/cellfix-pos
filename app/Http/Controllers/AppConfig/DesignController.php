<?php

namespace App\Http\Controllers\AppConfig;

use App\AppDesign;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin: Diseños visuales de la app Celfix Socios.
 *
 * Se accede desde el sidebar Admin → App Config → Diseños. Permite subir/reemplazar
 * imágenes que consume la app Flutter (por ejemplo el fondo de la tarjeta de
 * membresía). Las claves soportadas están declaradas en AppDesign::KEYS.
 */
class DesignController extends Controller
{
    private const BUSINESS_ID_FALLBACK = 2;
    private const STORAGE_DIR = 'app_designs';

    private function canUse(): bool
    {
        $u = auth()->user();
        return $u->can('business_settings.access')
            || $u->can('superadmin')
            || $u->can('celfix.app_config.access');
    }

    public function index(Request $request)
    {
        if (!$this->canUse()) abort(403);

        $business_id = $request->session()->get('user.business_id', self::BUSINESS_ID_FALLBACK);

        // Cargar todos los diseños ya guardados y indexar por key
        $existing = AppDesign::where('business_id', $business_id)->get()->keyBy('design_key');

        // Estructura para la vista: cada key definida en AppDesign::KEYS con su
        // registro actual (o null si aún no se ha subido nada).
        $designs = [];
        foreach (AppDesign::KEYS as $key => $meta) {
            $designs[$key] = [
                'key' => $key,
                'meta' => $meta,
                'record' => $existing->get($key),
            ];
        }

        return view('app_config.designs.index', compact('designs'));
    }

    public function update(Request $request, string $key)
    {
        if (!$this->canUse()) abort(403);

        // Validar que la key esté definida (evita crear diseños arbitrarios)
        if (!array_key_exists($key, AppDesign::KEYS)) {
            return back()->with('status', ['success' => 0, 'msg' => 'Diseño no válido.']);
        }

        $spec = AppDesign::KEYS[$key];
        $business_id = $request->session()->get('user.business_id', self::BUSINESS_ID_FALLBACK);

        if (!$request->hasFile('image')) {
            return back()->with('status', ['success' => 0, 'msg' => 'Debes elegir una imagen.']);
        }
        $file = $request->file('image');
        if (!$file->isValid()) {
            return back()->with('status', ['success' => 0, 'msg' => 'La imagen no llegó completa.']);
        }
        if ($file->getSize() > $spec['max_bytes']) {
            $mb = round($spec['max_bytes'] / 1024 / 1024, 1);
            return back()->with('status', ['success' => 0, 'msg' => "La imagen supera el máximo permitido ({$mb} MB)."]);
        }
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            return back()->with('status', ['success' => 0, 'msg' => 'Formato no soportado. Usa JPG, PNG o WEBP.']);
        }

        // Guardar archivo con nombre único (evita cache del CDN/browser)
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = self::STORAGE_DIR . '/' . $key . '_' . Str::random(8) . '.' . strtolower($ext);
        $stored = Storage::disk('public')->putFileAs(
            self::STORAGE_DIR,
            $file,
            basename($filename)
        );
        if (!$stored) {
            return back()->with('status', ['success' => 0, 'msg' => 'No pudimos guardar la imagen.']);
        }

        // Upsert del registro; guarda dimensiones si podemos leerlas
        $meta = null;
        try {
            $info = @getimagesize($file->getRealPath());
            if ($info) {
                $meta = ['width' => $info[0], 'height' => $info[1]];
            }
        } catch (\Throwable $e) { /* no crítico */ }

        $design = AppDesign::updateOrCreate(
            ['business_id' => $business_id, 'design_key' => $key],
            [
                'image_path' => $filename,
                'metadata' => $meta,
                'updated_by' => auth()->id(),
            ]
        );

        // Borrar imagen anterior si había una distinta
        // (upsert reemplaza image_path; recuperamos la anterior antes del save)
        $old_path = $request->input('_previous_path');
        if ($old_path && $old_path !== $filename && Storage::disk('public')->exists($old_path)) {
            try { Storage::disk('public')->delete($old_path); }
            catch (\Throwable $e) { \Log::info('[app-design] no borró anterior: ' . $e->getMessage()); }
        }

        return back()->with('status', [
            'success' => 1,
            'msg' => 'Imagen actualizada. La app la reflejará al refrescar.',
        ]);
    }

    public function destroy(Request $request, string $key)
    {
        if (!$this->canUse()) abort(403);
        if (!array_key_exists($key, AppDesign::KEYS)) abort(404);

        $business_id = $request->session()->get('user.business_id', self::BUSINESS_ID_FALLBACK);
        $design = AppDesign::where('business_id', $business_id)
            ->where('design_key', $key)
            ->first();

        if ($design) {
            if ($design->image_path && Storage::disk('public')->exists($design->image_path)) {
                try { Storage::disk('public')->delete($design->image_path); }
                catch (\Throwable $e) { \Log::info('[app-design] no borró: ' . $e->getMessage()); }
            }
            $design->delete();
        }

        return back()->with('status', ['success' => 1, 'msg' => 'Imagen eliminada.']);
    }
}
