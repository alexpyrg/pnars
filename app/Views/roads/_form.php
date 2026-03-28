<?php
$road = $road ?? [];
$editing = $editing ?? false;
$value = static function (string $key, mixed $default = '') use ($road) {
    return old($key, $road[$key] ?? $default);
};
$select = static function (string $name, array $options, callable $valueFn) {
    ?>
    <select name="<?= e($name) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        <option value="">Επιλέξτε</option>
        <?php foreach ($options as $opt): ?>
            <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $valueFn($name) === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php
};
?>
<form method="post" action="<?= e($editing ? url('/roads/' . $road['id'] . '/update') : url('/accidents/' . $accident['id'] . '/roads')) ?>" class="space-y-6">
    <?= csrf_field() ?>
    <?php if (!$editing): ?><input type="hidden" name="road_order" value="<?= e((string) $roadOrder) ?>"><?php endif; ?>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Α. Γενικά Στοιχεία Οδού</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Ροή κυκλοφορίας</label><?php $select('traffic_flow_lookup_id', $lookup['traffic_flow'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Αριθμός λωρίδων</label><input type="number" min="0" name="lanes_count" value="<?= e((string) $value('lanes_count')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Τύπος επιφάνειας</label><?php $select('surface_type_lookup_id', $lookup['surface_type'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Όριο ταχύτητας (km/h)</label><input type="number" min="0" name="speed_limit_kmh" value="<?= e((string) $value('speed_limit_kmh')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-sm">Τύπος ορίου ταχύτητας</label><?php $select('speed_limit_type_lookup_id', $lookup['speed_limit_type'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Διασταύρωση</label><?php $select('intersection_lookup_id', $lookup['intersection'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Τοπική περιοχή</label><?php $select('local_area_lookup_id', $lookup['local_area'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Χάραξη οδού</label><?php $select('road_alignment_lookup_id', $lookup['road_alignment'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ζώνη έργων/συντήρησης</label><?php $select('construction_zone_lookup_id', $lookup['construction_zone'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Σήμανση / έλεγχος</label><?php $select('traffic_control_signs_lookup_id', $lookup['traffic_control_signs'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Λειτουργία σηματοδότησης</label><?php $select('traffic_signal_operation_lookup_id', $lookup['traffic_signal_operation'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Κατάσταση επιφάνειας</label><?php $select('road_surface_condition_lookup_id', $lookup['road_surface_condition'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Υποδομές πεζών</label><?php $select('pedestrian_infrastructure_lookup_id', $lookup['pedestrian_infrastructure'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Υποδομές ποδηλάτου</label><?php $select('bicycle_infrastructure_lookup_id', $lookup['bicycle_infrastructure'], $value); ?></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Β. Συνθήκες</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Φωτισμός</label><?php $select('lighting_condition_lookup_id', $lookup['lighting_condition'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Καιρικές συνθήκες</label><?php $select('weather_condition_lookup_id', $lookup['weather_condition'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ισχυροί άνεμοι</label><?php $select('strong_winds_lookup_id', $lookup['strong_winds'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Ομίχλη</label><?php $select('fog_lookup_id', $lookup['fog'], $value); ?></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm">Σχόλια συνθηκών</label><input name="conditions_comments" value="<?= e((string) $value('conditions_comments')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Γ. Πιθανές Αιτίες</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="mb-1 block text-sm">Ελαττώματα οδού</label><?php $select('road_defects_lookup_id', $lookup['road_defects'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Παροδικοί παράγοντες</label><?php $select('temporary_factors_lookup_id', $lookup['temporary_factors'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Σηματοδότηση σχετική με ατύχημα</label><?php $select('signaling_related_lookup_id', $lookup['signaling_related'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Υποδομή περιορισμού ταχύτητας</label><?php $select('speed_restriction_infrastructure_lookup_id', $lookup['speed_restriction_infrastructure'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Συνεισφορά υποδομής στο ατύχημα</label><?php $select('speed_restriction_contributed_lookup_id', $lookup['speed_restriction_contributed'], $value); ?></div>
            <div><label class="mb-1 block text-sm">Σχόλια</label><input name="possible_causes_comments" value="<?= e((string) $value('possible_causes_comments')) ?>" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
            <div class="md:col-span-3"><label class="mb-1 block text-sm">Πρόσθετα σχόλια</label><textarea name="additional_comments" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?= e((string) $value('additional_comments')) ?></textarea></div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Δ. Πηγή / Βεβαιότητα / Διερεύνηση</h2>
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
