<section class="space-y-4">
    <div>
        <h1 class="text-2xl font-semibold">Επεξεργασία Οχήματος</h1>
        <p class="text-sm text-slate-600">Ατύχημα: <?= e((string) $accident['case_number']) ?></p>
    </div>
    <?php require __DIR__ . '/_form.php'; ?>

    <article class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-lg font-semibold">Συνημμένα Οχήματος</h2>
        <form action="<?= e(url('/attachments/upload')) ?>" method="post" enctype="multipart/form-data" class="mb-3 flex flex-wrap items-center gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="entity_type" value="vehicle">
            <input type="hidden" name="entity_id" value="<?= e((string) $vehicle['id']) ?>">
            <input type="hidden" name="redirect_to" value="<?= e(url('/vehicles/' . $vehicle['id'] . '/edit')) ?>">
            <input type="file" name="attachment[]" class="text-sm" multiple required>
            <button class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white">Μεταφόρτωση</button>
        </form>

        <?php if (($attachments ?? []) === []): ?>
            <p class="text-sm text-slate-500">Δεν υπάρχουν συνημμένα.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($attachments as $att): ?>
                    <li class="flex items-center justify-between rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <a class="underline" href="<?= e(url('/attachments/' . $att['id'] . '/download')) ?>"><?= e((string) $att['original_name']) ?></a>
                        <form action="<?= e(url('/attachments/' . $att['id'] . '/delete')) ?>" method="post" onsubmit="return confirm('Διαγραφή συνημμένου;');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="redirect_to" value="<?= e(url('/vehicles/' . $vehicle['id'] . '/edit')) ?>">
                            <button class="rounded-md border border-rose-300 px-2 py-1 text-xs text-rose-700">Διαγραφή</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <form action="<?= e(url('/vehicles/' . $vehicle['id'] . '/delete')) ?>" method="post" onsubmit="return confirm('Διαγραφή οχήματος;');">
        <?= csrf_field() ?>
        <button class="rounded-md bg-rose-700 px-4 py-2 text-sm text-white">Διαγραφή Οχήματος</button>
    </form>
</section>
