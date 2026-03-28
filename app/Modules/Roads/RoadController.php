<?php

declare(strict_types=1);

namespace App\Modules\Roads;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;
use App\Core\Support\Input;
use App\Modules\Attachments\AttachmentRepository;
use App\Modules\Accidents\AccidentRepository;
use App\Modules\Lookup\LookupRepository;

final class RoadController extends Controller
{
    private RoadRepository $roads;
    private AccidentRepository $accidents;
    private LookupRepository $lookups;
    private AttachmentRepository $attachments;

    public function __construct()
    {
        $pdo = $this->db();
        $this->roads = new RoadRepository($pdo);
        $this->accidents = new AccidentRepository($pdo);
        $this->lookups = new LookupRepository($pdo);
        $this->attachments = new AttachmentRepository($pdo);
    }

    public function create(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $accidentId = (string) $request->route('accident_id');
        $accident = $this->accidents->findById($accidentId, $user);

        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $existingRoads = $this->roads->listByAccident($accidentId);
        $nextOrder = count($existingRoads) + 1;
        if ($nextOrder > 2) {
            Flash::set('error', 'Έχουν ήδη συνδεθεί δύο δρόμοι στο ατύχημα.');
            $response->redirect(url('/accidents/' . $accidentId));
            return;
        }

        $response->view('roads/create', [
            'title' => 'Νέος Δρόμος',
            'accident' => $accident,
            'roadOrder' => $nextOrder,
            'lookup' => $this->formLookupData(),
            'editing' => false,
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $accidentId = (string) $request->route('accident_id');
        $accident = $this->accidents->findById($accidentId, $user);

        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = $request->body();
        $errors = $this->validateRoadInput($input);
        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα δρόμου.');
            $response->redirect(url('/accidents/' . $accidentId . '/roads/create'));
            return;
        }

        $roadOrder = max(1, min(2, (int) $request->input('road_order', 1)));
        $payload = $this->buildPayload($input);

        try {
            $roadId = $this->roads->create($accidentId, $roadOrder, $payload, (string) $user['id']);
        } catch (\Throwable) {
            Flash::set('error', 'Δεν ήταν δυνατή η αποθήκευση δρόμου. Ελέγξτε ότι δεν έχουν ήδη καταχωρηθεί δύο δρόμοι.');
            Flash::keepInput($input);
            $response->redirect(url('/accidents/' . $accidentId . '/roads/create'));
            return;
        }

        $this->audit()->log('road.create', 'road', $roadId, 'Δημιουργήθηκε εγγραφή δρόμου.');

        Flash::set('success', 'Ο δρόμος αποθηκεύτηκε επιτυχώς.');
        $response->redirect(url('/accidents/' . $accidentId));
    }

    public function show(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $roadId = (string) $request->route('id');
        $road = $this->roads->findById($roadId);

        if ($road === null) {
            $response->view('errors/404', ['title' => 'Ο δρόμος δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $road['accident_id'], $user);
        if ($accident === null || !$this->canViewAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('roads/show', [
            'title' => 'Προβολή Δρόμου',
            'road' => $road,
            'accident' => $accident,
            'roadOrder' => (int) $road['road_order'],
            'lookup' => $this->formLookupData(),
            'attachments' => $this->attachments->listForRoad($roadId),
            'canEdit' => $this->canModifyAccident($user, $accident),
        ]);
    }

    public function edit(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $roadId = (string) $request->route('id');
        $road = $this->roads->findById($roadId);

        if ($road === null) {
            $response->view('errors/404', ['title' => 'Ο δρόμος δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $road['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('roads/edit', [
            'title' => 'Επεξεργασία Δρόμου',
            'road' => $road,
            'accident' => $accident,
            'roadOrder' => (int) $road['road_order'],
            'lookup' => $this->formLookupData(),
            'editing' => true,
            'attachments' => $this->attachments->listForRoad($roadId),
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $roadId = (string) $request->route('id');
        $road = $this->roads->findById($roadId);

        if ($road === null) {
            $response->view('errors/404', ['title' => 'Ο δρόμος δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $road['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = $request->body();
        $errors = $this->validateRoadInput($input);
        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα δρόμου.');
            $response->redirect(url('/roads/' . $roadId . '/edit'));
            return;
        }

        $payload = $this->buildPayload($input);
        $this->roads->update($roadId, $payload, (string) $user['id']);
        $this->audit()->log('road.update', 'road', $roadId, 'Ενημερώθηκε εγγραφή δρόμου.');

        Flash::set('success', 'Τα στοιχεία δρόμου ενημερώθηκαν.');
        $response->redirect(url('/accidents/' . $accident['id']));
    }

    public function destroy(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $roadId = (string) $request->route('id');
        $road = $this->roads->findById($roadId);

        if ($road === null) {
            $response->view('errors/404', ['title' => 'Ο δρόμος δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $road['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $this->roads->softDelete($roadId, (string) $user['id']);
        $this->audit()->log('road.delete', 'road', $roadId, 'Η εγγραφή δρόμου διαγράφηκε λογικά.');

        Flash::set('success', 'Ο δρόμος διαγράφηκε.');
        $response->redirect(url('/accidents/' . $accident['id']));
    }

    /** @param array<string, mixed> $input */
    private function validateRoadInput(array $input): array
    {
        $errors = [];

        $intRanges = [
            'lanes_count' => [0, 20, 'Ο αριθμός λωρίδων δεν είναι έγκυρος.'],
            'speed_limit_kmh' => [0, 300, 'Το όριο ταχύτητας δεν είναι έγκυρο.'],
        ];

        foreach ($intRanges as $field => [$min, $max, $message]) {
            $value = Input::nullableInt($input[$field] ?? null);
            if ($value !== null && ($value < $min || $value > $max)) {
                $errors[$field] = $message;
            }
        }

        $lookupMap = [
            'traffic_flow_lookup_id' => 'road_trafficway_flow',
            'surface_type_lookup_id' => 'road_surface_type',
            'speed_limit_type_lookup_id' => 'road_speed_limit_type',
            'intersection_lookup_id' => 'road_junction',
            'local_area_lookup_id' => 'road_local_area',
            'road_alignment_lookup_id' => 'road_alignment',
            'construction_zone_lookup_id' => 'road_construction_zone',
            'traffic_control_signs_lookup_id' => 'road_traffic_signal_control',
            'traffic_signal_operation_lookup_id' => 'road_traffic_signal_device_functioning',
            'road_surface_condition_lookup_id' => 'road_surface_contaminents',
            'pedestrian_infrastructure_lookup_id' => 'road_pedestrian_facility',
            'bicycle_infrastructure_lookup_id' => 'road_cycle_facilities',
            'lighting_condition_lookup_id' => 'road_lighting_condition',
            'weather_condition_lookup_id' => 'road_weather_conditions',
            'strong_winds_lookup_id' => 'road_strong_winds',
            'fog_lookup_id' => 'road_fog',
            'road_defects_lookup_id' => 'road_surface',
            'temporary_factors_lookup_id' => 'road_transient_factors',
            'signaling_related_lookup_id' => 'road_signaling_factors',
            'speed_restriction_infrastructure_lookup_id' => 'road_speed_limiting_facility',
            'speed_restriction_contributed_lookup_id' => 'road_sli_contributed_collision',
            'information_source_lookup_id' => 'information_source',
            'confidence_level_lookup_id' => 'confidence_level',
            'investigation_method_lookup_id' => 'investigation_method',
            'investigation_confidence_lookup_id' => 'investigation_confidence_level',
        ];

        foreach ($lookupMap as $field => $domainCode) {
            $lookupId = Input::nullableInt($input[$field] ?? null);
            if ($lookupId !== null && !$this->lookups->isValueInDomain($lookupId, $domainCode)) {
                $errors[$field] = 'Επιλέξτε έγκυρη τιμή από τη λίστα.';
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $input */
    private function buildPayload(array $input): array
    {
        return [
            ':traffic_flow_lookup_id' => Input::nullableInt($input['traffic_flow_lookup_id'] ?? null),
            ':lanes_count' => Input::nullableInt($input['lanes_count'] ?? null),
            ':surface_type_lookup_id' => Input::nullableInt($input['surface_type_lookup_id'] ?? null),
            ':speed_limit_kmh' => Input::nullableInt($input['speed_limit_kmh'] ?? null),
            ':speed_limit_type_lookup_id' => Input::nullableInt($input['speed_limit_type_lookup_id'] ?? null),
            ':intersection_lookup_id' => Input::nullableInt($input['intersection_lookup_id'] ?? null),
            ':local_area_lookup_id' => Input::nullableInt($input['local_area_lookup_id'] ?? null),
            ':road_alignment_lookup_id' => Input::nullableInt($input['road_alignment_lookup_id'] ?? null),
            ':construction_zone_lookup_id' => Input::nullableInt($input['construction_zone_lookup_id'] ?? null),
            ':traffic_control_signs_lookup_id' => Input::nullableInt($input['traffic_control_signs_lookup_id'] ?? null),
            ':traffic_signal_operation_lookup_id' => Input::nullableInt($input['traffic_signal_operation_lookup_id'] ?? null),
            ':road_surface_condition_lookup_id' => Input::nullableInt($input['road_surface_condition_lookup_id'] ?? null),
            ':pedestrian_infrastructure_lookup_id' => Input::nullableInt($input['pedestrian_infrastructure_lookup_id'] ?? null),
            ':bicycle_infrastructure_lookup_id' => Input::nullableInt($input['bicycle_infrastructure_lookup_id'] ?? null),
            ':lighting_condition_lookup_id' => Input::nullableInt($input['lighting_condition_lookup_id'] ?? null),
            ':weather_condition_lookup_id' => Input::nullableInt($input['weather_condition_lookup_id'] ?? null),
            ':strong_winds_lookup_id' => Input::nullableInt($input['strong_winds_lookup_id'] ?? null),
            ':fog_lookup_id' => Input::nullableInt($input['fog_lookup_id'] ?? null),
            ':conditions_comments' => Input::nullableString($input['conditions_comments'] ?? null),
            ':road_defects_lookup_id' => Input::nullableInt($input['road_defects_lookup_id'] ?? null),
            ':temporary_factors_lookup_id' => Input::nullableInt($input['temporary_factors_lookup_id'] ?? null),
            ':signaling_related_lookup_id' => Input::nullableInt($input['signaling_related_lookup_id'] ?? null),
            ':speed_restriction_infrastructure_lookup_id' => Input::nullableInt($input['speed_restriction_infrastructure_lookup_id'] ?? null),
            ':speed_restriction_contributed_lookup_id' => Input::nullableInt($input['speed_restriction_contributed_lookup_id'] ?? null),
            ':possible_causes_comments' => Input::nullableString($input['possible_causes_comments'] ?? null),
            ':additional_comments' => Input::nullableString($input['additional_comments'] ?? null),
            ':information_source_lookup_id' => Input::nullableInt($input['information_source_lookup_id'] ?? null),
            ':confidence_level_lookup_id' => Input::nullableInt($input['confidence_level_lookup_id'] ?? null),
            ':confidence_description' => Input::nullableString($input['confidence_description'] ?? null),
            ':investigation_method_lookup_id' => Input::nullableInt($input['investigation_method_lookup_id'] ?? null),
            ':investigation_confidence_lookup_id' => Input::nullableInt($input['investigation_confidence_lookup_id'] ?? null),
            ':investigation_confidence_description' => Input::nullableString($input['investigation_confidence_description'] ?? null),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function formLookupData(): array
    {
        return [
            'traffic_flow' => $this->lookups->options('road_trafficway_flow'),
            'surface_type' => $this->lookups->options('road_surface_type'),
            'speed_limit_type' => $this->lookups->options('road_speed_limit_type'),
            'intersection' => $this->lookups->options('road_junction'),
            'local_area' => $this->lookups->options('road_local_area'),
            'road_alignment' => $this->lookups->options('road_alignment'),
            'construction_zone' => $this->lookups->options('road_construction_zone'),
            'traffic_control_signs' => $this->lookups->options('road_traffic_signal_control'),
            'traffic_signal_operation' => $this->lookups->options('road_traffic_signal_device_functioning'),
            'road_surface_condition' => $this->lookups->options('road_surface_contaminents'),
            'pedestrian_infrastructure' => $this->lookups->options('road_pedestrian_facility'),
            'bicycle_infrastructure' => $this->lookups->options('road_cycle_facilities'),
            'lighting_condition' => $this->lookups->options('road_lighting_condition'),
            'weather_condition' => $this->lookups->options('road_weather_conditions'),
            'strong_winds' => $this->lookups->options('road_strong_winds'),
            'fog' => $this->lookups->options('road_fog'),
            'road_defects' => $this->lookups->options('road_surface'),
            'temporary_factors' => $this->lookups->options('road_transient_factors'),
            'signaling_related' => $this->lookups->options('road_signaling_factors'),
            'speed_restriction_infrastructure' => $this->lookups->options('road_speed_limiting_facility'),
            'speed_restriction_contributed' => $this->lookups->options('road_sli_contributed_collision'),
            'information_source' => $this->lookups->options('information_source'),
            'confidence_level' => $this->lookups->options('confidence_level'),
            'investigation_method' => $this->lookups->options('investigation_method'),
            'investigation_confidence' => $this->lookups->options('investigation_confidence_level'),
        ];
    }

    /** @return array<string, mixed> */
    private function requireUser(): array
    {
        $user = $this->auth()->user();
        if ($user === null) {
            throw new \RuntimeException('Μη έγκυρη συνεδρία χρήστη.');
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    /** @param array<string, mixed> $accident */
    private function canModifyAccident(array $user, array $accident): bool
    {
        $role = (string) ($user['role_code'] ?? '');
        if ($role === 'administrator') {
            return true;
        }

        return $role === 'registrar' && (string) $accident['created_by'] === (string) $user['id'];
    }

    /** @param array<string, mixed> $user */
    /** @param array<string, mixed> $accident */
    private function canViewAccident(array $user, array $accident): bool
    {
        $role = (string) ($user['role_code'] ?? '');
        if (in_array($role, ['administrator', 'expert'], true)) {
            return true;
        }

        return $role === 'registrar' && (string) $accident['created_by'] === (string) $user['id'];
    }
}
