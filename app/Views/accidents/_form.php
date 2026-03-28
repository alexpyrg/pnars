<?php
$accident = $accident ?? [];
$value = static function (string $key, mixed $fallback = '') use ($accident) {
    return old($key, $accident[$key] ?? $fallback);
};
$selectedFactors = old('factor_ids', $factorIds ?? []);
if (!is_array($selectedFactors)) {
    $selectedFactors = [];
}
$participantInput = old_array('participant_counts');
$participantCountsFinal = $participantInput !== [] ? $participantInput : ($participantCounts ?? []);
$editing = $editing ?? false;
$sequenceSelected = (string) old('sequence_of_events_lookup_id', '');
if ($sequenceSelected === '' && isset($accident['sequence_of_events']) && (string) $accident['sequence_of_events'] !== '') {
    foreach ($lookup['event_sequence'] as $opt) {
        if ((string) ($opt['label_el'] ?? '') === (string) $accident['sequence_of_events']) {
            $sequenceSelected = (string) ($opt['id'] ?? '');
            break;
        }
    }
}
?>
<form method="post" action="<?= e($editing ? url('/accidents/' . $accident['id'] . '/update') : url('/accidents')) ?>" class="space-y-6">
    <?= csrf_field() ?>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Α. Γενικά Στοιχεία Ατυχήματος</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Αριθμός υπόθεσης</label>
                <input name="case_number" value="<?= e((string) $value('case_number')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                <?php if ($err = error_for('case_number')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Ημερομηνία και ώρα ατυχήματος</label>
                <input type="datetime-local" name="accident_datetime" value="<?= e(datetime_local_value($value('accident_datetime'))) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                <?php if ($err = error_for('accident_datetime')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Ημέρα ατυχήματος</label>
                <select name="accident_day_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['days'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('accident_day_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Ημερομηνία/ώρα άφιξης εμπειρογνώμονα</label>
                <input type="datetime-local" name="expert_arrival_datetime" value="<?= e(datetime_local_value($value('expert_arrival_datetime'))) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <?php if ($err = error_for('expert_arrival_datetime')): ?><p class="mt-1 text-xs text-rose-700"><?= e($err) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Γεωγρ. μήκος</label>
                <input name="longitude" value="<?= e((string) $value('longitude')) ?>" placeholder="π.χ. 23.7275" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Γεωγρ. πλάτος</label>
                <input name="latitude" value="<?= e((string) $value('latitude')) ?>" placeholder="π.χ. 37.9838" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Αναγνωριστικό συμβάντος</label>
                <input name="incident_identifier" value="<?= e((string) $value('incident_identifier')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Σοβαρότητα</label>
                <select name="severity_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['severity'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('severity_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end gap-4">
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="entry_completed" value="1" <?= ((string) $value('entry_completed', '0') === '1') ? 'checked' : '' ?>> Καταχώριση ολοκληρωμένη</label>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Ναρκωτικά</label>
                <select name="drugs_involved_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['narcotics'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('drugs_involved_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Αλκοόλ</label>
                <select name="alcohol_involved_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['alcohol'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('alcohol_involved_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Εγκατάλειψη σκηνής</label>
                <select name="hit_and_run_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['hit_and_run'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('hit_and_run_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Σύγκρουση με ζώο</label>
                <select name="animal_collision_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['animal'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('animal_collision_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Πλήθος ξεχωριστών συμβάντων</label>
                <input type="number" min="0" name="separate_events_count" value="<?= e((string) $value('separate_events_count', '0')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Τύπος GDV</label>
                <select name="gdv_type_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['gdv'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('gdv_type_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Τύπος GADAS</label>
                <select name="gadas_type_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['gadas'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('gadas_type_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Ακολουθία συμβάντων</label>
                <select name="sequence_of_events_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['event_sequence'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ($sequenceSelected === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Πρώτο επιβλαβές συμβάν</label>
                <select name="first_harmful_event_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['first_harmful'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('first_harmful_event_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Πλέον επιβλαβές συμβάν</label>
                <select name="most_harmful_event_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['most_harmful'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('most_harmful_event_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Β. Συνεισφέροντες Παράγοντες</h2>
        <div class="grid gap-2 md:grid-cols-2">
            <?php foreach ($lookup['factors'] as $opt): ?>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="factor_ids[]" value="<?= e((string) $opt['id']) ?>" <?= in_array((string) $opt['id'], array_map('strval', $selectedFactors), true) ? 'checked' : '' ?>>
                    <?= e((string) $opt['label_el']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Γ. Συμμετέχοντες</h2>
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Σύνολο συμμετεχόντων</label>
                <input type="number" min="0" name="participants_total" value="<?= e((string) $value('participants_total', '0')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2"></div>
            <?php foreach ($lookup['participant_categories'] as $opt):
                $catId = (int) $opt['id'];
                $countValue = $participantCountsFinal[$catId] ?? 0;
            ?>
                <div>
                    <label class="mb-1 block text-sm font-medium"><?= e((string) $opt['label_el']) ?></label>
                    <input type="number" min="0" name="participant_counts[<?= e((string) $catId) ?>]" value="<?= e((string) $countValue) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Δ. Περίληψη</h2>
        <textarea name="summary" rows="5" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Συνοπτική περιγραφή ατυχήματος"><?= e((string) $value('summary')) ?></textarea>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ε. Πηγή / Βεβαιότητα / Διερεύνηση</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium">Πηγή πληροφόρησης</label>
                <select name="information_source_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['information_source'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('information_source_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Βαθμός βεβαιότητας</label>
                <select name="confidence_level_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['confidence_level'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('confidence_level_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Μέθοδος διερεύνησης</label>
                <select name="investigation_method_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['investigation_method'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('investigation_method_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="mb-1 block text-sm font-medium">Περιγραφή βεβαιότητας</label>
                <input name="confidence_description" value="<?= e((string) $value('confidence_description')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Βεβαιότητα διερεύνησης</label>
                <select name="investigation_confidence_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($lookup['investigation_confidence'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('investigation_confidence_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Περιγραφή βεβαιότητας διερεύνησης</label>
                <input name="investigation_confidence_description" value="<?= e((string) $value('investigation_confidence_description')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">ΣΤ. Κατάσταση Εγγραφής</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Κατάσταση</label>
                <select name="status_lookup_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <?php foreach ($lookup['status'] as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $value('status_lookup_id') === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Αποθήκευση</button>
        <a href="<?= e($editing ? url('/accidents/' . $accident['id']) : url('/accidents')) ?>" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Ακύρωση</a>
    </div>
</form>