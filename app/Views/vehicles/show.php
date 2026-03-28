<?php
$findLabel = static function (array $options, mixed $id): string {
    $target = (string) ($id ?? '');
    if ($target === '') {
        return '-';
    }

    foreach ($options as $option) {
        $value = (string) ($option['id'] ?? '');
        if ($value === $target) {
            return (string) $option['label_el'];
        }
    }

    return '-';
};
?>
<section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Όχημα: <?= e((string) ($vehicle['plate_number'] ?: 'Χωρίς πινακίδα')) ?></h1>
            <p class="text-sm text-slate-600">Ατύχημα: <?= e((string) $accident['case_number']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= e(url('/vehicles/' . $vehicle['id'] . '/edit')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επεξεργασία</a>
            <?php endif; ?>
            <a href="<?= e(url('/accidents/' . $accident['id'])) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επιστροφή στο ατύχημα</a>
        </div>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Α. Γενικά</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Τύπος οχήματος:</strong> <?= e($findLabel($lookup['vehicle_type'], $vehicle['vehicle_type_lookup_id'] ?? null)) ?></p>
            <p><strong>Χρώμα:</strong> <?= e($findLabel($lookup['vehicle_color'], $vehicle['vehicle_color_lookup_id'] ?? null)) ?></p>
            <p><strong>Κατασκευαστής:</strong> <?= e($findLabel($lookup['manufacturers'], $vehicle['vehicle_make_id'] ?? null)) ?></p>
            <p><strong>Μοντέλο:</strong> <?= e($findLabel($lookup['models'], $vehicle['vehicle_model_id'] ?? null)) ?></p>
            <p><strong>Κινητήριοι τροχοί:</strong> <?= e($findLabel($lookup['drive_wheels'], $vehicle['drive_wheels_lookup_id'] ?? null)) ?></p>
            <p><strong>Θέση οδήγησης:</strong> <?= e($findLabel($lookup['steering_position'], $vehicle['steering_position_lookup_id'] ?? null)) ?></p>
            <p><strong>Μήκος (mm):</strong> <?= e((string) ($vehicle['length_mm'] ?? '-')) ?></p>
            <p><strong>Πλάτος (mm):</strong> <?= e((string) ($vehicle['width_mm'] ?? '-')) ?></p>
            <p><strong>Ευθυγράμμιση οδού:</strong> <?= e($findLabel($lookup['road_alignment'], $vehicle['road_alignment_lookup_id'] ?? null)) ?></p>
            <p><strong>Ρυμούλκηση:</strong> <?= e($findLabel($lookup['towing'], $vehicle['towing_lookup_id'] ?? null)) ?></p>
            <p><strong>Ισχύς κινητήρα (kW):</strong> <?= e((string) ($vehicle['engine_power_kw'] ?? '-')) ?></p>
            <p><strong>Έτος κατασκευής:</strong> <?= e((string) ($vehicle['manufacturing_year'] ?? '-')) ?></p>
            <p><strong>Βάρος (kg):</strong> <?= e((string) ($vehicle['curb_weight_kg'] ?? '-')) ?></p>
            <p><strong>Άξονες:</strong> <?= e((string) ($vehicle['axles_count'] ?? '-')) ?></p>
            <p><strong>Επιβαίνοντες:</strong> <?= e((string) ($vehicle['passengers_count'] ?? '-')) ?></p>
            <p class="md:col-span-2"><strong>Γενικά σχόλια:</strong> <?= e((string) ($vehicle['general_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Β. Πιθανές Αιτίες</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Ελαττώματα που προκάλεσαν ατύχημα:</strong> <?= e($findLabel($lookup['defects_caused'], $vehicle['defects_caused_lookup_id'] ?? null)) ?></p>
            <p><strong>Τεχνικός έλεγχος:</strong> <?= e($findLabel($lookup['technical_inspection'], $vehicle['technical_inspection_passed_lookup_id'] ?? null)) ?></p>
            <p><strong>Ελιγμός πριν το ατύχημα:</strong> <?= e($findLabel($lookup['maneuver'], $vehicle['maneuver_before_accident_lookup_id'] ?? null)) ?></p>
            <p><strong>Επικίνδυνο φορτίο:</strong> <?= e($findLabel($lookup['dangerous_load'], $vehicle['dangerous_load_lookup_id'] ?? null)) ?></p>
            <p><strong>Διασπορά φορτίου:</strong> <?= e($findLabel($lookup['dangerous_load_dispersion'], $vehicle['dangerous_load_dispersion_lookup_id'] ?? null)) ?></p>
            <p class="md:col-span-2"><strong>Σχόλια αιτιών:</strong> <?= e((string) ($vehicle['defects_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Γ. Συνέπειες</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Πλήθος συγκρούσεων:</strong> <?= e((string) ($vehicle['collisions_count'] ?? '-')) ?></p>
            <p><strong>CDC3:</strong> <?= e($findLabel($lookup['cdc3'], $vehicle['cdc3_lookup_id'] ?? null)) ?></p>
            <p><strong>CDC4:</strong> <?= e($findLabel($lookup['cdc4'], $vehicle['cdc4_lookup_id'] ?? null)) ?></p>
            <p><strong>Πυρκαγιά:</strong> <?= e($findLabel($lookup['on_fire'], $vehicle['on_fire_lookup_id'] ?? null)) ?></p>
            <p><strong>Πυροσβεστικό υλικό:</strong> <?= e($findLabel($lookup['firefighting_material'], $vehicle['firefighting_material_used_lookup_id'] ?? null)) ?></p>
            <p><strong>Σύγκρουση εκτός οδού:</strong> <?= e($findLabel($lookup['collision_offroad_object'], $vehicle['collision_offroad_object_lookup_id'] ?? null)) ?></p>
            <p><strong>Τύπος σύγκρουσης:</strong> <?= e($findLabel($lookup['collision_type'], $vehicle['collision_type_lookup_id'] ?? null)) ?></p>
            <p class="md:col-span-2"><strong>Σχόλια ζημιών:</strong> <?= e((string) ($vehicle['damage_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Δ. Ηλεκτρονικά Συστήματα Ασφαλείας</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>ABS:</strong> <?= e($findLabel($lookup['abs'], $vehicle['abs_lookup_id'] ?? null)) ?></p>
            <p><strong>ESP:</strong> <?= e($findLabel($lookup['esp'], $vehicle['esp_lookup_id'] ?? null)) ?></p>
            <p><strong>TCS:</strong> <?= e($findLabel($lookup['tcs'], $vehicle['tcs_lookup_id'] ?? null)) ?></p>
            <p><strong>ACS:</strong> <?= e($findLabel($lookup['acs'], $vehicle['acs_lookup_id'] ?? null)) ?></p>
            <p><strong>LDW:</strong> <?= e($findLabel($lookup['ldw'], $vehicle['ldw_lookup_id'] ?? null)) ?></p>
            <p><strong>CSS:</strong> <?= e($findLabel($lookup['css'], $vehicle['css_lookup_id'] ?? null)) ?></p>
            <p class="md:col-span-2"><strong>Σχόλια:</strong> <?= e((string) ($vehicle['safety_systems_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ε. Πηγή / Βεβαιότητα / Διερεύνηση</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Πηγή πληροφόρησης:</strong> <?= e($findLabel($lookup['information_source'], $vehicle['information_source_lookup_id'] ?? null)) ?></p>
            <p><strong>Βαθμός βεβαιότητας:</strong> <?= e($findLabel($lookup['confidence_level'], $vehicle['confidence_level_lookup_id'] ?? null)) ?></p>
            <p><strong>Περιγραφή βεβαιότητας:</strong> <?= e((string) ($vehicle['confidence_description'] ?? '-')) ?></p>
            <p><strong>Μέθοδος διερεύνησης:</strong> <?= e($findLabel($lookup['investigation_method'], $vehicle['investigation_method_lookup_id'] ?? null)) ?></p>
            <p><strong>Βεβαιότητα διερεύνησης:</strong> <?= e($findLabel($lookup['investigation_confidence'], $vehicle['investigation_confidence_lookup_id'] ?? null)) ?></p>
            <p><strong>Περιγραφή διερεύνησης:</strong> <?= e((string) ($vehicle['investigation_confidence_description'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-lg font-semibold">Συνημμένα Οχήματος</h2>
        <?php if ($canEdit): ?>
            <form action="<?= e(url('/attachments/upload')) ?>" method="post" enctype="multipart/form-data" class="mb-3 flex flex-wrap items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="entity_type" value="vehicle">
                <input type="hidden" name="entity_id" value="<?= e((string) $vehicle['id']) ?>">
                <input type="hidden" name="redirect_to" value="<?= e(url('/vehicles/' . $vehicle['id'])) ?>">
                <input type="file" name="attachment[]" class="text-sm" multiple required>
                <button class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Μεταφόρτωση</button>
            </form>
        <?php endif; ?>

        <?php if ($attachments === []): ?>
            <p class="text-sm text-slate-500">Δεν υπάρχουν συνημμένα.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($attachments as $att): ?>
                    <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <a href="<?= e(url('/attachments/' . $att['id'] . '/download')) ?>" class="text-slate-800 underline"><?= e((string) $att['original_name']) ?></a>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500"><?= e((string) $att['created_at']) ?></span>
                            <?php if ($canEdit): ?>
                                <form method="post" action="<?= e(url('/attachments/' . $att['id'] . '/delete')) ?>" onsubmit="return confirm('Διαγραφή συνημμένου;');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="redirect_to" value="<?= e(url('/vehicles/' . $vehicle['id'])) ?>">
                                    <button class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700">Διαγραφή</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
</section>
