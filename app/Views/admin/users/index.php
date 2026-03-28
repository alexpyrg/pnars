<section class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-900">Χρήστες</h1>
        <span class="text-sm text-slate-500">Σελίδα <?= e((string) $page) ?></span>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-4 py-3">Ονοματεπώνυμο</th>
                <th class="px-4 py-3">Ηλεκτρονικό ταχυδρομείο</th>
                <th class="px-4 py-3">Ρόλος</th>
                <th class="px-4 py-3">Κατάσταση</th>
                <th class="px-4 py-3">Τελευταία σύνδεση</th>
                <th class="px-4 py-3">Ενέργεια</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if ($users === []): ?>
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Δεν βρέθηκαν χρήστες.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $row): ?>
                    <tr>
                        <td class="px-4 py-3 text-slate-800"><?= e((string) $row['full_name']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $row['email']) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) $row['role_label']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ((bool) $row['is_active']): ?>
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700">Ενεργός</span>
                            <?php else: ?>
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-medium text-rose-700">Ανενεργός</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-700"><?= e((string) ($row['last_login_at'] ?? '-')) ?></td>
                        <td class="px-4 py-3">
                            <form action="<?= e(url('/admin/users/' . $row['id'] . '/toggle-active')) ?>" method="post">
                                <?= csrf_field() ?>
                                <button class="rounded-md border border-slate-300 px-2 py-1 text-xs">
                                    <?= ((bool) $row['is_active']) ? 'Απενεργοποίηση' : 'Ενεργοποίηση' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
