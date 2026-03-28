<?php
$qs = static function (array $overrides = []) use ($filters): string {
    $params = array_merge($filters, $overrides);
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');

    return http_build_query($params);
};
?>
<section class="space-y-6">
    <h1 class="text-2xl font-semibold">Αρχεία Ελέγχου</h1>

    <form method="get" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-5">
        <input name="action_type" value="<?= e((string) ($filters['action_type'] ?? '')) ?>" placeholder="Ενέργεια" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="entity_type" value="<?= e((string) ($filters['entity_type'] ?? '')) ?>" placeholder="Οντότητα" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="actor_user_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">Χρήστης</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= e((string) $u['id']) ?>" <?= ((string) ($filters['actor_user_id'] ?? '') === (string) $u['id']) ? 'selected' : '' ?>><?= e((string) $u['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white md:col-span-1">Φιλτράρισμα</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-4 py-3">Ημερομηνία</th>
                <th class="px-4 py-3">Χρήστης</th>
                <th class="px-4 py-3">Ενέργεια</th>
                <th class="px-4 py-3">Οντότητα</th>
                <th class="px-4 py-3">Σύνοψη</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if ($items === []): ?>
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Δεν βρέθηκαν καταχωρήσεις.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $item['created_at']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) ($item['actor_name'] ?? '-')) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $item['action_type']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $item['entity_type']) ?><?= $item['entity_id'] ? ' #' . e((string) $item['entity_id']) : '' ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $item['summary']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-600">Προβολή με keyset pagination για υψηλή απόδοση.</p>
        <?php if (!empty($nextCursorCreatedAt) && !empty($nextCursorId)): ?>
            <a href="<?= e(url('/admin/audit-logs?' . $qs(['cursor_created_at' => $nextCursorCreatedAt, 'cursor_id' => $nextCursorId]))) ?>" class="rounded-md border border-slate-300 px-3 py-1 text-sm">Επόμενα</a>
        <?php endif; ?>
    </div>
</section>
