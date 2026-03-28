<?php
$vehicle = $vehicle ?? [];
$editing = $editing ?? false;
$value = static function (string $key, mixed $default = '') use ($vehicle) {
    return old($key, $vehicle[$key] ?? $default);
};
$select = static function (string $name, array $options, callable $valueFn, string $valueKey = 'id') {
    ?>
    <select name="<?= e($name) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        <option value="">Επιλέξτε</option>
        <?php foreach ($options as $opt): ?>
            <?php $optValue = (string) ($opt[$valueKey] ?? $opt['id']); ?>
            <option value="<?= e($optValue) ?>" <?= ((string) $valueFn($name) === $optValue) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>
<form method="post" action="<?= e($editing ? url('/vehicles/' . $vehicle['id'] . '/update') : url('/accidents/' . $accident['id'] . '/vehicles')) ?>" class="space-y-6">
    <?= csrf_field() ?>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Α. Γενικά</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Πινακίδα</label><input name="plate_number" value="<?= e((string) $value('plate_number')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Τύπος οχήματος</label><?php $select('vehicle_type_lookup_id', $lookup['vehicle_type'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Χρώμα</label><?php $select('vehicle_color_lookup_id', $lookup['vehicle_color'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Κατασκευαστής</label><?php $select('vehicle_make_id', $lookup['manufacturers'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Μοντέλο</label><?php $select('vehicle_model_id', $lookup['models'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Κινητήριοι τροχοί</label><?php $select('drive_wheels_lookup_id', $lookup['drive_wheels'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Θέση οδήγησης</label><?php $select('steering_position_lookup_id', $lookup['steering_position'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Μήκος (mm)</label><input type="number" min="0" name="length_mm" value="<?= e((string) $value('length_mm')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Πλάτος (mm)</label><input type="number" min="0" name="width_mm" value="<?= e((string) $value('width_mm')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Ευθυγράμμιση ως προς οδό</label><?php $select('road_alignment_lookup_id', $lookup['road_alignment'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ρυμούλκηση / ρυμουλκούμενο</label><?php $select('towing_lookup_id', $lookup['towing'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ισχύς κινητήρα (kW)</label><input type="number" min="0" name="engine_power_kw" value="<?= e((string) $value('engine_power_kw')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Έτος κατασκευής</label><input type="number" min="1900" max="2100" name="manufacturing_year" value="<?= e((string) $value('manufacturing_year')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Βάρος (kg)</label><input type="number" min="0" name="curb_weight_kg" value="<?= e((string) $value('curb_weight_kg')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Άξονες</label><input type="number" min="0" name="axles_count" value="<?= e((string) $value('axles_count')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Επιβαίνοντες</label><input type="number" min="0" name="passengers_count" value="<?= e((string) $value('passengers_count')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm">Γενικά σχόλια</label><textarea name="general_comments" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?= e((string) $value('general_comments')) ?></textarea></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Β. Πιθανές Αιτίες</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Ελαττώματα προκάλεσαν το ατύχημα</label><?php $select('defects_caused_lookup_id', $lookup['defects_caused'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Πέρασε τεχνικό έλεγχο</label><?php $select('technical_inspection_passed_lookup_id', $lookup['technical_inspection'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ελιγμός πριν το ατύχημα</label><?php $select('maneuver_before_accident_lookup_id', $lookup['maneuver'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Επικίνδυνο φορτίο</label><?php $select('dangerous_load_lookup_id', $lookup['dangerous_load'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Διασπορά επικίνδυνου φορτίου</label><?php $select('dangerous_load_dispersion_lookup_id', $lookup['dangerous_load_dispersion'], $value); ?></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm">Σχόλια πιθανών αιτιών</label><textarea name="defects_comments" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?= e((string) $value('defects_comments')) ?></textarea></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Γ. Συνέπειες</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Πλήθος συγκρούσεων</label><input type="number" min="0" name="collisions_count" value="<?= e((string) $value('collisions_count')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">CDC 3</label><?php $select('cdc3_lookup_id', $lookup['cdc3'], $value); ?></div>
            <div><label class="mb-1 block text-sm">CDC 4</label><?php $select('cdc4_lookup_id', $lookup['cdc4'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Πυρκαγιά</label><?php $select('on_fire_lookup_id', $lookup['on_fire'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Χρήση πυροσβεστικού υλικού</label><?php $select('firefighting_material_used_lookup_id', $lookup['firefighting_material'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Σύγκρουση με αντικείμενο εκτός οδού</label><?php $select('collision_offroad_object_lookup_id', $lookup['collision_offroad_object'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Τύπος σύγκρουσης</label><?php $select('collision_type_lookup_id', $lookup['collision_type'], $value); ?></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm">Σχόλια ζημιών</label><textarea name="damage_comments" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?= e((string) $value('damage_comments')) ?></textarea></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Δ. Ηλεκτρονικά Συστήματα Ασφαλείας</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">ABS</label><?php $select('abs_lookup_id', $lookup['abs'], $value); ?></div>
            <div><label class="mb-1 block text-sm">ESP</label><?php $select('esp_lookup_id', $lookup['esp'], $value); ?></div>
            <div><label class="mb-1 block text-sm">TCS</label><?php $select('tcs_lookup_id', $lookup['tcs'], $value); ?></div>
            <div><label class="mb-1 block text-sm">ACS</label><?php $select('acs_lookup_id', $lookup['acs'], $value); ?></div>
            <div><label class="mb-1 block text-sm">LDW</label><?php $select('ldw_lookup_id', $lookup['ldw'], $value); ?></div>
            <div><label class="mb-1 block text-sm">CSS</label><?php $select('css_lookup_id', $lookup['css'], $value); ?></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm">Σχόλια συστημάτων</label><textarea name="safety_systems_comments" rows="2" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?= e((string) $value('safety_systems_comments')) ?></textarea></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Ε. Πηγή / Βεβαιότητα / Διερεύνηση</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Πηγή πληροφόρησης</label><?php $select('information_source_lookup_id', $lookup['information_source'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Βαθμός βεβαιότητας</label><?php $select('confidence_level_lookup_id', $lookup['confidence_level'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Μέθοδος διερεύνησης</label><?php $select('investigation_method_lookup_id', $lookup['investigation_method'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Περιγραφή βεβαιότητας</label><input name="confidence_description" value="<?= e((string) $value('confidence_description')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Βεβαιότητα διερεύνησης</label><?php $select('investigation_confidence_lookup_id', $lookup['investigation_confidence'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Περιγραφή διερεύνησης</label><input name="investigation_confidence_description" value="<?= e((string) $value('investigation_confidence_description')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        </div>
    </section>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white">Αποθήκευση</button>
        <a href="<?= e(url('/accidents/' . $accident['id'])) ?>" class="rounded-md border border-slate-300 px-4 py-2 text-sm">Ακύρωση</a>
    </div>
</form>
