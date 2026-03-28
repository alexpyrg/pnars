<?php

declare(strict_types=1);

namespace App\Modules\Accidents;

use App\Core\Http\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Support\Flash;
use App\Core\Support\Input;
use App\Core\Support\Validator;
use App\Modules\Attachments\AttachmentRepository;
use App\Modules\Flags\FlagRepository;
use App\Modules\Lookup\LookupRepository;
use App\Modules\Roads\RoadRepository;
use App\Modules\Vehicles\VehicleRepository;

final class AccidentController extends Controller
{
    private AccidentRepository $accidents;
    private LookupRepository $lookups;
    private RoadRepository $roads;
    private VehicleRepository $vehicles;
    private AttachmentRepository $attachments;
    private FlagRepository $flags;

    public function __construct()
    {
        $pdo = $this->db();
        $this->accidents = new AccidentRepository($pdo);
        $this->lookups = new LookupRepository($pdo);
        $this->roads = new RoadRepository($pdo);
        $this->vehicles = new VehicleRepository($pdo);
        $this->attachments = new AttachmentRepository($pdo);
        $this->flags = new FlagRepository($pdo);
    }

    public function index(Request $request, Response $response): void
    {
        $user = $this->requireUser();

        $filters = [
            'status_id' => $request->input('status_id'),
            'flagged' => $request->input('flagged'),
            'creator_id' => $request->input('creator_id'),
            'date_from' => $this->validDateFilter($request->input('date_from')),
            'date_to' => $this->validDateFilter($request->input('date_to')),
            'accident_date' => $this->validDateFilter($request->input('accident_date')),
            'severity_id' => $request->input('severity_id'),
            'vehicle_type_id' => $request->input('vehicle_type_id'),
            'plate_number' => $request->input('plate_number'),
            'has_coordinates' => $request->input('has_coordinates'),
            'entry_completed' => $request->input('entry_completed'),
            'information_source_id' => $request->input('information_source_id'),
            'confidence_level_id' => $request->input('confidence_level_id'),
            'case_number' => $request->input('case_number'),
            'q' => $request->input('q'),
        ];

        $response->view('accidents/index', [
            'title' => 'Ατυχήματα',
            'filters' => $filters,
            'statusOptions' => $this->lookups->options('accident_status'),
            'severityOptions' => $this->lookups->options('accident_severity'),
            'vehicleTypeOptions' => $this->lookups->options('vehicle_type'),
            'sourceOptions' => $this->lookups->options('information_source'),
            'confidenceOptions' => $this->lookups->options('confidence_level'),
            'isRegistrar' => ($user['role_code'] ?? '') === 'registrar',
            'currentUser' => $user,
        ]);
    }

    public function datatable(Request $request, Response $response): void
    {
        $user = $this->requireUser();

        $filters = [
            'status_id' => $request->input('status_id'),
            'flagged' => $request->input('flagged'),
            'creator_id' => $request->input('creator_id'),
            'date_from' => $this->validDateFilter($request->input('date_from')),
            'date_to' => $this->validDateFilter($request->input('date_to')),
            'accident_date' => $this->validDateFilter($request->input('accident_date')),
            'severity_id' => $request->input('severity_id'),
            'vehicle_type_id' => $request->input('vehicle_type_id'),
            'plate_number' => $request->input('plate_number'),
            'has_coordinates' => $request->input('has_coordinates'),
            'entry_completed' => $request->input('entry_completed'),
            'information_source_id' => $request->input('information_source_id'),
            'confidence_level_id' => $request->input('confidence_level_id'),
            'case_number' => $request->input('case_number'),
            'q' => $request->input('q'),
        ];

        $draw = max(0, (int) $request->input('draw', 0));
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 20);
        if ($length <= 0) {
            $length = 20;
        }

        $order = $request->input('order', []);
        $orderColumn = (int) ($order[0]['column'] ?? 1);
        $orderDirection = (string) ($order[0]['dir'] ?? 'desc');

        $search = $request->input('search', []);
        $globalSearch = trim((string) ($search['value'] ?? ''));

        $result = $this->accidents->searchForDataTable(
            $filters,
            $user,
            $start,
            $length,
            $orderColumn,
            $orderDirection,
            $globalSearch
        );

        $items = [];
        foreach ($result['items'] as $row) {
            $canEdit = $this->canEdit($user, ['created_by' => (string) $row['created_by']]);

            $items[] = [
                'id' => (string) $row['id'],
                'case_number' => (string) $row['case_number'],
                'accident_datetime' => (string) $row['accident_datetime'],
                'status_label' => (string) ($row['status_label'] ?? '-'),
                'severity_label' => (string) ($row['severity_label'] ?? '-'),
                'creator_name' => (string) ($row['creator_name'] ?? '-'),
                'plate_numbers' => (string) ($row['plate_numbers'] ?? '-'),
                'has_open_flag' => (bool) $row['has_open_flag'],
                'show_url' => url('/accidents/' . $row['id']),
                'edit_url' => url('/accidents/' . $row['id'] . '/edit'),
                'can_edit' => $canEdit,
            ];
        }

        $response->json([
            'draw' => $draw,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $items,
        ]);
    }

    public function create(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        if (!$this->canCreate($user)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('accidents/create', [
            'title' => 'Νέο Ατύχημα',
            'lookup' => $this->formLookupData(),
            'editing' => false,
            'factorIds' => [],
            'participantCounts' => [],
        ]);
    }

    public function store(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        if (!$this->canCreate($user)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = $request->body();
        $validationErrors = $this->validateAccidentInput($input);

        if ($validationErrors !== []) {
            Flash::setErrors($validationErrors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα ατυχήματος.');
            $response->redirect(url('/accidents/create'));
            return;
        }

        $draftStatus = $this->lookups->idByCode('accident_status', 'draft');
        if ($draftStatus === null) {
            Flash::set('error', 'Δεν βρέθηκε η αρχική κατάσταση εγγραφής.');
            Flash::keepInput($input);
            $response->redirect(url('/accidents/create'));
            return;
        }

        $payload = $this->buildPayload($input, $draftStatus);
        $db = $this->db();

        try {
            $db->beginTransaction();

            $accidentId = $this->accidents->create($payload, (string) $user['id']);

            $factorIds = array_map('intval', (array) ($input['factor_ids'] ?? []));
            $this->accidents->syncFactors($accidentId, $factorIds, (string) $user['id']);

            $participantCounts = $this->extractParticipantCounts((array) ($input['participant_counts'] ?? []));
            $this->accidents->syncParticipantCounts($accidentId, $participantCounts);

            $this->audit()->log('accident.create', 'accident', $accidentId, 'Δημιουργήθηκε νέο ατύχημα.');

            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            Flash::set('error', 'Η αποθήκευση απέτυχε. Παρακαλώ δοκιμάστε ξανά.');
            Flash::keepInput($input);
            $response->redirect(url('/accidents/create'));
            return;
        }

        Flash::set('success', 'Η εγγραφή ατυχήματος δημιουργήθηκε επιτυχώς.');
        $response->redirect(url('/accidents/' . $accidentId));
    }

    public function show(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $id = (string) $request->route('id');

        $accident = $this->accidents->findById($id, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        $response->view('accidents/show', [
            'title' => 'Λεπτομέρειες Ατυχήματος',
            'accident' => $accident,
            'roads' => $this->roads->listByAccident($id),
            'vehicles' => $this->vehicles->listByAccident($id),
            'attachments' => $this->attachments->listForAccident($id),
            'flags' => $this->flags->listByAccident($id),
            'canEdit' => $this->canEdit($user, $accident),
            'canDelete' => $this->canDelete($user, $accident),
            'canFlag' => in_array($user['role_code'] ?? '', ['expert', 'administrator'], true),
            'canResolveFlag' => ($user['role_code'] ?? '') === 'administrator',
            'statusOptions' => $this->lookups->options('accident_status'),
            'flagTypeOptions' => $this->lookups->options('flag_type'),
        ]);
    }

    public function edit(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $id = (string) $request->route('id');

        $accident = $this->accidents->findById($id, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canEdit($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $response->view('accidents/edit', [
            'title' => 'Επεξεργασία Ατυχήματος',
            'accident' => $accident,
            'lookup' => $this->formLookupData(),
            'editing' => true,
            'factorIds' => $this->accidents->factorIds($id),
            'participantCounts' => $this->accidents->participantCounts($id),
        ]);
    }

    public function update(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $id = (string) $request->route('id');

        $accident = $this->accidents->findById($id, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canEdit($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $input = $request->body();
        $validationErrors = $this->validateAccidentInput($input);

        $oldStatus = (int) $accident['status_lookup_id'];
        $statusInput = Input::nullableInt($input['status_lookup_id'] ?? null);
        $newStatus = $statusInput ?? $oldStatus;

        if ($statusInput !== null && !$this->lookups->isValueInDomain($statusInput, 'accident_status')) {
            $validationErrors['status_lookup_id'] = 'Επιλέξτε έγκυρη κατάσταση.';
        }

        $statusTransitionError = $this->validateStatusTransition($user, $oldStatus, $newStatus);
        if ($statusTransitionError !== null) {
            $validationErrors['status_lookup_id'] = $statusTransitionError;
        }

        if ($validationErrors !== []) {
            Flash::setErrors($validationErrors);
            Flash::keepInput($input);
            Flash::set('error', 'Παρακαλώ διορθώστε τα σφάλματα στη φόρμα ατυχήματος.');
            $response->redirect(url('/accidents/' . $id . '/edit'));
            return;
        }

        $payload = $this->buildPayload($input, $newStatus);
        $db = $this->db();

        try {
            $db->beginTransaction();

            $this->accidents->update($id, $payload, (string) $user['id']);

            $factorIds = array_map('intval', (array) ($input['factor_ids'] ?? []));
            $this->accidents->syncFactors($id, $factorIds, (string) $user['id']);

            $participantCounts = $this->extractParticipantCounts((array) ($input['participant_counts'] ?? []));
            $this->accidents->syncParticipantCounts($id, $participantCounts);

            if ($oldStatus !== $newStatus) {
                $this->accidents->addStatusHistory($id, $oldStatus, $newStatus, (string) $user['id'], 'Αλλαγή κατάστασης από φόρμα επεξεργασίας.');
                $this->audit()->log('accident.status_change', 'accident', $id, 'Αλλαγή κατάστασης ατυχήματος.');
            }

            $this->audit()->log('accident.update', 'accident', $id, 'Ενημερώθηκε εγγραφή ατυχήματος.');

            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            Flash::setErrors(['general' => 'Η ενημέρωση απέτυχε. Δοκιμάστε ξανά.']);
            Flash::keepInput($input);
            Flash::set('error', 'Η ενημέρωση της εγγραφής απέτυχε.');
            $response->redirect(url('/accidents/' . $id . '/edit'));
            return;
        }

        Flash::set('success', 'Η εγγραφή ατυχήματος ενημερώθηκε επιτυχώς.');
        $response->redirect(url('/accidents/' . $id));
    }

    public function destroy(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $id = (string) $request->route('id');

        $accident = $this->accidents->findById($id, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canDelete($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $this->accidents->softDelete($id, (string) $user['id']);
        $this->audit()->log('accident.delete', 'accident', $id, 'Η εγγραφή ατυχήματος διαγράφηκε λογικά.');

        Flash::set('success', 'Η εγγραφή ατυχήματος διαγράφηκε.');
        $response->redirect(url('/accidents'));
    }

    public function changeStatus(Request $request, Response $response): void
    {
        $user = $this->requireUser();
        $id = (string) $request->route('id');

        $accident = $this->accidents->findById($id, $user);
        if ($accident === null) {
            $response->view('errors/404', ['title' => 'Το ατύχημα δεν βρέθηκε'], 404);
            return;
        }

        if (!$this->canEdit($user, $accident)) {
            $response->view('errors/403', ['title' => 'Μη εξουσιοδοτημένη πρόσβαση'], 403);
            return;
        }

        $newStatusId = Input::nullableInt($request->input('status_lookup_id'));
        if ($newStatusId === null || !$this->lookups->isValueInDomain($newStatusId, 'accident_status')) {
            Flash::set('error', 'Επιλέξτε έγκυρη κατάσταση.');
            $response->redirect(url('/accidents/' . $id));
            return;
        }

        $oldStatus = (int) $accident['status_lookup_id'];
        $statusTransitionError = $this->validateStatusTransition($user, $oldStatus, $newStatusId);
        if ($statusTransitionError !== null) {
            Flash::set('error', $statusTransitionError);
            $response->redirect(url('/accidents/' . $id));
            return;
        }

        if ($oldStatus !== $newStatusId) {
            $db = $this->db();
            try {
                $db->beginTransaction();

                $note = Input::nullableString($request->input('status_note'));
                $this->accidents->setStatus($id, $newStatusId, (string) $user['id']);
                $this->accidents->addStatusHistory($id, $oldStatus, $newStatusId, (string) $user['id'], $note);
                $this->audit()->log('accident.status_change', 'accident', $id, 'Άλλαξε η κατάσταση ατυχήματος.');

                $db->commit();
            } catch (\Throwable) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                Flash::set('error', 'Η αλλαγή κατάστασης απέτυχε. Δοκιμάστε ξανά.');
                $response->redirect(url('/accidents/' . $id));
                return;
            }
        }

        Flash::set('success', 'Η κατάσταση ενημερώθηκε.');
        $response->redirect(url('/accidents/' . $id));
    }

    /** @param array<string, mixed> $input */
    private function validateAccidentInput(array $input): array
    {
        $validator = new Validator();

        $errors = $validator->validate($input, [
            'case_number' => ['required'],
            'accident_datetime' => ['required'],
        ]);

        if (($input['accident_datetime'] ?? '') !== '' && $this->normalizeDateTimeInput($input['accident_datetime']) === null) {
            $errors['accident_datetime'] = 'Η ημερομηνία/ώρα ατυχήματος δεν είναι έγκυρη.';
        }

        if (($input['expert_arrival_datetime'] ?? '') !== '' && $this->normalizeDateTimeInput($input['expert_arrival_datetime']) === null) {
            $errors['expert_arrival_datetime'] = 'Η ημερομηνία/ώρα άφιξης εμπειρογνώμονα δεν είναι έγκυρη.';
        }

        $lookupMap = [
            'accident_day_lookup_id' => 'accident_day',
            'severity_lookup_id' => 'accident_severity',
            'drugs_involved_lookup_id' => 'accident_narcotics',
            'alcohol_involved_lookup_id' => 'accident_alcohol',
            'hit_and_run_lookup_id' => 'accident_abandoned_victim',
            'animal_collision_lookup_id' => 'accident_animal',
            'gdv_type_lookup_id' => 'accident_gadas_sort',
            'gadas_type_lookup_id' => 'accident_gadas_sort',
            'sequence_of_events_lookup_id' => 'accident_events_sequence',
            'first_harmful_event_lookup_id' => 'accident_first_collision_event',
            'most_harmful_event_lookup_id' => 'accident_most_harmful_event',
            'information_source_lookup_id' => 'information_source',
            'confidence_level_lookup_id' => 'confidence_level',
            'investigation_method_lookup_id' => 'investigation_method',
            'investigation_confidence_lookup_id' => 'investigation_confidence_level',
        ];

        foreach ($lookupMap as $field => $domain) {
            $lookupId = Input::nullableInt($input[$field] ?? null);
            if ($lookupId !== null && !$this->lookups->isValueInDomain($lookupId, $domain)) {
                $errors[$field] = 'Επιλέξτε έγκυρη τιμή από τη λίστα.';
            }
        }

        foreach ((array) ($input['factor_ids'] ?? []) as $rawFactorId) {
            $factorId = (int) $rawFactorId;
            if ($factorId <= 0 || !$this->lookups->isValueInDomain($factorId, 'accident_related_factor')) {
                $errors['factor_ids'] = 'Υπάρχει μη έγκυρος παράγοντας ατυχήματος.';
                break;
            }
        }

        foreach (array_keys((array) ($input['participant_counts'] ?? [])) as $rawCategoryId) {
            $categoryId = (int) $rawCategoryId;
            if ($categoryId <= 0 || !$this->lookups->isValueInDomain($categoryId, 'participant_category')) {
                $errors['participant_counts'] = 'Υπάρχει μη έγκυρη κατηγορία συμμετεχόντων.';
                break;
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $input */
    private function buildPayload(array $input, ?int $statusId): array
    {
        $accidentDateTime = $this->normalizeDateTimeInput($input['accident_datetime'] ?? null);
        $expertArrivalDateTime = $this->normalizeDateTimeInput($input['expert_arrival_datetime'] ?? null);
        $sequenceLookupId = Input::nullableInt($input['sequence_of_events_lookup_id'] ?? null);
        $sequenceOfEvents = $this->lookups->labelById($sequenceLookupId) ?? Input::nullableString($input['sequence_of_events'] ?? null);

        return [
            ':case_number' => trim((string) ($input['case_number'] ?? '')),
            ':entry_completed' => Input::boolInt($input['entry_completed'] ?? null),
            ':accident_datetime' => $accidentDateTime ?? (string) ($input['accident_datetime'] ?? ''),
            ':accident_day_lookup_id' => Input::nullableInt($input['accident_day_lookup_id'] ?? null),
            ':expert_arrival_datetime' => $expertArrivalDateTime,
            ':longitude' => Input::nullableFloat($input['longitude'] ?? null),
            ':latitude' => Input::nullableFloat($input['latitude'] ?? null),
            ':incident_identifier' => Input::nullableString($input['incident_identifier'] ?? null),
            ':severity_lookup_id' => Input::nullableInt($input['severity_lookup_id'] ?? null),
            ':drugs_involved_lookup_id' => Input::nullableInt($input['drugs_involved_lookup_id'] ?? null),
            ':alcohol_involved_lookup_id' => Input::nullableInt($input['alcohol_involved_lookup_id'] ?? null),
            ':hit_and_run_lookup_id' => Input::nullableInt($input['hit_and_run_lookup_id'] ?? null),
            ':animal_collision_lookup_id' => Input::nullableInt($input['animal_collision_lookup_id'] ?? null),
            ':separate_events_count' => Input::nullableInt($input['separate_events_count'] ?? null) ?? 0,
            ':gdv_type_lookup_id' => Input::nullableInt($input['gdv_type_lookup_id'] ?? null),
            ':gadas_type_lookup_id' => Input::nullableInt($input['gadas_type_lookup_id'] ?? null),
            ':sequence_of_events' => $sequenceOfEvents,
            ':first_harmful_event_lookup_id' => Input::nullableInt($input['first_harmful_event_lookup_id'] ?? null),
            ':most_harmful_event_lookup_id' => Input::nullableInt($input['most_harmful_event_lookup_id'] ?? null),
            ':participants_total' => Input::nullableInt($input['participants_total'] ?? null) ?? 0,
            ':summary' => Input::nullableString($input['summary'] ?? null),
            ':information_source_lookup_id' => Input::nullableInt($input['information_source_lookup_id'] ?? null),
            ':confidence_level_lookup_id' => Input::nullableInt($input['confidence_level_lookup_id'] ?? null),
            ':confidence_description' => Input::nullableString($input['confidence_description'] ?? null),
            ':investigation_method_lookup_id' => Input::nullableInt($input['investigation_method_lookup_id'] ?? null),
            ':investigation_confidence_lookup_id' => Input::nullableInt($input['investigation_confidence_lookup_id'] ?? null),
            ':investigation_confidence_description' => Input::nullableString($input['investigation_confidence_description'] ?? null),
            ':status_lookup_id' => $statusId,
        ];
    }

    private function normalizeDateTimeInput(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $appTimezone = new \DateTimeZone((string) config('app.timezone', 'Europe/Athens'));

        if (preg_match('/^\d{13}$/', $raw) === 1) {
            $raw = (string) intdiv((int) $raw, 1000);
        }

        if (preg_match('/^\d{10}$/', $raw) === 1) {
            $dateTime = (new \DateTimeImmutable('@' . $raw))->setTimezone($appTimezone);

            return $dateTime->format('Y-m-d H:i:sP');
        }

        try {
            $dateTime = new \DateTimeImmutable($raw, $appTimezone);
        } catch (\Exception) {
            return null;
        }

        return $dateTime->setTimezone($appTimezone)->format('Y-m-d H:i:sP');
    }

    /** @param array<string, mixed> $rawCounts */
    private function extractParticipantCounts(array $rawCounts): array
    {
        $counts = [];

        foreach ($rawCounts as $categoryId => $count) {
            $categoryInt = (int) $categoryId;
            if ($categoryInt <= 0) {
                continue;
            }

            $counts[$categoryInt] = max(0, (int) $count);
        }

        return $counts;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function formLookupData(): array
    {
        return [
            'status' => $this->lookups->options('accident_status'),
            'days' => $this->lookups->options('accident_day'),
            'severity' => $this->lookups->options('accident_severity'),
            'narcotics' => $this->lookups->options('accident_narcotics'),
            'alcohol' => $this->lookups->options('accident_alcohol'),
            'hit_and_run' => $this->lookups->options('accident_abandoned_victim'),
            'animal' => $this->lookups->options('accident_animal'),
            'gdv' => $this->lookups->options('accident_gadas_sort'),
            'gadas' => $this->lookups->options('accident_gadas_sort'),
            'event_sequence' => $this->lookups->options('accident_events_sequence'),
            'first_harmful' => $this->lookups->options('accident_first_collision_event'),
            'most_harmful' => $this->lookups->options('accident_most_harmful_event'),
            'factors' => $this->lookups->options('accident_related_factor'),
            'participant_categories' => $this->lookups->options('participant_category'),
            'information_source' => $this->lookups->options('information_source'),
            'confidence_level' => $this->lookups->options('confidence_level'),
            'investigation_method' => $this->lookups->options('investigation_method'),
            'investigation_confidence' => $this->lookups->options('investigation_confidence_level'),
        ];
    }

    private function validDateFilter(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if ($normalized === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $normalized));

        return checkdate($month, $day, $year) ? $normalized : null;
    }

    /** @return array<string, mixed> */
    private function requireUser(): array
    {
        $user = $this->auth()->user();

        if ($user === null) {
            throw new \RuntimeException('Δεν βρέθηκε ενεργός χρήστης στη συνεδρία.');
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    private function canCreate(array $user): bool
    {
        return in_array($user['role_code'] ?? '', ['registrar', 'administrator'], true);
    }

    /** @param array<string, mixed> $user */
    /** @param array<string, mixed> $accident */
    private function canEdit(array $user, array $accident): bool
    {
        $role = (string) ($user['role_code'] ?? '');
        if ($role === 'administrator') {
            return true;
        }

        if ($role !== 'registrar') {
            return false;
        }

        return (string) $accident['created_by'] === (string) $user['id'];
    }

    /** @param array<string, mixed> $user */
    /** @param array<string, mixed> $accident */
    private function canDelete(array $user, array $accident): bool
    {
        $role = (string) ($user['role_code'] ?? '');
        if ($role === 'administrator') {
            return true;
        }

        if ($role !== 'registrar') {
            return false;
        }

        return (string) $accident['created_by'] === (string) $user['id']
            && in_array((string) ($accident['status_code'] ?? ''), ['draft'], true);
    }

    /** @param array<string, mixed> $user */
    private function validateStatusTransition(array $user, int $oldStatusId, int $newStatusId): ?string
    {
        if ($oldStatusId === $newStatusId) {
            return null;
        }

        $oldCode = $this->lookups->codeById($oldStatusId);
        $newCode = $this->lookups->codeById($newStatusId);

        if ($oldCode === null || $newCode === null) {
            return 'Δεν ήταν δυνατή η ανάγνωση των καταστάσεων ατυχήματος.';
        }

        $role = (string) ($user['role_code'] ?? '');
        if ($role === 'administrator') {
            return null;
        }

        if ($role === 'registrar') {
            $allowed = ['draft', 'submitted'];
            if (in_array($oldCode, $allowed, true) && in_array($newCode, $allowed, true)) {
                return null;
            }

            return 'Ο καταχωρητής μπορεί να αλλάζει μόνο μεταξύ Πρόχειρου και Υποβλημένου.';
        }

        return 'Μη εξουσιοδοτημένη αλλαγή κατάστασης.';
    }
}
