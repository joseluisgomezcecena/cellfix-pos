<?php

namespace App\Http\Controllers\AppConfig;

use App\Contact;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Administración de membresías premium desde el POS.
 *
 * Una membresía premium dura 1 año a partir de la fecha en que se activa.
 * Los clientes premium ven promos/beneficios marcados como is_premium sin
 * el overlay de "Paga tu suscripción para acceder".
 *
 * El estado se guarda en contacts.membership_expires_at (DATE, NULL).
 * - NULL o fecha < hoy  → no premium
 * - fecha >= hoy        → premium activo
 */
class MembershipController extends Controller
{
    /** Duración estándar de la membresía premium en meses. */
    private const DURATION_MONTHS = 12;

    private function guard()
    {
        if (! auth()->user()->can('business_settings.access')
            && ! auth()->user()->can('celfix.app_config.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $today = now()->toDateString();

        $active = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNotNull('membership_expires_at')
            ->whereDate('membership_expires_at', '>=', $today)
            ->orderBy('membership_expires_at')
            ->get();

        $expired = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNotNull('membership_expires_at')
            ->whereDate('membership_expires_at', '<', $today)
            ->orderByDesc('membership_expires_at')
            ->limit(50)
            ->get();

        return view('app_config.memberships.index', compact('active', 'expired'));
    }

    /**
     * AJAX: busca clientes por nombre / mobile / membership_no.
     * Devuelve máximo 20 resultados con datos mínimos para autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $q = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where(function ($x) use ($term) {
                $x->where('name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%")
                    ->orWhere('membership_no', 'like', "%{$term}%");
            })
            ->limit(20);

        $today = now()->toDateString();
        $data = $q->get()->map(function ($c) use ($today) {
            $expires = $c->membership_expires_at
                ? Carbon::parse($c->membership_expires_at)->toDateString()
                : null;
            $isPremium = $expires !== null && $expires >= $today;
            return [
                'id' => $c->id,
                'name' => trim(($c->name ?? '') ?: (($c->first_name ?? '') . ' ' . ($c->last_name ?? ''))),
                'mobile' => $c->mobile,
                'membership_no' => $c->membership_no,
                'membership_expires_at' => $expires,
                'is_premium' => $isPremium,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Activa la membresía premium: fija expiración = hoy + DURATION_MONTHS.
     * Se usa para clientes que nunca han sido premium o cuya membresía ya expiró.
     */
    public function activate(Request $request, $id): JsonResponse
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        $contact->membership_expires_at = now()->addMonths(self::DURATION_MONTHS)->toDateString();
        $contact->save();

        return response()->json([
            'success' => 1,
            'msg' => 'Membresía activada hasta ' . $contact->membership_expires_at,
            'membership_expires_at' => $contact->membership_expires_at,
        ]);
    }

    /**
     * Renueva: si aún es premium, extiende desde su fecha actual;
     * si ya expiró, se comporta como activate (fecha = hoy + año).
     */
    public function renew(Request $request, $id): JsonResponse
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        $today = now()->toDateString();
        $base = ($contact->membership_expires_at && $contact->membership_expires_at >= $today)
            ? Carbon::parse($contact->membership_expires_at)
            : now();
        $contact->membership_expires_at = $base->addMonths(self::DURATION_MONTHS)->toDateString();
        $contact->save();

        return response()->json([
            'success' => 1,
            'msg' => 'Membresía renovada hasta ' . $contact->membership_expires_at,
            'membership_expires_at' => $contact->membership_expires_at,
        ]);
    }

    /**
     * Cancela la membresía inmediatamente (limpia la fecha de expiración).
     * El cliente pasa a no-premium en el momento — sus siguientes requests
     * a /me devolverán is_premium = false.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $this->guard();
        $business_id = $request->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        $contact->membership_expires_at = null;
        $contact->save();

        return response()->json([
            'success' => 1,
            'msg' => 'Membresía cancelada.',
        ]);
    }
}
