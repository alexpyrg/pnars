<section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Ατύχημα: <?= e((string) $accident['case_number']) ?></h1>
            <p class="text-sm text-slate-600">Κατάσταση: <?= e((string) ($accident['status_label'] ?? '-')) ?> | Καταχωρητής: <?= e((string) ($accident['creator_name'] ?? '-')) ?></p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= e(url('/accidents/' . $accident['id'] . '/edit')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επεξεργασία</a>
            <?php endif; ?>
            <a href="<?= e(url('/accidents')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">Επιστροφή στη λίστα</a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold text-slate-700">Βασικά</h2>
            <p class="text-sm">Ημ/νία ατυχήματος: <strong><?= e((string) $accident['accident_datetime']) ?></strong></p>
            <p class="text-sm">Σοβαρότητα: <strong><?= e((string) ($accident['severity_label'] ?? '-')) ?></strong></p>
            <p class="text-sm">Αναγνωριστικό: <strong><?= e((string) ($accident['incident_identifier'] ?? '-')) ?></strong></p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold text-slate-700">Συντεταγμένες</h2>
            <p class="text-sm">Μήκος: <strong><?= e((string) ($accident['longitude'] ?? '-')) ?></strong></p>
            <p class="text-sm">Πλάτος: <strong><?= e((string) ($accident['latitude'] ?? '-')) ?></strong></p>
            <p class="text-sm">Συμμετέχοντες: <strong><?= e((string) ($accident['participants_total'] ?? '0')) ?></strong></p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold text-slate-700">Ενέργειες</h2>
            <?php if ($canEdit): ?>
                <a href="<?= e(url('/accidents/' . $accident['id'] . '/roads/create')) ?>" class="mb-2 block rounded-md border border-slate-300 px-3 py-2 text-sm">Προσθήκη Δρόμου</a>
                <a href="<?= e(url('/accidents/' . $accident['id'] . '/vehicles/create')) ?>" class="block rounded-md border border-slate-300 px-3 py-2 text-sm">Προσθήκη Οχήματος</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <form action="<?= e(url('/accidents/' . $accident['id'] . '/delete')) ?>" method="post" class="mt-2" onsubmit="return confirm('Είστε βέβαιοι για διαγραφή;');">
                    <?= csrf_field() ?>
                    <button class="rounded-md bg-rose-700 px-3 py-2 text-sm text-white">Διαγραφή εγγραφής</button>
                </form>
            <?php endif; ?>
        </article>
    </div>

    <?php if ((string) ($accident['summary'] ?? '') !== ''): ?>
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-2 text-lg font-semibold">Περίληψη</h2>
            <p class="whitespace-pre-line text-sm text-slate-800"><?= e((string) $accident['summary']) ?></p>
        </article>
    <?php endif; ?>

    <?php
    $factorAnswerLabelsById = [];
    foreach ($factorAnswerOptions as $answerOption) {
        $factorAnswerLabelsById[(string) $answerOption['id']] = (string) $answerOption['label_el'];
    }

    $answeredFactors = [];
    foreach ($factorOptions as $factorOption) {
        $factorId = (string) ($factorOption['id'] ?? '');
        $answerLookupId = '';

        if (array_key_exists($factorId, $factorAnswerLookupIds)) {
            $answerLookupId = (string) $factorAnswerLookupIds[$factorId];
        } elseif (array_key_exists((int) $factorId, $factorAnswerLookupIds)) {
            $answerLookupId = (string) $factorAnswerLookupIds[(int) $factorId];
        }

        if ($answerLookupId === '') {
            continue;
        }

        $answeredFactors[] = [
            'factor_label' => (string) ($factorOption['label_el'] ?? '-'),
            'answer_label' => $factorAnswerLabelsById[$answerLookupId] ?? '-',
        ];
    }
    ?>

    <article class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-semibold">Β. Συνεισφέροντες Παράγοντες</h2>
        <?php if ($answeredFactors === []): ?>
            <p class="text-sm text-slate-500">Για κάθε παράγοντα επιλέξτε τι ισχύει: Ναι, Όχι ή Δεν γνωρίζω.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($answeredFactors as $row): ?>
                    <li class="flex items-start justify-between gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <span class="text-slate-800"><?= e((string) $row['factor_label']) ?></span>
                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"><?= e((string) $row['answer_label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <?php if ($canEdit): ?>
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">Αλλαγή Κατάστασης</h2>
            <form action="<?= e(url('/accidents/' . $accident['id'] . '/status')) ?>" method="post" class="grid gap-3 md:grid-cols-3">
                <?= csrf_field() ?>
                <select name="status_lookup_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <?php foreach ($statusOptions as $opt): ?>
                        <option value="<?= e((string) $opt['id']) ?>" <?= ((string) $accident['status_lookup_id'] === (string) $opt['id']) ? 'selected' : '' ?>><?= e((string) $opt['label_el']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input name="status_note" placeholder="Σημείωση αλλαγής" class="rounded-md border border-slate-300 px-3 py-2 text-sm md:col-span-1">
                <button type="submit" class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Ενημέρωση</button>
            </form>
        </article>
    <?php endif; ?>

    <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">Συνδεδεμένοι Δρόμοι</h2>
            <?php if ($roads === []): ?>
                <p class="text-sm text-slate-500">Δεν έχουν καταχωρηθεί δρόμοι.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($roads as $road): ?>
                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <span>Δρόμος #<?= e((string) $road['road_order']) ?></span>
                            <div class="flex items-center gap-2">
                                <a href="<?= e(url('/roads/' . $road['id'])) ?>" class="rounded-md border border-slate-300 px-2 py-1 text-xs">Προβολή</a>
                                <?php if ($canEdit): ?>
                                    <a href="<?= e(url('/roads/' . $road['id'] . '/edit')) ?>" class="rounded-md border border-slate-300 px-2 py-1 text-xs">Επεξεργασία</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">Συνδεδεμένα Οχήματα</h2>
            <?php if ($vehicles === []): ?>
                <p class="text-sm text-slate-500">Δεν έχουν καταχωρηθεί οχήματα.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <span><?= e((string) ($vehicle['plate_number'] ?: 'Χωρίς πινακίδα')) ?></span>
                            <div class="flex items-center gap-2">
                                <a href="<?= e(url('/vehicles/' . $vehicle['id'])) ?>" class="rounded-md border border-slate-300 px-2 py-1 text-xs">Προβολή</a>
                                <?php if ($canEdit): ?>
                                    <a href="<?= e(url('/vehicles/' . $vehicle['id'] . '/edit')) ?>" class="rounded-md border border-slate-300 px-2 py-1 text-xs">Επεξεργασία</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">Συνημμένα Ατυχήματος</h2>
            <?php if ($canEdit): ?>
                <form action="<?= e(url('/attachments/upload')) ?>" method="post" enctype="multipart/form-data" class="mb-3 flex flex-wrap items-center gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="entity_type" value="accident">
                    <input type="hidden" name="entity_id" value="<?= e((string) $accident['id']) ?>">
                    <input type="hidden" name="redirect_to" value="<?= e(url('/accidents/' . $accident['id'])) ?>">
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
                                        <input type="hidden" name="redirect_to" value="<?= e(url('/accidents/' . $accident['id'])) ?>">
                                        <button class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700">Διαγραφή</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="mb-3 text-lg font-semibold">Σημάνσεις</h2>
            <?php if ($canFlag): ?>
                <form action="<?= e(url('/accidents/' . $accident['id'] . '/flags')) ?>" method="post" class="mb-3 grid gap-2">
                    <?= csrf_field() ?>
                    <select name="flag_type_lookup_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm" required>
                        <option value="">Επιλέξτε τύπο σήμανσης</option>
                        <?php foreach ($flagTypeOptions as $opt): ?>
                            <option value="<?= e((string) $opt['id']) ?>"><?= e((string) $opt['label_el']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="note" rows="2" class="rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Σημείωση"></textarea>
                    <button class="rounded-md bg-amber-600 px-3 py-2 text-sm text-white">Καταχώρηση Σήμανσης</button>
                </form>
            <?php endif; ?>

            <?php if ($flags === []): ?>
                <p class="text-sm text-slate-500">Δεν υπάρχουν σημάνσεις.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($flags as $flag): ?>
                        <li class="rounded-md border border-slate-200 p-3 text-sm">
                            <div class="mb-1 flex items-center justify-between">
                                <strong><?= e((string) $flag['flag_type_label']) ?></strong>
                                <span class="text-xs <?= ((bool) $flag['is_open']) ? 'text-amber-700' : 'text-emerald-700' ?>"><?= ((bool) $flag['is_open']) ? 'Ανοικτή' : 'Κλειστή' ?></span>
                            </div>
                            <p class="text-slate-700"><?= e((string) ($flag['note'] ?? '-')) ?></p>
                            <?php if ($canResolveFlag && (bool) $flag['is_open']): ?>
                                <form action="<?= e(url('/flags/' . $flag['id'] . '/resolve')) ?>" method="post" class="mt-2 flex items-center gap-2">
                                    <?= csrf_field() ?>
                                    <input name="resolution_note" placeholder="Σημείωση επίλυσης" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                                    <button class="rounded-md bg-emerald-700 px-2 py-1 text-xs text-white">Επίλυση</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </div>
</section>
