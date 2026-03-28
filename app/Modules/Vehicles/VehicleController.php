<?php

declare(strict_types=1);

namespace App\Modules\Vehicles;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;
use App\Core\Support\Input;
use App\Modules\Attachments\AttachmentRepository;
use App\Modules\Accidents\AccidentRepository;
use App\Modules\Lookup\LookupRepository;

final class VehicleController extends Controller
{
    private VehicleRepository $vehicles;
    private AccidentRepository $accidents;
    private LookupRepository $lookups;
    private AttachmentRepository $attachments;

    public function __construct()
    {
        $pdo = $this->db();
        $this->vehicles = new VehicleRepository($pdo);
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

        $response->view('vehicles/create', [
            'title' => 'Νέο Όχημα',
            'accident' => $accident,
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
        $errors = $this->validateVehicleInput($input);
        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα οχήματος.');
            $response->redirect(url('/accidents/' . $accidentId . '/vehicles/create'));
            return;
        }

        $vehicleId = $this->vehicles->create($accidentId, $this->buildPayload($input), (string) $user['id']);
        $this->audit()->log('vehicle.create', 'vehicle', $vehicleId, 'Δημιουργήθηκε εγγραφή οχήματος.');

        Flash::set('success', 'Το όχημα αποθηκεύτηκε επιτυχώς.');
        $response->redirect(url('/accidents/' . $accidentId));
    }

    public function show(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $vehicleId = (string) $request->route('id');
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null) {
            $response->view('errors/404', ['title' => 'Το όχημα δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $vehicle['accident_id'], $user);
        if ($accident === null || !$this->canViewAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('vehicles/show', [
            'title' => 'Προβολή Οχήματος',
            'vehicle' => $vehicle,
            'accident' => $accident,
            'lookup' => $this->formLookupData(),
            'attachments' => $this->attachments->listForVehicle($vehicleId),
            'canEdit' => $this->canModifyAccident($user, $accident),
        ]);
    }

    public function edit(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $vehicleId = (string) $request->route('id');
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null) {
            $response->view('errors/404', ['title' => 'Το όχημα δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $vehicle['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('vehicles/edit', [
            'title' => 'Επεξεργασία Οχήματος',
            'vehicle' => $vehicle,
            'accident' => $accident,
            'lookup' => $this->formLookupData(),
            'editing' => true,
            'attachments' => $this->attachments->listForVehicle($vehicleId),
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $vehicleId = (string) $request->route('id');
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null) {
            $response->view('errors/404', ['title' => 'Το όχημα δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $vehicle['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = $request->body();
        $errors = $this->validateVehicleInput($input);
        if ($errors !== []) {
            Flash::setErrors($errors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα οχήματος.');
            $response->redirect(url('/vehicles/' . $vehicleId . '/edit'));
            return;
        }

        $this->vehicles->update($vehicleId, $this->buildPayload($input), (string) $user['id']);
        $this->audit()->log('vehicle.update', 'vehicle', $vehicleId, 'Ενημερώθηκε εγγραφή οχήματος.');

        Flash::set('success', 'Τα στοιχεία οχήματος ενημερώθηκαν.');
        $response->redirect(url('/accidents/' . $accident['id']));
    }

    public function destroy(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $vehicleId = (string) $request->route('id');
        $vehicle = $this->vehicles->findById($vehicleId);

        if ($vehicle === null) {
            $response->view('errors/404', ['title' => 'Το όχημα δεν βρέθηκε'], 404);
            return;
        }

        $accident = $this->accidents->findById((string) $vehicle['accident_id'], $user);
        if ($accident === null || !$this->canModifyAccident($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $this->vehicles->softDelete($vehicleId, (string) $user['id']);
        $this->audit()->log('vehicle.delete', 'vehicle', $vehicleId, 'Η εγγραφή οχήματος διαγράφηκε λογικά.');

        Flash::set('success', 'Το όχημα διαγράφηκε.');
        $response->redirect(url('/accidents/' . $accident['id']));
    }

    /** @param array<string, mixed> $input */
    private function validateVehicleInput(array $input): array
    {
        $errors = [];

        $plateNumber = trim((string) ($input['plate_number'] ?? ''));
        if ($plateNumber !== '' && (mb_strlen($plateNumber) > 20 || preg_match('/^[\p{L}\p{N}\s\-]+$/u', $plateNumber) !== 1)) {
            $errors['plate_number'] = 'Η πινακίδα δεν είναι έγκυρη.';
        }

        $intRanges = [
            'length_mm' => [0, 50000, 'Το μήκος δεν είναι έγκυρο.'],
            'width_mm' => [0, 10000, 'Το πλάτος δεν είναι έγκυρο.'],
            'engine_power_kw' => [0, 4000, 'Η ισχύς κινητήρα δεν είναι έγκυρη.'],
            'manufacturing_year' => [1900, 2100, 'Το έτος κατασκευής δεν είναι έγκυρο.'],
            'curb_weight_kg' => [0, 200000, 'Το βάρος δεν είναι έγκυρο.'],
            'axles_count' => [0, 20, 'Ο αριθμός αξόνων δεν είναι έγκυρος.'],
            'passengers_count' => [0, 500, 'Ο αριθμός επιβαινόντων δεν είναι έγκυρος.'],
            'collisions_count' => [0, 20, 'Το πλήθος συγκρούσεων δεν είναι έγκυρο.'],
        ];

        foreach ($intRanges as $field => [$min, $max, $message]) {
            $value = Input::nullableInt($input[$field] ?? null);
            if ($value !== null && ($value < $min || $value > $max)) {
                $errors[$field] = $message;
            }
        }

        $lookupMap = [
            'vehicle_type_lookup_id' => 'vehicle_type',
            'vehicle_color_lookup_id' => 'vehicle_color',
            'drive_wheels_lookup_id' => 'vehicle_drive_wheels',
            'steering_position_lookup_id' => 'vehicle_drive_position',
            'road_alignment_lookup_id' => 'vehicle_roadway_alignment',
            'towing_lookup_id' => 'vehicle_trailer',
            'defects_caused_lookup_id' => 'vehicle_damage_possible_factor',
            'technical_inspection_passed_lookup_id' => 'vehicle_inspected',
            'maneuver_before_accident_lookup_id' => 'vehicle_swerved',
            'dangerous_load_lookup_id' => 'vehicle_dangerous_cargo',
            'dangerous_load_dispersion_lookup_id' => 'vehicle_scattered_dangerous_cargo',
            'cdc3_lookup_id' => 'vehicle_cdc3',
            'cdc4_lookup_id' => 'vehicle_cdc4',
            'on_fire_lookup_id' => 'vehicle_on_fire',
            'firefighting_material_used_lookup_id' => 'vehicle_firefighting_equipment_used',
            'collision_offroad_object_lookup_id' => 'vehicle_collision_offroad_object',
            'collision_type_lookup_id' => 'vehicle_collision_type',
            'abs_lookup_id' => 'vehicle_abs',
            'esp_lookup_id' => 'vehicle_esp',
            'tcs_lookup_id' => 'vehicle_tcs',
            'acs_lookup_id' => 'vehicle_acs',
            'ldw_lookup_id' => 'vehicle_ldw',
            'css_lookup_id' => 'vehicle_css',
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

        $makeId = Input::nullableInt($input['vehicle_make_id'] ?? null);
        if ($makeId !== null && !$this->manufacturerExists($makeId)) {
            $errors['vehicle_make_id'] = 'Ο κατασκευαστής δεν είναι έγκυρος.';
        }

        $modelId = Input::nullableInt($input['vehicle_model_id'] ?? null);
        if ($modelId !== null && !$this->modelExistsForManufacturer($modelId, $makeId)) {
            $errors['vehicle_model_id'] = 'Το μοντέλο δεν αντιστοιχεί σε έγκυρο κατασκευαστή.';
        }

        return $errors;
    }

    /** @param array<string, mixed> $input */
    private function buildPayload(array $input): array
    {
        return [
            ':plate_number' => Input::nullableString($input['plate_number'] ?? null),
            ':vehicle_type_lookup_id' => Input::nullableInt($input['vehicle_type_lookup_id'] ?? null),
            ':vehicle_make_id' => Input::nullableInt($input['vehicle_make_id'] ?? null),
            ':vehicle_model_id' => Input::nullableInt($input['vehicle_model_id'] ?? null),
            ':vehicle_color_lookup_id' => Input::nullableInt($input['vehicle_color_lookup_id'] ?? null),
            ':drive_wheels_lookup_id' => Input::nullableInt($input['drive_wheels_lookup_id'] ?? null),
            ':steering_position_lookup_id' => Input::nullableInt($input['steering_position_lookup_id'] ?? null),
            ':length_mm' => Input::nullableInt($input['length_mm'] ?? null),
            ':width_mm' => Input::nullableInt($input['width_mm'] ?? null),
            ':road_alignment_lookup_id' => Input::nullableInt($input['road_alignment_lookup_id'] ?? null),
            ':towing_lookup_id' => Input::nullableInt($input['towing_lookup_id'] ?? null),
            ':engine_power_kw' => Input::nullableInt($input['engine_power_kw'] ?? null),
            ':manufacturing_year' => Input::nullableInt($input['manufacturing_year'] ?? null),
            ':curb_weight_kg' => Input::nullableInt($input['curb_weight_kg'] ?? null),
            ':axles_count' => Input::nullableInt($input['axles_count'] ?? null),
            ':general_comments' => Input::nullableString($input['general_comments'] ?? null),
            ':passengers_count' => Input::nullableInt($input['passengers_count'] ?? null),
            ':defects_caused_lookup_id' => Input::nullableInt($input['defects_caused_lookup_id'] ?? null),
            ':defects_comments' => Input::nullableString($input['defects_comments'] ?? null),
            ':technical_inspection_passed_lookup_id' => Input::nullableInt($input['technical_inspection_passed_lookup_id'] ?? null),
            ':maneuver_before_accident_lookup_id' => Input::nullableInt($input['maneuver_before_accident_lookup_id'] ?? null),
            ':dangerous_load_lookup_id' => Input::nullableInt($input['dangerous_load_lookup_id'] ?? null),
            ':dangerous_load_dispersion_lookup_id' => Input::nullableInt($input['dangerous_load_dispersion_lookup_id'] ?? null),
            ':collisions_count' => Input::nullableInt($input['collisions_count'] ?? null),
            ':damage_comments' => Input::nullableString($input['damage_comments'] ?? null),
            ':cdc3_lookup_id' => Input::nullableInt($input['cdc3_lookup_id'] ?? null),
            ':cdc4_lookup_id' => Input::nullableInt($input['cdc4_lookup_id'] ?? null),
            ':on_fire_lookup_id' => Input::nullableInt($input['on_fire_lookup_id'] ?? null),
            ':firefighting_material_used_lookup_id' => Input::nullableInt($input['firefighting_material_used_lookup_id'] ?? null),
            ':collision_offroad_object_lookup_id' => Input::nullableInt($input['collision_offroad_object_lookup_id'] ?? null),
            ':collision_type_lookup_id' => Input::nullableInt($input['collision_type_lookup_id'] ?? null),
            ':abs_lookup_id' => Input::nullableInt($input['abs_lookup_id'] ?? null),
            ':esp_lookup_id' => Input::nullableInt($input['esp_lookup_id'] ?? null),
            ':tcs_lookup_id' => Input::nullableInt($input['tcs_lookup_id'] ?? null),
            ':acs_lookup_id' => Input::nullableInt($input['acs_lookup_id'] ?? null),
            ':ldw_lookup_id' => Input::nullableInt($input['ldw_lookup_id'] ?? null),
            ':css_lookup_id' => Input::nullableInt($input['css_lookup_id'] ?? null),
            ':safety_systems_comments' => Input::nullableString($input['safety_systems_comments'] ?? null),
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
            'vehicle_type' => $this->lookups->options('vehicle_type'),
            'vehicle_color' => $this->lookups->options('vehicle_color'),
            'drive_wheels' => $this->lookups->options('vehicle_drive_wheels'),
            'steering_position' => $this->lookups->options('vehicle_drive_position'),
            'road_alignment' => $this->lookups->options('vehicle_roadway_alignment'),
            'towing' => $this->lookups->options('vehicle_trailer'),
            'defects_caused' => $this->lookups->options('vehicle_damage_possible_factor'),
            'technical_inspection' => $this->lookups->options('vehicle_inspected'),
            'maneuver' => $this->lookups->options('vehicle_swerved'),
            'dangerous_load' => $this->lookups->options('vehicle_dangerous_cargo'),
            'dangerous_load_dispersion' => $this->lookups->options('vehicle_scattered_dangerous_cargo'),
            'cdc3' => $this->lookups->options('vehicle_cdc3'),
            'cdc4' => $this->lookups->options('vehicle_cdc4'),
            'on_fire' => $this->lookups->options('vehicle_on_fire'),
            'firefighting_material' => $this->lookups->options('vehicle_firefighting_equipment_used'),
            'collision_offroad_object' => $this->lookups->options('vehicle_collision_offroad_object'),
            'collision_type' => $this->lookups->options('vehicle_collision_type'),
            'abs' => $this->lookups->options('vehicle_abs'),
            'esp' => $this->lookups->options('vehicle_esp'),
            'tcs' => $this->lookups->options('vehicle_tcs'),
            'acs' => $this->lookups->options('vehicle_acs'),
            'ldw' => $this->lookups->options('vehicle_ldw'),
            'css' => $this->lookups->options('vehicle_css'),
            'information_source' => $this->lookups->options('information_source'),
            'confidence_level' => $this->lookups->options('confidence_level'),
            'investigation_method' => $this->lookups->options('investigation_method'),
            'investigation_confidence' => $this->lookups->options('investigation_confidence_level'),
            'manufacturers' => $this->manufacturerOptions(),
            'models' => $this->modelOptions(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function manufacturerOptions(): array
    {
        $stmt = $this->db()->query('SELECT id, name AS label_el FROM vehicle_manufacturers WHERE is_active = TRUE ORDER BY name ASC');

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    private function modelOptions(): array
    {
        $stmt = $this->db()->query('SELECT id, manufacturer_id, name AS label_el FROM vehicle_models WHERE is_active = TRUE ORDER BY name ASC');

        return $stmt->fetchAll() ?: [];
    }

    private function manufacturerExists(int $manufacturerId): bool
    {
        $stmt = $this->db()->prepare('SELECT EXISTS(SELECT 1 FROM vehicle_manufacturers WHERE id = :id AND is_active = TRUE)');
        $stmt->execute([':id' => $manufacturerId]);

        return (bool) $stmt->fetchColumn();
    }

    private function modelExistsForManufacturer(int $modelId, ?int $manufacturerId): bool
    {
        $sql = 'SELECT EXISTS(SELECT 1 FROM vehicle_models WHERE id = :id AND is_active = TRUE';
        $params = [':id' => $modelId];

        if ($manufacturerId !== null) {
            $sql .= ' AND manufacturer_id = :manufacturer_id';
            $params[':manufacturer_id'] = $manufacturerId;
        }

        $sql .= ')';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
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
