<?php
$findLabel = static function (array $options, mixed $id): string {
    $target = (string) ($id ?? '');
    if ($target === '') {
        return '-';
    }

    foreach ($options as $option) {
        if ((string) $option['id'] === $target) {
            return (string) $option['label_el'];
        }
    }

    return '-';
};
?>
<section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Δρόμος #<?= e((string) $roadOrder) ?></h1>
            <p class="text-sm text-slate-600">Ατύχημα: <?= e((string) $accident['case_number']) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= e(url('/roads/' . $road['id'] . '/edit')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επεξεργασία</a>
            <?php endif; ?>
            <a href="<?= e(url('/accidents/' . $accident['id'])) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επιστροφή στο ατύχημα</a>
        </div>
    </div>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Α. Γενικά Στοιχεία Οδού</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Ροή κυκλοφορίας:</strong> <?= e($findLabel($lookup['traffic_flow'], $road['traffic_flow_lookup_id'] ?? null)) ?></p>
            <p><strong>Αριθμός λωρίδων:</strong> <?= e((string) ($road['lanes_count'] ?? '-')) ?></p>
            <p><strong>Τύπος επιφάνειας:</strong> <?= e($findLabel($lookup['surface_type'], $road['surface_type_lookup_id'] ?? null)) ?></p>
            <p><strong>Όριο ταχύτητας:</strong> <?= e((string) ($road['speed_limit_kmh'] ?? '-')) ?></p>
            <p><strong>Τύπος ορίου ταχύτητας:</strong> <?= e($findLabel($lookup['speed_limit_type'], $road['speed_limit_type_lookup_id'] ?? null)) ?></p>
            <p><strong>Διασταύρωση:</strong> <?= e($findLabel($lookup['intersection'], $road['intersection_lookup_id'] ?? null)) ?></p>
            <p><strong>Τοπική περιοχή:</strong> <?= e($findLabel($lookup['local_area'], $road['local_area_lookup_id'] ?? null)) ?></p>
            <p><strong>Χάραξη οδού:</strong> <?= e($findLabel($lookup['road_alignment'], $road['road_alignment_lookup_id'] ?? null)) ?></p>
            <p><strong>Ζώνη έργων:</strong> <?= e($findLabel($lookup['construction_zone'], $road['construction_zone_lookup_id'] ?? null)) ?></p>
            <p><strong>Σήμανση/έλεγχος:</strong> <?= e($findLabel($lookup['traffic_control_signs'], $road['traffic_control_signs_lookup_id'] ?? null)) ?></p>
            <p><strong>Λειτουργία σηματοδότησης:</strong> <?= e($findLabel($lookup['traffic_signal_operation'], $road['traffic_signal_operation_lookup_id'] ?? null)) ?></p>
            <p><strong>Κατάσταση επιφάνειας:</strong> <?= e($findLabel($lookup['road_surface_condition'], $road['road_surface_condition_lookup_id'] ?? null)) ?></p>
            <p><strong>Υποδομές πεζών:</strong> <?= e($findLabel($lookup['pedestrian_infrastructure'], $road['pedestrian_infrastructure_lookup_id'] ?? null)) ?></p>
            <p><strong>Υποδομές ποδηλάτου:</strong> <?= e($findLabel($lookup['bicycle_infrastructure'], $road['bicycle_infrastructure_lookup_id'] ?? null)) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Β. Συνθήκες</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Φωτισμός:</strong> <?= e($findLabel($lookup['lighting_condition'], $road['lighting_condition_lookup_id'] ?? null)) ?></p>
            <p><strong>Καιρικές συνθήκες:</strong> <?= e($findLabel($lookup['weather_condition'], $road['weather_condition_lookup_id'] ?? null)) ?></p>
            <p><strong>Ισχυροί άνεμοι:</strong> <?= e($findLabel($lookup['strong_winds'], $road['strong_winds_lookup_id'] ?? null)) ?></p>
            <p><strong>Ομίχλη:</strong> <?= e($findLabel($lookup['fog'], $road['fog_lookup_id'] ?? null)) ?></p>
            <p class="md:col-span-2"><strong>Σχόλια:</strong> <?= e((string) ($road['conditions_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Γ. Πιθανές Αιτίες</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Ελαττώματα οδού:</strong> <?= e($findLabel($lookup['road_defects'], $road['road_defects_lookup_id'] ?? null)) ?></p>
            <p><strong>Παροδικοί παράγοντες:</strong> <?= e($findLabel($lookup['temporary_factors'], $road['temporary_factors_lookup_id'] ?? null)) ?></p>
            <p><strong>Σηματοδότηση σχετική:</strong> <?= e($findLabel($lookup['signaling_related'], $road['signaling_related_lookup_id'] ?? null)) ?></p>
            <p><strong>Υποδομή περιορισμού ταχύτητας:</strong> <?= e($findLabel($lookup['speed_restriction_infrastructure'], $road['speed_restriction_infrastructure_lookup_id'] ?? null)) ?></p>
            <p><strong>Συνεισφορά υποδομής:</strong> <?= e($findLabel($lookup['speed_restriction_contributed'], $road['speed_restriction_contributed_lookup_id'] ?? null)) ?></p>
            <p><strong>Σχόλια αιτιών:</strong> <?= e((string) ($road['possible_causes_comments'] ?? '-')) ?></p>
            <p class="md:col-span-2"><strong>Πρόσθετα σχόλια:</strong> <?= e((string) ($road['additional_comments'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-semibold">Δ. Πηγή / Βεβαιότητα / Διερεύνηση</h2>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <p><strong>Πηγή πληροφόρησης:</strong> <?= e($findLabel($lookup['information_source'], $road['information_source_lookup_id'] ?? null)) ?></p>
            <p><strong>Βαθμός βεβαιότητας:</strong> <?= e($findLabel($lookup['confidence_level'], $road['confidence_level_lookup_id'] ?? null)) ?></p>
            <p><strong>Μέθοδος διερεύνησης:</strong> <?= e($findLabel($lookup['investigation_method'], $road['investigation_method_lookup_id'] ?? null)) ?></p>
            <p><strong>Περιγραφή βεβαιότητας:</strong> <?= e((string) ($road['confidence_description'] ?? '-')) ?></p>
            <p><strong>Βεβαιότητα διερεύνησης:</strong> <?= e($findLabel($lookup['investigation_confidence'], $road['investigation_confidence_lookup_id'] ?? null)) ?></p>
            <p><strong>Περιγραφή διερεύνησης:</strong> <?= e((string) ($road['investigation_confidence_description'] ?? '-')) ?></p>
        </div>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-lg font-semibold">Συνημμένα Δρόμου</h2>
        <?php if ($canEdit): ?>
            <form action="<?= e(url('/attachments/upload')) ?>" method="post" enctype="multipart/form-data" class="mb-3 flex flex-wrap items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="entity_type" value="road">
                <input type="hidden" name="entity_id" value="<?= e((string) $road['id']) ?>">
                <input type="hidden" name="redirect_to" value="<?= e(url('/roads/' . $road['id'])) ?>">
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
                                    <input type="hidden" name="redirect_to" value="<?= e(url('/roads/' . $road['id'])) ?>">
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
