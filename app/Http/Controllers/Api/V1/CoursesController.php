<?php

namespace App\Http\Controllers\Api\V1;

use App\AppCourse;
use App\AppCourseEnrollment;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints autenticados para inscripción a cursos (app Celfix Socios).
 * El contact se resuelve desde $request->attributes->api_customer
 * (lo pone la middleware AuthCustomerApi).
 */
class CoursesController extends Controller
{
    private const BUSINESS_ID = 2;

    /**
     * POST /api/v1/courses/{id}/enroll
     * Inscribe al cliente autenticado en el curso.
     *
     * Reglas:
     *   - El curso debe existir, estar activo y no haber empezado.
     *   - No debe haberse llenado la capacidad.
     *   - No se puede inscribir dos veces (respuesta 200 idempotente).
     */
    public function enroll(Request $request, $id): JsonResponse
    {
        $c = $request->attributes->get('api_customer');

        $course = AppCourse::where('business_id', self::BUSINESS_ID)
            ->where('id', $id)
            ->where('is_active', 1)
            ->first();

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'El curso no existe o no está disponible.'], 404);
        }

        if ($course->starts_at && $course->starts_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Este curso ya inició, no se aceptan más inscripciones.'], 422);
        }

        // Idempotente: si ya estaba inscrito, devuelve success sin volver a insertar.
        $existing = AppCourseEnrollment::where('course_id', $course->id)
            ->where('contact_id', $c->id)
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Ya estás inscrito en este curso.',
                'already_enrolled' => true,
            ]);
        }

        // Chequeo de capacidad (race condition mitigada por UNIQUE key: dos requests
        // simultáneos podrían pasar este chequeo pero solo uno logra insertar).
        if ((int) $course->capacity > 0) {
            $count = AppCourseEnrollment::where('course_id', $course->id)->count();
            if ($count >= (int) $course->capacity) {
                return response()->json(['success' => false, 'message' => 'Este curso ya está lleno.'], 409);
            }
        }

        try {
            AppCourseEnrollment::create([
                'course_id' => $course->id,
                'contact_id' => $c->id,
                'enrolled_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Cae aquí si otro request ganó la carrera con la misma UNIQUE key.
            return response()->json(['success' => false, 'message' => 'No se pudo inscribir. Intenta de nuevo.'], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscripción confirmada. ¡Nos vemos en el curso!',
        ]);
    }

    /**
     * DELETE /api/v1/courses/{id}/enroll
     * Cancela la inscripción del cliente autenticado. Solo permitido si el curso
     * no ha empezado.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $c = $request->attributes->get('api_customer');

        $course = AppCourse::where('business_id', self::BUSINESS_ID)->find($id);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'El curso no existe.'], 404);
        }

        if ($course->starts_at && $course->starts_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'No puedes cancelar un curso que ya inició.'], 422);
        }

        $enrollment = AppCourseEnrollment::where('course_id', $course->id)
            ->where('contact_id', $c->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['success' => false, 'message' => 'No estás inscrito en este curso.'], 404);
        }

        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscripción cancelada.',
        ]);
    }

    /**
     * GET /api/v1/me/courses
     * Devuelve los cursos en los que el cliente autenticado está inscrito.
     * Por default solo trae los que aún no terminan; ?include_past=1 para incluir históricos.
     */
    public function mine(Request $request): JsonResponse
    {
        $c = $request->attributes->get('api_customer');
        $include_past = (int) $request->query('include_past', 0) === 1;

        $q = AppCourse::where('business_id', self::BUSINESS_ID)
            ->whereIn('id', function ($sub) use ($c) {
                $sub->select('course_id')
                    ->from('app_course_enrollments')
                    ->where('contact_id', $c->id);
            })
            ->withCount('enrollments');

        if (!$include_past) {
            $q->where('ends_at', '>=', now());
        }

        $courses = $q->orderBy('starts_at')->get();

        $data = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'instructor_name' => $course->instructor_name,
                'image_url' => $course->image_path ? asset('storage/' . $course->image_path) : null,
                'target_location_id' => $course->target_location_id,
                'starts_at' => $course->starts_at ? $course->starts_at->toIso8601String() : null,
                'ends_at' => $course->ends_at ? $course->ends_at->toIso8601String() : null,
                'has_started' => $course->starts_at && $course->starts_at->isPast(),
                'has_ended' => $course->ends_at && $course->ends_at->isPast(),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }
}
